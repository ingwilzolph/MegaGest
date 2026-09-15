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

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            COUNT(*) AS totalPedidos,

            SUM(
                estado = 'pendiente_pago'
            ) AS pedidosPendientes,

            SUM(
                estado_pago = 'aprobado'
            ) AS pedidosPagados,

            SUM(
                estado = 'preparando'
            ) AS pedidosPreparando,

            SUM(
                estado = 'listo_retiro'
            ) AS pedidosListos,

            SUM(
                estado = 'entregado'
            ) AS pedidosEntregados,

            SUM(
                estado = 'cancelado'
            ) AS pedidosCancelados,

            COALESCE(
                SUM(
                    CASE
                        WHEN
                            estado_pago = 'aprobado'
                            AND DATE(fecha_pedido) = CURDATE()
                        THEN total
                        ELSE 0
                    END
                ),
                0
            ) AS ventasHoy,

            COALESCE(
                SUM(
                    CASE
                        WHEN
                            estado_pago = 'aprobado'
                            AND YEAR(fecha_pedido) = YEAR(CURDATE())
                            AND MONTH(fecha_pedido) = MONTH(CURDATE())
                        THEN total
                        ELSE 0
                    END
                ),
                0
            ) AS ventasMes

        FROM pedidos 
        WHERE estado <> 'cotizacion'
    ";

    $resultado = $conexion->query($sql);

    $datos = $resultado->fetch_assoc();

    /*
     * SUM puede devolver NULL cuando no existen pedidos.
     */

    foreach ($datos as $campo => $valor) {
        $datos[$campo] = intval($valor ?? 0);
    }

    echo json_encode([
        "ok" => true,
        "datos" => $datos
    ]);

} catch (Throwable $error) {

    error_log(
        "Error resumen pedidos: " .
        $error->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" =>
            "No fue posible cargar el resumen de pedidos."
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}
?>