<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {

    /* =====================================================
       PERMISOS
    ===================================================== */

    $rol = strtolower(
        trim((string) ($_SESSION["rol"] ?? ""))
    );

    $rolesPermitidos = [
        "administrador",
        "vendedor",
        "cajero"
    ];

    if (
        !in_array(
            $rol,
            $rolesPermitidos,
            true
        )
    ) {
        http_response_code(403);

        throw new RuntimeException(
            "No tiene permiso para consultar cotizaciones.",
            403
        );
    }

    /* =====================================================
       CONEXIÓN
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /* =====================================================
       CONSULTAR COTIZACIONES
    ===================================================== */

    $sql = "
        SELECT
            pe.id_pedido,
            pe.numero_cotizacion,
            pe.fecha_cotizacion,
            pe.fecha_expiracion_cotizacion,
            pe.neto,
            pe.iva,
            pe.subtotal_productos,
            pe.total,
            pe.observaciones,
            pe.ajustes_aceptados,
            pe.id_usuario,
            pe.id_usuario_confirmacion,

            cl.id_cliente,
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

            CONCAT(
                us.nombre,
                ' ',
                us.apellido
            ) AS usuario_creacion,

            CASE
                WHEN pe.fecha_expiracion_cotizacion < NOW()
                    THEN 'vencida'
                ELSE 'vigente'
            END AS vigencia,

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
            ) AS cantidad_lineas

        FROM pedidos pe

        INNER JOIN clientes cl
            ON cl.id_cliente = pe.id_cliente

        LEFT JOIN login_admin us
            ON us.id_usuario = pe.id_usuario

        WHERE pe.estado = 'cotizacion'

        ORDER BY
            pe.fecha_cotizacion DESC,
            pe.id_pedido DESC
    ";

    $resultado = $conexion->query($sql);

    $cotizaciones = [];

    while ($fila = $resultado->fetch_assoc()) {

        $fila["id_pedido"] =
            intval($fila["id_pedido"]);

        $fila["id_cliente"] =
            intval($fila["id_cliente"]);

        $fila["id_usuario"] =
            $fila["id_usuario"] !== null
                ? intval($fila["id_usuario"])
                : null;

        $fila["id_usuario_confirmacion"] =
            $fila["id_usuario_confirmacion"] !== null
                ? intval(
                    $fila["id_usuario_confirmacion"]
                )
                : null;

        $fila["neto"] =
            intval($fila["neto"]);

        $fila["iva"] =
            intval($fila["iva"]);

        $fila["subtotal_productos"] =
            intval(
                $fila["subtotal_productos"]
            );

        $fila["total"] =
            intval($fila["total"]);

        $fila["ajustes_aceptados"] =
            intval(
                $fila["ajustes_aceptados"]
            );

        $fila["cantidad_productos"] =
            intval(
                $fila["cantidad_productos"]
            );

        $fila["cantidad_lineas"] =
            intval(
                $fila["cantidad_lineas"]
            );

        $cotizaciones[] = $fila;
    }

    echo json_encode(
        [
            "ok" => true,
            "datos" => $cotizaciones
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $error) {

    error_log(
        "Error obteniendo cotizaciones: " .
        $error->getMessage()
    );

    $esErrorControlado =
        $error instanceof RuntimeException &&
        $error->getCode() === 403;

    if (!$esErrorControlado) {
        http_response_code(500);
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $esErrorControlado
                ? $error->getMessage()
                : "No fue posible cargar las cotizaciones."
        ],
        JSON_UNESCAPED_UNICODE
    );

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>