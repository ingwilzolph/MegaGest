<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;

try {

    /* =====================================================
       VALIDAR MÉTODO
    ===================================================== */

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    /* =====================================================
       RECIBIR ID
    ===================================================== */

    $idProducto = filter_input(
        INPUT_GET,
        "id",
        FILTER_VALIDATE_INT
    );

    if (!$idProducto || $idProducto <= 0) {

        throw new Exception(
            "El ID del producto no es válido."
        );
    }

    /* =====================================================
       CONSULTAR PRODUCTO
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            id_producto,
            sku,
            categoria,
            nombre,
            descripcion,
            marca,
            cantidad,
            stock_minimo,
            visible_tienda,
            destacado,
            compra,
            precio,
            precio_oferta,
            inicio_oferta,
            fin_oferta,
            ubicacion,
            garantia,
            compatibilidad,
            fecha_actualizacion
        FROM productos
        WHERE id_producto = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "i",
        $idProducto
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {

        throw new Exception(
            "El producto no fue encontrado."
        );
    }

    /*
     * Devolvemos un solo objeto porque JavaScript utiliza:
     *
     * const producto = resultado.datos;
     */

    $producto = $resultado->fetch_assoc();

    /* =====================================================
       CONVERTIR DATOS NUMÉRICOS
    ===================================================== */

    $producto["id_producto"] = intval(
        $producto["id_producto"]
    );

    $producto["cantidad"] = intval(
        $producto["cantidad"]
    );

    $producto["stock_minimo"] = intval(
        $producto["stock_minimo"]
    );

    $producto["visible_tienda"] = intval(
        $producto["visible_tienda"]
    );

    $producto["destacado"] = intval(
        $producto["destacado"]
    );

    $producto["compra"] = intval(
        $producto["compra"]
    );

    $producto["precio"] = intval(
        $producto["precio"]
    );

    $producto["precio_oferta"] =
        $producto["precio_oferta"] !== null
            ? intval(
                $producto["precio_oferta"]
            )
            : null;

    echo json_encode([
        "ok" => true,
        "datos" => $producto
    ]);

    $stmt->close();

} catch (Throwable $error) {

    error_log(
        "Error obteniendo producto: " .
        $error->getMessage()
    );

    $mensaje =
        $error instanceof mysqli_sql_exception
            ? "No fue posible cargar el producto."
            : $error->getMessage();

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}