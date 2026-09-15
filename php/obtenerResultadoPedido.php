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

    $idCliente =
        exigirSesionCliente();

    $numeroPedido = trim(
        $_GET["pedido"] ?? ""
    );

    if (
        $numeroPedido === "" ||
        strlen($numeroPedido) > 50
    ) {
        throw new Exception(
            "El número de pedido no es válido."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            numero_pedido,
            tipo_entrega,
            region,
            comuna,
            direccion,
            referencia_direccion,
            costo_despacho,
            total,
            estado,
            estado_pago
        FROM pedidos
        WHERE numero_pedido = ?
          AND id_cliente = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "si",
        $numeroPedido,
        $idCliente
    );

    $stmt->execute();

    $pedido =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$pedido) {

        http_response_code(404);

        echo json_encode([
            "ok" => false,
            "mensaje" =>
                "El pedido no fue encontrado."
        ]);

        exit;
    }

    $resultado = determinarResultadoPedido(
        $pedido["estado"],
        $pedido["estado_pago"]
    );

    echo json_encode([
        "ok" => true,

        "resultado" => $resultado,

        "pedido" => [
            "numero_pedido" =>
                $pedido["numero_pedido"],

            "tipo_entrega" =>
                $pedido["tipo_entrega"],

            "region" =>
                $pedido["region"],

            "comuna" =>
                $pedido["comuna"],

            "direccion" =>
                $pedido["direccion"],

            "referencia_direccion" =>
                $pedido[
                    "referencia_direccion"
                ],

            "costo_despacho" =>
                intval(
                    $pedido[
                        "costo_despacho"
                    ]
                ),

            "total" =>
                intval($pedido["total"]),

            "estado" =>
                $pedido["estado"],

            "estado_pago" =>
                $pedido["estado_pago"]
        ]
    ]);

} catch (Throwable $error) {

    error_log(
        "Error obteniendo resultado: " .
        $error->getMessage()
    );

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" =>
            $error instanceof
                mysqli_sql_exception
                ? "No fue posible consultar el pedido."
                : $error->getMessage()
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

function determinarResultadoPedido(
    string $estado,
    string $estadoPago
): string {

    if (
        $estadoPago === "reembolsado"
    ) {
        return "reembolsado";
    }

    if (
        $estadoPago ===
        "reembolso_pendiente"
    ) {
        return "reembolso_pendiente";
    }

    if (
        $estadoPago === "aprobado" &&
        in_array(
            $estado,
            [
                "pagado",
                "preparando",
                "listo_retiro",
                "enviado",
                "entregado"
            ],
            true
        )
    ) {
        return "aprobado";
    }

    if ($estadoPago === "rechazado") {
        return "rechazado";
    }

    if ($estadoPago === "anulado") {
        return "anulado";
    }

    return "error";
}

?>