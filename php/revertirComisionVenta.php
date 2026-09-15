<?php

/*
 * Revierte la comisión asociada a un reembolso total.
 * Debe ejecutarse dentro de la misma transacción que confirma localmente
 * el reembolso. No realiza commit ni rollback.
 */
function revertirComisionVenta(
    mysqli $conexion,
    int $idPedido,
    int $idReembolso
): array {

    if ($idPedido <= 0 || $idReembolso <= 0) {
        throw new RuntimeException(
            "Los datos para revertir la comisión no son válidos."
        );
    }

    $stmt = $conexion->prepare(
        "SELECT
            id_comision,
            id_usuario,
            monto_comision,
            estado,
            observaciones
         FROM comisiones_usuarios
         WHERE id_pedido = ?
           AND tipo_comision = 'venta'
         ORDER BY id_comision DESC
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $comision = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$comision) {
        return [
            "procesada" => false,
            "accion" => "sin_comision"
        ];
    }

    $idComision = intval($comision["id_comision"]);
    $idUsuario = intval($comision["id_usuario"]);
    $monto = intval($comision["monto_comision"]);
    $estado = strtolower(trim((string) $comision["estado"]));

    if ($estado === "anulada") {
        return [
            "procesada" => false,
            "accion" => "ya_anulada",
            "id_comision" => $idComision
        ];
    }

    if ($estado === "pendiente") {
        anularComisionPorReembolso(
            $conexion,
            $idComision,
            $idReembolso,
            $comision["observaciones"]
        );

        return [
            "procesada" => true,
            "accion" => "comision_anulada",
            "id_comision" => $idComision,
            "monto" => $monto
        ];
    }

    $liquidacion = obtenerLiquidacionDeComision(
        $conexion,
        $idComision
    );

    /*
     * Si la liquidación todavía no fue pagada, la comisión se retira,
     * se recalculan sus totales y la comisión queda anulada.
     */
    if (
        $estado === "liquidada" &&
        $liquidacion &&
        in_array(
            $liquidacion["estado"],
            ["borrador", "cerrada"],
            true
        )
    ) {
        $idLiquidacion = intval($liquidacion["id_liquidacion"]);

        $stmt = $conexion->prepare(
            "DELETE FROM detalle_liquidacion_comisiones
             WHERE id_liquidacion = ? AND id_comision = ?"
        );
        $stmt->bind_param("ii", $idLiquidacion, $idComision);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new RuntimeException(
                "No fue posible retirar la comisión de la liquidación."
            );
        }

        $stmt->close();

        recalcularLiquidacionComisiones(
            $conexion,
            $idLiquidacion
        );

        anularComisionPorReembolso(
            $conexion,
            $idComision,
            $idReembolso,
            $comision["observaciones"]
        );

        return [
            "procesada" => true,
            "accion" => "retirada_de_liquidacion",
            "id_comision" => $idComision,
            "id_liquidacion" => $idLiquidacion,
            "monto" => $monto
        ];
    }

    /*
     * Una comisión pagada no se modifica. Se crea un descuento pendiente
     * para mantener intacto el comprobante salarial histórico.
     */
    return crearDescuentoPorComisionReembolsada(
        $conexion,
        $idUsuario,
        $idPedido,
        $idReembolso,
        $idComision,
        $monto
    );
}

function anularComisionPorReembolso(
    mysqli $conexion,
    int $idComision,
    int $idReembolso,
    ?string $observacionesAnteriores
): void {

    $nota = trim((string) $observacionesAnteriores);
    $texto = "Anulada por reembolso #" . $idReembolso . ".";
    $observaciones = mb_substr(
        $nota === "" ? $texto : $nota . " " . $texto,
        0,
        500
    );

    $stmt = $conexion->prepare(
        "UPDATE comisiones_usuarios
         SET estado = 'anulada', observaciones = ?
         WHERE id_comision = ?
           AND estado IN ('pendiente', 'liquidada')"
    );

    $stmt->bind_param("si", $observaciones, $idComision);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new RuntimeException(
            "No fue posible anular la comisión del pedido."
        );
    }

    $stmt->close();
}

function obtenerLiquidacionDeComision(
    mysqli $conexion,
    int $idComision
): ?array {

    $stmt = $conexion->prepare(
        "SELECT
            lr.id_liquidacion,
            lr.estado
         FROM detalle_liquidacion_comisiones dlc
         INNER JOIN liquidaciones_remuneraciones lr
            ON lr.id_liquidacion = dlc.id_liquidacion
         WHERE dlc.id_comision = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->bind_param("i", $idComision);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $fila ?: null;
}

function recalcularLiquidacionComisiones(
    mysqli $conexion,
    int $idLiquidacion
): void {

    $stmt = $conexion->prepare(
        "UPDATE liquidaciones_remuneraciones lr
         SET
            lr.total_comisiones = (
                SELECT COALESCE(SUM(dlc.monto_comision), 0)
                FROM detalle_liquidacion_comisiones dlc
                WHERE dlc.id_liquidacion = lr.id_liquidacion
            ),
            lr.total_liquidacion = GREATEST(
                0,
                lr.sueldo_base +
                (
                    SELECT COALESCE(SUM(dlc2.monto_comision), 0)
                    FROM detalle_liquidacion_comisiones dlc2
                    WHERE dlc2.id_liquidacion = lr.id_liquidacion
                ) +
                lr.bonos -
                lr.descuentos
            )
         WHERE lr.id_liquidacion = ?
           AND lr.estado IN ('borrador', 'cerrada')"
    );

    $stmt->bind_param("i", $idLiquidacion);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new RuntimeException(
            "No fue posible recalcular la liquidación."
        );
    }

    $stmt->close();
}

function crearDescuentoPorComisionReembolsada(
    mysqli $conexion,
    int $idUsuario,
    int $idPedido,
    int $idReembolso,
    int $idComision,
    int $monto
): array {

    if ($idUsuario <= 0 || $monto <= 0) {
        throw new RuntimeException(
            "La comisión pagada no permite crear un descuento válido."
        );
    }

    $clave =
        "AJ-REEMBOLSO-COMISION-" .
        $idReembolso .
        "-" .
        $idComision;

    $observaciones =
        "Descuento por reembolso del pedido #" . $idPedido .
        " y comisión pagada #" . $idComision . ".";

    $stmt = $conexion->prepare(
        "INSERT INTO ajustes_remuneraciones (
            id_usuario,
            id_comision,
            id_pedido,
            id_reembolso,
            id_liquidacion,
            tipo_ajuste,
            origen,
            monto,
            estado,
            clave_operacion,
            observaciones
         ) VALUES (
            ?, ?, ?, ?, NULL,
            'descuento', 'reembolso', ?, 'pendiente', ?, ?
         )
         ON DUPLICATE KEY UPDATE
            id_ajuste = LAST_INSERT_ID(id_ajuste)"
    );

    $stmt->bind_param(
        "iiiiiss",
        $idUsuario,
        $idComision,
        $idPedido,
        $idReembolso,
        $monto,
        $clave,
        $observaciones
    );

    $stmt->execute();
    $idAjuste = intval($conexion->insert_id);
    $fueCreado = $stmt->affected_rows === 1;
    $stmt->close();

    return [
        "procesada" => true,
        "accion" => $fueCreado
            ? "descuento_creado"
            : "descuento_ya_existente",
        "id_comision" => $idComision,
        "id_ajuste" => $idAjuste,
        "monto" => $monto
    ];
}

?>
