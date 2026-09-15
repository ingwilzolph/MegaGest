<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new Exception("Método no permitido.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            COUNT(*) AS totalProductos,
            COALESCE(SUM(cantidad), 0) AS unidadesDisponibles,
            COALESCE(SUM(
                visible_tienda = 1
                AND cantidad > 0
                AND cantidad <= stock_minimo
            ), 0) AS productosBajoStock,
            COALESCE(SUM(
                visible_tienda = 1
                AND cantidad = 0
            ), 0) AS productosAgotados,
            COALESCE(SUM(
                visible_tienda = 1
                AND precio_oferta IS NOT NULL
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
            ), 0) AS productosEnOferta
        FROM productos
    ";

    $datos = $conexion->query($sql)->fetch_assoc();

    foreach ($datos as $campo => $valor) {
        $datos[$campo] = intval($valor ?? 0);
    }

    echo json_encode([
        "ok" => true,
        "datos" => $datos
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    error_log(
        "Error obteniendo resumen de productos: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar el resumen de productos.";
    } else {
        if (http_response_code() < 400) {
            http_response_code(400);
        }
        $mensaje = $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>
