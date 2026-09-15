<?php

header("Content-Type: application/json");

require_once "conexion.php";

$conexion = conexion();

if(
    !isset($_POST["id_ot"]) ||
    !isset($_POST["id_usuario"])
){

    echo json_encode(["ok"=>false, "mensaje"=>"Faltan datos."]);

    exit;

}

$idOT = intval($_POST["id_ot"]);

$idUsuario = intval($_POST["id_usuario"]);

$kilometraje = intval($_POST["kilometraje"]);

$combustible = trim($_POST["combustible"]);

$observacionesRecepcion = trim($_POST["observacionesRecepcion"]);


$stmt = $conexion->prepare("

UPDATE orden_trabajo

SET

id_usuario=?,
kilometraje=?,
combustible=?,
observacionesRecepcion=?

WHERE id_ot=?

");

$stmt->bind_param("iissi", $idUsuario, $kilometraje, $combustible, $observacionesRecepcion, $idOT);

if($stmt->execute()){

    echo json_encode(["ok"=>true, "mensaje"=>"Orden guardada correctamente."]);

}else{

    echo json_encode(["ok"=>false, "mensaje"=>$stmt->error]);

}

$stmt->close();

$conexion->close();