<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {
    /* =====================================================
       VALIDAR SOLICITUD
    ===================================================== */

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        header("Allow: GET");

        throw new RuntimeException(
            "Método no permitido.",
            405
        );
    }

    $numeroRecibido = $_GET["numero"] ?? "";

    if (!is_string($numeroRecibido)) {
        throw new RuntimeException(
            "El número del documento no es válido.",
            400
        );
    }

    $numero = trim($numeroRecibido);

    if ($numero === "" || mb_strlen($numero) > 30) {
        throw new RuntimeException(
            "Ingrese un número de cotización o pedido válido.",
            400
        );
    }

    $idUsuario = (int) ($_SESSION["id_usuario"] ?? 0);

    if ($idUsuario <= 0) {
        throw new RuntimeException(
            "No se pudo identificar al usuario.",
            403
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /* =====================================================
       COMPROBAR ROL ACTUAL
    ===================================================== */

    $stmt = $conexion->prepare("
        SELECT rol, estado
        FROM login_admin
        WHERE id_usuario = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();

    $usuario = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (
        !$usuario ||
        strtolower(trim($usuario["estado"])) !== "activo" ||
        !in_array(
            strtolower(trim($usuario["rol"])),
            ["administrador", "cajero"],
            true
        )
    ) {
        throw new RuntimeException(
            "Solo administrador y cajero pueden recuperar documentos en Ventas.",
            403
        );
    }

    /* =====================================================
       BUSCAR DOCUMENTO
    ===================================================== */

    $stmt = $conexion->prepare("
        SELECT
            pe.id_pedido,
            pe.numero_pedido,
            pe.numero_cotizacion,
            pe.fecha_cotizacion,
            pe.fecha_expiracion_cotizacion,
            CASE
                WHEN pe.estado <> 'cotizacion'
                    THEN 'no_aplica'
                WHEN pe.fecha_expiracion_cotizacion IS NULL
                    THEN 'sin_vencimiento'
                WHEN pe.fecha_expiracion_cotizacion <= NOW()
                    THEN 'vencida'
                ELSE 'vigente'
            END AS vigencia,
            pe.fecha_pedido,
            pe.estado,
            pe.estado_pago,
            pe.canal,
            pe.tipo_entrega,
            pe.neto,
            pe.iva,
            pe.subtotal_productos,
            pe.costo_despacho,
            pe.total,
            pe.observaciones,

            cl.id_cliente,
            cl.rut,
            cl.nombre,
            cl.apellido,
            cl.correo,
            cl.telefono

        FROM pedidos pe

        INNER JOIN clientes cl
            ON cl.id_cliente = pe.id_cliente

        WHERE
            pe.numero_pedido = ?
            OR pe.numero_cotizacion = ?

        LIMIT 2
    ");

    $stmt->bind_param("ss", $numero, $numero);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        throw new RuntimeException(
            "No se encontró una cotización o pedido con ese número.",
            404
        );
    }

    if ($resultado->num_rows > 1) {
        throw new RuntimeException(
            "El número coincide con más de un documento. Revise los registros.",
            409
        );
    }

    $documento = $resultado->fetch_assoc();

    $stmt->close();

    $idPedido = (int) $documento["id_pedido"];

    /* =====================================================
       PRECIOS ORIGINALES Y DATOS ACTUALES
    ===================================================== */

    $stmt = $conexion->prepare("
        SELECT
            dp.id_detalle,
            dp.id_producto,
            dp.sku AS sku_original,
            dp.nombre_producto AS nombre_original,
            dp.marca_producto AS marca_original,
            dp.precio_unitario AS precio_original,
            dp.cantidad,
            dp.total_linea AS total_original,

            p.id_producto AS id_producto_actual,
            p.sku AS sku_actual,
            p.nombre AS nombre_actual,
            p.marca AS marca_actual,
            p.cantidad AS stock_actual,

            CASE
                WHEN p.id_producto IS NULL THEN NULL

                WHEN
                    p.precio_oferta > 0
                    AND p.precio_oferta < p.precio
                    AND (
                        p.inicio_oferta IS NULL
                        OR p.inicio_oferta <= NOW()
                    )
                    AND (
                        p.fin_oferta IS NULL
                        OR p.fin_oferta >= NOW()
                    )
                THEN p.precio_oferta

                ELSE p.precio

            END AS precio_actual

        FROM detalle_pedido dp

        LEFT JOIN productos p
            ON p.id_producto = dp.id_producto

        WHERE dp.id_pedido = ?

        ORDER BY dp.id_detalle ASC
    ");

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();

    $resultadoProductos = $stmt->get_result();

    $productos = [];
    $cantidadesAgrupadas = [];
    $subtotalActual = 0;
    $hayCambiosPrecio = false;
    $hayProblemas = false;

    while ($fila = $resultadoProductos->fetch_assoc()) {

        $idProducto = $fila["id_producto"] !== null
            ? (int) $fila["id_producto"]
            : null;

        $cantidad = (int) $fila["cantidad"];
        $precioOriginal = (int) $fila["precio_original"];

        $existe = $fila["id_producto_actual"] !== null;

        $precioActual = $existe
            ? (int) $fila["precio_actual"]
            : null;

        $stockActual = $existe
            ? (int) $fila["stock_actual"]
            : 0;

        $problemas = [];

        if (!$existe) {
            $problemas[] = "El producto ya no existe.";
        }

        if ($cantidad <= 0 || $cantidad > 1000) {
            $problemas[] = "La cantidad debe revisarse.";
        }

        if ($existe && $precioActual <= 0) {
            $problemas[] = "El producto no tiene un precio actual válido.";
        }

        $totalActual = null;

        if (
            $existe &&
            $precioActual > 0 &&
            $cantidad > 0 &&
            $cantidad <= 1000
        ) {
            if (
                $precioActual >
                intdiv(PHP_INT_MAX - $subtotalActual, $cantidad)
            ) {
                throw new RuntimeException(
                    "Los importes superan el límite permitido.",
                    409
                );
            }

            $totalActual = $precioActual * $cantidad;
            $subtotalActual += $totalActual;
        }

        if ($existe && $cantidad > 0) {
            $cantidadesAgrupadas[$idProducto] =
                ($cantidadesAgrupadas[$idProducto] ?? 0) +
                $cantidad;
        }

        $cambioPrecio =
            $existe &&
            $precioActual !== $precioOriginal;

        $hayCambiosPrecio =
            $hayCambiosPrecio || $cambioPrecio;

        $productos[] = [
            "id_detalle" => (int) $fila["id_detalle"],
            "id_producto" => $idProducto,

            "nombre_original" => $fila["nombre_original"],
            "sku_original" => $fila["sku_original"],
            "marca_original" => $fila["marca_original"],
            "precio_original" => $precioOriginal,
            "total_original" => (int) $fila["total_original"],

            "nombre_actual" => $fila["nombre_actual"],
            "sku_actual" => $fila["sku_actual"],
            "marca_actual" => $fila["marca_actual"],
            "precio_actual" => $precioActual,
            "total_actual" => $totalActual,
            "stock_actual" => $stockActual,

            "cantidad" => $cantidad,
            "existe" => $existe,
            "cambio_precio" => $cambioPrecio,
            "problemas" => $problemas
        ];
    }

    $stmt->close();

    /*
     * Comprobamos cantidades acumuladas por producto,
     * incluso si aparece en varias líneas del documento.
     */

    foreach ($productos as &$producto) {

        if ($producto["existe"]) {

            $cantidadAcumulada =
                $cantidadesAgrupadas[$producto["id_producto"]] ?? 0;

            if ($cantidadAcumulada > $producto["stock_actual"]) {
                $producto["problemas"][] =
                    "Stock insuficiente. Disponibles: " .
                    $producto["stock_actual"] . ".";
            }

            if ($cantidadAcumulada > 1000) {
                $producto["problemas"][] =
                    "La cantidad acumulada supera 1000 unidades.";
            }
        }

        if ($producto["problemas"]) {
            $hayProblemas = true;
        }
    }

    unset($producto);

    if (!$productos) {
        $hayProblemas = true;
    }

    /* =====================================================
       CLASIFICAR EL REGISTRO
    ===================================================== */

    $esCotizacion =
        $documento["estado"] === "cotizacion";

    /*
     * Un pedido pendiente no se presenta como una venta cobrada.
     * Los estados de anulación/reembolso requieren su propio
     * tratamiento al imprimir el comprobante.
     */

    $tipo = $esCotizacion
        ? "cotizacion"
        : (
            $documento["estado_pago"] === "aprobado"
                ? "venta"
                : "pedido"
        );

    $netoActual = (int) round($subtotalActual / 1.19);
    $ivaActual = $subtotalActual - $netoActual;

    echo json_encode([
        "ok" => true,

        "datos" => [
            "tipo" => $tipo,
            "cotizacion_vigente" => $esCotizacion &&  $documento["vigencia"] === "vigente",

            "mensaje_vigencia" => !$esCotizacion ? null : (
                    $documento["vigencia"] === "vigente"
                        ? "Cotización vigente."
                        : (
                            $documento["vigencia"] === "vencida"
                                ? "Cotización vencida. No se puede confirmar la venta."
                                : "Cotización sin vencimiento válido. No se puede confirmar la venta."
                        )
                ),

            "documento" => [
                "id_pedido" => $idPedido,
                "numero_pedido" => $documento["numero_pedido"],
                "numero_cotizacion" => $documento["numero_cotizacion"],
                "fecha_cotizacion" => $documento["fecha_cotizacion"],
                "fecha_expiracion_cotizacion" =>
                    $documento["fecha_expiracion_cotizacion"],
                "fecha_pedido" => $documento["fecha_pedido"],
                "estado" => $documento["estado"],
                "estado_pago" => $documento["estado_pago"],
                "canal" => $documento["canal"],
                "tipo_entrega" => $documento["tipo_entrega"],
                "observaciones" => $documento["observaciones"],
                "vigencia" => $documento["vigencia"],
            ],

            "cliente" => [
                "id_cliente" => (int) $documento["id_cliente"],
                "rut" => $documento["rut"],
                "nombre" => $documento["nombre"],
                "apellido" => $documento["apellido"],
                "correo" => $documento["correo"],
                "telefono" => $documento["telefono"]
            ],

            "totales_guardados" => [
                "neto" => (int) $documento["neto"],
                "iva" => (int) $documento["iva"],
                "subtotal_productos" =>
                    (int) $documento["subtotal_productos"],
                "costo_despacho" =>
                    (int) $documento["costo_despacho"],
                "total" => (int) $documento["total"]
            ],

            "revision_actual" => [
                "neto" => $netoActual,
                "iva" => $ivaActual,
                "subtotal_productos" => $subtotalActual,
                "completa" => !$hayProblemas,
                "hay_cambios_precio" => $hayCambiosPrecio,
                "hay_problemas" => $hayProblemas
            ],

            "productos" => $productos
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Throwable $error) {

    error_log(
        "Error buscando documento de venta: " .
        $error->getMessage()
    );

    $controlado =
        $error instanceof RuntimeException &&
        !($error instanceof mysqli_sql_exception) &&
        in_array(
            $error->getCode(),
            [400, 403, 404, 405, 409],
            true
        );

    http_response_code(
        $controlado ? $error->getCode() : 500
    );

    echo json_encode([
        "ok" => false,
        "mensaje" => $controlado
            ? $error->getMessage()
            : "No fue posible buscar el documento."
    ], JSON_UNESCAPED_UNICODE);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>