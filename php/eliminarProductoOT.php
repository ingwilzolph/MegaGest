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

    $idProductoOT = intval(
        $_POST["id_producto_ot"] ?? 0
    );

    $idUsuario = intval(
        $_SESSION["id_usuario"] ?? 0
    );

    if ($idProductoOT <= 0) {
        throw new RuntimeException(
            "La línea de producto no es válida."
        );
    }

    if ($idUsuario <= 0) {
        http_response_code(401);
        throw new RuntimeException(
            "No se pudo identificar al usuario."
        );
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

    /* =====================================================
       OBTENER LA LÍNEA PARA IDENTIFICAR LA ORDEN
    ===================================================== */

    $stmtReferencia = $conexion->prepare(
        "SELECT id_ot
         FROM orden_trabajo_productos
         WHERE id_producto_ot = ?
         LIMIT 1"
    );

    $stmtReferencia->bind_param("i", $idProductoOT);
    $stmtReferencia->execute();

    $referenciaLinea = $stmtReferencia
        ->get_result()
        ->fetch_assoc();

    $stmtReferencia->close();

    if (!$referenciaLinea) {
        throw new RuntimeException(
            "El producto de la orden no fue encontrado."
        );
    }

    $idOT = intval($referenciaLinea["id_ot"] ?? 0);

    /* =====================================================
       BLOQUEAR ORDEN Y VALIDAR ESTADO
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
        throw new RuntimeException(
            "La orden de trabajo no fue encontrada."
        );
    }

    $estadoOT = strtolower(trim((string) $orden["estado"]));

    if (!in_array($estadoOT, ["abierta", "en proceso"], true)) {
        throw new RuntimeException(
            "No se pueden eliminar productos de una orden terminada o facturada."
        );
    }

    /* =====================================================
       BLOQUEAR Y OBTENER LA LÍNEA COMPLETA
    ===================================================== */

    $stmtLinea = $conexion->prepare(
        "SELECT
            id_producto_ot,
            id_ot,
            id_producto,
            id_usuario,
            descripcion,
            cantidad,
            costo_unitario,
            stock_descontado
         FROM orden_trabajo_productos
         WHERE id_producto_ot = ?
           AND id_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtLinea->bind_param(
        "ii",
        $idProductoOT,
        $idOT
    );

    $stmtLinea->execute();

    $linea = $stmtLinea
        ->get_result()
        ->fetch_assoc();

    $stmtLinea->close();

    if (!$linea) {
        throw new RuntimeException(
            "El producto de la orden ya no existe."
        );
    }

    $idUsuarioCreador = intval($linea["id_usuario"] ?? 0);

    if (
        $rol !== "administrador" &&
        $idUsuarioCreador !== $idUsuario
    ) {
        http_response_code(403);
        throw new RuntimeException(
            "Solo puede eliminar los productos que usted agregó a la orden."
        );
    }

    $idProducto = intval($linea["id_producto"] ?? 0);
    $cantidad = intval($linea["cantidad"] ?? 0);
    $stockDescontado = intval($linea["stock_descontado"] ?? 0) === 1;
    $costoUnitario = $linea["costo_unitario"] !== null
        ? intval($linea["costo_unitario"])
        : null;

    if ($idProducto <= 0 || $cantidad <= 0) {
        throw new RuntimeException(
            "La línea requiere revisión de inventario y no puede eliminarse."
        );
    }

    $stockAnterior = null;
    $stockResultante = null;

    /* =====================================================
       DEVOLVER STOCK SI ESTA LÍNEA YA FUE DESCONTADA
    ===================================================== */

    if ($stockDescontado) {

        $stmtProducto = $conexion->prepare(
            "SELECT id_producto, cantidad
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
            throw new RuntimeException(
                "El producto ya no existe y su stock no puede devolverse automáticamente."
            );
        }

        $stockAnterior = intval($producto["cantidad"] ?? 0);

        if ($stockAnterior > 2147483647 - $cantidad) {
            throw new RuntimeException(
                "La devolución supera el límite permitido de existencias."
            );
        }

        $stockResultante = $stockAnterior + $cantidad;

        $stmtStock = $conexion->prepare(
            "UPDATE productos
             SET cantidad = cantidad + ?
             WHERE id_producto = ?
               AND cantidad <= 2147483647 - ?"
        );

        $stmtStock->bind_param(
            "iii",
            $cantidad,
            $idProducto,
            $cantidad
        );

        $stmtStock->execute();

        if ($stmtStock->affected_rows !== 1) {
            $stmtStock->close();
            throw new RuntimeException(
                "No fue posible devolver el producto al stock."
            );
        }

        $stmtStock->close();

        /* =================================================
           REGISTRAR DEVOLUCIÓN EN EL HISTORIAL
        ================================================= */

        $motivo = "Producto eliminado de la orden de trabajo";
        $referencia = substr((string) $orden["numeroOT"], 0, 100);
        $claveOperacion = substr(
            "OT-PRODUCTO-" . $idProductoOT . "-DEVOLUCION",
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
                'devolucion_ot', 'entrada',
                ?, ?, ?, ?, ?, ?, ?
             )"
        );

        $stmtMovimiento->bind_param(
            "iiiiiiisss",
            $idProducto,
            $idUsuario,
            $idOT,
            $cantidad,
            $stockAnterior,
            $stockResultante,
            $costoUnitario,
            $motivo,
            $referencia,
            $claveOperacion
        );

        $stmtMovimiento->execute();
        $stmtMovimiento->close();
    }

    /* =====================================================
       ELIMINAR LA LÍNEA
    ===================================================== */

    $stmtEliminar = $conexion->prepare(
        "DELETE FROM orden_trabajo_productos
         WHERE id_producto_ot = ?"
    );

    $stmtEliminar->bind_param("i", $idProductoOT);
    $stmtEliminar->execute();

    if ($stmtEliminar->affected_rows !== 1) {
        $stmtEliminar->close();
        throw new RuntimeException(
            "No fue posible eliminar el producto de la orden."
        );
    }

    $stmtEliminar->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => $stockDescontado
            ? "Producto eliminado y devuelto al stock correctamente."
            : "Producto eliminado correctamente.",
        "datos" => [
            "id_producto_ot" => $idProductoOT,
            "id_producto" => $idProducto,
            "cantidad_devuelta" => $stockDescontado ? $cantidad : 0,
            "stock_anterior" => $stockAnterior,
            "stock_resultante" => $stockResultante
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
        "Error eliminando producto OT: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible eliminar el producto de la orden.";
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
