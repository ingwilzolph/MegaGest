<?php

/*
 * Registra la comisión de una venta aprobada.
 * Debe ejecutarse dentro de la misma transacción que aprueba el pedido.
 * No realiza commit ni rollback.
 */
function registrarComisionVenta(
    mysqli $conexion,
    int $idPedido
): array {

    if ($idPedido <= 0) {
        throw new RuntimeException("El pedido de la comisión no es válido.");
    }

    $stmtPedido = $conexion->prepare(
        "SELECT
            p.id_pedido,
            p.numero_pedido,
            p.id_usuario,
            p.canal,
            p.subtotal_productos,
            p.estado_pago,
            u.rol,
            u.estado AS estado_usuario,
            cr.porcentaje_ventas,
            cr.estado AS estado_configuracion
         FROM pedidos p
         LEFT JOIN login_admin u
            ON u.id_usuario = p.id_usuario
         LEFT JOIN configuracion_remuneraciones cr
            ON cr.id_usuario = p.id_usuario
         WHERE p.id_pedido = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtPedido->bind_param("i", $idPedido);
    $stmtPedido->execute();
    $pedido = $stmtPedido->get_result()->fetch_assoc();
    $stmtPedido->close();

    if (!$pedido) {
        throw new RuntimeException("El pedido no existe.");
    }

    if ($pedido["estado_pago"] !== "aprobado") {
        return resultadoComisionVenta(false, "pago_no_aprobado");
    }

    $idUsuario = intval($pedido["id_usuario"] ?? 0);

    /* Una compra online sin vendedor asignado no genera comisión. */
    if ($idUsuario <= 0) {
        return resultadoComisionVenta(false, "sin_responsable_comercial");
    }

    if (
        strtolower(trim((string) ($pedido["estado_usuario"] ?? ""))) !==
        "activo"
    ) {
        return resultadoComisionVenta(false, "usuario_inactivo");
    }

    if (
        strtolower(trim((string) ($pedido["estado_configuracion"] ?? ""))) !==
        "activo"
    ) {
        return resultadoComisionVenta(false, "configuracion_inactiva");
    }

    $porcentaje = round(
        (float) ($pedido["porcentaje_ventas"] ?? 0),
        2
    );

    if ($porcentaje <= 0) {
        return resultadoComisionVenta(false, "porcentaje_cero");
    }

    if ($porcentaje > 100) {
        throw new RuntimeException(
            "El porcentaje de comisión configurado no es válido."
        );
    }

    /* El despacho no forma parte de la base de comisión del vendedor. */
    $baseCalculo = intval($pedido["subtotal_productos"] ?? 0);

    if ($baseCalculo <= 0) {
        return resultadoComisionVenta(false, "base_sin_monto");
    }

    $montoComision = (int) round(
        $baseCalculo * $porcentaje / 100
    );

    if ($montoComision <= 0) {
        return resultadoComisionVenta(false, "monto_cero");
    }

    $canal = strtolower(trim((string) $pedido["canal"]));

    if ($canal === "presencial") {
        $origen = "venta_presencial";
    } elseif ($canal === "online") {
        $origen = "venta_online";
    } else {
        throw new RuntimeException(
            "El canal del pedido no permite registrar esta comisión."
        );
    }

    $claveOperacion = "COM-PEDIDO-VENTA-" . $idPedido;

    /* La clave única evita pagar dos veces aunque el endpoint se repita. */
    $stmtExiste = $conexion->prepare(
        "SELECT id_comision, estado, monto_comision
         FROM comisiones_usuarios
         WHERE clave_operacion = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtExiste->bind_param("s", $claveOperacion);
    $stmtExiste->execute();
    $existente = $stmtExiste->get_result()->fetch_assoc();
    $stmtExiste->close();

    if ($existente) {
        return [
            "generada" => false,
            "motivo" => "ya_registrada",
            "id_comision" => intval($existente["id_comision"]),
            "monto_comision" => intval($existente["monto_comision"]),
            "estado" => $existente["estado"]
        ];
    }

    $observaciones =
        "Comisión por venta " . $pedido["numero_pedido"];

    $stmtInsertar = $conexion->prepare(
        "INSERT INTO comisiones_usuarios (
            id_usuario,
            tipo_comision,
            origen,
            id_pedido,
            id_ot,
            id_servicio_ot,
            id_mano_obra,
            base_calculo,
            porcentaje_aplicado,
            monto_comision,
            estado,
            clave_operacion,
            observaciones
         ) VALUES (
            ?,
            'venta',
            ?,
            ?,
            NULL,
            NULL,
            NULL,
            ?,
            ?,
            ?,
            'pendiente',
            ?,
            ?
         )"
    );

    $stmtInsertar->bind_param(
        "isiidiss",
        $idUsuario,
        $origen,
        $idPedido,
        $baseCalculo,
        $porcentaje,
        $montoComision,
        $claveOperacion,
        $observaciones
    );

    $stmtInsertar->execute();
    $idComision = intval($conexion->insert_id);
    $stmtInsertar->close();

    return [
        "generada" => true,
        "motivo" => "registrada",
        "id_comision" => $idComision,
        "id_usuario" => $idUsuario,
        "rol" => strtolower(trim((string) $pedido["rol"])),
        "base_calculo" => $baseCalculo,
        "porcentaje_aplicado" => $porcentaje,
        "monto_comision" => $montoComision,
        "origen" => $origen,
        "estado" => "pendiente"
    ];
}

function resultadoComisionVenta(
    bool $generada,
    string $motivo
): array {
    return [
        "generada" => $generada,
        "motivo" => $motivo
    ];
}

?>
