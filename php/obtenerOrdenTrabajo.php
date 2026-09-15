<?php

header("Content-Type: application/json; charset=utf-8");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

$idCita = intval($_GET["id"] ?? 0);

if($idCita <= 0){

    echo json_encode(["ok"=>false, "mensaje"=>"ID inválido."]);

    exit;
}

$sql = "

SELECT

    ot.id_ot,
    ot.numeroOT,
    ot.kilometraje,
    ot.combustible,
    ot.observacionesRecepcion,
    ot.observacionesEntrega,
    ot.estado,

    c.numeroReserva,
    c.nombre,
    c.telefono,
    c.correo,
    c.vehiculo,
    c.patente,
    c.servicio,
    c.fecha,
    c.hora

FROM orden_trabajo ot

INNER JOIN citas c
ON c.id_cita = ot.id_cita

WHERE ot.id_cita = ?

LIMIT 1

";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i",$idCita);

$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows == 0){

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"Orden de Trabajo no encontrada."
    ]);

    exit;
}

echo json_encode([
    "ok"=>true,
    "datos"=>$resultado->fetch_assoc()
]);

$stmt->close();
$conexion->close();