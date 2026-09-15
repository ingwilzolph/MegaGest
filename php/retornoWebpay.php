<?php

require_once "conexion.php";
require_once "configWebpay.php";
require_once "registrarComisionVenta.php";
require_once "revertirComisionVenta.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    /* =====================================================
       RECIBIR RESPUESTA DE WEBPAY
    ===================================================== */

    $token = trim(
        $_GET["token_ws"] ??
        $_POST["token_ws"] ??
        ""
    );

    $tokenAbortado = trim(
        $_GET["TBK_TOKEN"] ??
        $_POST["TBK_TOKEN"] ??
        ""
    );

    $buyOrderAbortado = trim(
        $_GET["TBK_ORDEN_COMPRA"] ??
        $_POST["TBK_ORDEN_COMPRA"] ??
        ""
    );

    $sessionIdAbortado = trim(
        $_GET["TBK_ID_SESION"] ??
        $_POST["TBK_ID_SESION"] ??
        $_GET["TBK_ID_SESSION"] ??
        $_POST["TBK_ID_SESSION"] ??
        ""
    );

    /*
     * Si no llega token_ws, la compra fue anulada,
     * agotó el tiempo o ocurrió un error en Webpay.
     */

    if ($token === "") {

        procesarPagoNoCompletado(
            $tokenAbortado,
            $buyOrderAbortado,
            $sessionIdAbortado
        );

        exit;
    }

    if (strlen($token) > 100) {
        throw new Exception(
            "El token recibido no es válido."
        );
    }

    /* =====================================================
       VERIFICAR SI YA FUE PROCESADO
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $sqlPagoAnterior = "
        SELECT
            pa.id_pago,
            pa.id_pedido,
            pa.estado,
            pe.numero_pedido,
            pe.estado AS estado_pedido,
            pe.estado_pago,
            pe.control_stock
        FROM pagos pa
        INNER JOIN pedidos pe
            ON pe.id_pedido = pa.id_pedido
        WHERE pa.token_ws = ?
        LIMIT 1
    ";

    $stmtPagoAnterior = $conexion->prepare(
        $sqlPagoAnterior
    );

    $stmtPagoAnterior->bind_param(
        "s",
        $token
    );

    $stmtPagoAnterior->execute();

    $resultadoPagoAnterior =
        $stmtPagoAnterior->get_result();

    if ($resultadoPagoAnterior->num_rows === 0) {
        throw new Exception(
            "No se encontró el pago asociado."
        );
    }

    $pagoAnterior =
        $resultadoPagoAnterior->fetch_assoc();

    $stmtPagoAnterior->close();

    /*
     * Evita ejecutar nuevamente el commit y descontar
     * el stock si el cliente actualiza la página.
     */

    $resultadoAnterior = resultadoRetornoYaProcesado($pagoAnterior);

    if ($resultadoAnterior !== null) {

        redirigirResultadoPago(
            $resultadoAnterior,
            $pagoAnterior["numero_pedido"]
        );
    }

    $conexion->close();
    $conexion = null;

    /* =====================================================
       CONFIRMAR TRANSACCIÓN EN WEBPAY
    ===================================================== */

    $transaccionWebpay =
        obtenerTransaccionWebpay();

    $respuesta =
        $transaccionWebpay->commit($token);

    $responseCode = intval(
        $respuesta->getResponseCode()
    );

    $status = strtoupper(
        trim($respuesta->getStatus())
    );

    $montoWebpay = intval(
        round($respuesta->getAmount())
    );

    $buyOrder = trim(
        $respuesta->getBuyOrder()
    );

    $sessionId = trim(
        $respuesta->getSessionId()
    );

    $authorizationCode = trim(
        strval(
            $respuesta->getAuthorizationCode()
        )
    );

    $paymentTypeCode = trim(
        strval(
            $respuesta->getPaymentTypeCode()
        )
    );

    $installmentsNumber = intval(
        $respuesta->getInstallmentsNumber() ?? 0
    );

    $transactionDateOriginal = trim(
        strval(
            $respuesta->getTransactionDate()
        )
    );

    $transactionDate =
        convertirFechaWebpay(
            $transactionDateOriginal
        );

    $pagoAprobado =
        $responseCode === 0 &&
        $status === "AUTHORIZED";

    /* =====================================================
       PREPARAR RESPUESTA PARA AUDITORÍA
    ===================================================== */

    $respuestaProveedor = json_encode(
        [
            "token_ws" => $token,
            "response_code" => $responseCode,
            "status" => $status,
            "amount" => $montoWebpay,
            "buy_order" => $buyOrder,
            "session_id" => $sessionId,
            "authorization_code" =>
                $authorizationCode,
            "payment_type_code" =>
                $paymentTypeCode,
            "installments_number" =>
                $installmentsNumber,
            "transaction_date" =>
                $transactionDateOriginal
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    /* =====================================================
       INICIAR TRANSACCIÓN MYSQL
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       BLOQUEAR PAGO Y PEDIDO
    ===================================================== */

    $sqlPago = "
        SELECT
            pa.id_pago,
            pa.id_pedido,
            pa.buy_order,
            pa.session_id,
            pa.monto,
            pa.estado,
            pe.numero_pedido,
            pe.estado AS estado_pedido,
            pe.estado_pago,
            pe.control_stock
        FROM pagos pa
        INNER JOIN pedidos pe
            ON pe.id_pedido = pa.id_pedido
        WHERE pa.token_ws = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtPago = $conexion->prepare(
        $sqlPago
    );

    $stmtPago->bind_param(
        "s",
        $token
    );

    $stmtPago->execute();

    $resultadoPago =
        $stmtPago->get_result();

    if ($resultadoPago->num_rows === 0) {
        throw new Exception(
            "No se encontró el intento de pago."
        );
    }

    $pago = $resultadoPago->fetch_assoc();

    $stmtPago->close();

    $idPago = intval($pago["id_pago"]);
    $idPedido = intval($pago["id_pedido"]);
    $montoPedido = intval($pago["monto"]);
    $numeroPedido = $pago["numero_pedido"];

    /*
     * Segunda protección contra actualizaciones
     * simultáneas de la página.
     */

    $resultadoActual = resultadoRetornoYaProcesado($pago);

    if ($resultadoActual !== null) {

        $conexion->commit();
        $transaccionIniciada = false;

        redirigirResultadoPago(
            $resultadoActual,
            $numeroPedido
        );
    }

    /* =====================================================
       COMPROBAR DATOS CONTRA LA BASE DE DATOS
    ===================================================== */

    if ($buyOrder !== $pago["buy_order"]) {
        throw new Exception(
            "La orden informada por Webpay no coincide."
        );
    }

    if ($sessionId !== $pago["session_id"]) {
        throw new Exception(
            "La sesión informada por Webpay no coincide."
        );
    }

    if ($montoWebpay !== $montoPedido) {
        throw new Exception(
            "El monto informado por Webpay no coincide con el pedido."
        );
    }

    /* =====================================================
       PAGO RECHAZADO
    ===================================================== */

    if (!$pagoAprobado) {

        $estadoPagoRechazado = "rechazado";

        actualizarPagoWebpay(
            $conexion,
            $idPago,
            $estadoPagoRechazado,
            $responseCode,
            $authorizationCode,
            $paymentTypeCode,
            $installmentsNumber,
            $transactionDate,
            $respuestaProveedor
        );

        /*
         * No sobrescribimos un pedido aprobado por otro
         * intento de pago rechazado.
         */

        if ($pago["estado_pago"] !== "aprobado") {

            $sqlPedidoRechazado = "
                UPDATE pedidos
                SET estado_pago = 'rechazado'
                WHERE id_pedido = ?
            ";

            $stmtPedidoRechazado =
                $conexion->prepare(
                    $sqlPedidoRechazado
                );

            $stmtPedidoRechazado->bind_param(
                "i",
                $idPedido
            );

            $stmtPedidoRechazado->execute();
            $stmtPedidoRechazado->close();
        }

        $conexion->commit();
        $transaccionIniciada = false;

        redirigirResultadoPago(
            "rechazado",
            $numeroPedido
        );
    }

    /* =====================================================
   COMPROBAR STOCK
    ===================================================== */

    $sqlDetalles = "
        SELECT
            dp.id_producto,
            dp.nombre_producto,
            dp.cantidad,
            p.cantidad AS stock_disponible
        FROM detalle_pedido dp
        INNER JOIN productos p
            ON p.id_producto = dp.id_producto
        WHERE dp.id_pedido = ?
        FOR UPDATE
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

    $detalles = [];
    $productosSinStock = [];

    while (
        $detalle =
            $resultadoDetalles->fetch_assoc()
    ) {

        $cantidadSolicitada =
            intval($detalle["cantidad"]);

        $stockDisponible =
            intval(
                $detalle["stock_disponible"]
            );

        if (
            $cantidadSolicitada >
            $stockDisponible
        ) {

            $productosSinStock[] = [
                "nombre" =>
                    $detalle[
                        "nombre_producto"
                    ],

                "cantidad_solicitada" =>
                    $cantidadSolicitada,

                "stock_disponible" =>
                    $stockDisponible
            ];
        }

        $detalles[] = $detalle;
    }

    $stmtDetalles->close();

    if (count($detalles) === 0) {
        throw new Exception(
            "El pedido no contiene productos."
        );
    }

    /* =====================================================
    REEMBOLSO POR STOCK INSUFICIENTE
    ===================================================== */

    if (count($productosSinStock) > 0) {

        $motivo =
            "Reembolso automático por stock insuficiente después de la aprobación de Webpay.";

        $productosSinStockJson =
            json_encode(
                $productosSinStock,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

        /*
        * Registramos la aprobación financiera,
        * pero dejamos el pago en proceso de
        * reembolso.
        */

        actualizarPagoWebpay(
            $conexion,
            $idPago,
            "reembolso_pendiente",
            $responseCode,
            $authorizationCode,
            $paymentTypeCode,
            $installmentsNumber,
            $transactionDate,
            $respuestaProveedor
        );

        $sqlPedidoReembolso = "
        UPDATE pedidos
        SET
            estado_antes_cancelacion = estado,
            estado =
                'cancelacion_solicitada',
            estado_pago =
                'reembolso_pendiente',
            control_stock = 'no_descontado'
        WHERE id_pedido = ?
        AND estado = 'pendiente_pago'
        AND control_stock IN ('desconocido', 'no_descontado')
        ";

        $stmtPedidoReembolso =
            $conexion->prepare(
                $sqlPedidoReembolso
            );

        $stmtPedidoReembolso->bind_param(
            "i",
            $idPedido
        );

    $stmtPedidoReembolso->execute();
    if ($stmtPedidoReembolso->affected_rows !== 1) {
        throw new RuntimeException(
            "El pedido cambió durante el proceso. Revise el pago antes de continuar."
        );
    }
        $stmtPedidoReembolso->close();

        $estadoPedidoAnterior =
            $pago["estado_pedido"];

        $estadoPagoAnterior =
            $pago["estado_pago"];

        $sqlReembolso = "
            INSERT INTO reembolsos (
                id_pedido,
                id_pago,
                token_ws,
                monto,
                motivo,
                estado_pedido_anterior,
                estado_pago_anterior,
                estado,
                respuesta_transbank
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'procesando',
                ?
            )
        ";

        $stmtReembolso =
            $conexion->prepare(
                $sqlReembolso
            );

        $stmtReembolso->bind_param(
            "iisissss",
            $idPedido,
            $idPago,
            $token,
            $montoPedido,
            $motivo,
            $estadoPedidoAnterior,
            $estadoPagoAnterior,
            $productosSinStockJson
        );

        $stmtReembolso->execute();

        $idReembolso =
            $conexion->insert_id;

        $stmtReembolso->close();

        /*
        * Confirmamos primero el estado pendiente.
        * El llamado externo a Transbank se realiza
        * después de liberar la transacción MySQL.
        */

        $conexion->commit();

        $transaccionIniciada = false;

        procesarReembolsoAutomaticoWebpay(
            $token,
            $montoPedido,
            $idReembolso,
            $idPago,
            $idPedido,
            $numeroPedido
        );
    }

    /* =====================================================
    DESCONTAR STOCK
    ===================================================== */

    $sqlDescontarStock = "
        UPDATE productos
        SET cantidad = cantidad - ?
        WHERE id_producto = ?
        AND cantidad >= ?
    ";

    $stmtDescontarStock =
        $conexion->prepare(
            $sqlDescontarStock
        );

    foreach ($detalles as $detalle) {

        $idProducto = intval(
            $detalle["id_producto"]
        );

        $cantidad = intval(
            $detalle["cantidad"]
        );

        $stmtDescontarStock->bind_param(
            "iii",
            $cantidad,
            $idProducto,
            $cantidad
        );

        $stmtDescontarStock->execute();

        if (
            $stmtDescontarStock
                ->affected_rows !== 1
        ) {
            throw new Exception(
                "No fue posible actualizar el stock del producto."
            );
        }
    }

    $stmtDescontarStock->close();



    /* Registrar el descuento dentro de la misma transacción. */

    $stmtControlStock = $conexion->prepare(
        "UPDATE pedidos
        SET control_stock = 'descontado'
        WHERE id_pedido = ?
        AND control_stock IN ('desconocido', 'no_descontado')"
    );

    $stmtControlStock->bind_param("i", $idPedido);
    $stmtControlStock->execute();

    if ($stmtControlStock->affected_rows !== 1) {
        $stmtControlStock->close();

        throw new RuntimeException(
            "El control de stock del pedido no permite registrar otro descuento."
        );
    }

    $stmtControlStock->close();



    /* =====================================================
       ACTUALIZAR PAGO APROBADO
    ===================================================== */

    actualizarPagoWebpay(
        $conexion,
        $idPago,
        "aprobado",
        $responseCode,
        $authorizationCode,
        $paymentTypeCode,
        $installmentsNumber,
        $transactionDate,
        $respuestaProveedor
    );

    /* =====================================================
       ACTUALIZAR PEDIDO
    ===================================================== */

    $sqlPedidoAprobado = "
        UPDATE pedidos
        SET
            estado = 'pagado',
            estado_pago = 'aprobado'
        WHERE id_pedido = ?
    ";

    $stmtPedidoAprobado =
        $conexion->prepare(
            $sqlPedidoAprobado
        );

    $stmtPedidoAprobado->bind_param(
        "i",
        $idPedido
    );

    $stmtPedidoAprobado->execute();
    $stmtPedidoAprobado->close();

    /*
     * Solo genera comisión si el pedido online tiene un responsable
     * comercial configurado. La clave única evita duplicados.
     */
    registrarComisionVenta(
        $conexion,
        $idPedido
    );

    /* =====================================================
       CONFIRMAR CAMBIOS
    ===================================================== */

    $conexion->commit();

    $transaccionIniciada = false;

    redirigirResultadoPago(
        "aprobado",
        $numeroPedido
    );

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error retorno Webpay: " .
        $error->getMessage()
    );

    redirigirResultadoPago(
        "error",
        ""
    );

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}



    /* =====================================================
        REEMBOLSO AUTOMÁTICO
        ===================================================== */

        function procesarReembolsoAutomaticoWebpay(
            string $token,
            int $monto,
            int $idReembolso,
            int $idPago,
            int $idPedido,
            string $numeroPedido
        ): void {

            $conexionReembolso = null;
            $transaccionReembolsoIniciada =
                false;

            try {

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

                $codigoAutorizacion =
                    method_exists(
                        $respuesta,
                        "getAuthorizationCode"
                    )
                        ? strval(
                            $respuesta
                                ->getAuthorizationCode()
                        )
                        : null;

                $fechaAutorizacion =
                    method_exists(
                        $respuesta,
                        "getAuthorizationDate"
                    )
                        ? strval(
                            $respuesta
                                ->getAuthorizationDate()
                        )
                        : null;

                $respuestaJson = json_encode(
                    [
                        "type" => $tipo,
                        "response_code" =>
                            $responseCode,
                        "nullified_amount" =>
                            $montoAnulado,
                        "balance" => $saldo,
                        "authorization_code" =>
                            $codigoAutorizacion,
                        "authorization_date" =>
                            $fechaAutorizacion
                    ],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );

                /*
                * Una reversa puede entregar solamente
                * type = REVERSED.
                * Una anulación entrega response_code = 0.
                */

                $reembolsoAceptado =
                    $tipo === "REVERSED" ||
                    $responseCode === 0;

                if (!$reembolsoAceptado) {
                    throw new Exception(
                        "Transbank no confirmó el reembolso."
                    );
                }

                $conexionReembolso = conexion();

                $conexionReembolso->set_charset(
                    "utf8mb4"
                );

                $conexionReembolso
                    ->begin_transaction();

                $transaccionReembolsoIniciada =
                    true;

                $sqlActualizarReembolso = "
                    UPDATE reembolsos
                    SET
                        estado = 'reembolsado',
                        respuesta_transbank = ?,
                        mensaje_error = NULL,
                        fecha_procesamiento = NOW()
                    WHERE id_reembolso = ?
                ";

                $stmtReembolso =
                    $conexionReembolso->prepare(
                        $sqlActualizarReembolso
                    );

                $stmtReembolso->bind_param(
                    "si",
                    $respuestaJson,
                    $idReembolso
                );

                $stmtReembolso->execute();
                $stmtReembolso->close();

                $sqlActualizarPago = "
                    UPDATE pagos
                    SET
                        estado = 'reembolsado',
                        respuesta_proveedor = ?
                    WHERE id_pago = ?
                    AND estado =
                        'reembolso_pendiente'
                ";

                $stmtPago =
                    $conexionReembolso->prepare(
                        $sqlActualizarPago
                    );

                $stmtPago->bind_param(
                    "si",
                    $respuestaJson,
                    $idPago
                );

                $stmtPago->execute();
                $stmtPago->close();

                $sqlActualizarPedido = "
                    UPDATE pedidos
                    SET
                        estado = 'cancelado',
                        estado_pago = 'reembolsado'
                    WHERE id_pedido = ?
                    AND estado =
                        'cancelacion_solicitada'
                ";

                $stmtPedido =
                    $conexionReembolso->prepare(
                        $sqlActualizarPedido
                    );

                $stmtPedido->bind_param(
                    "i",
                    $idPedido
                );

                $stmtPedido->execute();
                $stmtPedido->close();

                /* Si existiera una comisión, queda revertida con el reembolso. */
                revertirComisionVenta(
                    $conexionReembolso,
                    $idPedido,
                    $idReembolso
                );

                $conexionReembolso->commit();

                $transaccionReembolsoIniciada =
                    false;

                redirigirResultadoPago(
                    "reembolsado",
                    $numeroPedido
                );

            } catch (Throwable $error) {

                if (
                    $transaccionReembolsoIniciada &&
                    $conexionReembolso instanceof mysqli
                ) {
                    $conexionReembolso->rollback();
                }

                error_log(
                    "Error en reembolso automático: " .
                    $error->getMessage()
                );

                try {

                    if (
                        !(
                            $conexionReembolso
                            instanceof mysqli
                        )
                    ) {
                        $conexionReembolso =
                            conexion();

                        $conexionReembolso
                            ->set_charset(
                                "utf8mb4"
                            );
                    }

                    $mensajeError = mb_substr(
                        $error->getMessage(),
                        0,
                        500
                    );

                    $sqlError = "
                        UPDATE reembolsos
                        SET
                            estado = 'error',
                            mensaje_error = ?,
                            fecha_procesamiento = NOW()
                        WHERE id_reembolso = ?
                    ";

                    $stmtError =
                        $conexionReembolso->prepare(
                            $sqlError
                        );

                    $stmtError->bind_param(
                        "si",
                        $mensajeError,
                        $idReembolso
                    );

                    $stmtError->execute();
                    $stmtError->close();

                } catch (Throwable $errorRegistro) {

                    error_log(
                        "No fue posible registrar el error del reembolso: " .
                        $errorRegistro->getMessage()
                    );
                }

                /*
                * El pedido y pago permanecen en:
                *
                * cancelacion_solicitada
                * reembolso_pendiente
                *
                * para revisión administrativa.
                */

                redirigirResultadoPago(
                    "reembolso_pendiente",
                    $numeroPedido
                );

            } finally {

                if (
                    $conexionReembolso
                    instanceof mysqli
                ) {
                    $conexionReembolso->close();
                }
            }
        }

/* =====================================================
   ACTUALIZAR REGISTRO DE PAGO
===================================================== */

function actualizarPagoWebpay(
    mysqli $conexion,
    int $idPago,
    string $estado,
    int $responseCode,
    string $authorizationCode,
    string $paymentTypeCode,
    int $installmentsNumber,
    ?string $transactionDate,
    string $respuestaProveedor
): void {

    $sql = "
        UPDATE pagos
        SET
            estado = ?,
            response_code = ?,
            authorization_code = ?,
            payment_type_code = ?,
            installments_number = ?,
            transaction_date = ?,
            respuesta_proveedor = ?
        WHERE id_pago = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "sississi",
        $estado,
        $responseCode,
        $authorizationCode,
        $paymentTypeCode,
        $installmentsNumber,
        $transactionDate,
        $respuestaProveedor,
        $idPago
    );

    $stmt->execute();
    $stmt->close();
}

