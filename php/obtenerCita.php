<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

$idCita = intval($_GET["id"] ?? 0);

if ($idCita <= 0) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "El identificador de la cita no es válido."
    ]);

    exit;
}

$sql = "
    SELECT
    c.id_cita,
    c.numeroReserva,
    c.nombre,
    c.telefono,
    c.correo,
    c.patente,
    c.vehiculo,
    c.servicio,
    c.fecha,
    c.hora,
    c.fecha_registro,
    c.comentario,
    o.observacionesEntrega,
    c.estado,
    o.fechaInicio,
    o.fechaFin,
    c.fecha_registro

FROM citas c LEFT JOIN orden_trabajo o

ON c.id_cita = o.id_cita

WHERE c.id_cita = ?

LIMIT 1;
";

$consulta = $conexion->prepare($sql);

if (!$consulta) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "No fue posible preparar la consulta."
    ]);

    $conexion->close();
    exit;
}

$consulta->bind_param("i", $idCita);

$consulta->execute();

$resultado = $consulta->get_result();

if ($resultado->num_rows === 0) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La cita solicitada no existe."
    ]);

    $consulta->close();
    $conexion->close();

    exit;
}

$cita = $resultado->fetch_assoc();

echo json_encode([
    "ok" => true,
    "datos" => $cita
], JSON_UNESCAPED_UNICODE);

$consulta->close();
$conexion->close();

?>