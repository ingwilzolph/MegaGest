<?php

header("Content-Type: application/json");

require_once "conexion.php";
require_once "verificarSesionAjax.php";

$conexion = conexion();

$sql = "SELECT * FROM citas WHERE fecha = CURDATE() ORDER BY hora ASC";

$resultado = $conexion->query($sql);

$datos = [];

while($fila = $resultado->fetch_assoc()){
    $datos[] = $fila;
}

echo json_encode(["ok"=>true, "datos"=>$datos]);

$conexion->close();