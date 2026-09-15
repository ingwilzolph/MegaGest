<?php

header("Content-Type: application/json; charset=utf-8");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido.");
    }

    $idCategoria = filter_var(
        $_POST["id_categoria"] ?? null,
        FILTER_VALIDATE_INT
    );

    $visibleTienda = filter_var(
        $_POST["visible_tienda"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$idCategoria || $idCategoria <= 0) {
        throw new Exception(
            "El ID de la categoría no es válido."
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

    $conexion = conexion();

    $stmtExiste = $conexion->prepare(
        "SELECT categoria
         FROM categorias
         WHERE id_categoria = ?
         LIMIT 1"
    );

    if (!$stmtExiste) {
        throw new Exception($conexion->error);
    }

    $stmtExiste->bind_param(
        "i",
        $idCategoria
    );

    $stmtExiste->execute();

    $resultado =
        $stmtExiste->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception(
            "La categoría no existe."
        );
    }

    $categoria = $resultado->fetch_assoc();

    $stmtExiste->close();

    $stmt = $conexion->prepare(
        "UPDATE categorias
         SET visible_tienda = ?
         WHERE id_categoria = ?"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "ii",
        $visibleTienda,
        $idCategoria
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $mensaje = $visibleTienda === 1
        ? "La categoría ahora está visible en la tienda."
        : "La categoría fue ocultada de la tienda.";

    echo json_encode([
        "ok" => true,
        "mensaje" => $mensaje,
        "nombre" => $categoria["categoria"],
        "visible_tienda" => $visibleTienda
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