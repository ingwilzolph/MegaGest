<?php

header("Content-Type: application/json");

require_once "conexion.php";


if(!isset($_GET["buscar"])){

    echo json_encode([]);

    exit;

}


$texto = trim($_GET["buscar"]);


if(strlen($texto) < 2){

    echo json_encode([]);

    exit;

}


$palabras = explode(" ", $texto);


$conexion = conexion();


$sql = "
SELECT
    id_producto,
    nombre,
    descripcion,
    marca,
    cantidad,
    precio

FROM productos

WHERE
";


$condiciones = [];

$parametros = [];
$tipos = "";


foreach($palabras as $palabra){

    if($palabra == "")
        continue;


    $condiciones[] = "
    (
        nombre LIKE ?
        OR descripcion LIKE ?
        OR marca LIKE ?
        OR categoria LIKE ?
    )
    ";


    $buscar = "%".$palabra."%";


    $parametros[] = $buscar;
    $parametros[] = $buscar;
    $parametros[] = $buscar;
    $parametros[] = $buscar;


    $tipos .= "ssss";

}


$sql .= implode(" AND ", $condiciones);


$sql .= "

ORDER BY nombre ASC

LIMIT 10

";


$stmt = $conexion->prepare($sql);


$stmt->bind_param(
    $tipos,
    ...$parametros
);


$stmt->execute();


$resultado = $stmt->get_result();


$productos = [];


while($fila = $resultado->fetch_assoc()){

    $productos[] = $fila;

}


echo json_encode($productos);

?>