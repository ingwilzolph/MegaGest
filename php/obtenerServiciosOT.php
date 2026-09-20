<?php

header("Content-Type: application/json");

require_once "conexion.php";
require_once "verificarSesionAjax.php";

$conexion = conexion();

$sql = "
SELECT
    id_servicio,
    nombre,
    precio_min
FROM servicios
WHERE LOWER(TRIM(nombre)) <> 'otro servicio'
ORDER BY nombre
";

$resultado = $conexion->query($sql);

$datos = [];

while($fila = $resultado->fetch_assoc()){

    $datos[] = $fila;

}

echo json_encode([
    "ok"=>true,
    "datos"=>$datos
]);

$conexion->close();