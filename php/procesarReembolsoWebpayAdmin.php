<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/configWebpay.php";
require_once __DIR__ . "/revertirComisionVenta.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;
$transaccionIniciada = false;
$procesoPendiente = false;
$reembolsoWebpayConfirmado = false;
$idReembolso = 0;
$idPedido = 0;

function rechazarReembolsoWebpay(string $mensaje, int $codigo = 400): void
{
    throw new RuntimeException($mensaje, $codigo);
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        rechazarReembolsoWebpay("Método no permitido.", 405);
    }

    if (($_SERVER["HTTP_X_MEGAGEST_REEMBOLSO"] ?? "") !== "1") {
        rechazarReembolsoWebpay("Solicitud no autorizada.", 403);
    }

    $idUsuario = (int) ($_SESSION["id_usuario"] ?? 0);
    $datos = json_decode(file_get_contents("php://input"), true);

    if ($idUsuario <= 0 || !is_array($datos)) {
        rechazarReembolsoWebpay("Los datos de la solicitud no son válidos.");
    }

    $idPedido = filter_var(
        $datos["id_pedido"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    $motivo = is_string($datos["motivo"] ?? null)
        ? trim($datos["motivo"])
        : "";

    $clave = is_string($datos["clave_operacion"] ?? null)
        ? trim($datos["clave_operacion"])
        : "";

    if ($idPedido === false) {
        rechazarReembolsoWebpay("El pedido no es válido.");
    }

    if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 500) {
        rechazarReembolsoWebpay(
            "El motivo debe contener entre 5 y 500 caracteres."
        );
    }

    if (!preg_match('/^[a-f0-9]{32}$/D', $clave)) {
        rechazarReembolsoWebpay(
            "El identificador de la operación no es válido."
        );
    }

    if (($datos["reembolso_confirmado"] ?? false) !== true) {
        rechazarReembolsoWebpay(
            "Debe confirmar el reembolso total por Webpay."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmt = $conexion->prepare(
        "SELECT rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         FOR UPDATE"
    );
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
        rechazarReembolsoWebpay(
            "Solo un administrador o cajero activo puede gestionar el reembolso.",
            403
        );
    }

    $stmt = $conexion->prepare(
        "SELECT id_pedido, numero_pedido, canal, estado,
                estado_pago, total, control_stock
         FROM pedidos
         WHERE id_pedido = ?
         FOR UPDATE"
    );
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pedido) {
        rechazarReembolsoWebpay("El pedido no existe.", 404);
    }

    /* Repetir la misma solicitud solo consulta su resultado. */
    $stmt = $conexion->prepare(
        "SELECT id_reembolso, id_pedido, id_usuario_reembolso,
                motivo, monto, estado
         FROM reembolsos
         WHERE clave_operacion_manual = ?
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("s", $clave);
    $stmt->execute();
    $repetido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($repetido) {
        $mismaSolicitud =
            (int) $repetido["id_pedido"] === (int) $idPedido &&
            (int) $repetido["id_usuario_reembolso"] === $idUsuario &&
            $repetido["motivo"] === $motivo;

        if (
            $mismaSolicitud &&
            $repetido["estado"] === "reembolsado" &&
            $pedido["estado"] === "cancelado" &&
            $pedido["estado_pago"] === "reembolsado"
        ) {
            $conexion->commit();
            $transaccionIniciada = false;

            echo json_encode([
                "ok" => true,
                "mensaje" => "Este reembolso Webpay ya estaba registrado.",
                "datos" => [
                    "id_pedido" => (int) $idPedido,
                    "id_reembolso" => (int) $repetido["id_reembolso"],
                    "monto" => (int) $repetido["monto"]
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        rechazarReembolsoWebpay(
            "La operación ya existe y requiere revisión. No repita el reembolso.",
            409
        );
    }

    if (
        $pedido["canal"] !== "online" ||
        $pedido["estado"] !== "cancelacion_solicitada" ||
        $pedido["estado_pago"] !== "aprobado"
    ) {
        rechazarReembolsoWebpay(
            "El pedido en línea debe tener cancelación solicitada y pago aprobado.",
            409
        );
    }

    if (
        !in_array(
            $pedido["control_stock"],
            ["reintegrado", "no_descontado"],
            true
        )
    ) {
        rechazarReembolsoWebpay(
            "Primero debe confirmarse la recepción y reposición de los productos.",
            409
        );
    }

    $stmt = $conexion->prepare(
        "SELECT id_pago, token_ws, buy_order, monto, estado
         FROM pagos
         WHERE id_pedido = ?
           AND proveedor = 'webpay'
         ORDER BY id_pago DESC
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $pago = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$pago ||
        $pago["estado"] !== "aprobado" ||
        trim((string) $pago["token_ws"]) === "" ||
        (int) $pago["monto"] !== (int) $pedido["total"] ||
        (int) $pago["monto"] <= 0
    ) {
        rechazarReembolsoWebpay(
            "No existe un pago Webpay aprobado compatible con este pedido.",
            409
        );
    }

    $idPago = (int) $pago["id_pago"];
    $token = trim($pago["token_ws"]);
    $monto = (int) $pago["monto"];
    $referencia = trim((string) $pago["buy_order"]);

    $stmt = $conexion->prepare(
        "SELECT id_reembolso, estado, clave_operacion_manual
         FROM reembolsos
         WHERE id_pedido = ? OR id_pago = ?
         ORDER BY id_reembolso DESC
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("ii", $idPedido, $idPago);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existente) {
        rechazarReembolsoWebpay(
            $existente["estado"] === "reembolsado"
                ? "Este pedido ya fue reembolsado."
                : "Ya existe un proceso de reembolso. Revíselo antes de continuar.",
            409
        );
    }

    $stmt = $conexion->prepare(
        "INSERT INTO reembolsos (
            id_pedido, id_pago, token_ws, monto, motivo,
            estado_pedido_anterior, estado_pago_anterior,
            estado, id_usuario_reembolso, medio_devolucion,
            referencia_devolucion, clave_operacion_manual
         ) VALUES (
            ?, ?, ?, ?, ?, 'cancelacion_solicitada', 'aprobado',
            'procesando', ?, 'webpay', ?, ?
         )"
    );
    $stmt->bind_param(
        "iisisiss",
        $idPedido,
        $idPago,
        $token,
        $monto,
        $motivo,
        $idUsuario,
        $referencia,
        $clave
    );
    $stmt->execute();
    $idReembolso = (int) $conexion->insert_id;
    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE pagos
         SET estado = 'reembolso_pendiente'
         WHERE id_pago = ? AND estado = 'aprobado'"
    );
    $stmt->bind_param("i", $idPago);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        rechazarReembolsoWebpay("El pago cambió durante el proceso.", 409);
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE pedidos
         SET estado_pago = 'reembolso_pendiente'
         WHERE id_pedido = ?
           AND estado = 'cancelacion_solicitada'
           AND estado_pago = 'aprobado'"
    );
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        rechazarReembolsoWebpay("El pedido cambió durante el proceso.", 409);
    }
    $stmt->close();

    $conexion->commit();
    $transaccionIniciada = false;
    $procesoPendiente = true;
    $conexion->close();
    $conexion = null;

    $respuesta = obtenerTransaccionWebpay()->refund($token, $monto);

    $tipo = method_exists($respuesta, "getType")
        ? strtoupper(trim((string) $respuesta->getType()))
        : "";
    $responseCode = method_exists($respuesta, "getResponseCode")
        ? (int) $respuesta->getResponseCode()
        : null;
    $montoAnulado = method_exists($respuesta, "getNullifiedAmount")
        ? (int) round($respuesta->getNullifiedAmount() ?? 0)
        : 0;
    $saldo = method_exists($respuesta, "getBalance")
        ? (int) round($respuesta->getBalance() ?? 0)
        : null;

    $respuestaJson = json_encode([
        "type" => $tipo,
        "response_code" => $responseCode,
        "nullified_amount" => $montoAnulado,
        "balance" => $saldo
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($tipo !== "REVERSED" && $responseCode !== 0) {
        throw new RuntimeException(
            "Webpay no confirmó el reembolso.",
            502
        );
    }

    $reembolsoWebpayConfirmado = true;

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmt = $conexion->prepare(
        "SELECT estado, estado_pago
         FROM pedidos WHERE id_pedido = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $estadoPedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT estado FROM pagos WHERE id_pago = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $idPago);
    $stmt->execute();
    $estadoPago = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT estado FROM reembolsos
         WHERE id_reembolso = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $idReembolso);
    $stmt->execute();
    $estadoReembolso = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$estadoPedido || !$estadoPago || !$estadoReembolso ||
        $estadoPedido["estado"] !== "cancelacion_solicitada" ||
        $estadoPedido["estado_pago"] !== "reembolso_pendiente" ||
        $estadoPago["estado"] !== "reembolso_pendiente" ||
        $estadoReembolso["estado"] !== "procesando"
    ) {
        throw new RuntimeException(
            "Webpay confirmó la devolución, pero los estados locales requieren revisión.",
            500
        );
    }

    $stmt = $conexion->prepare(
        "UPDATE reembolsos
         SET estado = 'reembolsado', respuesta_transbank = ?,
             mensaje_error = NULL, fecha_procesamiento = NOW()
         WHERE id_reembolso = ? AND estado = 'procesando'"
    );
    $stmt->bind_param("si", $respuestaJson, $idReembolso);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException("No se confirmó el registro del reembolso.");
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE pagos SET estado = 'reembolsado'
         WHERE id_pago = ? AND estado = 'reembolso_pendiente'"
    );
    $stmt->bind_param("i", $idPago);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException("No se confirmó el estado del pago.");
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE pedidos
         SET estado = 'cancelado', estado_pago = 'reembolsado'
         WHERE id_pedido = ?
           AND estado = 'cancelacion_solicitada'
           AND estado_pago = 'reembolso_pendiente'"
    );
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException("No se confirmó el estado del pedido.");
    }
    $stmt->close();

    /* La comisión se revierte únicamente tras la confirmación de Webpay. */
    $reversionComision = revertirComisionVenta(
        $conexion,
        $idPedido,
        $idReembolso
    );

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Webpay confirmó el reembolso total.",
        "datos" => [
            "id_pedido" => $idPedido,
            "id_reembolso" => $idReembolso,
            "monto" => $monto,
            "estado" => "cancelado",
            "estado_pago" => "reembolsado",
            "comision" => $reversionComision
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log(
        "Error reembolso Webpay administrativo: " .
        $error->getMessage()
    );

    if ($procesoPendiente && $idReembolso > 0) {
        try {
            $conexionError = conexion();
            $conexionError->set_charset("utf8mb4");
            $mensajeError = mb_substr($error->getMessage(), 0, 500);
            $stmtError = $conexionError->prepare(
                "UPDATE reembolsos
                 SET estado = 'error', mensaje_error = ?,
                     fecha_procesamiento = NOW()
                 WHERE id_reembolso = ? AND estado = 'procesando'"
            );
            $stmtError->bind_param("si", $mensajeError, $idReembolso);
            $stmtError->execute();
            $stmtError->close();
            $conexionError->close();
        } catch (Throwable $errorRegistro) {
            error_log(
                "Error registrando fallo del reembolso administrativo: " .
                $errorRegistro->getMessage()
            );
        }
    }

    $codigo = 500;
    $mensaje = $reembolsoWebpayConfirmado
        ? "Webpay confirmó la devolución, pero el registro local requiere revisión. No repita el reembolso."
        : ($procesoPendiente
            ? "El reembolso quedó pendiente de revisión. No repita la operación."
            : "No fue posible iniciar el reembolso.");

    if (
        !$procesoPendiente &&
        $error instanceof RuntimeException &&
        in_array($error->getCode(), [400, 403, 404, 405, 409], true)
    ) {
        $codigo = $error->getCode();
        $mensaje = $error->getMessage();
    } elseif ($procesoPendiente) {
        $codigo = 502;
    }

    http_response_code($codigo);

    echo json_encode([
        "ok" => false,
        "reembolso_pendiente" => $procesoPendiente,
        "reembolso_confirmado_webpay" => $reembolsoWebpayConfirmado,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>