/* =====================================================
   COMPRA ANULADA O TIEMPO AGOTADO
===================================================== */

function procesarPagoNoCompletado(
    string $tokenAbortado,
    string $buyOrder,
    string $sessionId
): void {

    $conexionLocal = null;
    $transaccionLocal = false;

    try {

        if (
            $buyOrder === "" &&
            $sessionId === ""
        ) {
            redirigirResultadoPago(
                "anulado",
                ""
            );
        }

        $conexionLocal = conexion();

        $conexionLocal->set_charset(
            "utf8mb4"
        );

        $conexionLocal->begin_transaction();
        $transaccionLocal = true;

        $sql = "
            SELECT
                pa.id_pago,
                pa.id_pedido,
                pa.estado,
                pe.numero_pedido,
                pe.estado_pago,
                pe.estado AS estado_pedido,
                pe.control_stock
            FROM pagos pa
            INNER JOIN pedidos pe
                ON pe.id_pedido = pa.id_pedido
            WHERE
                pa.buy_order = ?
                OR pa.session_id = ?
            ORDER BY pa.id_pago DESC
            LIMIT 1
            FOR UPDATE
        ";

        $stmt = $conexionLocal->prepare($sql);

        $stmt->bind_param(
            "ss",
            $buyOrder,
            $sessionId
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {

            $stmt->close();
            $conexionLocal->rollback();
            $transaccionLocal = false;

            redirigirResultadoPago(
                "anulado",
                ""
            );
        }

        $pago = $resultado->fetch_assoc();

        $stmt->close();

        $idPago = intval($pago["id_pago"]);
        $idPedido = intval($pago["id_pedido"]);
        $numeroPedido = $pago["numero_pedido"];

        $resultadoPrevio = resultadoRetornoYaProcesado($pago);

        if ($resultadoPrevio !== null) {
            $conexionLocal->commit();
            $transaccionLocal = false;
            redirigirResultadoPago($resultadoPrevio, $numeroPedido);
        }

        if ($pago["estado_pago"] !== "aprobado") {

            $estado = $tokenAbortado !== ""
                ? "anulado"
                : "rechazado";

            $respuesta = json_encode(
                [
                    "TBK_TOKEN" =>
                        $tokenAbortado,
                    "TBK_ORDEN_COMPRA" =>
                        $buyOrder,
                    "TBK_ID_SESION" =>
                        $sessionId,
                    "resultado" =>
                        $estado
                ],
                JSON_UNESCAPED_UNICODE
            );

            $sqlActualizarPago = "
                UPDATE pagos
                SET
                    estado = ?,
                    respuesta_proveedor = ?
                WHERE id_pago = ?
            ";

            $stmtActualizarPago =
                $conexionLocal->prepare(
                    $sqlActualizarPago
                );

            $stmtActualizarPago->bind_param(
                "ssi",
                $estado,
                $respuesta,
                $idPago
            );

            $stmtActualizarPago->execute();
            $stmtActualizarPago->close();

            $estadoPedido = $estado === "anulado"
                ? "anulado"
                : "rechazado";

            $sqlActualizarPedido = "
                UPDATE pedidos
                SET
                    estado_pago = ?,

                    estado = CASE
                        WHEN ? = 'anulado'
                            THEN 'cancelado'
                        ELSE estado
                    END

                WHERE id_pedido = ?
                AND estado_pago <> 'aprobado'
            ";

            $stmtActualizarPedido =
                $conexionLocal->prepare(
                    $sqlActualizarPedido
                );

            $stmtActualizarPedido->bind_param(
                "ssi",
                $estadoPedido,
                $estadoPedido,
                $idPedido
            );

            $stmtActualizarPedido->execute();
            $stmtActualizarPedido->close();
        }

        $conexionLocal->commit();
        $transaccionLocal = false;

        redirigirResultadoPago(
            "anulado",
            $numeroPedido
        );

    } catch (Throwable $error) {

        if ($transaccionLocal && $conexionLocal instanceof mysqli) {
            $conexionLocal->rollback();
            $transaccionLocal = false;
        }

        error_log(
            "Error procesando pago no completado: " .
            $error->getMessage()
        );

        redirigirResultadoPago(
            "error",
            ""
        );

    } finally {

        if (
            $conexionLocal instanceof mysqli
        ) {
            $conexionLocal->close();
        }
    }
}

/* =====================================================
   CONVERTIR FECHA WEBPAY
===================================================== */

function convertirFechaWebpay(
    string $fecha
): ?string {

    if ($fecha === "") {
        return null;
    }

    try {

        $fechaWebpay = new DateTime($fecha);

        return $fechaWebpay->format(
            "Y-m-d H:i:s"
        );

    } catch (Throwable $error) {

        return null;
    }
}

/* =====================================================
   REDIRECCIONAR RESULTADO
===================================================== */

function redirigirResultadoPago(
    string $estado,
    string $numeroPedido
): void {

    $url =
        URL_BASE_SITIO .
        "/resultado-pago.html?estado=" .
        rawurlencode($estado);

    if ($numeroPedido !== "") {

        $url .=
            "&pedido=" .
            rawurlencode($numeroPedido);
    }

    header(
        "Location: " . $url,
        true,
        303
    );

    exit;
}



function resultadoRetornoYaProcesado(array $pago): ?string
{
    $estadoPago = $pago["estado"];
    $estadoPedido = $pago["estado_pedido"];
    $estadoPagoPedido = $pago["estado_pago"];
    $controlStock = $pago["control_stock"];

    /*
     * Una devolución completada no debe volver a procesarse.
     */
    if (
        $estadoPago === "reembolsado" &&
        $estadoPagoPedido === "reembolsado" &&
        $estadoPedido === "cancelado"
    ) {
        return "reembolsado";
    }

    /*
     * Una cancelación o devolución en curso requiere revisión.
     * El retorno del pago no debe reactivarla.
     */
    if (
        in_array(
            $estadoPago,
            ["reembolso_pendiente", "reembolsado"],
            true
        ) ||
        in_array(
            $estadoPagoPedido,
            ["reembolso_pendiente", "reembolsado"],
            true
        ) ||
        $estadoPedido === "cancelacion_solicitada"
    ) {
        return "reembolso_pendiente";
    }

    /*
     * No reactivar pedidos cancelados ni volver a descontar
     * productos que ya fueron reintegrados.
     */
    if (
        $estadoPedido === "cancelado" ||
        $controlStock === "reintegrado"
    ) {
        return "error";
    }

    if (
        $estadoPago === "aprobado" &&
        $estadoPagoPedido === "aprobado"
    ) {
        return "aprobado";
    }

    /*
     * Solo continuar con un intento todavía iniciado y
     * un pedido pendiente de pago, sin descuento registrado.
     */
    if (
        $estadoPago !== "iniciado" ||
        $estadoPedido !== "pendiente_pago" ||
        !in_array(
            $estadoPagoPedido,
            ["pendiente", "rechazado", "anulado"],
            true
        ) ||
        !in_array(
            $controlStock,
            ["desconocido", "no_descontado"],
            true
        )
    ) {
        return "error";
    }

    return null;
}

?>
