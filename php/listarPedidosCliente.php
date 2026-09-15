<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";
require_once "sesionCliente.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    $idCliente =
        exigirSesionCliente();

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /* =====================================================
       LISTAR PEDIDOS
    ===================================================== */

    $sqlPedidos = "
        SELECT
            id_pedido,
            numero_pedido,
            numero_cotizacion,
            canal,
            tipo_entrega,
            region,
            comuna,
            direccion,
            referencia_direccion,
            neto,
            iva,
            subtotal_productos,
            costo_despacho,
            total,
            estado,
            estado_pago,
            observaciones,
            fecha_pedido,
            fecha_actualizacion
        FROM pedidos
        WHERE id_cliente = ?
        ORDER BY
            fecha_pedido DESC,
            id_pedido DESC
        LIMIT 50
    ";

    $stmtPedidos =
        $conexion->prepare(
            $sqlPedidos
        );

    $stmtPedidos->bind_param(
        "i",
        $idCliente
    );

    $stmtPedidos->execute();

    $resultadoPedidos =
        $stmtPedidos->get_result();

    $pedidos = [];

    /* =====================================================
       CONSULTAR DETALLES
    ===================================================== */

    $sqlDetalles = "
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

    $stmtDetalles =
        $conexion->prepare(
            $sqlDetalles
        );

    while (
        $pedido =
            $resultadoPedidos->fetch_assoc()
    ) {

        $idPedido = intval(
            $pedido["id_pedido"]
        );

        $stmtDetalles->bind_param(
            "i",
            $idPedido
        );

        $stmtDetalles->execute();

        $resultadoDetalles =
            $stmtDetalles->get_result();

        $productos = [];
        $cantidadProductos = 0;

        while (
            $detalle =
                $resultadoDetalles
                    ->fetch_assoc()
        ) {

            $cantidad = intval(
                $detalle["cantidad"]
            );

            $cantidadProductos +=
                $cantidad;

            $productos[] = [
                "id_detalle" =>
                    intval(
                        $detalle[
                            "id_detalle"
                        ]
                    ),

                "id_producto" =>
                    $detalle["id_producto"] !==
                    null
                        ? intval(
                            $detalle[
                                "id_producto"
                            ]
                        )
                        : null,

                "sku" =>
                    $detalle["sku"],

                "nombre" =>
                    $detalle[
                        "nombre_producto"
                    ],

                "marca" =>
                    $detalle[
                        "marca_producto"
                    ],

                "precio_unitario" =>
                    intval(
                        $detalle[
                            "precio_unitario"
                        ]
                    ),

                "cantidad" =>
                    $cantidad,

                "total_linea" =>
                    intval(
                        $detalle[
                            "total_linea"
                        ]
                    )
            ];
        }

        $tipoEntrega =
            $pedido["tipo_entrega"];

        $estado =
            $pedido["estado"];

        $estadoPago =
            $pedido["estado_pago"];

        $pedidos[] = [
            "id_pedido" =>
                $idPedido,

            "numero_pedido" =>
                $pedido["numero_pedido"],

            "numero_cotizacion" =>
                $pedido[
                    "numero_cotizacion"
                ],

            "canal" =>
                $pedido["canal"],

            "tipo_entrega" =>
                $tipoEntrega,

            "entrega" => [
                "region" =>
                    $pedido["region"],

                "comuna" =>
                    $pedido["comuna"],

                "direccion" =>
                    $pedido["direccion"],

                "referencia" =>
                    $pedido[
                        "referencia_direccion"
                    ],

                "costo" =>
                    intval(
                        $pedido[
                            "costo_despacho"
                        ]
                    )
            ],

            "neto" =>
                intval($pedido["neto"]),

            "iva" =>
                intval($pedido["iva"]),

            "subtotal_productos" =>
                intval(
                    $pedido[
                        "subtotal_productos"
                    ]
                ),

            "total" =>
                intval($pedido["total"]),

            "estado" =>
                $estado,

            "estado_pago" =>
                $estadoPago,

            "observaciones" =>
                $pedido["observaciones"],

            "fecha_pedido" =>
                $pedido["fecha_pedido"],

            "fecha_actualizacion" =>
                $pedido[
                    "fecha_actualizacion"
                ],

            "cantidad_productos" =>
                $cantidadProductos,

            "productos" =>
                $productos,

            "acciones" =>
                determinarAccionesPedido(
                    $pedido
                )
        ];
    }

    $stmtDetalles->close();
    $stmtPedidos->close();

    echo json_encode([
        "ok" => true,
        "total_pedidos" =>
            count($pedidos),
        "pedidos" => $pedidos
    ]);

} catch (Throwable $error) {

    error_log(
        "Error listando pedidos del cliente: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {
        http_response_code(500);

        $mensaje =
            "No fue posible cargar los pedidos.";

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

/* =====================================================
   ACCIONES SEGÚN ESTADO
===================================================== */

function determinarAccionesPedido(
    array $pedido
): array {

    $canal =
        $pedido["canal"];

    $tipoEntrega =
        $pedido["tipo_entrega"];

    $estado =
        $pedido["estado"];

    $estadoPago =
        $pedido["estado_pago"];

    $puedePagar =
        $canal === "online" &&
        $estado === "pendiente_pago" &&
        in_array(
            $estadoPago,
            [
                "pendiente",
                "rechazado"
            ],
            true
        );

    $puedeCancelar = false;

    if ($estadoPago === "aprobado") {

        if ($tipoEntrega === "retiro") {

            $puedeCancelar = in_array(
                $estado,
                [
                    "pagado",
                    "preparando",
                    "listo_retiro"
                ],
                true
            );

        } elseif (
            $tipoEntrega === "despacho"
        ) {

            $puedeCancelar = in_array(
                $estado,
                [
                    "pagado",
                    "preparando"
                ],
                true
            );
        }
    }

    $puedeImprimir =
        $estadoPago === "aprobado" ||
        $estadoPago === "reembolsado" ||
        $estadoPago ===
            "reembolso_pendiente";

    $puedeVer =
        true;

    return [
        "puede_ver" =>
            $puedeVer,

        "puede_pagar" =>
            $puedePagar,

        "puede_cancelar" =>
            $puedeCancelar,

        "puede_imprimir" =>
            $puedeImprimir
    ];
}
?>