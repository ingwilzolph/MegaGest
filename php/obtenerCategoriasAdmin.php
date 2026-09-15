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
            c.id_categoria,
            c.categoria,
            c.descripcion,
            c.slug,
            c.visible_tienda,
            c.orden,
            c.fecha_actualizacion,

            COUNT(p.id_producto) AS total_productos,

            COALESCE(
                SUM(p.cantidad),
                0
            ) AS unidades_productos

        FROM categorias c

        LEFT JOIN productos p
            ON LOWER(TRIM(p.categoria)) =
               LOWER(TRIM(c.categoria))

        GROUP BY
            c.id_categoria,
            c.categoria,
            c.descripcion,
            c.slug,
            c.visible_tienda,
            c.orden,
            c.fecha_actualizacion

        ORDER BY
            c.orden ASC,
            c.categoria ASC
    ";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception($conexion->error);
    }

    $categorias = [];

    while ($categoria = $resultado->fetch_assoc()) {

        $categoria["id_categoria"] =
            (int)$categoria["id_categoria"];

        $categoria["visible_tienda"] =
            (int)$categoria["visible_tienda"];

        $categoria["orden"] =
            (int)$categoria["orden"];

        $categoria["total_productos"] =
            (int)$categoria["total_productos"];

        $categoria["unidades_productos"] =
            (int)$categoria["unidades_productos"];

        if ($categoria["visible_tienda"] === 0) {

            $categoria["estado_visual"] = "Oculta";

        } elseif ($categoria["total_productos"] === 0) {

            $categoria["estado_visual"] = "Vacía";

        } else {

            $categoria["estado_visual"] = "Visible";
        }

        $categorias[] = $categoria;
    }

    echo json_encode([
        "ok" => true,
        "datos" => $categorias
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