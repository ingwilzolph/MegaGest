<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";
require_once __DIR__ . "/reintegrarStockPedido.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    $idPedido = intval(
        $_POST["id_pedido"] ?? 0
    );

    $nuevoEstado = trim(
        $_POST["estado"] ?? ""
    );

    if ($idPedido <= 0) {
        throw new Exception(
            "El pedido no es válido."
        );
    }

    $estadosPermitidos = [
        "pendiente_pago",
        "pagado",
        "preparando",
        "listo_retiro",
        "enviado",
        "entregado",
        "cancelacion_solicitada",
        "cancelado"
    ];

    if (
        !in_array(
            $nuevoEstado,
            $estadosPermitidos,
            true
        )
    ) {
        throw new Exception(
            "El estado seleccionado no es válido."
        );
    }

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       OBTENER Y BLOQUEAR PEDIDO
    ===================================================== */

    $sqlPedido = "
        SELECT
            id_pedido,
            numero_pedido,
            canal,
            tipo_entrega,
            estado,
            estado_pago
        FROM pedidos
        WHERE id_pedido = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtPedido = $conexion->prepare(
        $sqlPedido
    );

    $stmtPedido->bind_param(
        "i",
        $idPedido
    );

    $stmtPedido->execute();

    $resultadoPedido =
        $stmtPedido->get_result();

    if ($resultadoPedido->num_rows === 0) {

        $stmtPedido->close();

        throw new Exception(
            "El pedido no fue encontrado."
        );
    }

    $pedido = $resultadoPedido->fetch_assoc();

    $stmtPedido->close();

    $estadoActual = $pedido["estado"];
    $estadoPago = $pedido["estado_pago"];
    $tipoEntrega = $pedido["tipo_entrega"];

    /* =====================================================
    SOLICITAR CANCELACIÓN DESDE EL PANEL
    ===================================================== */

    if ($nuevoEstado === "cancelacion_solicitada") {

        $rolSolicitante = strtolower(
            trim((string) ($_SESSION["rol"] ?? ""))
        );

        $canalPedido = $pedido["canal"];

        $rolesQueSolicitan = $canalPedido === "online"
            ? ["administrador"]
            : ["administrador", "vendedor"];

        if (!in_array($rolSolicitante, $rolesQueSolicitan, true)) {
            http_response_code(403);

            throw new Exception(
                "No tiene permisos para solicitar la cancelación."
            );
        }

        if (!in_array($canalPedido, ["presencial", "online"], true)) {
            http_response_code(409);

            throw new Exception(
                "El canal del pedido no permite esta acción."
            );
        }

        /*
        * Un segundo envío no vuelve a modificar el pedido.
        * Los permisos y el canal ya fueron comprobados.
        */

        if ($estadoActual === "cancelacion_solicitada") {

            $conexion->commit();
            $transaccionIniciada = false;

            echo json_encode([
                "ok" => true,
                "mensaje" =>
                    "El pedido ya tiene una solicitud de cancelación."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $estadosCancelables = $canalPedido === "online"
            ? [
                "pagado",
                "preparando",
                "listo_retiro",
                "enviado",
                "entregado"
            ]
            : [
                "pagado",
                "preparando",
                "listo_retiro",
                "entregado"
            ];

        if (
            !in_array(
                $estadoActual,
                $estadosCancelables,
                true
            )
        ) {
            http_response_code(409);

            throw new Exception(
                "El estado actual no permite solicitar la cancelación."
            );
        }

        if ($estadoPago !== "aprobado") {
            http_response_code(409);

            throw new Exception(
                "La venta no tiene un pago aprobado disponible para este proceso."
            );
        }

        $idUsuarioReposicion = (int) ($_SESSION["id_usuario"] ?? 0);

        $productosRecibidos = ($_POST["productos_recibidos"] ?? "") === "1";

        reintegrarStockPedido($conexion, $idPedido, $idUsuarioReposicion, $productosRecibidos);

        /*
        * El pedido ya está bloqueado con FOR UPDATE.
        * Solo cambiamos su estado: no tocamos pagos ni stock.
        */

        $stmtSolicitud = $conexion->prepare(
        "UPDATE pedidos
        SET estado_antes_cancelacion = ?,
            estado = 'cancelacion_solicitada'
        WHERE id_pedido = ?
        AND estado = ?
        AND estado_pago = 'aprobado'"
    );

    $stmtSolicitud->bind_param(
        "sis",
        $estadoActual,
        $idPedido,
        $estadoActual
    );

    $stmtSolicitud->execute();

    if ($stmtSolicitud->affected_rows !== 1) {
        $stmtSolicitud->close();

        throw new RuntimeException(
            "No se pudo registrar la cancelación. Se revertirán los cambios de esta operación."
        );
    }

    $stmtSolicitud->close();

    $conexion->commit();
    $transaccionIniciada = false;

        echo json_encode([
            "ok" => true,
            "mensaje" =>
                "Cancelación solicitada. El reembolso debe gestionarlo un administrador o cajero.",
            "datos" => [
                "id_pedido" => $idPedido,
                "numero_pedido" => $pedido["numero_pedido"],
                "estado_anterior" => $estadoActual,
                "estado_actual" => "cancelacion_solicitada"
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /* =====================================================
       MISMO ESTADO
    ===================================================== */

    if ($nuevoEstado === $estadoActual) {

        $conexion->commit();

        $transaccionIniciada = false;

        echo json_encode([
            "ok" => true,
            "mensaje" =>
                "El pedido ya se encuentra en ese estado."
        ]);

        exit;
    }

    /* =====================================================
       ESTADOS FINALES
    ===================================================== */

    if ($estadoActual === "entregado") {
        throw new Exception(
            "Un pedido entregado ya no puede modificarse."
        );
    }

    if ($estadoActual === "cancelado") {
        throw new Exception(
            "Un pedido cancelado ya no puede modificarse."
        );
    }

    /* =====================================================
       PROTEGER ESTADO PAGADO
    ===================================================== */

    if (
        $nuevoEstado === "pagado" &&
        $estadoPago !== "aprobado"
    ) {
        throw new Exception(
            "No puede marcar manualmente este pedido como pagado. El pago todavía no está aprobado."
        );
    }

    /*
     * Un pedido sin pago aprobado no puede prepararse,
     * entregarse ni marcarse como listo.
     */

    $estadosQueExigenPago = [
        "pagado",
        "preparando",
        "listo_retiro",
        "enviado",
        "entregado"
    ];

    if (
        in_array(
            $nuevoEstado,
            $estadosQueExigenPago,
            true
        ) &&
        $estadoPago !== "aprobado"
    ) {
        throw new Exception(
            "El pedido no puede avanzar porque su pago no está aprobado."
        );
    }

    /*
     * Un pedido pagado no debe cancelarse directamente.
     * Primero se debe realizar el reembolso en Webpay.
     */

    if (
        $nuevoEstado === "cancelado" &&
        $estadoPago === "aprobado"
    ) {
        throw new Exception(
            "El pedido tiene un pago aprobado. Primero debe realizar el reembolso antes de cancelarlo."
        );
    }

    /* =====================================================
       VALIDAR TRANSICIÓN
    ===================================================== */

    $transiciones = [

        "pendiente_pago" => [
            "cancelado"
        ],

        "pagado" => [
            "preparando"
        ],

        "preparando" => [
            "listo_retiro",
            "enviado"
        ],

        "listo_retiro" => [
            "entregado"
        ],

        "enviado" => [
            "entregado"
        ]
    ];

    $transicionesDisponibles =
        $transiciones[$estadoActual] ?? [];

    if (
        !in_array(
            $nuevoEstado,
            $transicionesDisponibles,
            true
        )
    ) {
        throw new Exception(
            obtenerMensajeTransicionPedido(
                $estadoActual,
                $nuevoEstado
            )
        );
    }

    /* =====================================================
       VALIDAR TIPO DE ENTREGA
    ===================================================== */

    if (
        $nuevoEstado === "enviado" &&
        $tipoEntrega !== "despacho"
    ) {
        throw new Exception(
            "Un pedido con retiro en tienda no puede marcarse como enviado."
        );
    }

    if (
        $nuevoEstado === "listo_retiro" &&
        $tipoEntrega !== "retiro"
    ) {
        throw new Exception(
            "Un pedido con despacho no puede marcarse como listo para retirar."
        );
    }


    /* =====================================================
       VALIDAR PERMISOS POR ROL
    ===================================================== */

    $rolUsuario = strtolower(
        trim((string) ($_SESSION["rol"] ?? ""))
    );

    $permisosPorEstado = [

        "preparando" => [
            "administrador",
            "vendedor",
            "bodeguero"
        ],

        "listo_retiro" => [
            "administrador",
            "vendedor",
            "bodeguero"
        ],

        "enviado" => [
            "administrador",
            "vendedor"
        ],

        "cancelado" => [
            "administrador",
            "vendedor"
        ]
    ];

    /*
     * Los permisos de entrega dependen del método
     * seleccionado en el pedido.
     */

    if ($tipoEntrega === "despacho") {

        $permisosPorEstado["entregado"] = [
            "administrador",
            "chofer"
        ];

    } elseif ($tipoEntrega === "retiro") {

        $permisosPorEstado["entregado"] = [
            "administrador",
            "vendedor",
            "cajero"
        ];
    }

    /*
     * Un estado o rol no reconocido no concede acceso.
     * El rol se obtiene de la sesión, nunca del formulario.
     */

    $rolesAutorizados =
        $permisosPorEstado[$nuevoEstado] ?? [];

    if (
        !in_array(
            $rolUsuario,
            $rolesAutorizados,
            true
        )
    ) {

        http_response_code(403);

        throw new Exception(
            "No tiene permisos para realizar este cambio de estado."
        );
    }

    /* =====================================================
       ACTUALIZAR
    ===================================================== */

    $sqlActualizar = "
        UPDATE pedidos
        SET estado = ?
        WHERE id_pedido = ?
    ";

    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );

    $stmtActualizar->bind_param(
        "si",
        $nuevoEstado,
        $idPedido
    );

    $stmtActualizar->execute();

    $stmtActualizar->close();

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "Estado del pedido actualizado correctamente.",

        "datos" => [
            "id_pedido" => $idPedido,
            "numero_pedido" =>
                $pedido["numero_pedido"],
            "estado_anterior" => $estadoActual,
            "estado_actual" => $nuevoEstado
        ]
    ]);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error modificando estado pedido: " .
        $error->getMessage()
    );

        if ($error instanceof mysqli_sql_exception) {

        http_response_code(500);

        $mensaje =
            "No fue posible actualizar el pedido.";

    } else {

        if (http_response_code() < 400) {
            http_response_code(400);
        }

        $mensaje = $error->getMessage();
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

/* =====================================================
   MENSAJE DE TRANSICIÓN
===================================================== */

function obtenerMensajeTransicionPedido(
    string $estadoActual,
    string $nuevoEstado
): string {

    $nombres = [
        "pendiente_pago" => "Pendiente de pago",
        "pagado" => "Pagado",
        "preparando" => "Preparando",
        "listo_retiro" => "Listo para retirar",
        "enviado" => "Enviado",
        "entregado" => "Entregado",
        "cancelado" => "Cancelado"
    ];

    $actual =
        $nombres[$estadoActual] ??
        $estadoActual;

    $nuevo =
        $nombres[$nuevoEstado] ??
        $nuevoEstado;

    return
        'No se puede cambiar el pedido de "' .
        $actual .
        '" a "' .
        $nuevo .
        '".';
}

?>
