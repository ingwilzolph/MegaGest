<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

try {

    $idProducto = filter_var(
        $_POST["idProducto"] ?? null,
        FILTER_VALIDATE_INT
    );

    $visibleTienda = filter_var(
        $_POST["visible_tienda"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$idProducto || $idProducto <= 0) {
        throw new Exception(
            "El ID del producto no es válido."
        );
    }

    if (
        $visibleTienda === false ||
        !in_array($visibleTienda, [0, 1], true)
    ) {
        throw new Exception(
            "El valor de visibilidad no es válido."
        );
    }

    $stmtProducto = $conexion->prepare(
        "SELECT nombre
         FROM productos
         WHERE id_producto = ?
         LIMIT 1"
    );

    if (!$stmtProducto) {
        throw new Exception($conexion->error);
    }

    $stmtProducto->bind_param(
        "i",
        $idProducto
    );

    $stmtProducto->execute();

    $resultado =
        $stmtProducto->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception(
            "El producto no existe."
        );
    }

    $producto = $resultado->fetch_assoc();

    $stmtProducto->close();

    $stmt = $conexion->prepare(
        "UPDATE productos
         SET visible_tienda = ?
         WHERE id_producto = ?"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "ii",
        $visibleTienda,
        $idProducto
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $accion =
        $visibleTienda === 1
            ? "visible"
            : "oculto";

    echo json_encode([
        "ok" => true,
        "mensaje" =>
            "El producto \"{$producto['nombre']}\" ahora está {$accion} en la tienda."
    ]);

    $stmt->close();

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    $conexion->close();
}
?>