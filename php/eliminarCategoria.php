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

    if (!$idCategoria || $idCategoria <= 0) {
        throw new Exception(
            "El ID de la categoría no es válido."
        );
    }

    $conexion = conexion();
    $conexion->begin_transaction();

    /* Obtener categoría */

    $stmtCategoria = $conexion->prepare(
        "SELECT categoria
         FROM categorias
         WHERE id_categoria = ?
         LIMIT 1"
    );

    if (!$stmtCategoria) {
        throw new Exception($conexion->error);
    }

    $stmtCategoria->bind_param(
        "i",
        $idCategoria
    );

    $stmtCategoria->execute();

    $resultado =
        $stmtCategoria->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception(
            "La categoría no existe."
        );
    }

    $categoria = $resultado->fetch_assoc();

    $stmtCategoria->close();

    /* Comprobar productos asociados */

    $stmtProductos = $conexion->prepare(
        "SELECT COUNT(*) AS total
         FROM productos
         WHERE LOWER(TRIM(categoria)) =
               LOWER(TRIM(?))"
    );

    if (!$stmtProductos) {
        throw new Exception($conexion->error);
    }

    $stmtProductos->bind_param(
        "s",
        $categoria["categoria"]
    );

    $stmtProductos->execute();

    $productos = $stmtProductos
        ->get_result()
        ->fetch_assoc();

    $stmtProductos->close();

    if ((int)$productos["total"] > 0) {

        throw new Exception(
            "No se puede eliminar la categoría porque tiene " .
            (int)$productos["total"] .
            " producto(s) asociado(s). Puede ocultarla en su lugar."
        );
    }

    /* Eliminar categoría */

    $stmtEliminar = $conexion->prepare(
        "DELETE FROM categorias
         WHERE id_categoria = ?"
    );

    if (!$stmtEliminar) {
        throw new Exception($conexion->error);
    }

    $stmtEliminar->bind_param(
        "i",
        $idCategoria
    );

    if (!$stmtEliminar->execute()) {
        throw new Exception($stmtEliminar->error);
    }

    $stmtEliminar->close();

    $conexion->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Categoría eliminada correctamente."
    ]);

} catch (Throwable $error) {

    if ($conexion instanceof mysqli) {
        $conexion->rollback();
    }

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