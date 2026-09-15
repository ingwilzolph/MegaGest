<?php

function guardarDocumentoImpresion(
    mysqli $conexion,
    int $idPedido,
    string $tipo
): void {

    if (
        $idPedido <= 0 ||
        !in_array($tipo, ["cotizacion", "venta"], true)
    ) {
        throw new RuntimeException(
            "Los datos del documento de impresión no son válidos."
        );
    }

    /*
     * Debe ejecutarse dentro de la misma transacción
     * que guarda la cotización o confirma la venta.
     */

    $stmt = $conexion->prepare("
        SELECT
            pe.id_pedido,
            pe.numero_pedido,
            pe.numero_cotizacion,
            pe.fecha_cotizacion,
            pe.fecha_expiracion_cotizacion,
            pe.fecha_pedido,
            pe.canal,
            pe.tipo_entrega,
            pe.region,
            pe.comuna,
            pe.direccion,
            pe.referencia_direccion,
            pe.neto,
            pe.iva,
            pe.subtotal_productos,
            pe.costo_despacho,
            pe.total,
            pe.estado,
            pe.estado_pago,
            pe.observaciones,

            cl.rut,
            cl.nombre,
            cl.apellido,
            cl.correo,
            cl.telefono

        FROM pedidos pe

        INNER JOIN clientes cl
            ON cl.id_cliente = pe.id_cliente

        WHERE pe.id_pedido = ?

        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();

    $pedido = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$pedido) {
        throw new RuntimeException(
            "No se encontró el pedido para generar su documento."
        );
    }

    /*
     * Si ya existe, conservamos la primera versión emitida.
     * Nunca actualizamos la copia histórica.
     */

    $stmt = $conexion->prepare("
        SELECT id_documento
        FROM documentos_impresion
        WHERE id_pedido = ?
          AND tipo = ?
        LIMIT 1
    ");

    $stmt->bind_param("is", $idPedido, $tipo);
    $stmt->execute();

    $existente = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($existente) {
        return;
    }

    if (
        $tipo === "cotizacion" &&
        (
            $pedido["estado"] !== "cotizacion" ||
            empty($pedido["numero_cotizacion"])
        )
    ) {
        throw new RuntimeException(
            "No se puede reconstruir una cotización original " .
            "desde un pedido que ya fue convertido."
        );
    }

    if (
        $tipo === "venta" &&
        (
            $pedido["estado"] === "cotizacion" ||
            $pedido["estado_pago"] !== "aprobado"
        )
    ) {
        throw new RuntimeException(
            "No se puede emitir un comprobante de venta sin pago aprobado."
        );
    }

    /* PRODUCTOS CON SUS DATOS GUARDADOS */

    $stmt = $conexion->prepare("
        SELECT
            id_producto,
            sku,
            nombre_producto,
            marca_producto,
            precio_unitario,
            cantidad,
            total_linea
        FROM detalle_pedido
        WHERE id_pedido = ?
        ORDER BY id_detalle ASC
    ");

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $productos = [];

    while ($producto = $resultado->fetch_assoc()) {

        $producto["id_producto"] =
            $producto["id_producto"] !== null
                ? (int) $producto["id_producto"]
                : null;

        $producto["precio_unitario"] =
            (int) $producto["precio_unitario"];

        $producto["cantidad"] =
            (int) $producto["cantidad"];

        $producto["total_linea"] =
            (int) $producto["total_linea"];

        $productos[] = $producto;
    }

    $stmt->close();

    if (!$productos) {
        throw new RuntimeException(
            "El documento no contiene productos."
        );
    }

    /* PAGO: SOLO PARA EL COMPROBANTE DE VENTA */

    $pago = null;

    if ($tipo === "venta") {

        $stmt = $conexion->prepare("
            SELECT
                id_pago,
                proveedor,
                monto,
                authorization_code,
                transaction_date
            FROM pagos
            WHERE id_pedido = ?
              AND estado = 'aprobado'
            ORDER BY id_pago DESC
            LIMIT 1
        ");

        $stmt->bind_param("i", $idPedido);
        $stmt->execute();

        $pago = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if (!$pago) {
            throw new RuntimeException(
                "No se encontró el pago aprobado de la venta."
            );
        }

        $pago["id_pago"] = (int) $pago["id_pago"];
        $pago["monto"] = (int) $pago["monto"];

        // Corresponde al flujo actual de un pago por venta.
        if ($pago["monto"] !== (int) $pedido["total"]) {
            throw new RuntimeException(
                "El pago no coincide con el total de la venta."
            );
        }
    }

    $numero = $tipo === "cotizacion"
        ? $pedido["numero_cotizacion"]
        : $pedido["numero_pedido"];

    $documento = [
        "version" => 1,
        "tipo" => $tipo,
        "numero" => $numero,
        "id_pedido" => $idPedido,
        "empresa" => "AlianzaPro SPA",

        "fecha_documento" => $tipo === "cotizacion"
            ? $pedido["fecha_cotizacion"]
            : $pedido["fecha_pedido"],

        "fecha_expiracion" => $tipo === "cotizacion"
            ? $pedido["fecha_expiracion_cotizacion"]
            : null,

        "cliente" => [
            "rut" => $pedido["rut"],
            "nombre" => $pedido["nombre"],
            "apellido" => $pedido["apellido"],
            "correo" => $pedido["correo"],
            "telefono" => $pedido["telefono"]
        ],

        "entrega" => [
            "tipo" => $pedido["tipo_entrega"],
            "region" => $pedido["region"],
            "comuna" => $pedido["comuna"],
            "direccion" => $pedido["direccion"],
            "referencia" => $pedido["referencia_direccion"]
        ],

        "productos" => $productos,

        "totales" => [
            "neto" => (int) $pedido["neto"],
            "iva" => (int) $pedido["iva"],
            "subtotal_productos" =>
                (int) $pedido["subtotal_productos"],
            "costo_despacho" => (int) $pedido["costo_despacho"],
            "total" => (int) $pedido["total"]
        ],

        "observaciones" => $pedido["observaciones"],
        "pago" => $pago
    ];

    $contenido = json_encode(
        $documento,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

    $stmt = $conexion->prepare("
        INSERT INTO documentos_impresion (
            id_pedido,
            tipo,
            numero_documento,
            contenido
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isss",
        $idPedido,
        $tipo,
        $numero,
        $contenido
    );

    $stmt->execute();
    $stmt->close();
}

?>