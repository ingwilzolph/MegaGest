<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;

try {

    $idPedido = intval(
        $_GET["id"] ?? 0
    );

    if ($idPedido <= 0) {
        throw new Exception(
            "El ID del pedido no es válido."
        );
    }

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    /* =====================================================
       INFORMACIÓN DEL PEDIDO
    ===================================================== */

    $sqlPedido = "
        SELECT
            pe.id_pedido,
            pe.numero_pedido,
            pe.id_cliente,
            pe.id_usuario,
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
            pe.control_stock,
            pe.estado_antes_cancelacion,
            pe.observaciones,
            pe.fecha_pedido,
            pe.fecha_actualizacion,

            cl.rut,
            cl.nombre AS nombre_cliente,
            cl.apellido AS apellido_cliente,
            cl.correo AS correo_cliente,
            cl.telefono AS telefono_cliente,

            CONCAT(
                cl.nombre,
                ' ',
                cl.apellido
            ) AS cliente,

            CASE
                WHEN pe.id_usuario IS NULL
                    THEN 'Venta en línea'
                ELSE CONCAT(
                    us.nombre,
                    ' ',
                    us.apellido
                )
            END AS usuario_venta,

            CASE
                WHEN pe.canal = 'online'
                    THEN 'En línea'
                WHEN pe.canal = 'presencial'
                    THEN 'Presencial'
                ELSE pe.canal
            END AS canal_visual,

            CASE
                WHEN pe.tipo_entrega = 'retiro'
                    THEN 'Retiro en tienda'
                WHEN pe.tipo_entrega = 'despacho'
                    THEN 'Despacho'
                ELSE pe.tipo_entrega
            END AS entrega_visual,

            CASE pe.estado
                WHEN 'pendiente_pago'
                    THEN 'Pendiente de pago'
                WHEN 'pagado'
                    THEN 'Pagado'
                WHEN 'preparando'
                    THEN 'Preparando'
                WHEN 'listo_retiro'
                    THEN 'Listo para retirar'
                WHEN 'enviado'
                    THEN 'Enviado'
                WHEN 'entregado'
                    THEN 'Entregado'
                WHEN 'cancelado'
                    THEN 'Cancelado'
                ELSE pe.estado
            END AS estado_visual,

            CASE pe.estado_pago
                WHEN 'pendiente'
                    THEN 'Pendiente'
                WHEN 'aprobado'
                    THEN 'Aprobado'
                WHEN 'rechazado'
                    THEN 'Rechazado'
                WHEN 'anulado'
                    THEN 'Anulado'
                WHEN 'reembolsado'
                    THEN 'Reembolsado'
                ELSE pe.estado_pago
            END AS estado_pago_visual

        FROM pedidos pe

        INNER JOIN clientes cl
            ON cl.id_cliente = pe.id_cliente

        LEFT JOIN login_admin us
            ON us.id_usuario = pe.id_usuario

        WHERE pe.id_pedido = ?

        LIMIT 1
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

    /* =====================================================
       CONVERTIR DATOS NUMÉRICOS
    ===================================================== */

    $camposEnteros = [
        "id_pedido",
        "id_cliente",
        "neto",
        "iva",
        "subtotal_productos",
        "costo_despacho",
        "total"
    ];

    foreach ($camposEnteros as $campo) {

        $pedido[$campo] = intval(
            $pedido[$campo] ?? 0
        );
    }

    $pedido["id_usuario"] =
        $pedido["id_usuario"] !== null
            ? intval($pedido["id_usuario"])
            : null;

    /* =====================================================
       PRODUCTOS
    ===================================================== */

    $sqlProductos = "
        SELECT
            id_detalle,
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
    ";

    $stmtProductos = $conexion->prepare(
        $sqlProductos
    );

    $stmtProductos->bind_param(
        "i",
        $idPedido
    );

    $stmtProductos->execute();

    $resultadoProductos =
        $stmtProductos->get_result();

    $productos = [];

    while (
        $producto =
        $resultadoProductos->fetch_assoc()
    ) {

        $producto["id_detalle"] =
            intval($producto["id_detalle"]);

        $producto["id_producto"] =
            $producto["id_producto"] !== null
                ? intval($producto["id_producto"])
                : null;

        $producto["precio_unitario"] =
            intval($producto["precio_unitario"]);

        $producto["cantidad"] =
            intval($producto["cantidad"]);

        $producto["total_linea"] =
            intval($producto["total_linea"]);

        $productos[] = $producto;
    }

    $stmtProductos->close();

    /* =====================================================
       ÚLTIMO PAGO
    ===================================================== */

    $sqlPago = "
        SELECT
            id_pago,
            proveedor,
            buy_order,
            monto,
            estado,
            response_code,
            authorization_code,
            payment_type_code,
            installments_number,
            transaction_date,
            fecha_registro
        FROM pagos
        WHERE id_pedido = ?
        ORDER BY id_pago DESC
        LIMIT 1
    ";

    $stmtPago = $conexion->prepare(
        $sqlPago
    );

    $stmtPago->bind_param(
        "i",
        $idPedido
    );

    $stmtPago->execute();

    $resultadoPago =
        $stmtPago->get_result();

    $pago = null;

    if ($resultadoPago->num_rows > 0) {

        $pago = $resultadoPago->fetch_assoc();

        $pago["id_pago"] =
            intval($pago["id_pago"]);

        $pago["monto"] =
            intval($pago["monto"]);

        $pago["response_code"] =
            $pago["response_code"] !== null
                ? intval($pago["response_code"])
                : null;

        $pago["installments_number"] =
            $pago["installments_number"] !== null
                ? intval(
                    $pago["installments_number"]
                )
                : null;
    }

    $stmtPago->close();


        /* =====================================================
       ESTADOS DISPONIBLES PARA EL USUARIO
    ===================================================== */

    $rolUsuario = strtolower(
        trim((string) ($_SESSION["rol"] ?? ""))
    );

    $estadoActual = $pedido["estado"];
    $estadoPago = $pedido["estado_pago"];
    $tipoEntrega = $pedido["tipo_entrega"];

    $transiciones = [
        "pendiente_pago" => ["cancelado"],
        "pagado" => ["preparando"],
        "preparando" => ["listo_retiro", "enviado"],
        "listo_retiro" => ["entregado"],
        "enviado" => ["entregado"]
    ];

    $rolesPorEstado = [

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

    if ($tipoEntrega === "despacho") {

        $rolesPorEstado["entregado"] = [
            "administrador",
            "chofer"
        ];

    } elseif ($tipoEntrega === "retiro") {

        $rolesPorEstado["entregado"] = [
            "administrador",
            "vendedor",
            "cajero"
        ];
    }

    $nombresEstados = [
        "preparando" => "Preparando",
        "listo_retiro" => "Listo para retirar",
        "enviado" => "Enviado",
        "entregado" => "Entregado",
        "cancelado" => "Cancelado"
    ];

    $estadosDisponibles = [];

    foreach (
        $transiciones[$estadoActual] ?? []
        as $estadoDestino
    ) {

        /*
         * Comprobar rol.
         */

        if (
            !in_array(
                $rolUsuario,
                $rolesPorEstado[$estadoDestino] ?? [],
                true
            )
        ) {
            continue;
        }

        /*
         * Mantener las mismas reglas de pago
         * que modificarEstadoPedido.php.
         */

        if ($estadoDestino === "cancelado") {

            if ($estadoPago === "aprobado") {
                continue;
            }

        } elseif ($estadoPago !== "aprobado") {

            continue;
        }

        /*
         * Comprobar método de entrega.
         */

        if (
            $estadoDestino === "enviado" &&
            $tipoEntrega !== "despacho"
        ) {
            continue;
        }

        if (
            $estadoDestino === "listo_retiro" &&
            $tipoEntrega !== "retiro"
        ) {
            continue;
        }

        $estadosDisponibles[] = [
            "valor" => $estadoDestino,
            "texto" => $nombresEstados[$estadoDestino]
        ];
    }


    /* Solicitud de cancelación desde el panel. */

    $puedeSolicitarCancelacion = false;
    $estadosCancelablesPanel = [];

    if ($pedido["canal"] === "presencial") {
        $puedeSolicitarCancelacion = in_array(
            $rolUsuario,
            ["administrador", "vendedor"],
            true
        );
        $estadosCancelablesPanel = [
            "pagado",
            "preparando",
            "listo_retiro",
            "entregado"
        ];
    } elseif ($pedido["canal"] === "online") {
        $puedeSolicitarCancelacion =
            $rolUsuario === "administrador";
        $estadosCancelablesPanel = [
            "pagado",
            "preparando",
            "listo_retiro",
            "enviado",
            "entregado"
        ];
    }

    if (
        $puedeSolicitarCancelacion &&
        $estadoPago === "aprobado" &&
        in_array(
            $estadoActual,
            $estadosCancelablesPanel,
            true
        ) &&
        $estadoActual !== "cancelacion_solicitada"
    ) {
        $estadosDisponibles[] = [
            "valor" => "cancelacion_solicitada",
            "texto" => "Solicitar cancelación"
        ];
    }

    /* Etiquetas para mostrar los estados en el detalle. */

    if ($pedido["estado"] === "cancelacion_solicitada") {
        $pedido["estado_visual"] = "Cancelación solicitada";
    }

    if ($pedido["estado_pago"] === "reembolso_pendiente") {
        $pedido["estado_pago_visual"] = "Reembolso pendiente";
    }



    /* =====================================================
    PERMISOS PARA REEMBOLSO PRESENCIAL
    ===================================================== */

    $idUsuarioActual = (int) ($_SESSION["id_usuario"] ?? 0);

    $stmtUsuarioReembolso = $conexion->prepare(
        "SELECT rol, estado
        FROM login_admin
        WHERE id_usuario = ?"
    );

    $stmtUsuarioReembolso->bind_param("i", $idUsuarioActual);
    $stmtUsuarioReembolso->execute();

    $usuarioReembolso = $stmtUsuarioReembolso
        ->get_result()
        ->fetch_assoc();

    $stmtUsuarioReembolso->close();

    $gestionaReembolsos =
        $usuarioReembolso &&
        strtolower(trim($usuarioReembolso["estado"])) === "activo" &&
        in_array(
            strtolower(trim($usuarioReembolso["rol"])),
            ["administrador", "cajero"],
            true
        );

    $stmtHistorialReembolso = $conexion->prepare(
        "SELECT COUNT(*) AS cantidad
        FROM reembolsos
        WHERE id_pedido = ?"
    );

    $stmtHistorialReembolso->bind_param("i", $idPedido);
    $stmtHistorialReembolso->execute();

    $historialReembolso = $stmtHistorialReembolso
        ->get_result()
        ->fetch_assoc();

    $stmtHistorialReembolso->close();

    $sinProcesoReembolso =
        (int) $historialReembolso["cantidad"] === 0;

    $stockPreparadoParaReembolso = in_array(
        $pedido["control_stock"],
        ["reintegrado", "no_descontado"],
        true
    );

    $puedeRegistrarReembolso =
        $gestionaReembolsos &&
        $pedido["estado"] === "cancelacion_solicitada" &&
        $pedido["estado_pago"] === "aprobado" &&
        $sinProcesoReembolso &&
        (
            $pedido["canal"] === "presencial" ||
            (
                $pedido["canal"] === "online" &&
                $stockPreparadoParaReembolso
            )
        );

    $permisosReembolso = [
        "id_usuario" => $idUsuarioActual,
        "gestionar" => (bool) $gestionaReembolsos,
        "registrar" => (bool) $puedeRegistrarReembolso,
        "canal" => $pedido["canal"],
        "tipo" => $pedido["canal"] === "online"
            ? "webpay"
            : "manual",
        "stock_preparado" => (bool) $stockPreparadoParaReembolso,
        "proceso_existente" => !$sinProcesoReembolso
    ];


        echo json_encode([
        "ok" => true,
        "datos" => [
            "pedido" => $pedido,
            "productos" => $productos,
            "pago" => $pago,
            "estados_disponibles" => $estadosDisponibles,
            "permisos_reembolso" => $permisosReembolso
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {

    error_log(
        "Error obteniendo detalle pedido: " .
        $error->getMessage()
    );

    $mensaje = $error instanceof mysqli_sql_exception
        ? "No fue posible cargar el pedido."
        : $error->getMessage();

    http_response_code(400);

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
