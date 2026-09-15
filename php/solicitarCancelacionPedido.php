<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";
require_once "sesionCliente.php";
require_once "configWebpay.php";
require_once "revertirComisionVenta.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

$idReembolso = 0;
$idPago = 0;
$idPedido = 0;

$procesoPendienteRegistrado = false;
$reembolsoConfirmadoWebpay = false;

try {

    /* =====================================================
       MÉTODO Y SESIÓN
    ===================================================== */

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    $idCliente =
        exigirSesionCliente();

    $datos = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($datos)) {
        throw new Exception(
            "Los datos recibidos no son válidos."
        );
    }

    $idPedido = intval(
        $datos["id_pedido"] ?? 0
    );

    $motivoCliente = trim(
        $datos["motivo"] ??
        "Cancelación solicitada por el cliente."
    );

    if ($idPedido <= 0) {
        throw new Exception(
            "El pedido no es válido."
        );
    }

    if (
        $motivoCliente === "" ||
        mb_strlen($motivoCliente) > 500
    ) {
        throw new Exception(
            "El motivo de cancelación no es válido."
        );
    }

    /* =====================================================
       CONEXIÓN Y BLOQUEO
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    $sqlPedido = "
        SELECT
            pe.id_pedido,
            pe.numero_pedido,
            pe.id_cliente,
            pe.canal,
            pe.tipo_entrega,
            pe.estado,
            pe.estado_pago,
            pe.control_stock,
            pe.total,
            pa.id_pago,
            pa.token_ws,
            pa.monto,
            pa.estado AS estado_pago_registro
        FROM pedidos pe
        INNER JOIN pagos pa
            ON pa.id_pedido = pe.id_pedido
        WHERE pe.id_pedido = ?
          AND pe.id_cliente = ?
          AND pa.estado = 'aprobado'
        ORDER BY pa.id_pago DESC
        LIMIT 1
        FOR UPDATE
    ";

    $stmtPedido =
        $conexion->prepare(
            $sqlPedido
        );

    $stmtPedido->bind_param(
        "ii",
        $idPedido,
        $idCliente
    );

    $stmtPedido->execute();

    $pedido =
        $stmtPedido
            ->get_result()
            ->fetch_assoc();

    $stmtPedido->close();

    if (!$pedido) {
        throw new Exception(
            "No se encontró un pago aprobado para este pedido."
        );
    }

    $idPago = intval(
        $pedido["id_pago"]
    );

    $numeroPedido =
        $pedido["numero_pedido"];

    $token =
        trim($pedido["token_ws"]);

    $monto = intval(
        $pedido["monto"]
    );

    /* =====================================================
       VALIDACIONES
    ===================================================== */

    if ($pedido["canal"] !== "online") {
        throw new Exception(
            "Este pedido no corresponde a una compra en línea."
        );
    }

    if (
        $pedido["estado_pago"] !==
        "aprobado"
    ) {
        throw new Exception(
            "El pedido no tiene un pago disponible para reembolso."
        );
    }

    if (
        $monto <= 0 ||
        $monto !== intval($pedido["total"])
    ) {
        throw new Exception(
            "El monto del pago no coincide con el pedido."
        );
    }

    if ($token === "") {
        throw new Exception(
            "El pago no tiene un token válido."
        );
    }

    $estadosPermitidos = [];

    if (
        $pedido["tipo_entrega"] ===
        "retiro"
    ) {

        $estadosPermitidos = [
            "pagado",
            "preparando",
            "listo_retiro"
        ];

    } elseif (
        $pedido["tipo_entrega"] ===
        "despacho"
    ) {

        $estadosPermitidos = [
            "pagado",
            "preparando",
            "enviado"
        ];
    }

    if (
        !in_array(
            $pedido["estado"],
            $estadosPermitidos,
            true
        )
    ) {
        throw new Exception(
            "El pedido ya no puede cancelarse automáticamente en su estado actual."
        );
    }

    /* =====================================================
       PEDIDO ENVIADO: ESPERAR DEVOLUCIÓN FÍSICA
    ===================================================== */

    if ($pedido["estado"] === "enviado") {

        $estadoPedidoAnterior = $pedido["estado"];

        $stmtSolicitud = $conexion->prepare(
            "UPDATE pedidos
             SET estado_antes_cancelacion = ?,
                 estado = 'cancelacion_solicitada'
             WHERE id_pedido = ?
               AND estado = 'enviado'
               AND estado_pago = 'aprobado'"
        );

        $stmtSolicitud->bind_param(
            "si",
            $estadoPedidoAnterior,
            $idPedido
        );

        $stmtSolicitud->execute();

        if ($stmtSolicitud->affected_rows !== 1) {
            $stmtSolicitud->close();

            throw new Exception(
                "El pedido cambió durante la solicitud de cancelación."
            );
        }

        $stmtSolicitud->close();

        $conexion->commit();
        $transaccionIniciada = false;

        echo json_encode([
            "ok" => true,
            "reembolsado" => false,
            "requiere_recepcion" => true,
            "mensaje" =>
                "La cancelación fue solicitada. El reembolso se gestionará cuando los productos regresen a la tienda."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /* =====================================================
       EVITAR REEMBOLSO DUPLICADO
    ===================================================== */

    $sqlReembolsoExistente = "
        SELECT
            id_reembolso,
            estado
        FROM reembolsos
        WHERE id_pedido = ?
          AND id_pago = ?
        ORDER BY id_reembolso DESC
        LIMIT 1
        FOR UPDATE
    ";

    $stmtExistente =
        $conexion->prepare(
            $sqlReembolsoExistente
        );

    $stmtExistente->bind_param(
        "ii",
        $idPedido,
        $idPago
    );

    $stmtExistente->execute();

    $reembolsoExistente =
        $stmtExistente
            ->get_result()
            ->fetch_assoc();

    $stmtExistente->close();

    if ($reembolsoExistente) {

        if (
            $reembolsoExistente["estado"] ===
            "reembolsado"
        ) {

            $conexion->commit();
            $transaccionIniciada = false;

            echo json_encode([
                "ok" => true,
                "reembolsado" => true,
                "mensaje" =>
                    "Este pedido ya fue reembolsado."
            ]);

            exit;
        }

        throw new Exception(
            "Ya existe una solicitud de reembolso para este pedido."
        );
    }

    /* =====================================================
       REGISTRAR SOLICITUD
    ===================================================== */

    $estadoPedidoAnterior =
        $pedido["estado"];

    $estadoPagoAnterior =
        $pedido["estado_pago"];

    $sqlReembolso = "
        INSERT INTO reembolsos (
            id_pedido,
            id_pago,
            token_ws,
            monto,
            motivo,
            estado_pedido_anterior,
            estado_pago_anterior,
            estado
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'procesando'
        )
    ";

    $stmtReembolso =
        $conexion->prepare(
            $sqlReembolso
        );

    $stmtReembolso->bind_param(
        "iisisss",
        $idPedido,
        $idPago,
        $token,
        $monto,
        $motivoCliente,
        $estadoPedidoAnterior,
        $estadoPagoAnterior
    );

    $stmtReembolso->execute();

    $idReembolso =
        $conexion->insert_id;

    $stmtReembolso->close();

    $sqlPedidoPendiente = "
        UPDATE pedidos
        SET
            estado_antes_cancelacion = ?,
            estado =
                'cancelacion_solicitada',
            estado_pago =
                'reembolso_pendiente'
        WHERE id_pedido = ?
          AND estado_pago = 'aprobado'
    ";

    $stmtPedidoPendiente =
        $conexion->prepare(
            $sqlPedidoPendiente
        );

    $stmtPedidoPendiente->bind_param(
        "si",
        $estadoPedidoAnterior,
        $idPedido
    );

    $stmtPedidoPendiente->execute();

    if (
        $stmtPedidoPendiente
            ->affected_rows !== 1
    ) {
        throw new Exception(
            "No fue posible bloquear el pedido para reembolso."
        );
    }

    $stmtPedidoPendiente->close();

    $sqlPagoPendiente = "
        UPDATE pagos
        SET estado =
            'reembolso_pendiente'
        WHERE id_pago = ?
          AND estado = 'aprobado'
    ";

    $stmtPagoPendiente =
        $conexion->prepare(
            $sqlPagoPendiente
        );

    $stmtPagoPendiente->bind_param(
        "i",
        $idPago
    );

    $stmtPagoPendiente->execute();

    if (
        $stmtPagoPendiente
            ->affected_rows !== 1
    ) {
        throw new Exception(
            "No fue posible bloquear el pago para reembolso."
        );
    }

    $stmtPagoPendiente->close();

    $conexion->commit();

    $transaccionIniciada = false;
    $procesoPendienteRegistrado = true;

    $conexion->close();
    $conexion = null;

    /* =====================================================
       SOLICITAR REEMBOLSO A WEBPAY
    ===================================================== */

    $transaccionWebpay =
        obtenerTransaccionWebpay();

    $respuesta =
        $transaccionWebpay->refund(
            $token,
            $monto
        );

    $tipo = method_exists(
        $respuesta,
        "getType"
    )
        ? strtoupper(
            trim(
                strval(
                    $respuesta->getType()
                )
            )
        )
        : "";

    $responseCode = method_exists(
        $respuesta,
        "getResponseCode"
    )
        ? intval(
            $respuesta->getResponseCode()
        )
        : null;

    $montoAnulado = method_exists(
        $respuesta,
        "getNullifiedAmount"
    )
        ? intval(
            round(
                $respuesta
                    ->getNullifiedAmount() ??
                0
            )
        )
        : 0;

    $saldo = method_exists(
        $respuesta,
        "getBalance"
    )
        ? intval(
            round(
                $respuesta->getBalance() ??
                0
            )
        )
        : null;

    $respuestaJson = json_encode(
        [
            "type" => $tipo,
            "response_code" =>
                $responseCode,
            "nullified_amount" =>
                $montoAnulado,
            "balance" => $saldo
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    $reembolsoAceptado =
        $tipo === "REVERSED" ||
        $responseCode === 0;

    if (!$reembolsoAceptado) {
        throw new Exception(
            "Transbank no confirmó el reembolso."
        );
    }

    $reembolsoConfirmadoWebpay = true;

    /* =====================================================
       CONFIRMAR Y RESTAURAR STOCK
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* Bloquear primero el pedido y después el reembolso. */
    $stmtControlPedido = $conexion->prepare(
        "SELECT estado, estado_pago, control_stock
         FROM pedidos
         WHERE id_pedido = ?
         FOR UPDATE"
    );

    $stmtControlPedido->bind_param("i", $idPedido);
    $stmtControlPedido->execute();
    $controlPedido = $stmtControlPedido
        ->get_result()
        ->fetch_assoc();
    $stmtControlPedido->close();

    if (!$controlPedido) {
        throw new Exception("No se encontró el pedido del reembolso.");
    }

    $sqlBloquearReembolso = "
        SELECT estado
        FROM reembolsos
        WHERE id_reembolso = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtBloquear =
        $conexion->prepare(
            $sqlBloquearReembolso
        );

    $stmtBloquear->bind_param(
        "i",
        $idReembolso
    );

    $stmtBloquear->execute();

    $registroReembolso =
        $stmtBloquear
            ->get_result()
            ->fetch_assoc();

    $stmtBloquear->close();

    if (!$registroReembolso) {
        throw new Exception(
            "No se encontró el registro del reembolso."
        );
    }

    if ($registroReembolso["estado"] === "reembolsado") {
        throw new Exception(
            "El reembolso ya estaba registrado. No se modificó el stock."
        );
    }

    if ($controlPedido["control_stock"] === "descontado") {

        $sqlDetalles = "
            SELECT
                id_producto,
                cantidad
            FROM detalle_pedido
            WHERE id_pedido = ?
              AND id_producto IS NOT NULL
        ";

        $stmtDetalles =
            $conexion->prepare(
                $sqlDetalles
            );

        $stmtDetalles->bind_param(
            "i",
            $idPedido
        );

        $stmtDetalles->execute();

        $resultadoDetalles =
            $stmtDetalles->get_result();

        $sqlRestaurarStock = "
            UPDATE productos
            SET cantidad = cantidad + ?
            WHERE id_producto = ?
              AND cantidad >= 0
              AND cantidad <= 2147483647 - ?
        ";

        $stmtRestaurar =
            $conexion->prepare(
                $sqlRestaurarStock
            );

        while (
            $detalle =
                $resultadoDetalles
                    ->fetch_assoc()
        ) {

            $cantidad = intval(
                $detalle["cantidad"]
            );

            $idProducto = intval(
                $detalle["id_producto"]
            );

            $stmtRestaurar->bind_param(
                "iii",
                $cantidad,
                $idProducto,
                $cantidad
            );

            $stmtRestaurar->execute();

            if (
                $stmtRestaurar
                    ->affected_rows !== 1
            ) {
                throw new Exception(
                    "No fue posible restaurar el stock."
                );
            }
        }

        $stmtRestaurar->close();
        $stmtDetalles->close();

        $stmtMarcarStock = $conexion->prepare(
            "UPDATE pedidos
             SET control_stock = 'reintegrado',
                 fecha_reintegro_stock = NOW(),
                 id_usuario_reintegro_stock = NULL
             WHERE id_pedido = ?
               AND control_stock = 'descontado'"
        );

        $stmtMarcarStock->bind_param("i", $idPedido);
        $stmtMarcarStock->execute();

        if ($stmtMarcarStock->affected_rows !== 1) {
            $stmtMarcarStock->close();
            throw new Exception(
                "No fue posible registrar la reposición del stock."
            );
        }

        $stmtMarcarStock->close();

    } elseif (
        !in_array(
            $controlPedido["control_stock"],
            ["no_descontado", "reintegrado"],
            true
        )
    ) {
        throw new Exception(
            "El stock de este pedido antiguo requiere revisión manual."
        );
    }

    $sqlConfirmarReembolso = "
        UPDATE reembolsos
        SET
            estado = 'reembolsado',
            respuesta_transbank = ?,
            mensaje_error = NULL,
            fecha_procesamiento = NOW()
        WHERE id_reembolso = ?
    ";

    $stmtConfirmar =
        $conexion->prepare(
            $sqlConfirmarReembolso
        );

    $stmtConfirmar->bind_param(
        "si",
        $respuestaJson,
        $idReembolso
    );

    $stmtConfirmar->execute();
    $stmtConfirmar->close();

    $stmtPago = $conexion->prepare(
        "UPDATE pagos
         SET estado = 'reembolsado'
         WHERE id_pago = ?"
    );

    $stmtPago->bind_param(
        "i",
        $idPago
    );

    $stmtPago->execute();
    $stmtPago->close();

    $stmtPedido = $conexion->prepare(
        "UPDATE pedidos
         SET
            estado = 'cancelado',
            estado_pago = 'reembolsado'
         WHERE id_pedido = ?"
    );

    $stmtPedido->bind_param(
        "i",
        $idPedido
    );

    $stmtPedido->execute();
    $stmtPedido->close();

    $reversionComision = revertirComisionVenta(
        $conexion,
        $idPedido,
        $idReembolso
    );

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "reembolsado" => true,
        "mensaje" =>
            "El pedido fue cancelado y Transbank aceptó el reembolso.",
        "numero_pedido" =>
            $numeroPedido
    ]);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error cancelando pedido: " .
        $error->getMessage()
    );

    /*
     * Si el estado pendiente ya fue guardado,
     * registramos el error para revisión.
     */

    if (
        $procesoPendienteRegistrado &&
        $idReembolso > 0
    ) {

        try {

            $conexionError = conexion();

            $mensajeError = mb_substr(
                $error->getMessage(),
                0,
                500
            );

            $stmtError =
                $conexionError->prepare(
                    "UPDATE reembolsos
                     SET
                        estado = 'error',
                        mensaje_error = ?,
                        fecha_procesamiento = NOW()
                     WHERE id_reembolso = ?"
                );

            $stmtError->bind_param(
                "si",
                $mensajeError,
                $idReembolso
            );

            $stmtError->execute();
            $stmtError->close();
            $conexionError->close();

        } catch (Throwable $errorRegistro) {

            error_log(
                "Error registrando fallo de reembolso: " .
                $errorRegistro->getMessage()
            );
        }
    }

    http_response_code(
        $procesoPendienteRegistrado
            ? 502
            : 400
    );

    echo json_encode([
        "ok" => false,

        "reembolso_pendiente" =>
            $procesoPendienteRegistrado,

        "reembolso_confirmado_webpay" =>
            $reembolsoConfirmadoWebpay,

        "mensaje" =>
            $procesoPendienteRegistrado
                ? "La cancelación quedó registrada, pero requiere revisión administrativa."
                : $error->getMessage()
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>
