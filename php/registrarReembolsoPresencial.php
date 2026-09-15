<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/revertirComisionVenta.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;
$transaccionIniciada = false;

function rechazarReembolsoPresencial(
    string $mensaje,
    int $codigo = 400
): void {
    throw new RuntimeException($mensaje, $codigo);
}

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        rechazarReembolsoPresencial("Método no permitido.", 405);
    }

    /*
     * El JavaScript enviará esta cabecera.
     * No habilitar CORS en este endpoint.
     */
    if (
        ($_SERVER["HTTP_X_MEGAGEST_REEMBOLSO"] ?? "") !== "1"
    ) {
        rechazarReembolsoPresencial(
            "Solicitud no autorizada.",
            403
        );
    }

    $idUsuario = (int) ($_SESSION["id_usuario"] ?? 0);

    if ($idUsuario <= 0) {
        rechazarReembolsoPresencial(
            "No se pudo identificar al usuario.",
            403
        );
    }

    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos)) {
        rechazarReembolsoPresencial("Los datos no son válidos.");
    }

    $idPedido = filter_var(
        $datos["id_pedido"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($idPedido === false) {
        rechazarReembolsoPresencial("El pedido no es válido.");
    }

    foreach (["motivo", "medio", "referencia", "clave_operacion"] as $campo) {
        if (!isset($datos[$campo]) || !is_string($datos[$campo])) {
            rechazarReembolsoPresencial(
                "Debe completar los datos del reembolso."
            );
        }
    }

    $motivo = trim($datos["motivo"]);
    $medio = trim($datos["medio"]);
    $referencia = trim($datos["referencia"]);
    $clave = trim($datos["clave_operacion"]);

    if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 500) {
        rechazarReembolsoPresencial(
            "El motivo debe contener entre 5 y 500 caracteres."
        );
    }

    if (
        !in_array(
            $medio,
            ["efectivo", "transferencia", "reversa_tarjeta"],
            true
        )
    ) {
        rechazarReembolsoPresencial(
            "Seleccione un medio de devolución válido."
        );
    }

    if ($referencia === "" || mb_strlen($referencia) > 150) {
        rechazarReembolsoPresencial(
            "Ingrese una referencia de hasta 150 caracteres."
        );
    }

    if (!preg_match('/^[a-f0-9]{32}$/D', $clave)) {
        rechazarReembolsoPresencial(
            "El identificador de la operación no es válido."
        );
    }

    if (($datos["devolucion_confirmada"] ?? false) !== true) {
        rechazarReembolsoPresencial(
            "Debe confirmar que el dinero ya fue devuelto."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /*
     * Todas las tablas involucradas deben admitir transacciones.
     */
    $resultadoMotores = $conexion->query(
        "SELECT TABLE_NAME, ENGINE
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME IN (
             'login_admin',
             'pedidos',
             'pagos',
             'reembolsos',
             'comisiones_usuarios',
             'liquidaciones_remuneraciones',
             'detalle_liquidacion_comisiones',
             'ajustes_remuneraciones'
         )"
    );

    if ($resultadoMotores->num_rows !== 8) {
        rechazarReembolsoPresencial(
            "La estructura necesaria para el reembolso está incompleta.",
            500
        );
    }

    while ($tabla = $resultadoMotores->fetch_assoc()) {
        if (strcasecmp((string) $tabla["ENGINE"], "InnoDB") !== 0) {
            rechazarReembolsoPresencial(
                "Las tablas del reembolso deben utilizar InnoDB.",
                500
            );
        }
    }

    $conexion->begin_transaction();
    $transaccionIniciada = true;

    /*
     * Comprobar el rol y estado actuales en la base de datos.
     */
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
        rechazarReembolsoPresencial(
            "Solo un administrador o cajero activo puede registrar reembolsos.",
            403
        );
    }

    /*
     * Bloquear el pedido para serializar las solicitudes.
     */
    $stmt = $conexion->prepare(
        "SELECT id_pedido, canal, estado, estado_pago, total
         FROM pedidos
         WHERE id_pedido = ?
         FOR UPDATE"
    );

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pedido) {
        rechazarReembolsoPresencial("El pedido no existe.", 404);
    }

    if ($pedido["canal"] !== "presencial") {
        rechazarReembolsoPresencial(
            "Este registro solo admite ventas presenciales.",
            409
        );
    }

    /*
     * Bloquear los pagos y reembolsos asociados.
     */
    $stmt = $conexion->prepare(
        "SELECT id_pago, proveedor, monto, estado
         FROM pagos
         WHERE id_pedido = ?
         ORDER BY id_pago
         FOR UPDATE"
    );

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $pagos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT id_reembolso, id_pago, monto, estado,
                motivo, id_usuario_reembolso,
                medio_devolucion, referencia_devolucion,
                clave_operacion_manual
         FROM reembolsos
         WHERE id_pedido = ?
         ORDER BY id_reembolso
         FOR UPDATE"
    );

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $reembolsos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    /*
     * Si se perdió la respuesta y se repite la misma solicitud,
     * devolver su resultado sin registrar otra devolución.
     */
    foreach ($reembolsos as $existente) {

        if ($existente["clave_operacion_manual"] !== $clave) {
            continue;
        }

        $mismosDatos =
            (int) $existente["id_usuario_reembolso"] === $idUsuario &&
            $existente["motivo"] === $motivo &&
            $existente["medio_devolucion"] === $medio &&
            $existente["referencia_devolucion"] === $referencia;

        $pagoReembolsado = false;

        foreach ($pagos as $pagoRegistrado) {
            if (
                (int) $pagoRegistrado["id_pago"] ===
                    (int) $existente["id_pago"] &&
                $pagoRegistrado["estado"] === "reembolsado"
            ) {
                $pagoReembolsado = true;
            }
        }

        if (
            !$mismosDatos ||
            $existente["estado"] !== "reembolsado" ||
            $pedido["estado"] !== "cancelado" ||
            $pedido["estado_pago"] !== "reembolsado" ||
            !$pagoReembolsado
        ) {
            rechazarReembolsoPresencial(
                "La operación requiere revisión. No registre otra devolución.",
                409
            );
        }

        $conexion->commit();
        $transaccionIniciada = false;

        echo json_encode([
            "ok" => true,
            "mensaje" => "Este reembolso ya estaba registrado.",
            "datos" => [
                "id_reembolso" => (int) $existente["id_reembolso"],
                "id_pedido" => $idPedido,
                "monto" => (int) $existente["monto"]
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /*
     * Cualquier intento anterior requiere revisión.
     * Un estado "error" no garantiza que no se devolvió dinero.
     */
    if (count($reembolsos) > 0) {
        rechazarReembolsoPresencial(
            "El pedido ya tiene un registro de reembolso. Revíselo antes de continuar.",
            409
        );
    }

    if (
        $pedido["estado"] !== "cancelacion_solicitada" ||
        $pedido["estado_pago"] !== "aprobado"
    ) {
        rechazarReembolsoPresencial(
            "El pedido debe tener cancelación solicitada y pago aprobado.",
            409
        );
    }

    /*
     * Este flujo cubre una devolución total de una venta
     * presencial con un único pago.
     */
    if (count($pagos) !== 1) {
        rechazarReembolsoPresencial(
            "La venta no tiene un único pago. Requiere revisión.",
            409
        );
    }

    $pago = $pagos[0];
    $monto = (int) $pago["monto"];
    $idPago = (int) $pago["id_pago"];

    if (
        $pago["estado"] !== "aprobado" ||
        !in_array(
            $pago["proveedor"],
            ["efectivo", "transferencia", "debito", "credito"],
            true
        ) ||
        $monto <= 0 ||
        $monto !== (int) $pedido["total"] ||
        $monto > 4294967295
    ) {
        rechazarReembolsoPresencial(
            "El pago no es compatible con este registro de devolución total.",
            409
        );
    }

    /*
     * Guardar el registro manual sin inventar un token
     * ni una respuesta de Transbank.
     */
    $stmt = $conexion->prepare(
        "INSERT INTO reembolsos (
            id_pedido,
            id_pago,
            token_ws,
            monto,
            motivo,
            estado_pedido_anterior,
            estado_pago_anterior,
            estado,
            fecha_procesamiento,
            id_usuario_reembolso,
            medio_devolucion,
            referencia_devolucion,
            clave_operacion_manual
         ) VALUES (
            ?, ?, NULL, ?, ?,
            'cancelacion_solicitada',
            'aprobado',
            'reembolsado',
            NOW(),
            ?, ?, ?, ?
         )"
    );

    $stmt->bind_param(
        "iiisisss",
        $idPedido,
        $idPago,
        $monto,
        $motivo,
        $idUsuario,
        $medio,
        $referencia,
        $clave
    );

    $stmt->execute();
    $idReembolso = $conexion->insert_id;
    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE pagos
         SET estado = 'reembolsado'
         WHERE id_pago = ?
         AND estado = 'aprobado'"
    );

    $stmt->bind_param("i", $idPago);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        rechazarReembolsoPresencial(
            "El pago cambió durante la operación.",
            409
        );
    }

    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE pedidos
         SET estado = 'cancelado',
             estado_pago = 'reembolsado'
         WHERE id_pedido = ?
         AND estado = 'cancelacion_solicitada'
         AND estado_pago = 'aprobado'"
    );

    $stmt->bind_param("i", $idPedido);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        rechazarReembolsoPresencial(
            "El pedido cambió durante la operación.",
            409
        );
    }

    $stmt->close();

    $reversionComision = revertirComisionVenta(
        $conexion,
        $idPedido,
        (int) $idReembolso
    );

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Reembolso presencial registrado correctamente.",
        "datos" => [
            "id_reembolso" => $idReembolso,
            "id_pedido" => $idPedido,
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
        "Error registrando reembolso presencial: " .
        $error->getMessage()
    );

    $codigo = 500;
    $mensaje = "No fue posible registrar el reembolso. Revise su estado antes de repetirlo.";

    if (
        $error instanceof RuntimeException &&
        !($error instanceof mysqli_sql_exception) &&
        in_array($error->getCode(), [400, 403, 404, 405, 409, 500], true)
    ) {
        $codigo = $error->getCode();
        $mensaje = $error->getMessage();
    }

    http_response_code($codigo);

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>
