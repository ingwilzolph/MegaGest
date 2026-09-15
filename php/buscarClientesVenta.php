<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

try {

    $buscar = trim($_GET["buscar"] ?? "");

    if (mb_strlen($buscar) < 2) {
        throw new Exception(
            "Ingrese al menos 2 caracteres."
        );
    }

    $conexion = conexion();

    $termino = "%" . $buscar . "%";

    $sql = "
        SELECT
            id_cliente,
            rut,
            nombre,
            apellido,
            correo,
            telefono
        FROM clientes
        WHERE
            rut LIKE ?
            OR nombre LIKE ?
            OR apellido LIKE ?
            OR correo LIKE ?
            OR telefono LIKE ?
            OR CONCAT(nombre, ' ', apellido) LIKE ?
        ORDER BY nombre ASC, apellido ASC
        LIMIT 10
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "No fue posible preparar la búsqueda."
        );
    }

    $stmt->bind_param(
        "ssssss",
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

    $datos = [];

    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,
        "datos" => $datos
    ]);

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>