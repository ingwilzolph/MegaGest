<?php

/*
 * Esta función debe ejecutarse dentro de la transacción
 * del proceso de cancelación, después de validar permisos.
 *
 * No realiza commit ni rollback: eso corresponde al llamador.
 */

function reintegrarStockPedido(
    mysqli $conexion,
    int $idPedido,
    int $idUsuario,
    bool $productosRecibidos
): void {

    $stmt = $conexion->prepare(
        "SELECT estado, control_stock
         FROM pedidos
         WHERE id_pedido = ?
         FOR UPDATE"
    );

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();

    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pedido) {
        throw new RuntimeException("El pedido no existe.");
    }

    $controlStock = $pedido["control_stock"];

    /*
     * Nunca sumar dos veces ni reponer lo que no se descontó.
     */
    if (
        $controlStock === "reintegrado" ||
        $controlStock === "no_descontado"
    ) {
        return;
    }

    if ($controlStock !== "descontado") {
        throw new RuntimeException(
            "Este pedido antiguo requiere revisar su stock antes de cancelarlo. No se modificaron las existencias."
        );
    }

    $estadosEnTienda = [
        "pagado",
        "preparando",
        "listo_retiro"
    ];

    $estadosFueraTienda = [
        "enviado",
        "entregado"
    ];

    if (
        !in_array($pedido["estado"], $estadosEnTienda, true) &&
        !in_array($pedido["estado"], $estadosFueraTienda, true)
    ) {
        throw new RuntimeException(
            "El estado del pedido no permite esta reposición."
        );
    }

    if (
        in_array($pedido["estado"], $estadosFueraTienda, true) &&
        !$productosRecibidos
    ) {
        throw new RuntimeException(
            "Debe confirmar la recepción de todos los productos y que están aptos para volver a venderse."
        );
    }

    if ($idUsuario <= 0) {
        throw new RuntimeException(
            "No se pudo identificar al responsable de la reposición."
        );
    }

    /*
     * Leer todas las líneas. Un producto eliminado no se omite:
     * en ese caso se requiere revisión.
     */
    $stmt = $conexion->prepare(
        "SELECT id_producto, cantidad
         FROM detalle_pedido
         WHERE id_pedido = ?
         ORDER BY id_detalle
         FOR UPDATE"
    );

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $cantidades = [];

    while ($detalle = $resultado->fetch_assoc()) {

        $idProducto = (int) $detalle["id_producto"];
        $cantidad = (int) $detalle["cantidad"];

        if ($idProducto <= 0 || $cantidad <= 0) {
            throw new RuntimeException(
                "El pedido contiene una línea que requiere revisión de inventario."
            );
        }

        $acumulado = $cantidades[$idProducto] ?? 0;

        if ($cantidad > 2147483647 - $acumulado) {
            throw new RuntimeException(
                "La cantidad que se intenta reponer no es válida."
            );
        }

        $cantidades[$idProducto] = $acumulado + $cantidad;
    }

    $stmt->close();

    if (!$cantidades) {
        throw new RuntimeException(
            "No se encontraron productos para reponer."
        );
    }

    /*
     * Orden estable de actualización para reducir bloqueos
     * cruzados entre operaciones.
     */
    ksort($cantidades, SORT_NUMERIC);

    $stmt = $conexion->prepare(
        "UPDATE productos
         SET cantidad = cantidad + ?
         WHERE id_producto = ?
           AND cantidad >= 0
           AND cantidad <= 2147483647 - ?"
    );

    foreach ($cantidades as $idProducto => $cantidad) {

        $stmt->bind_param(
            "iii",
            $cantidad,
            $idProducto,
            $cantidad
        );

        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            throw new RuntimeException(
                "No fue posible reponer uno de los productos. La operación debe revertirse."
            );
        }
    }

    $stmt->close();

    /*
     * La marca y las cantidades se guardan en la misma
     * transacción: ambas se confirman o ambas se revierten.
     */
    $stmt = $conexion->prepare(
        "UPDATE pedidos
         SET control_stock = 'reintegrado',
             fecha_reintegro_stock = NOW(),
             id_usuario_reintegro_stock = ?
         WHERE id_pedido = ?
           AND control_stock = 'descontado'"
    );

    $stmt->bind_param("ii", $idUsuario, $idPedido);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException(
            "No fue posible registrar el control de reposición."
        );
    }

    $stmt->close();
}

?>