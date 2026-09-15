<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            id_producto,
            categoria,
            nombre,
            descripcion,
            marca,
            cantidad,
            compra,
            precio,
            sku,
            stock_minimo,
            visible_tienda,
            destacado,
            precio_oferta,
            inicio_oferta,
            fin_oferta,
            ubicacion,
            garantia,
            compatibilidad,
            fecha_actualizacion,

            CASE
                WHEN
                    precio_oferta > 0
                    AND precio_oferta < precio
                    AND (
                        inicio_oferta IS NULL
                        OR inicio_oferta <= NOW()
                    )
                    AND (
                        fin_oferta IS NULL
                        OR fin_oferta >= NOW()
                    )
                THEN 1
                ELSE 0
            END AS en_oferta

        FROM productos
        ORDER BY nombre ASC
    ";

    $resultado = $conexion->query($sql);

    $productos = [];

    while ($producto = $resultado->fetch_assoc()) {

        $cantidad = (int) $producto["cantidad"];
        $stockMinimo = (int) $producto["stock_minimo"];
        $visible = (int) $producto["visible_tienda"];

        if ($visible === 0) {
            $estado = "Oculto";
        } elseif ($cantidad <= 0) {
            $estado = "Agotado";
        } elseif ($cantidad <= $stockMinimo) {
            $estado = "Bajo stock";
        } else {
            $estado = "Disponible";
        }

        $producto["id_producto"] = (int) $producto["id_producto"];
        $producto["cantidad"] = $cantidad;
        $producto["stock_minimo"] = $stockMinimo;
        $producto["visible_tienda"] = $visible;
        $producto["destacado"] = (int) $producto["destacado"];

        $producto["compra"] = (int) $producto["compra"];
        $producto["precio"] = (int) $producto["precio"];

        $producto["precio_oferta"] =
            $producto["precio_oferta"] !== null
                ? (int) $producto["precio_oferta"]
                : null;

        $producto["en_oferta"] = (int) $producto["en_oferta"];

        $producto["precio_efectivo"] =
            $producto["en_oferta"] === 1
                ? $producto["precio_oferta"]
                : $producto["precio"];

        $producto["estado"] = $estado;

        $productos[] = $producto;
    }

    echo json_encode([
        "ok" => true,
        "datos" => $productos
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {

    error_log(
        "Error cargando productos administrativos: " .
        $error->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No fue posible cargar los productos."
    ], JSON_UNESCAPED_UNICODE);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>