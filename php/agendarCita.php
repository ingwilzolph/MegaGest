
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require '../vendor/autoload.php';
require 'conexion.php';
require 'config.php';
require_once __DIR__ . '/configQr.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
$conexion = null;

try {

    /*====================
    VALIDAR CAPTCHA
    ====================*/

    if (
        !isset($_SESSION['gfm_captcha']) ||
        strtoupper(trim($_POST['captcha'])) !== strtoupper($_SESSION['gfm_captcha'])
    ) {

        echo json_encode([
            'ok' => false,
            'mensaje' => 'CAPTCHA incorrecto'
        ]);

        exit;
    }

    /*====================
    OBTENER DATOS
    ====================*/

    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $patente = trim(strtoupper($_POST['patente']));
    $vehiculo = trim($_POST['vehiculo']);
    $servicio = trim($_POST['servicio']);

    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    $comentario = trim($_POST['comentario']);

    $telefono = preg_replace('/\D/', '', $_POST['telefono']);

    if (strlen($telefono) != 9) {

        echo json_encode(["ok" => false, "mensaje" => "El teléfono debe tener 9 dígitos."]);

        exit;
    }

    /*====================
    GUARDAR MYSQL
    ====================*/

    $conexion = conexion();
    $conexion->set_charset('utf8mb4');
    $conexion->begin_transaction();

    /*====================
VALIDAR CITA PENDIENTE
====================*/

$stmt = $conexion->prepare("SELECT id_cita, numeroReserva 
   FROM citas 
   WHERE (correo = ? OR telefono = ?) AND patente = ?
      AND estado IN ('Pendiente','Confirmada','En proceso')
      AND (fecha > CURDATE() OR (fecha = CURDATE() AND hora >= CURTIME()))
    LIMIT 1
");

if(!$stmt){
    throw new Exception($conexion->error);
}

$stmt->bind_param("sss", $correo, $telefono, $patente);
$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows > 0){

    $cita = $resultado->fetch_assoc();

    echo json_encode([
        "ok" => false,
        "mensaje" => "Ya tienes una cita pendiente con este auto (Reserva {$cita['numeroReserva']}). Si deseas modificarla o cancelarla, comunícate con AlianzaPro."
    ]);

    exit;
}

    /*====================
VALIDAR DISPONIBILIDAD
====================*/

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM citas WHERE fecha = ? AND hora = ? AND estado IN ('Pendiente','Confirmada','En proceso')");

$stmt->bind_param("ss",$fecha, $hora);

$stmt->execute();

$resultado = $stmt->get_result()->fetch_assoc();


if($resultado['total'] >= 2){

    echo json_encode(["ok" => false, "mensaje" => "El horario seleccionado ya está completo. Por favor elige otro horario."]);

    exit;
}

    $stmt = $conexion->prepare("INSERT INTO citas(nombre,telefono,correo,patente,vehiculo,servicio,fecha,hora,comentario) VALUES(?,?,?,?,?,?,?,?,?)");

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param("sssssssss", $nombre, $telefono, $correo, $patente, $vehiculo, $servicio, $fecha, $hora, $comentario);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $idCita = $conexion->insert_id;

    $numeroReserva = sprintf("AP-%s-%06d", date("Ymd"), $idCita);

    $stmt = $conexion->prepare("UPDATE citas SET numeroReserva = ? WHERE id_cita = ?");

    $stmt->bind_param("si", $numeroReserva, $idCita);

    if(!$stmt->execute()){
        throw new Exception($stmt->error);
    }

    /*====================
    PREPARAR CORREO
====================*/

/* ====================
   QR (SIN LIBRERÍA)
==================== */

$qrData = generarUrlCitaQr($numeroReserva);

$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=" . rawurlencode($qrData);

$contextoQr = stream_context_create([
    'http' => ['timeout' => 12],
    'https' => ['timeout' => 12]
]);

$qrImage = @file_get_contents($qrUrl, false, $contextoQr);

if ($qrImage === false || strlen($qrImage) < 100) {
    throw new Exception("No fue posible generar el código QR.");
}

/* ====================
   FECHA
==================== */

date_default_timezone_set('America/Santiago');

$timestamp = strtotime($fecha . " " . $hora);

$dias = [
    'Sunday' => 'Domingo',
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado'
];

$meses = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];

$hora = date('g:i a', $timestamp);

$fechaPdf = $dias[date('l', $timestamp)] . ' ' .
            date('d', $timestamp) . ' de ' .
            $meses[(int)date('n', $timestamp)] . ' de ' .
            date('Y', $timestamp) . ' a las ' . $hora;

$fechaPdf = ucfirst($fechaPdf);

/* ====================
   LOGO
==================== */

