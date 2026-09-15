<?php

header("Content-Type: application/json");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

$id = intval($_GET["id"] ?? 0);

if($id <= 0){

    echo json_encode(["ok"=>false, "mensaje"=>"ID de cita inválido."]);

    exit;
}

$sql = "UPDATE citas SET estado='confirmada' WHERE id_cita=?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i",$id);

if(!$stmt->execute()){

    echo json_encode(["ok"=>false, "mensaje"=>"No se pudo confirmar la cita."]);

    exit;
}

echo json_encode(["ok"=>true, "mensaje"=>"La cita fue confirmada correctamente."]);

$stmt->close();
$conexion->close();

?>