<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    /* =====================================================
       VALIDAR SOLICITUD
    ===================================================== */

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    $idOT = intval(
        $_POST["id_ot"] ?? 0
    );

    $observaciones = trim(
        (string) (
            $_POST["observacionesEntrega"] ?? ""
        )
    );

    $idUsuario = intval(
        $_SESSION["id_usuario"] ?? 0
    );

    $rolUsuario = strtolower(
        trim((string) ($_SESSION["rol"] ?? ""))
    );

    if ($idOT <= 0) {
        throw new Exception(
            "La orden de trabajo no es válida."
        );
    }

    if ($idUsuario <= 0) {
        throw new Exception(
            "No se pudo identificar al usuario responsable."
        );
    }

    if (mb_strlen($observaciones) > 5000) {
        throw new Exception(
            "Las observaciones de entrega son demasiado extensas."
        );
    }

    $rolesPermitidos = [
        "administrador",
        "mecanico"
    ];

    if (!in_array(
        $rolUsuario,
        $rolesPermitidos,
        true
    )) {

        http_response_code(403);

        throw new Exception(
            "No tiene permisos para finalizar órdenes de trabajo."
        );
    }

    /* =====================================================
       INICIAR TRANSACCIÓN
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       OBTENER Y BLOQUEAR ORDEN
    ===================================================== */

    $stmtOT = $conexion->prepare(
        "SELECT
            id_ot,
            id_cita,
            numeroOT,
            estado
         FROM orden_trabajo
         WHERE id_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtOT->bind_param("i", $idOT);
    $stmtOT->execute();

    $resultadoOT = $stmtOT->get_result();

    if ($resultadoOT->num_rows === 0) {

        $stmtOT->close();

        throw new Exception(
            "La orden de trabajo no fue encontrada."
        );
    }

    $orden = $resultadoOT->fetch_assoc();
    $stmtOT->close();

    $idCita = intval($orden["id_cita"]);
    $numeroOT = trim(
        (string) ($orden["numeroOT"] ?? "")
    );

    $estadoActual = strtolower(
        trim((string) ($orden["estado"] ?? ""))
    );

    if ($estadoActual === "terminada") {

        $conexion->commit();
        $transaccionIniciada = false;

        echo json_encode(
            [
                "ok" => true,
                "mensaje" =>
                    "La orden de trabajo ya se encuentra terminada."
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    if ($estadoActual === "facturada") {
        throw new Exception(
            "Una orden facturada ya no puede finalizarse nuevamente."
        );
    }

    /* =====================================================
       OBTENER LÍNEAS PENDIENTES DE STOCK
    ===================================================== */

    $stmtLineas = $conexion->prepare(
        "SELECT
            id_producto_ot,
            id_producto,
            descripcion,
            cantidad,
            precio,
            total,
            stock_descontado
         FROM orden_trabajo_productos
         WHERE id_ot = ?
           AND stock_descontado = 0
         ORDER BY id_producto ASC,
                  id_producto_ot ASC
         FOR UPDATE"
    );

    $stmtLineas->bind_param("i", $idOT);
    $stmtLineas->execute();

    $resultadoLineas =
        $stmtLineas->get_result();

    $lineasPorProducto = [];

    while (
        $linea = $resultadoLineas->fetch_assoc()
    ) {

        $idProducto = intval(
            $linea["id_producto"] ?? 0
        );

        $cantidad = intval(
            $linea["cantidad"] ?? 0
        );

        if ($idProducto <= 0 || $cantidad <= 0) {
            throw new Exception(
                "La orden contiene un producto que requiere revisión."
            );
        }

        $linea["id_producto_ot"] = intval(
            $linea["id_producto_ot"]
        );

        $linea["id_producto"] = $idProducto;
        $linea["cantidad"] = $cantidad;
        $linea["precio"] = intval(
            $linea["precio"] ?? 0
        );
        $linea["total"] = intval(
            $linea["total"] ?? 0
        );

        if (!isset($lineasPorProducto[$idProducto])) {
            $lineasPorProducto[$idProducto] = [];
        }

        $lineasPorProducto[$idProducto][] = $linea;
    }

    $stmtLineas->close();

    ksort($lineasPorProducto, SORT_NUMERIC);

    /* =====================================================
       PREPARAR CONSULTAS DE INVENTARIO
    ===================================================== */

    $stmtProducto = $conexion->prepare(
        "SELECT
            id_producto,
            nombre,
            cantidad,
            compra
         FROM productos
         WHERE id_producto = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtDescontar = $conexion->prepare(
        "UPDATE productos
         SET cantidad = cantidad - ?
         WHERE id_producto = ?
           AND cantidad >= ?"
    );

    $stmtActualizarLinea = $conexion->prepare(
        "UPDATE orden_trabajo_productos
         SET costo_unitario = ?,
             costo_total = ?,
             stock_descontado = 1,
             fecha_descuento_stock = NOW()
         WHERE id_producto_ot = ?
           AND stock_descontado = 0"
    );

    $stmtMovimiento = $conexion->prepare(
        "INSERT INTO movimientos_inventario (
            id_producto,
            id_usuario,
            id_pedido,
            id_entrada,
            id_ot,
            tipo_movimiento,
            sentido,
            cantidad,
            stock_anterior,
            stock_resultante,
            costo_unitario,
            motivo,
            referencia,
            clave_operacion
         ) VALUES (
            ?, ?, NULL, NULL, ?,
            'salida_ot', 'salida',
            ?, ?, ?, ?, ?, ?, ?
         )"
    );

    $unidadesDescontadas = 0;
    $costoProductosOT = 0;

    /* =====================================================
       DESCONTAR PRODUCTOS
    ===================================================== */

    foreach (
        $lineasPorProducto
        as $idProducto => $lineas
    ) {

        $stmtProducto->bind_param(
            "i",
            $idProducto
        );

        $stmtProducto->execute();

        $resultadoProducto =
            $stmtProducto->get_result();

        if ($resultadoProducto->num_rows === 0) {
            throw new Exception(
                "Uno de los productos de la orden ya no existe."
            );
        }

        $producto =
            $resultadoProducto->fetch_assoc();

        $stockDisponible = intval(
            $producto["cantidad"] ?? 0
        );

        $costoUnitario = intval(
            $producto["compra"] ?? 0
        );

        $cantidadTotal = 0;

        foreach ($lineas as $linea) {
            $cantidadTotal += $linea["cantidad"];
        }

        if ($cantidadTotal > $stockDisponible) {
            throw new Exception(
                'Stock insuficiente para "' .
                $producto["nombre"] .
                '". Disponible: ' .
                $stockDisponible .
                ', requerido: ' .
                $cantidadTotal .
                '.'
            );
        }

        if ($costoUnitario < 0) {
            throw new Exception(
                'El costo de "' .
                $producto["nombre"] .
                '" no es válido.'
            );
        }

        $stockActual = $stockDisponible;

        foreach ($lineas as $linea) {

            $idProductoOT =
                $linea["id_producto_ot"];

            $cantidadLinea =
                $linea["cantidad"];

            $stockAnterior = $stockActual;
            $stockResultante =
                $stockAnterior - $cantidadLinea;

            $costoTotal =
                $costoUnitario * $cantidadLinea;

            $stmtDescontar->bind_param(
                "iii",
                $cantidadLinea,
                $idProducto,
                $cantidadLinea
            );

            $stmtDescontar->execute();

            if ($stmtDescontar->affected_rows !== 1) {
                throw new Exception(
                    "No fue posible descontar el stock de uno de los productos."
                );
            }

            $stmtActualizarLinea->bind_param(
                "iii",
                $costoUnitario,
                $costoTotal,
                $idProductoOT
            );

            $stmtActualizarLinea->execute();

            if (
                $stmtActualizarLinea->affected_rows !== 1
            ) {
                throw new Exception(
                    "No fue posible registrar el control de stock de la orden."
                );
            }

            $motivo =
                "Producto utilizado en orden de trabajo.";

            $referencia = $numeroOT !== ""
                ? $numeroOT
                : "OT-" . $idOT;

            $claveOperacion =
                "SALIDA-OT-" . $idProductoOT;

            $stmtMovimiento->bind_param(
                "iiiiiiisss",
                $idProducto,
                $idUsuario,
                $idOT,
                $cantidadLinea,
                $stockAnterior,
                $stockResultante,
                $costoUnitario,
                $motivo,
                $referencia,
                $claveOperacion
            );

            $stmtMovimiento->execute();

            $stockActual = $stockResultante;
            $unidadesDescontadas += $cantidadLinea;
            $costoProductosOT += $costoTotal;
        }
    }

    $stmtProducto->close();
    $stmtDescontar->close();
    $stmtActualizarLinea->close();
    $stmtMovimiento->close();

    /* =====================================================
       FINALIZAR CITA Y ORDEN
    ===================================================== */

    $stmtCita = $conexion->prepare(
        "UPDATE citas
        SET estado = 'Finalizada'
        WHERE id_cita = ?"
    );

    $stmtCita->bind_param(
        "i",
        $idCita
    );

    $stmtCita->execute();
    $stmtCita->close();

    $stmtFinalizar = $conexion->prepare(
        "UPDATE orden_trabajo
         SET observacionesEntrega = ?,
             fechaFin = NOW(),
             estado = 'Terminada'
         WHERE id_ot = ?
           AND estado NOT IN ('Terminada', 'Facturada')"
    );

    $stmtFinalizar->bind_param(
        "si",
        $observaciones,
        $idOT
    );

    $stmtFinalizar->execute();

    if ($stmtFinalizar->affected_rows !== 1) {
        throw new Exception(
            "No fue posible finalizar la orden de trabajo."
        );
    }

    $stmtFinalizar->close();

    /* =====================================================
       CONFIRMAR
    ===================================================== */

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode(
        [
            "ok" => true,
            "mensaje" =>
                "Orden finalizada correctamente.",
            "datos" => [
                "id_ot" => $idOT,
                "numero_ot" => $numeroOT,
                "unidades_descontadas" =>
                    $unidadesDescontadas,
                "costo_productos" =>
                    $costoProductosOT
            ]
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error finalizando orden de trabajo: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {

        http_response_code(500);

        $mensaje =
            "No fue posible finalizar la orden de trabajo.";

    } else {

        if (http_response_code() < 400) {
            http_response_code(400);
        }

        $mensaje = $error->getMessage();
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensaje
        ],
        JSON_UNESCAPED_UNICODE
    );

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>