$rutaLogo = realpath(__DIR__ . '/../images/logoAlianzaPro.webp');

if (!$rutaLogo || !is_readable($rutaLogo)) {
    throw new Exception("No se encontró el logo de AlianzaPro.");
}

$nombreHtml = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
$correoHtml = htmlspecialchars($correo, ENT_QUOTES, 'UTF-8');
$telefonoHtml = htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8');
$vehiculoHtml = htmlspecialchars($vehiculo, ENT_QUOTES, 'UTF-8');
$patenteHtml = htmlspecialchars($patente, ENT_QUOTES, 'UTF-8');
$servicioHtml = htmlspecialchars($servicio, ENT_QUOTES, 'UTF-8');
$comentarioHtml = nl2br(htmlspecialchars($comentario ?: 'Sin comentarios.', ENT_QUOTES, 'UTF-8'));
$numeroReservaHtml = htmlspecialchars($numeroReserva, ENT_QUOTES, 'UTF-8');
$fechaPdfHtml = htmlspecialchars($fechaPdf, ENT_QUOTES, 'UTF-8');

/* =====================================================
   HTML DEL CORREO
   Las imágenes usan CID porque el destinatario no puede
   acceder a las rutas locales del servidor.
===================================================== */

$htmlCorreo = "
<!DOCTYPE html>
<html lang='es'>
<body style='margin:0; padding:24px; background:#f3f6fa; font-family:Arial,Helvetica,sans-serif; color:#202938;'>
<table role='presentation' width='100%' cellpadding='0' cellspacing='0'><tr><td align='center'>
    <table role='presentation' width='680' cellpadding='0' cellspacing='0' style='width:100%; max-width:680px; overflow:hidden; border:1px solid #dce2e9; border-radius:18px; background:#ffffff;'>
        <tr>
            <td style='padding:24px 26px; border-bottom:3px solid #2479ed;'>
                <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td width='125' valign='middle'><img src='cid:logo_alianzapro' alt='AlianzaPro SPA' width='112' style='display:block; max-height:65px; object-fit:contain;'></td>
                        <td valign='middle'>
                            <div style='color:#2479ed; font-size:12px; font-weight:800; letter-spacing:1px;'>GESTIÓN DE CITAS</div>
                            <h1 style='margin:5px 0 3px; color:#202938; font-size:25px;'>Reserva confirmada</h1>
                            <div style='color:#6c7687; font-size:13px;'>AlianzaPro SPA · Su auto, nuestro compromiso</div>
                        </td>
                        <td width='92' align='right' valign='middle'><img src='cid:qr_reserva' alt='Código QR' width='82' height='82' style='display:block; border:1px solid #e1e6ed;'></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr><td style='padding:26px;'>
            <p style='margin:0 0 18px; font-size:15px;'>Hola <strong>{$nombreHtml}</strong>, tu cita fue registrada correctamente.</p>

            <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                <tr>
                    <td style='padding:15px; border:1px solid #c8dcfa; border-radius:10px; background:#eaf3ff;'>
                        <div style='color:#687386; font-size:10px; font-weight:bold;'>NÚMERO DE RESERVA</div>
                        <div style='margin-top:5px; color:#1768d3; font-size:20px; font-weight:bold;'>{$numeroReservaHtml}</div>
                    </td>
                </tr>
            </table>

            <h2 style='margin:24px 0 10px; padding-bottom:8px; border-bottom:1px solid #dce2e9; color:#344000; font-size:17px;'>Información de la cita</h2>
            <table role='presentation' width='100%' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-size:13px;'>
                <tr><td width='34%' style='border-bottom:1px solid #e7ebf0; color:#687386; font-weight:bold;'>Fecha y hora</td><td style='border-bottom:1px solid #e7ebf0;'>{$fechaPdfHtml}</td></tr>
                <tr><td style='border-bottom:1px solid #e7ebf0; color:#687386; font-weight:bold;'>Servicio</td><td style='border-bottom:1px solid #e7ebf0;'>{$servicioHtml}</td></tr>
                <tr><td style='border-bottom:1px solid #e7ebf0; color:#687386; font-weight:bold;'>Vehículo</td><td style='border-bottom:1px solid #e7ebf0;'>{$vehiculoHtml}</td></tr>
                <tr><td style='border-bottom:1px solid #e7ebf0; color:#687386; font-weight:bold;'>Patente</td><td style='border-bottom:1px solid #e7ebf0;'>{$patenteHtml}</td></tr>
                <tr><td style='border-bottom:1px solid #e7ebf0; color:#687386; font-weight:bold;'>Teléfono</td><td style='border-bottom:1px solid #e7ebf0;'>{$telefonoHtml}</td></tr>
            </table>

            <div style='margin-top:20px; padding:14px; border-left:4px solid #2479ed; background:#f5f7fa; color:#566173; font-size:13px;'>
                <strong style='color:#202938;'>Comentario</strong><br>{$comentarioHtml}
            </div>

            <p style='margin:21px 0 0; color:#566173; font-size:13px;'>Puedes presentar el número de reserva o el código QR al llegar.</p>
        </td></tr>

        <tr><td style='padding:17px 26px; background:#202938; color:#ffffff; font-size:11px; text-align:center;'>AlianzaPro SPA · Venta de repuestos y taller mecánico</td></tr>
    </table>
