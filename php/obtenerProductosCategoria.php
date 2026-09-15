<?php

require_once "verificarSesionAjax.php";

require 'conexion.php';

$conexion = conexion();

$sql = "SELECT id_producto, categoria, nombre, descripcion, marca, cantidad, compra, precio FROM productos ORDER BY categoria";

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