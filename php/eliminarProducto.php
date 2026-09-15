<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

try {

    $conexion = conexion();

    $idProducto = intval($_GET["id"] ?? 0);

    if ($idProducto <= 0) {
        throw new Exception(
            "ID de producto no válido."
        );
    }

    /*
     * Verificar que exista.
     */
    $stmtExiste = $conexion->prepare(
        "SELECT id_producto
         FROM productos
         WHERE id_producto = ?
         LIMIT 1"
    );

    if (!$stmtExiste) {
        throw new Exception(
            $conexion->error
        );
    }

    $stmtExiste->bind_param(
        "i",
        $idProducto
    );

    $stmtExiste->execute();

    if (
        $stmtExiste
            ->get_result()
            ->num_rows === 0
    ) {

        $stmtExiste->close();

        echo json_encode([
            "ok" => false,
            "mensaje" => "El producto no existe."
        ]);

        exit;
    }

    $stmtExiste->close();


    /*
     * IMPORTANTE:
     * Primero intentar eliminar de BD.
     * La imagen se elimina solamente
     * después de un DELETE exitoso.
     */
    try {

        $stmt = $conexion->prepare(
            "DELETE FROM productos
             WHERE id_producto = ?"
        );

        if (!$stmt) {
            throw new Exception(
                $conexion->error
            );
        }

        $stmt->bind_param(
            "i",
            $idProducto
        );

        $stmt->execute();

        $stmt->close();


        /*
         * Producto realmente eliminado.
         * Ahora sí borrar su imagen.
         */
        $imagen =
            dirname(__DIR__) .
            "/images/productos/" .
            $idProducto .
            ".webp";

        if (is_file($imagen)) {
            @unlink($imagen);
        }


        echo json_encode([
            "ok" => true,
            "eliminado" => true,
            "archivado" => false,
            "mensaje" =>
                "Producto eliminado correctamente."
        ]);

        exit;

    } catch (mysqli_sql_exception $e) {

        /*
         * Código 1451:
         * existe una FK que protege registros
         * históricos del producto.
         */
        if ((int)$e->getCode() !== 1451) {
            throw $e;
        }
    }


    /*
     * El producto tiene operaciones asociadas.
     *
     * NO se elimina físicamente.
     * Se conserva para historial y se oculta.
     */

    $tieneEstado = false;

    $resultadoColumna =
        $conexion->query(
            "SHOW COLUMNS
             FROM productos
             LIKE 'estado'"
        );

    if (
        $resultadoColumna &&
        $resultadoColumna->num_rows > 0
    ) {
        $tieneEstado = true;
    }


    if ($tieneEstado) {

        try {

            $stmtArchivar =
                $conexion->prepare(
                    "UPDATE productos
                     SET
                        visible_tienda = 0,
                        estado = 'inactivo'
                     WHERE id_producto = ?"
                );

            $stmtArchivar->bind_param(
                "i",
                $idProducto
            );

            $stmtArchivar->execute();
            $stmtArchivar->close();

        } catch (mysqli_sql_exception $e) {

            /*
             * Por si el campo estado usa otros
             * valores ENUM, al menos ocultamos
             * el producto de la tienda.
             */
            $stmtArchivar =
                $conexion->prepare(
                    "UPDATE productos
                     SET visible_tienda = 0
                     WHERE id_producto = ?"
                );

            $stmtArchivar->bind_param(
                "i",
                $idProducto
            );

            $stmtArchivar->execute();
            $stmtArchivar->close();
        }

    } else {

        $stmtArchivar =
            $conexion->prepare(
                "UPDATE productos
                 SET visible_tienda = 0
                 WHERE id_producto = ?"
            );

        $stmtArchivar->bind_param(
            "i",
            $idProducto
        );

        $stmtArchivar->execute();
        $stmtArchivar->close();
    }


    /*
     * NO borrar la imagen.
     * El historial puede necesitarla
     * y el producto sigue existiendo.
     */
    echo json_encode([
        "ok" => true,
        "eliminado" => false,
        "archivado" => true,
        "mensaje" =>
            "El producto tiene movimientos asociados y no puede eliminarse definitivamente. Se conservó en el historial y se ocultó de la tienda."
    ]);

} catch (Throwable $error) {

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}
