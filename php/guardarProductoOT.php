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

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    $idOT = intval($_POST["id_ot"] ?? 0);
    $idProducto = intval($_POST["id_producto"] ?? 0);
    $cantidadSolicitada = intval($_POST["cantidad"] ?? 0);
    $idUsuario = intval($_SESSION["id_usuario"] ?? 0);

    if ($idOT <= 0) {
        throw new RuntimeException("La orden de trabajo no es válida.");
    }

    if ($idProducto <= 0) {
        throw new RuntimeException("El producto seleccionado no es válido.");
    }

    if ($cantidadSolicitada <= 0 || $cantidadSolicitada > 1000) {
        throw new RuntimeException("La cantidad solicitada no es válida.");
    }

    if ($idUsuario <= 0) {
        http_response_code(401);
        throw new RuntimeException("No se pudo identificar al usuario.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    /* =====================================================
       VALIDAR USUARIO DESDE LA BASE DE DATOS
    ===================================================== */

    $stmtUsuario = $conexion->prepare(
        "SELECT id_usuario, rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtUsuario->bind_param("i", $idUsuario);
    $stmtUsuario->execute();

    $usuario = $stmtUsuario
        ->get_result()
        ->fetch_assoc();

    $stmtUsuario->close();

    if (!$usuario) {
        http_response_code(403);
        throw new RuntimeException("El usuario no existe.");
    }

    if (strtolower(trim((string) $usuario["estado"])) !== "activo") {
        http_response_code(403);
        throw new RuntimeException("El usuario está inactivo.");
    }

    $rol = strtolower(trim((string) $usuario["rol"]));

    if (!in_array($rol, ["administrador", "mecanico"], true)) {
        http_response_code(403);
        throw new RuntimeException(
            "No tiene permisos para agregar productos a la orden."
        );
    }

    /* =====================================================
       VALIDAR Y BLOQUEAR ORDEN
    ===================================================== */

    $stmtOT = $conexion->prepare(
        "SELECT id_ot, numeroOT, estado
         FROM orden_trabajo
         WHERE id_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtOT->bind_param("i", $idOT);
    $stmtOT->execute();

    $orden = $stmtOT
        ->get_result()
        ->fetch_assoc();

    $stmtOT->close();

    if (!$orden) {
        throw new RuntimeException("La orden de trabajo no fue encontrada.");
    }

    $estadoOT = strtolower(trim((string) $orden["estado"]));

    if (!in_array($estadoOT, ["abierta", "en proceso"], true)) {
        throw new RuntimeException(
            "No se pueden agregar productos a una orden terminada o facturada."
        );
    }

    /* =====================================================
       OBTENER Y BLOQUEAR PRODUCTO
    ===================================================== */

    $stmtProducto = $conexion->prepare(
        "SELECT
            id_producto,
            nombre,
            marca,
            cantidad,
            compra,
            precio,
            precio_oferta,
            inicio_oferta,
            fin_oferta,
            CASE
                WHEN precio_oferta IS NOT NULL
                 AND precio_oferta > 0
                 AND precio_oferta < precio
                 AND (inicio_oferta IS NULL OR inicio_oferta <= NOW())
                 AND (fin_oferta IS NULL OR fin_oferta >= NOW())
                THEN precio_oferta
                ELSE precio
            END AS precio_venta
         FROM productos
         WHERE id_producto = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtProducto->bind_param("i", $idProducto);
    $stmtProducto->execute();

    $producto = $stmtProducto
        ->get_result()
        ->fetch_assoc();

    $stmtProducto->close();

    if (!$producto) {
        throw new RuntimeException("El producto no fue encontrado.");
    }

    $stockAnterior = intval($producto["cantidad"] ?? 0);
    $precioUnitario = intval($producto["precio_venta"] ?? 0);
    $costoUnitario = intval($producto["compra"] ?? 0);

    if ($cantidadSolicitada > $stockAnterior) {
        throw new RuntimeException(
            'Solo quedan ' .
            $stockAnterior .
            ' unidades de "' .
            $producto["nombre"] .
            '".'
        );
    }

    if ($precioUnitario <= 0) {
        throw new RuntimeException("El producto no tiene un precio de venta válido.");
    }

    if ($costoUnitario <= 0) {
        throw new RuntimeException(
            "El producto no tiene un costo de compra válido. Corríjalo antes de utilizarlo."
        );
    }

    if (
        $precioUnitario > intdiv(PHP_INT_MAX, $cantidadSolicitada) ||
        $costoUnitario > intdiv(PHP_INT_MAX, $cantidadSolicitada)
    ) {
        throw new RuntimeException("Los importes calculados son demasiado altos.");
    }

    $totalLinea = $precioUnitario * $cantidadSolicitada;
    $costoTotal = $costoUnitario * $cantidadSolicitada;
    $stockResultante = $stockAnterior - $cantidadSolicitada;

    /* =====================================================
       DESCONTAR STOCK INMEDIATAMENTE
    ===================================================== */

    $stmtStock = $conexion->prepare(
        "UPDATE productos
         SET cantidad = cantidad - ?
         WHERE id_producto = ?
           AND cantidad >= ?"
    );

    $stmtStock->bind_param(
        "iii",
        $cantidadSolicitada,
        $idProducto,
        $cantidadSolicitada
    );

    $stmtStock->execute();

    if ($stmtStock->affected_rows !== 1) {
        $stmtStock->close();
        throw new RuntimeException(
            "El stock cambió mientras se agregaba el producto. Inténtelo nuevamente."
        );
    }

    $stmtStock->close();

    /* =====================================================
       CREAR LÍNEA DE LA ORDEN
    ===================================================== */

    $descripcion = trim((string) $producto["nombre"]);

    $stmtLinea = $conexion->prepare(
        "INSERT INTO orden_trabajo_productos (
            id_ot,
            id_producto,
            id_usuario,
            descripcion,
            cantidad,
            precio,
            total,
            costo_unitario,
            costo_total,
            stock_descontado,
            fecha_descuento_stock
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())"
    );

    $stmtLinea->bind_param(
        "iiisiiiii",
        $idOT,
        $idProducto,
        $idUsuario,
        $descripcion,
        $cantidadSolicitada,
        $precioUnitario,
        $totalLinea,
        $costoUnitario,
        $costoTotal
    );

    $stmtLinea->execute();

    $idProductoOT = intval($conexion->insert_id);

    $stmtLinea->close();

    /* =====================================================
       REGISTRAR MOVIMIENTO DE INVENTARIO
    ===================================================== */

    $motivo = "Producto asignado a orden de trabajo";
    $referencia = substr((string) $orden["numeroOT"], 0, 100);
    $claveOperacion = substr(
        "OT-PRODUCTO-" . $idProductoOT . "-SALIDA",
        0,
        150
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

    $stmtMovimiento->bind_param(
        "iiiiiiisss",
        $idProducto,
        $idUsuario,
        $idOT,
        $cantidadSolicitada,
        $stockAnterior,
        $stockResultante,
        $costoUnitario,
        $motivo,
        $referencia,
        $claveOperacion
    );

    $stmtMovimiento->execute();
    $stmtMovimiento->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Producto agregado y descontado del stock correctamente.",
        "datos" => [
            "id_producto_ot" => $idProductoOT,
            "id_ot" => $idOT,
            "id_producto" => $idProducto,
            "producto" => $descripcion,
            "cantidad" => $cantidadSolicitada,
            "precio_unitario" => $precioUnitario,
            "total" => $totalLinea,
            "costo_unitario" => $costoUnitario,
            "costo_total" => $costoTotal,
            "stock_anterior" => $stockAnterior,
            "stock_resultante" => $stockResultante,
            "stock_descontado" => true
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error guardando producto en OT: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible agregar el producto a la orden.";
    } else {
        if (http_response_code() < 400) {
            http_response_code(400);
        }

        $mensaje = $error->getMessage();
    }

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
