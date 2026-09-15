<?php

use Dba\Connection;

header("Content-Type: application/json");

require_once "conexion.php";

if (!isset($_GET["id_ot"])) {

    echo json_encode([]);
    exit;

}

$idOT = intval($_GET["id_ot"]);

$sql = "

SELECT
    id_foto,
    nombreArchivo,
    fechaSubida
FROM fotos_ot
WHERE id_ot = ?
ORDER BY id_foto DESC

";

$conexion = conexion();

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i", $idOT);

$stmt->execute();

$resultado = $stmt->get_result();

$fotos = [];

while($fila = $resultado->fetch_assoc()){

    $fotos[] = $fila;

}

echo json_encode($fotos);
?>