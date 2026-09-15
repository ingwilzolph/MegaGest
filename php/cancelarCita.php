<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

$idCita = intval($_POST["id_cita"] ?? 0);

if ($idCita <= 0) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La cita no es válida."
    ]);

    exit;
}

$stmt = $conexion->prepare("
    UPDATE citas
    SET estado = 'Cancelada'
    WHERE id_cita = ?
      AND estado IN ('Pendiente', 'Confirmada')
");

$stmt->bind_param("i", $idCita);

$stmt->execute();

if ($stmt->affected_rows === 0) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La cita no existe o ya no puede cancelarse."
    ]);

} else {

    echo json_encode([
        "ok" => true,
        "mensaje" => "La cita fue cancelada correctamente."
    ]);
}

$stmt->close();
$conexion->close();