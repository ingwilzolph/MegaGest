<?php

header("Content-Type: application/json; charset=utf-8");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

header("Pragma: no-cache");
header("Expires: 0");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

try {

    $sql = "
        SELECT
            COUNT(*) AS total_categorias,

            COALESCE(
                SUM(c.visible_tienda = 1),
                0
            ) AS categorias_visibles,

            COALESCE(
                SUM(c.visible_tienda = 0),
                0
            ) AS categorias_ocultas,

            COALESCE(
                SUM(
                    (
                        SELECT COUNT(*)
                        FROM productos p
                        WHERE LOWER(TRIM(p.categoria)) =
                              LOWER(TRIM(c.categoria))
                    ) = 0
                ),
                0
            ) AS categorias_vacias

        FROM categorias c
    ";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception($conexion->error);
    }

    $resumen = $resultado->fetch_assoc();

    $resultadoProductos = $conexion->query(
        "SELECT COUNT(*) AS total
         FROM productos
         WHERE categoria IS NOT NULL
           AND TRIM(categoria) <> ''"
    );

    if (!$resultadoProductos) {
        throw new Exception($conexion->error);
    }

    $productos =
        $resultadoProductos->fetch_assoc();

    echo json_encode([
        "ok" => true,
        "datos" => [
            "totalCategorias" =>
                (int)$resumen["total_categorias"],

            "categoriasVisibles" =>
                (int)$resumen["categorias_visibles"],

            "categoriasOcultas" =>
                (int)$resumen["categorias_ocultas"],

            "categoriasVacias" =>
                (int)$resumen["categorias_vacias"],

            "productosClasificados" =>
                (int)$productos["total"]
        ]
    ]);

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    $conexion->close();
}
?>