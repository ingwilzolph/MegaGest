<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

$fechaInicio = trim($_GET["inicio"] ?? "");
$fechaFin = trim($_GET["fin"] ?? "");

function fechaValida($fecha) {

    $objeto = DateTime::createFromFormat(
        "Y-m-d",
        $fecha
    );

    return (
        $objeto &&
        $objeto->format("Y-m-d") === $fecha
    );
}

if (
    !fechaValida($fechaInicio) ||
    !fechaValida($fechaFin)
) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Las fechas seleccionadas no son válidas."
    ]);

    exit;
}

if ($fechaInicio > $fechaFin) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La fecha inicial no puede ser mayor que la fecha final."
    ]);

    exit;
}

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
    WHERE fecha BETWEEN ? AND ?
    ORDER BY fecha ASC, hora ASC
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "No fue posible preparar la consulta."
    ]);

    $conexion->close();
    exit;
}

$stmt->bind_param(
    "ss",
    $fechaInicio,
    $fechaFin
);

$stmt->execute();

$resultado = $stmt->get_result();

$datos = [];

while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode([
    "ok" => true,
    "datos" => $datos
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conexion->close();