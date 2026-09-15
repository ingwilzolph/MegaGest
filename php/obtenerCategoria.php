<?php

header("Content-Type: application/json; charset=utf-8");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

try {

    $idCategoria = filter_input(
        INPUT_GET,
        "id",
        FILTER_VALIDATE_INT
    );

    if (!$idCategoria || $idCategoria <= 0) {
        throw new Exception(
            "El ID de la categoría no es válido."
        );
    }

    $conexion = conexion();

    $stmt = $conexion->prepare(
        "SELECT
            id_categoria,
            categoria,
            descripcion,
            slug,
            visible_tienda,
            orden,
            fecha_actualizacion
         FROM categorias
         WHERE id_categoria = ?
         LIMIT 1"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "i",
        $idCategoria
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception(
            "Categoría no encontrada."
        );
    }

    $categoria = $resultado->fetch_assoc();

    $categoria["id_categoria"] =
        (int)$categoria["id_categoria"];

    $categoria["visible_tienda"] =
        (int)$categoria["visible_tienda"];

    $categoria["orden"] =
        (int)$categoria["orden"];

    echo json_encode([
        "ok" => true,
        "datos" => $categoria
    ]);

    $stmt->close();

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