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
            pe.id_pedido,
            pe.numero_pedido,
            pe.id_cliente,
            pe.id_usuario,
            pe.canal,
            pe.tipo_entrega,
            pe.neto,
            pe.iva,
            pe.subtotal_productos,
            pe.costo_despacho,
            pe.total,
            pe.estado,
            pe.estado_pago,
            pe.observaciones,
            pe.fecha_pedido,
            pe.fecha_actualizacion,

            cl.rut,
            cl.nombre AS nombre_cliente,
            cl.apellido AS apellido_cliente,
            cl.correo AS correo_cliente,
            cl.telefono AS telefono_cliente,

            CONCAT(
                cl.nombre,
                ' ',
                cl.apellido
            ) AS cliente,

            CASE
                WHEN pe.id_usuario IS NULL
                    THEN 'Venta en línea'
                ELSE CONCAT(
                    us.nombre,
                    ' ',
                    us.apellido
                )
            END AS usuario_venta,

            CASE
                WHEN pe.canal = 'online'
                    THEN 'En línea'
                WHEN pe.canal = 'presencial'
                    THEN 'Presencial'
                ELSE pe.canal
            END AS canal_visual,

            CASE
                WHEN pe.tipo_entrega = 'retiro'
                    THEN 'Retiro en tienda'
                WHEN pe.tipo_entrega = 'despacho'
                    THEN 'Despacho'
                ELSE pe.tipo_entrega
            END AS entrega_visual,

            CASE pe.estado
                WHEN 'pendiente_pago'
                    THEN 'Pendiente de pago'
                WHEN 'pagado'
                    THEN 'Pagado'
                WHEN 'preparando'
                    THEN 'Preparando'
                WHEN 'listo_retiro'
                    THEN 'Listo para retirar'
                WHEN 'enviado'
                    THEN 'Enviado'
                WHEN 'entregado'
                    THEN 'Entregado'
                WHEN 'cancelado'
                    THEN 'Cancelado'
                ELSE pe.estado
            END AS estado_visual,

            CASE pe.estado_pago
                WHEN 'pendiente'
                    THEN 'Pendiente'
                WHEN 'aprobado'
                    THEN 'Aprobado'
                WHEN 'rechazado'
                    THEN 'Rechazado'
                WHEN 'anulado'
                    THEN 'Anulado'
                WHEN 'reembolsado'
                    THEN 'Reembolsado'
                ELSE pe.estado_pago
            END AS estado_pago_visual,

            (
                SELECT COALESCE(
                    SUM(dp.cantidad),
                    0
                )
                FROM detalle_pedido dp
                WHERE dp.id_pedido = pe.id_pedido
            ) AS cantidad_productos,

            (
                SELECT COUNT(*)
                FROM detalle_pedido dp
                WHERE dp.id_pedido = pe.id_pedido
            ) AS cantidad_lineas,

            (
                SELECT pa.proveedor
                FROM pagos pa
                WHERE pa.id_pedido = pe.id_pedido
                ORDER BY pa.id_pago DESC
                LIMIT 1
            ) AS proveedor_pago,

            (
                SELECT pa.authorization_code
                FROM pagos pa
                WHERE pa.id_pedido = pe.id_pedido
                AND pa.estado = 'aprobado'
                ORDER BY pa.id_pago DESC
                LIMIT 1
            ) AS codigo_autorizacion

        FROM pedidos pe

        INNER JOIN clientes cl
            ON cl.id_cliente = pe.id_cliente

        LEFT JOIN login_admin us
            ON us.id_usuario = pe.id_usuario

            WHERE pe.estado <> 'cotizacion'

            ORDER BY pe.fecha_pedido DESC, pe.id_pedido DESC
    ";

    $resultado = $conexion->query($sql);

    $pedidos = [];

    while ($fila = $resultado->fetch_assoc()) {

        $fila["id_pedido"] =
            intval($fila["id_pedido"]);

        $fila["id_cliente"] =
            intval($fila["id_cliente"]);

        $fila["id_usuario"] =
            $fila["id_usuario"] !== null
                ? intval($fila["id_usuario"])
                : null;

        $fila["neto"] =
            intval($fila["neto"]);

        $fila["iva"] =
            intval($fila["iva"]);

        $fila["subtotal_productos"] =
            intval($fila["subtotal_productos"]);

        $fila["costo_despacho"] =
            intval($fila["costo_despacho"]);

        $fila["total"] =
            intval($fila["total"]);

        $fila["cantidad_productos"] =
            intval($fila["cantidad_productos"]);

        $fila["cantidad_lineas"] =
            intval($fila["cantidad_lineas"]);

        $pedidos[] = $fila;
    }

    echo json_encode([
        "ok" => true,
        "datos" => $pedidos
    ]);

} catch (Throwable $error) {

    error_log(
        "Error cargando pedidos: " .
        $error->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" =>
            "No fue posible cargar los pedidos."
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>