</td></tr></table>
</body>
</html>";

    /*====================
    CORREO A ALIANZAPRO
    ====================*/
    $mailEmpresa = new PHPMailer(true);
    
    try {

        $mailEmpresa->isSMTP();

        $mailEmpresa->Timeout = 30;

        $mailEmpresa->Host = SMTP_HOST;

        $mailEmpresa->SMTPAuth = true;

        $mailEmpresa->Username = SMTP_USER;

        $mailEmpresa->Password = SMTP_PASS;

        $mailEmpresa->SMTPSecure = SMTP_SECURE;

        $mailEmpresa->Port = SMTP_PORT;

        $mailEmpresa->CharSet = 'UTF-8';

        $mailEmpresa->setFrom(
            SMTP_USER,
            'AlianzaPro'
        );

        $mailEmpresa->addAddress(
            SMTP_USER
        );

        $mailEmpresa->addReplyTo(
            $correo,
            $nombre
        );

        $mailEmpresa->isHTML(true);

        $mailEmpresa->Subject = "Nueva reserva {$numeroReserva}";

        $mailEmpresa->addEmbeddedImage(
            $rutaLogo,
            'logo_alianzapro',
            basename($rutaLogo)
        );

        $mailEmpresa->addStringEmbeddedImage(
            $qrImage,
            'qr_reserva',
            'qr_' . $numeroReserva . '.webp',
            'base64',
            'image/png'
        );

        $mailEmpresa->Body = $htmlCorreo;
        $mailEmpresa->AltBody =
            "Nueva reserva {$numeroReserva}\n" .
            "Cliente: {$nombre}\n" .
            "Fecha: {$fechaPdf}\n" .
            "Servicio: {$servicio}\n" .
            "Vehículo: {$vehiculo}\n" .
            "Patente: {$patente}";

        $mailEmpresa->send();

    } catch (MailException $e) {

    throw new \Exception(
        'Correo empresa: ' . $mailEmpresa->ErrorInfo
    );

}

    /*====================
    CORREO CLIENTE + PDF
    ====================*/

     $mailCliente = new PHPMailer(true);

    try {
        $mailCliente->isSMTP();

        $mailCliente->Timeout = 30;

        $mailCliente->Host = SMTP_HOST;

        $mailCliente->SMTPAuth = true;

        $mailCliente->Username = SMTP_USER;

        $mailCliente->Password = SMTP_PASS;

        $mailCliente->SMTPSecure = SMTP_SECURE;

        $mailCliente->Port = SMTP_PORT;

        $mailCliente->CharSet = 'UTF-8';

        $mailCliente->setFrom(
            SMTP_USER,
            'AlianzaPro'
        );

        $mailCliente->addAddress(
            $correo,
            $nombre
        );

        $mailCliente->isHTML(true);

        $mailCliente->Subject = "Confirmación de reserva {$numeroReserva}";

        $mailCliente->addEmbeddedImage(
            $rutaLogo,
            'logo_alianzapro',
            basename($rutaLogo)
        );

        $mailCliente->addStringEmbeddedImage(
            $qrImage,
            'qr_reserva',
            'qr_' . $numeroReserva . '.webp',
            'base64',
            'image/png'
        );

        $mailCliente->Body = $htmlCorreo;
        $mailCliente->AltBody =
            "Hola {$nombre}, tu reserva fue confirmada.\n" .
            "Reserva: {$numeroReserva}\n" .
            "Fecha: {$fechaPdf}\n" .
            "Servicio: {$servicio}\n" .
            "Vehículo: {$vehiculo}\n" .
            "Patente: {$patente}";

        $mailCliente->send();

    } catch (MailException $e) {

    throw new \Exception(
        'Correo cliente: ' . $mailCliente->ErrorInfo
    );

}
    $conexion->commit();

    echo json_encode(['ok' => true, 'mensaje' => 'Cita registrada correctamente.']);

} catch (\Exception $e) {
    if ($conexion instanceof mysqli) {
        $conexion->rollback();
    }

    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}

?>
