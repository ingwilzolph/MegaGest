<?php

header("Content-Type: application/json; charset=utf-8");

/* Evita que una falla de carga produzca una respuesta vacía para JavaScript. */
ini_set("display_errors", "0");

register_shutdown_function(static function (): void {
    $error = error_get_last();

    if (!$error || !in_array($error["type"], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
        header("Content-Type: application/json; charset=utf-8");
    }

    error_log(
        "Error fatal registrando cita administrativa: " .
        $error["message"] . " en " . $error["file"] . ":" . $error["line"]
    );

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error interno al registrar la cita. Revise el registro de PHP.",
        "detalle" => $error["message"]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/configQr.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

date_default_timezone_set("America/Santiago");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;
$transaccionIniciada = false;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    $nombre = trim((string) ($_POST["nombre"] ?? ""));
    $telefono = preg_replace("/\D/", "", (string) ($_POST["telefono"] ?? ""));
    $correo = trim((string) ($_POST["correo"] ?? ""));
    $patente = strtoupper(trim((string) ($_POST["patente"] ?? "")));
    $vehiculo = trim((string) ($_POST["vehiculo"] ?? ""));
    $servicio = trim((string) ($_POST["servicio"] ?? ""));
    $fecha = trim((string) ($_POST["fecha"] ?? ""));
    $hora = trim((string) ($_POST["hora"] ?? ""));
    $comentario = trim((string) ($_POST["comentario"] ?? ""));

    if (
        $nombre === "" ||
        $telefono === "" ||
        $correo === "" ||
        $patente === "" ||
        $vehiculo === "" ||
        $servicio === "" ||
        $fecha === "" ||
        $hora === ""
    ) {
        throw new RuntimeException("Complete todos los campos obligatorios.");
    }

    if (strlen($telefono) !== 9) {
        throw new RuntimeException("El teléfono debe tener 9 dígitos.");
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException("El correo electrónico no es válido.");
    }

    if (mb_strlen($nombre) > 100 || mb_strlen($correo) > 150) {
        throw new RuntimeException("Los datos del cliente superan el tamaño permitido.");
    }

    if (
        mb_strlen($patente) > 15 ||
        mb_strlen($vehiculo) > 100 ||
        mb_strlen($servicio) > 150 ||
        mb_strlen($comentario) > 1000
    ) {
        throw new RuntimeException("Uno de los campos supera el tamaño permitido.");
    }

    $fechaObjeto = DateTime::createFromFormat("!Y-m-d", $fecha);

    if (!$fechaObjeto || $fechaObjeto->format("Y-m-d") !== $fecha) {
        throw new RuntimeException("La fecha seleccionada no es válida.");
    }

    $hoy = new DateTime("today");
    $ultimoDiaMes = new DateTime(date("Y-m-t"));

    if ($fechaObjeto < $hoy || $fechaObjeto > $ultimoDiaMes) {
        throw new RuntimeException("La fecha debe estar dentro del mes actual.");
    }

    $diaSemana = intval($fechaObjeto->format("N"));

    if ($diaSemana === 7) {
        throw new RuntimeException("El taller no atiende los domingos.");
    }

    if (!preg_match("/^\d{2}:\d{2}$/", $hora)) {
        throw new RuntimeException("La hora seleccionada no es válida.");
    }

    $horaNumero = intval(substr($hora, 0, 2));
    $minutos = substr($hora, 3, 2);

    if ($minutos !== "00") {
        throw new RuntimeException("La hora seleccionada no es válida.");
    }

    if ($diaSemana <= 5 && ($horaNumero < 9 || $horaNumero > 20)) {
        throw new RuntimeException("El horario es de 09:00 a 20:00.");
    }

    if ($diaSemana === 6 && ($horaNumero < 9 || $horaNumero > 14)) {
        throw new RuntimeException("El sábado se atiende de 09:00 a 14:00.");
    }

    $horaCompleta = $hora . ":00";
    $fechaHoraSeleccionada = new DateTime($fecha . " " . $horaCompleta);

    if ($fechaHoraSeleccionada <= new DateTime()) {
        throw new RuntimeException("No puede seleccionar una fecha u hora pasada.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmt = $conexion->prepare(
        "SELECT numeroReserva
         FROM citas
         WHERE (correo = ? OR telefono = ?)
           AND patente = ?
           AND estado IN ('Pendiente', 'Confirmada', 'En proceso')
           AND (
               fecha > CURDATE()
               OR (fecha = CURDATE() AND hora >= CURTIME())
           )
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("sss", $correo, $telefono, $patente);
    $stmt->execute();
    $citaExistente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($citaExistente) {
        throw new RuntimeException(
            "El cliente ya tiene una cita activa con este vehículo " .
            "(Reserva {$citaExistente['numeroReserva']})."
        );
    }

    $stmt = $conexion->prepare(
        "SELECT id_cita
         FROM citas
         WHERE fecha = ?
           AND hora = ?
           AND estado IN ('Pendiente', 'Confirmada', 'En proceso')
         FOR UPDATE"
    );
    $stmt->bind_param("ss", $fecha, $horaCompleta);
    $stmt->execute();
    $resultadoOcupacion = $stmt->get_result();
    $ocupacion = $resultadoOcupacion->num_rows;
    $stmt->close();

    if ($ocupacion >= 2) {
        throw new RuntimeException("El horario seleccionado ya está completo.");
    }

    $stmt = $conexion->prepare(
        "INSERT INTO citas (
            nombre,
            telefono,
            correo,
            patente,
            vehiculo,
            servicio,
            fecha,
            hora,
            comentario,
            estado
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')"
    );
    $stmt->bind_param(
        "sssssssss",
        $nombre,
        $telefono,
        $correo,
        $patente,
        $vehiculo,
        $servicio,
        $fecha,
        $horaCompleta,
        $comentario
    );
    $stmt->execute();
    $idCita = intval($conexion->insert_id);
    $stmt->close();

    $numeroReserva = sprintf("AP-%s-%06d", date("Ymd"), $idCita);

    $stmt = $conexion->prepare(
        "UPDATE citas SET numeroReserva = ? WHERE id_cita = ?"
    );
    $stmt->bind_param("si", $numeroReserva, $idCita);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new RuntimeException("No fue posible asignar el número de reserva.");
    }
    $stmt->close();

    $conexion->commit();
    $transaccionIniciada = false;

    $fechaVisual = formatearFechaCitaAdmin($fecha, $horaCompleta);
    $rutaLogo = realpath(__DIR__ . "/../images/logoAlianzaPro.webp");
    $qrImage = obtenerQrCitaAdmin(
        generarUrlCitaQr($numeroReserva)
    );

    $htmlCorreo = crearHtmlCorreoCitaAdmin([
        "nombre" => $nombre,
        "telefono" => $telefono,
        "correo" => $correo,
        "patente" => $patente,
        "vehiculo" => $vehiculo,
        "servicio" => $servicio,
        "comentario" => $comentario,
        "numero_reserva" => $numeroReserva,
        "fecha_visual" => $fechaVisual,
        "logo_disponible" => $rutaLogo !== false && is_readable($rutaLogo),
        "qr_disponible" => $qrImage !== null
    ]);

    $erroresCorreo = [];

    try {
        $mailEmpresa = configurarCorreoCitaAdmin();
        $mailEmpresa->addAddress(SMTP_USER, "AlianzaPro SPA");
        $mailEmpresa->addReplyTo($correo, $nombre);
        $mailEmpresa->Subject = "Nueva reserva {$numeroReserva}";
        prepararImagenesCorreoCitaAdmin($mailEmpresa, $rutaLogo, $qrImage, $numeroReserva);
        $mailEmpresa->Body = $htmlCorreo;
        $mailEmpresa->AltBody = crearTextoCorreoCitaAdmin(
            $nombre,
            $numeroReserva,
            $fechaVisual,
            $servicio,
            $vehiculo,
            $patente
        );
        $mailEmpresa->send();
    } catch (Throwable $errorCorreo) {
        $erroresCorreo[] = "empresa";
        error_log("Correo de cita a empresa: " . $errorCorreo->getMessage());
    }

    try {
        $mailCliente = configurarCorreoCitaAdmin();
        $mailCliente->addAddress($correo, $nombre);
        $mailCliente->Subject = "Confirmación de reserva {$numeroReserva}";
        prepararImagenesCorreoCitaAdmin($mailCliente, $rutaLogo, $qrImage, $numeroReserva);
        $mailCliente->Body = $htmlCorreo;
        $mailCliente->AltBody = crearTextoCorreoCitaAdmin(
            $nombre,
            $numeroReserva,
            $fechaVisual,
            $servicio,
            $vehiculo,
            $patente
        );
        $mailCliente->send();
    } catch (Throwable $errorCorreo) {
        $erroresCorreo[] = "cliente";
        error_log("Correo de cita al cliente: " . $errorCorreo->getMessage());
    }

    $mensaje = "Cita registrada correctamente. Reserva {$numeroReserva}.";

    if ($erroresCorreo) {
        $mensaje .= " La cita quedó guardada, pero uno de los correos no pudo enviarse.";
    }

    echo json_encode([
        "ok" => true,
        "mensaje" => $mensaje,
        "id_cita" => $idCita,
        "numeroReserva" => $numeroReserva,
        "correo_enviado" => !$erroresCorreo,
        "advertencias_correo" => $erroresCorreo
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error registrando cita desde administración: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible registrar la cita.";
    } else {
        if (http_response_code() < 400) {
            http_response_code(400);
        }
        $mensaje = $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

function configurarCorreoCitaAdmin(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Timeout = 30;
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";
    $mail->setFrom(SMTP_USER, "AlianzaPro SPA");
    $mail->isHTML(true);

    return $mail;
}

function prepararImagenesCorreoCitaAdmin(
    PHPMailer $mail,
    string|false $rutaLogo,
    ?string $qrImage,
    string $numeroReserva
): void {
    if ($rutaLogo && is_readable($rutaLogo)) {
        $mail->addEmbeddedImage(
            $rutaLogo,
            "logo_alianzapro",
            basename($rutaLogo)
        );
    }

    if ($qrImage !== null) {
        $mail->addStringEmbeddedImage(
            $qrImage,
            "qr_reserva",
            "qr_{$numeroReserva}.webp",
            "base64",
            "image/png"
        );
    }
}

function obtenerQrCitaAdmin(string $contenido): ?string
{
    $url =
        "https://api.qrserver.com/v1/create-qr-code/" .
        "?size=220x220&margin=8&data=" . rawurlencode($contenido);

    $contexto = stream_context_create([
        "http" => ["timeout" => 12],
        "https" => ["timeout" => 12]
    ]);

    $imagen = @file_get_contents($url, false, $contexto);

    if ($imagen === false || strlen($imagen) < 100) {
        error_log("No fue posible generar el QR de la cita administrativa.");
        return null;
    }

    return $imagen;
}

function formatearFechaCitaAdmin(string $fecha, string $hora): string
{
    $objeto = new DateTime($fecha . " " . $hora);
    $dias = [
        1 => "Lunes",
        2 => "Martes",
        3 => "Miércoles",
        4 => "Jueves",
        5 => "Viernes",
        6 => "Sábado",
        7 => "Domingo"
    ];
    $meses = [
        1 => "enero",
        2 => "febrero",
        3 => "marzo",
        4 => "abril",
        5 => "mayo",
        6 => "junio",
        7 => "julio",
        8 => "agosto",
        9 => "septiembre",
        10 => "octubre",
        11 => "noviembre",
        12 => "diciembre"
    ];

    return
        $dias[intval($objeto->format("N"))] . " " .
        $objeto->format("d") . " de " .
        $meses[intval($objeto->format("n"))] . " de " .
        $objeto->format("Y") . " a las " .
        $objeto->format("H:i");
}

function crearHtmlCorreoCitaAdmin(array $datos): string
{
    $escapar = static fn($valor) => htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );

    $nombre = $escapar($datos["nombre"]);
    $telefono = $escapar($datos["telefono"]);
    $correo = $escapar($datos["correo"]);
    $patente = $escapar($datos["patente"]);
    $vehiculo = $escapar($datos["vehiculo"]);
    $servicio = $escapar($datos["servicio"]);
    $numeroReserva = $escapar($datos["numero_reserva"]);
    $fechaVisual = $escapar($datos["fecha_visual"]);
    $comentario = nl2br($escapar(
        $datos["comentario"] ?: "Sin comentarios."
    ));
    $logoHtml = !empty($datos["logo_disponible"])
        ? '<img src="cid:logo_alianzapro" alt="AlianzaPro SPA" width="112" style="display:block; max-height:65px; object-fit:contain;">'
        : '<strong style="color:#2479ed;font-size:18px;">AlianzaPro</strong>';
    $qrHtml = !empty($datos["qr_disponible"])
        ? '<img src="cid:qr_reserva" alt="Código QR" width="82" height="82" style="display:block;border:1px solid #e1e6ed;">'
        : '<div style="padding:10px;border:1px solid #c8dcfa;border-radius:8px;color:#1768d3;font-size:11px;font-weight:bold;text-align:center;">RESERVA<br>' . $numeroReserva . '</div>';

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:24px;background:#f3f6fa;font-family:Arial,Helvetica,sans-serif;color:#202938;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table role="presentation" width="680" cellpadding="0" cellspacing="0" style="width:100%;max-width:680px;border:1px solid #dce2e9;border-radius:18px;background:#ffffff;">
    <tr><td style="padding:24px 26px;border-bottom:3px solid #2479ed;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td width="125" valign="middle">{$logoHtml}</td>
            <td valign="middle">
                <div style="color:#2479ed;font-size:12px;font-weight:800;letter-spacing:1px;">GESTIÓN DE CITAS</div>
                <h1 style="margin:5px 0 3px;color:#202938;font-size:25px;">Reserva confirmada</h1>
                <div style="color:#6c7687;font-size:13px;">AlianzaPro SPA · Su auto, nuestro compromiso</div>
            </td>
            <td width="92" align="right" valign="middle">{$qrHtml}</td>
        </tr></table>
    </td></tr>

    <tr><td style="padding:26px;">
        <p style="margin:0 0 18px;font-size:15px;">Hola <strong>{$nombre}</strong>, tu cita fue registrada correctamente por AlianzaPro.</p>

        <div style="padding:15px;border:1px solid #c8dcfa;border-radius:10px;background:#eaf3ff;">
            <div style="color:#687386;font-size:10px;font-weight:bold;">NÚMERO DE RESERVA</div>
            <div style="margin-top:5px;color:#1768d3;font-size:20px;font-weight:bold;">{$numeroReserva}</div>
        </div>

        <h2 style="margin:24px 0 10px;padding-bottom:8px;border-bottom:1px solid #dce2e9;color:#344000;font-size:17px;">Información de la cita</h2>
        <table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
            <tr><td width="34%" style="border-bottom:1px solid #e7ebf0;color:#687386;font-weight:bold;">Fecha y hora</td><td style="border-bottom:1px solid #e7ebf0;">{$fechaVisual}</td></tr>
            <tr><td style="border-bottom:1px solid #e7ebf0;color:#687386;font-weight:bold;">Servicio</td><td style="border-bottom:1px solid #e7ebf0;">{$servicio}</td></tr>
            <tr><td style="border-bottom:1px solid #e7ebf0;color:#687386;font-weight:bold;">Vehículo</td><td style="border-bottom:1px solid #e7ebf0;">{$vehiculo}</td></tr>
            <tr><td style="border-bottom:1px solid #e7ebf0;color:#687386;font-weight:bold;">Patente</td><td style="border-bottom:1px solid #e7ebf0;">{$patente}</td></tr>
            <tr><td style="border-bottom:1px solid #e7ebf0;color:#687386;font-weight:bold;">Teléfono</td><td style="border-bottom:1px solid #e7ebf0;">{$telefono}</td></tr>
            <tr><td style="border-bottom:1px solid #e7ebf0;color:#687386;font-weight:bold;">Correo</td><td style="border-bottom:1px solid #e7ebf0;">{$correo}</td></tr>
        </table>

        <div style="margin-top:20px;padding:14px;border-left:4px solid #2479ed;background:#f5f7fa;color:#566173;font-size:13px;">
            <strong style="color:#202938;">Comentario</strong><br>{$comentario}
        </div>

        <p style="margin:21px 0 0;color:#566173;font-size:13px;">Puedes presentar el número de reserva o el código QR al llegar.</p>
    </td></tr>

    <tr><td style="padding:17px 26px;background:#202938;color:#ffffff;font-size:11px;text-align:center;">AlianzaPro SPA · Venta de repuestos y taller mecánico</td></tr>
</table>
</td></tr></table>
</body>
</html>
HTML;
}

function crearTextoCorreoCitaAdmin(
    string $nombre,
    string $numeroReserva,
    string $fechaVisual,
    string $servicio,
    string $vehiculo,
    string $patente
): string {
    return
        "Hola {$nombre}, tu reserva fue confirmada.\n" .
        "Reserva: {$numeroReserva}\n" .
        "Fecha: {$fechaVisual}\n" .
        "Servicio: {$servicio}\n" .
        "Vehículo: {$vehiculo}\n" .
        "Patente: {$patente}";
}

?>
