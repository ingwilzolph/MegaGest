<?php

require_once "verificarSesionAjax.php";
require 'conexion.php';

$conexion = conexion();

if (!isset($_GET["id"])) {
    echo json_encode(["ok" => false, "mensaje" => "ID de servicio no recibido." ]);
    exit;
}

$idServicio = intval($_GET["id"]);

//==============================
// Eliminar imágenes del servicio
//==============================

$imagenGrande = "../images/" . $idServicio . ".webp";
$imagenMini = "../images/thumbs/" . $idServicio . ".webp";


if (file_exists($imagenGrande)) {
    unlink($imagenGrande);
}


if (file_exists($imagenMini)) {
    unlink($imagenMini);
}

$sql = "DELETE FROM servicios WHERE id_servicio = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode(["ok" => false, "mensaje" => "Error al preparar la consulta."]);
    exit;
}
$stmt = $conexion->prepare($sql);


$stmt->bind_param("i", $idServicio);

if ($stmt->execute()) {

    echo json_encode(["ok" => true, "mensaje" => "Servicio eliminado correctamente."]);

} else {

    echo json_encode(["ok" => false, "mensaje" => "No se pudo eliminar el Servicio."]);

}

$stmt->close();
$conexion->close();

