<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "conexion.php";

$conexion = null;

try {

    $conexion = conexion();

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
            destacado,
            garantia,
            compatibilidad,

            precio AS precio_normal,

            CASE
                WHEN precio_oferta IS NOT NULL
                AND precio_oferta > 0
                AND precio_oferta < precio
                AND (
                    inicio_oferta IS NULL
                    OR inicio_oferta <= NOW()
                )
                AND (
                    fin_oferta IS NULL
                    OR fin_oferta >= NOW()
                )
                THEN precio_oferta
                ELSE precio
            END AS precio,

            CASE
                WHEN precio_oferta IS NOT NULL
                AND precio_oferta > 0
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

        WHERE visible_tienda = 1

        ORDER BY
            destacado DESC,
            nombre ASC
    ";

    $resultado = $conexion->query($sql);

    if (!$resultado) {

        throw new Exception(
            "No fue posible consultar los productos."
        );
    }

    $datos = [];

    while ($fila = $resultado->fetch_assoc()) {

        $fila["id_producto"] =
            (int) $fila["id_producto"];

        $fila["cantidad"] =
            (int) $fila["cantidad"];

        $fila["stock_minimo"] =
            (int) $fila["stock_minimo"];

        $fila["destacado"] =
            (int) $fila["destacado"];

        $fila["precio_normal"] =
            (int) $fila["precio_normal"];

        $fila["precio"] =
            (int) $fila["precio"];

        $fila["en_oferta"] =
            (int) $fila["en_oferta"];

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

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}