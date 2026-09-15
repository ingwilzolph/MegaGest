<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

$sql = "
    SELECT
        id_cita,
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
        estado,
        fecha_registro
    FROM citas
    WHERE fecha > CURDATE()
      AND fecha <= LAST_DAY(CURDATE())
      AND estado IN ('Pendiente', 'Confirmada')
    ORDER BY fecha ASC, hora ASC
";

$resultado = $conexion->query($sql);

if (!$resultado) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "No fue posible consultar las próximas citas."
    ]);

    $conexion->close();
    exit;
}

$datos = [];

while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode([
    "ok" => true,
    "datos" => $datos
], JSON_UNESCAPED_UNICODE);

$conexion->close();

?>