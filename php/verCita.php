<?php

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/configQr.php";

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");
header("X-Robots-Tag: noindex, nofollow");

function escaparCitaQr($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

$cita = null;
$mensajeError = "El enlace de la reserva no es válido.";
$conexion = null;

try {
    $numeroReserva = trim((string) ($_GET["reserva"] ?? ""));
    $token = trim((string) ($_GET["qr"] ?? ""));

    if (
        $numeroReserva === "" ||
        strlen($numeroReserva) > 40 ||
        $token === "" ||
        !hash_equals(generarTokenCitaQr($numeroReserva), $token)
    ) {
        throw new RuntimeException($mensajeError, 403);
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $stmt = $conexion->prepare("
        SELECT
            numeroReserva,
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
        FROM citas
        WHERE numeroReserva = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $numeroReserva);
    $stmt->execute();
    $cita = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cita) {
        throw new RuntimeException("No se encontró la reserva.", 404);
    }
} catch (Throwable $error) {
    $codigo = (int) $error->getCode();
    http_response_code(in_array($codigo, [403, 404], true) ? $codigo : 500);
    $mensajeError = in_array($codigo, [403, 404], true)
        ? $error->getMessage()
        : "No fue posible consultar la reserva.";
    error_log("Error consultando QR de cita: " . $error->getMessage());
} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

$estadoClase = strtolower((string) ($cita["estado"] ?? ""));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $cita ? escaparCitaQr($cita["numeroReserva"]) : "Reserva no disponible" ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #eef2f6; color: #172033; font-family: Arial, sans-serif; }
        main { max-width: 760px; margin: auto; padding: 30px; background: #fff; border-radius: 14px; box-shadow: 0 10px 35px rgba(23,32,51,.10); }
        h1 { margin: 0 0 6px; color: #1768d3; }
        h2 { margin: 0 0 24px; }
        .reserva { font-size: 20px; font-weight: 700; }
        dl { display: grid; grid-template-columns: 170px 1fr; gap: 12px 18px; margin: 24px 0; }
        dt { font-weight: 700; }
        dd { margin: 0; overflow-wrap: anywhere; }
        .estado { display: inline-block; padding: 7px 12px; border-radius: 999px; background: #e8f1ff; color: #145bbb; font-weight: 700; text-transform: capitalize; }
        .error { color: #b4232d; }
        .nota { margin-top: 25px; padding: 13px; background: #f7f9fc; border-left: 4px solid #1768d3; }
        @media (max-width: 560px) { body { padding: 12px; } main { padding: 22px; } dl { grid-template-columns: 1fr; gap: 4px; } dd { margin-bottom: 10px; } }
        @media print { body { padding: 0; background: #fff; } main { box-shadow: none; } }
    </style>
</head>
<body>
<main>
    <h1>AlianzaPro SPA</h1>

    <?php if ($cita): ?>
        <h2>Comprobante de reserva</h2>
        <p class="reserva"><?= escaparCitaQr($cita["numeroReserva"]) ?></p>
        <span class="estado"><?= escaparCitaQr($estadoClase) ?></span>

        <dl>
            <dt>Cliente</dt><dd><?= escaparCitaQr($cita["nombre"]) ?></dd>
            <dt>Teléfono</dt><dd><?= escaparCitaQr($cita["telefono"]) ?></dd>
            <dt>Correo</dt><dd><?= escaparCitaQr($cita["correo"]) ?></dd>
            <dt>Vehículo</dt><dd><?= escaparCitaQr($cita["vehiculo"]) ?></dd>
            <dt>Patente</dt><dd><?= escaparCitaQr($cita["patente"]) ?></dd>
            <dt>Servicio</dt><dd><?= escaparCitaQr($cita["servicio"]) ?></dd>
            <dt>Fecha</dt><dd><?= escaparCitaQr($cita["fecha"]) ?></dd>
            <dt>Hora</dt><dd><?= escaparCitaQr(substr((string) $cita["hora"], 0, 5)) ?></dd>
            <?php if (trim((string) $cita["comentario"]) !== ""): ?>
                <dt>Comentario</dt><dd><?= escaparCitaQr($cita["comentario"]) ?></dd>
            <?php endif; ?>
        </dl>

        <p class="nota">Su auto, nuestro compromiso… Presente esta reserva al llegar.</p>
    <?php else: ?>
        <h2 class="error">Reserva no disponible</h2>
        <p><?= escaparCitaQr($mensajeError) ?></p>
    <?php endif; ?>
</main>
</body>
</html>
