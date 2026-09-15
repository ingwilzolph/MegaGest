<?php

header("Content-Type: application/json");

require_once "conexion.php";


if(!isset($_POST["id_foto"])){

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"Falta id_foto"
    ]);

    exit;

}


$idFoto = intval($_POST["id_foto"]);


$conexion = conexion();


// Buscar archivo

$stmt = $conexion->prepare("
    SELECT nombreArchivo
    FROM fotos_ot
    WHERE id_foto = ?
");


$stmt->bind_param(
    "i",
    $idFoto
);


$stmt->execute();


$resultado = $stmt->get_result();


if($resultado->num_rows == 0){

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"Fotografía no encontrada"
    ]);

    exit;

}


$fila = $resultado->fetch_assoc();

$rutaArchivo = "../" . $fila["nombreArchivo"];


// Eliminar archivo físico

if(file_exists($rutaArchivo)){

    unlink($rutaArchivo);

}


// Eliminar registro

$stmt = $conexion->prepare("
    DELETE FROM fotos_ot
    WHERE id_foto = ?
");


$stmt->bind_param(
    "i",
    $idFoto
);


$stmt->execute();



echo json_encode([

    "ok"=>true,
    "mensaje"=>"Fotografía eliminada"

]);

?>