<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";
require_once "configWebpay.php";
require_once "sesionCliente.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    /* =====================================================
       VALIDAR MÉTODO
    ===================================================== */

    if (
        $_SERVER["REQUEST_METHOD"] !==
        "POST"
    ) {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    /* =====================================================
       CLIENTE CONECTADO
    ===================================================== */

    $idCliente =
        exigirSesionCliente();

    /* =====================================================
       LEER PETICIÓN
    ===================================================== */

    $contenido =
        file_get_contents("php://input");

    $datos =
        json_decode($contenido, true);

    if (!is_array($datos)) {
        throw new Exception(
            "Los datos recibidos no son válidos."
        );
    }

    $idPedido = intval(
        $datos["id_pedido"] ?? 0
    );

    if ($idPedido <= 0) {
        throw new Exception(
            "El pedido no es válido."
        );
    }

    /* =====================================================
       CONEXIÓN Y TRANSACCIÓN
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       CONSULTAR PEDIDO DEL CLIENTE
    ===================================================== */

    $sqlPedido = "
        SELECT
            id_pedido,
            numero_pedido,
            id_cliente,
            canal,
            tipo_entrega,
            region,
            comuna,
            costo_despacho,
            id_tarifa_despacho,
            total,
            estado,
            estado_pago
        FROM pedidos
        WHERE id_pedido = ?
          AND id_cliente = ?
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
            "El pedido no fue encontrado o no pertenece a su cuenta."
        );
    }

    /* =====================================================
       VALIDAR CANAL Y ESTADOS
    ===================================================== */

    if ($pedido["canal"] !== "online") {
        throw new Exception(
            "Este pedido no corresponde a una compra en línea."
        );
    }

    if (
        $pedido["estado"] !==
        "pendiente_pago"
    ) {
        throw new Exception(
            "El pedido no está disponible para pago."
        );
    }

    $estadosPagoPermitidos = [
        "pendiente",
        "rechazado"
    ];

    if (
        !in_array(
            $pedido["estado_pago"],
            $estadosPagoPermitidos,
            true
        )
    ) {

        if (
            $pedido["estado_pago"] ===
            "aprobado"
        ) {
            throw new Exception(
                "Este pedido ya se encuentra pagado."
            );
        }

        throw new Exception(
            "El estado del pago no permite iniciar una nueva transacción."
        );
    }

    /* =====================================================
       COMPROBAR PAGO APROBADO
    ===================================================== */

    $sqlPagoAprobado = "
        SELECT id_pago
        FROM pagos
        WHERE id_pedido = ?
          AND estado = 'aprobado'
        LIMIT 1
        FOR UPDATE
    ";

    $stmtPagoAprobado =
        $conexion->prepare(
            $sqlPagoAprobado
        );

    $stmtPagoAprobado->bind_param(
        "i",
        $idPedido
    );

    $stmtPagoAprobado->execute();

    $pagoAprobado =
        $stmtPagoAprobado
            ->get_result()
            ->fetch_assoc();

    $stmtPagoAprobado->close();

    if ($pagoAprobado) {
        throw new Exception(
            "Este pedido ya tiene un pago aprobado."
        );
    }

    /* =====================================================
       VALIDAR MÉTODO DE ENTREGA
    ===================================================== */

    $tipoEntrega =
        $pedido["tipo_entrega"];

    $costoDespacho = intval(
        $pedido["costo_despacho"]
    );

    if ($tipoEntrega === "retiro") {

        if ($costoDespacho !== 0) {
            throw new Exception(
                "El costo de retiro del pedido no es válido."
            );
        }

    } elseif (
        $tipoEntrega === "despacho"
    ) {

        if (
            empty(
                $pedido[
                    "id_tarifa_despacho"
                ]
            ) ||
            $costoDespacho < 5000
        ) {
            throw new Exception(
                "La tarifa de despacho del pedido no es válida."
            );
        }

        if (
            mb_strtolower(
                trim($pedido["region"]),
                "UTF-8"
            ) !==
            mb_strtolower(
                "Región Metropolitana",
                "UTF-8"
            )
        ) {
            throw new Exception(
                "La ubicación de despacho no tiene cobertura."
            );
        }

        if (
            trim($pedido["comuna"]) === ""
        ) {
            throw new Exception(
                "La comuna de despacho no es válida."
            );
        }

    } else {

        throw new Exception(
            "El método de entrega del pedido no es válido."
        );
    }

    /* =====================================================
       MONTO DEFINITIVO
    ===================================================== */

    /*
     * El monto procede exclusivamente de la base
     * de datos. Nunca se recibe desde JavaScript.
     */

    $monto = intval(
        $pedido["total"]
    );

    if ($monto <= 0) {
        throw new Exception(
            "El monto del pedido no es válido."
        );
    }

    /* =====================================================
       IDENTIFICADORES WEBPAY
    ===================================================== */

    $buyOrder =
        "AP" .
        $idPedido .
        "-" .
        date("YmdHis") .
        "-" .
        strtoupper(
            bin2hex(
                random_bytes(2)
            )
        );

    /*
     * Webpay permite un máximo de
     * 26 caracteres en buy_order.
     */

    $buyOrder = substr(
        $buyOrder,
        0,
        26
    );

    $sessionId =
        "CLI-" .
        $idCliente .
        "-PED-" .
        $idPedido .
        "-" .
        bin2hex(
            random_bytes(4)
        );

    /*
     * Reducimos el identificador por seguridad
     * ante límites del proveedor.
     */

    $sessionId = substr(
        $sessionId,
        0,
        61
    );

    /* =====================================================
       CREAR TRANSACCIÓN WEBPAY
    ===================================================== */

    $transaccion =
        obtenerTransaccionWebpay();

    $respuestaWebpay =
        $transaccion->create(
            $buyOrder,
            $sessionId,
            $monto,
            WEBPAY_URL_RETORNO
        );

    $token =
        $respuestaWebpay->getToken();

    $urlPago =
        $respuestaWebpay->getUrl();

    if (
        trim($token) === "" ||
        trim($urlPago) === ""
    ) {
        throw new Exception(
            "Webpay no entregó los datos necesarios para continuar."
        );
    }

    /* =====================================================
       REGISTRAR INTENTO
    ===================================================== */

    $proveedor = "webpay";
    $estadoPago = "iniciado";

    $sqlPago = "
        INSERT INTO pagos (
            id_pedido,
            proveedor,
            buy_order,
            session_id,
            token_ws,
            monto,
            estado
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $stmtPago =
        $conexion->prepare(
            $sqlPago
        );

    $stmtPago->bind_param(
        "issssis",
        $idPedido,
        $proveedor,
        $buyOrder,
        $sessionId,
        $token,
        $monto,
        $estadoPago
    );

    $stmtPago->execute();

    $idPago =
        $conexion->insert_id;

    $stmtPago->close();

    /* =====================================================
       CONFIRMAR REGISTRO
    ===================================================== */

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "Transacción iniciada correctamente.",

        "id_pago" =>
            $idPago,

        "id_pedido" =>
            $idPedido,

        "monto" =>
            $monto,

        "token" =>
            $token,

        "url_pago" =>
            $urlPago
    ]);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error iniciando Webpay: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensaje =
            "No fue posible iniciar el pago.";

    } else {

        if (
            http_response_code() < 400
        ) {
            http_response_code(400);
        }

        $mensaje =
            $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>