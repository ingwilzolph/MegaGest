<?php

require_once "verificarAdministrador.php";
require_once "conexion.php";


$conexion = conexion();

$sql = "SELECT * FROM login_admin";

$resultado = $conexion->query($sql);

if(!$resultado){

    echo json_encode(['ok'=>false,'mensaje'=>$conexion->error]);

    exit;
}

$datos = [];

while($fila = $resultado->fetch_assoc()){

    $datos[] = $fila;

}

echo json_encode(['ok'=>true, 'datos'=>$datos]);

$conexion->close();

?>