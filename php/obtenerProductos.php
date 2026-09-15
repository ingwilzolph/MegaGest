<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "conexion.php";

$conexion = null;
$stmt = null;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        throw new Exception(
            "Método no permitido."
        );
    }

    $categoria = trim(
        $_POST["id"] ?? ""
    );

    if ($categoria === "") {

        throw new Exception(
            "Categoría no recibida."
        );
    }

    if (mb_strlen($categoria) > 100) {

        throw new Exception(
            "La categoría seleccionada no es válida."
        );
    }

    $conexion = conexion();

    $sql = "
        SELECT
            id_producto,
            categoria,
            nombre,
            descripcion,
            marca,
            cantidad,
            precio
        FROM productos
        WHERE LOWER(categoria) = LOWER(?)
        ORDER BY nombre ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "No fue posible preparar la consulta."
        );
    }

    $stmt->bind_param(
        "s",
        $categoria
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "No fue posible consultar los productos."
        );
    }

    $resultado = $stmt->get_result();

    $datos = [];

    while ($fila = $resultado->fetch_assoc()) {

        $fila["id_producto"] =
            (int) $fila["id_producto"];

        $fila["cantidad"] =
            (int) $fila["cantidad"];

        $fila["precio"] =
            (int) $fila["precio"];

        $datos[] = $fila;
    }

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

    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}