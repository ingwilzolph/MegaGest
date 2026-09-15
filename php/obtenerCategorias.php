<?php

header('Content-Type: application/json');

require 'conexion.php';

$conexion = conexion();

$sql = "SELECT id_categoria, categoria FROM categorias WHERE visible_tienda = 1 ORDER BY orden ASC, categoria ASC";

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