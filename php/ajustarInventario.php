<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;
$transaccionIniciada = false;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new Exception("Método no permitido.");
    }

    $rol = strtolower(trim((string) ($_SESSION["rol"] ?? "")));

    if (!in_array($rol, ["administrador", "vendedor"], true)) {
        http_response_code(403);
        throw new Exception("No tiene permisos para ajustar el inventario.");
    }

    $idUsuario = intval($_SESSION["id_usuario"] ?? 0);

    if ($idUsuario <= 0) {
        throw new Exception("No se pudo identificar al usuario responsable.");
    }

    $contenido = file_get_contents("php://input");
    $datos = json_decode($contenido, true);

    if (!is_array($datos)) {
        throw new Exception("Los datos del ajuste no son válidos.");
    }

    $idProducto = intval($datos["id_producto"] ?? 0);
    $tipoAjuste = strtolower(trim((string) ($datos["tipo_ajuste"] ?? "")));
    $cantidad = intval($datos["cantidad"] ?? 0);
    $motivo = trim((string) ($datos["motivo"] ?? ""));

    $tiposPermitidos = [
        "entrada" => [
            "tipo_movimiento" => "ajuste_entrada",
            "sentido" => "entrada"
        ],
        "salida" => [
            "tipo_movimiento" => "ajuste_salida",
            "sentido" => "salida"
        ],
        "merma" => [
            "tipo_movimiento" => "merma",
            "sentido" => "salida"
        ]
    ];

    if ($idProducto <= 0) {
        throw new Exception("El producto seleccionado no es válido.");
    }

    if (!isset($tiposPermitidos[$tipoAjuste])) {
        throw new Exception("El tipo de ajuste seleccionado no es válido.");
    }

    if ($cantidad <= 0 || $cantidad > 100000) {
        throw new Exception("La cantidad del ajuste no es válida.");
    }

    if (mb_strlen($motivo) < 5) {
        throw new Exception("Debe indicar un motivo de al menos 5 caracteres.");
    }

    if (mb_strlen($motivo) > 500) {
        throw new Exception("El motivo no puede superar los 500 caracteres.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmtUsuario = $conexion->prepare(
        "SELECT id_usuario, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtUsuario->bind_param("i", $idUsuario);
    $stmtUsuario->execute();
    $usuario = $stmtUsuario->get_result()->fetch_assoc();
    $stmtUsuario->close();

    if (!$usuario || strtolower((string) $usuario["estado"]) !== "activo") {
        throw new Exception("El usuario responsable no está disponible.");
    }

    $stmtProducto = $conexion->prepare(
        "SELECT id_producto, sku, nombre, cantidad, compra
         FROM productos
         WHERE id_producto = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtProducto->bind_param("i", $idProducto);
    $stmtProducto->execute();
    $producto = $stmtProducto->get_result()->fetch_assoc();
    $stmtProducto->close();

    if (!$producto) {
        throw new Exception("El producto no fue encontrado.");
    }

    $stockAnterior = intval($producto["cantidad"]);
    $costoUnitario = intval($producto["compra"] ?? 0);
    $esEntrada = $tiposPermitidos[$tipoAjuste]["sentido"] === "entrada";

    if ($esEntrada) {
        if ($stockAnterior > 2147483647 - $cantidad) {
            throw new Exception("El stock resultante supera el límite permitido.");
        }

        $stockResultante = $stockAnterior + $cantidad;
    } else {
        if ($cantidad > $stockAnterior) {
            throw new Exception(
                "No puede retirar {$cantidad} unidades. El stock disponible es {$stockAnterior}."
            );
        }

        $stockResultante = $stockAnterior - $cantidad;
    }

    $stmtActualizar = $conexion->prepare(
        "UPDATE productos
         SET cantidad = ?
         WHERE id_producto = ?
           AND cantidad = ?"
    );

    $stmtActualizar->bind_param(
        "iii",
        $stockResultante,
        $idProducto,
        $stockAnterior
    );
    $stmtActualizar->execute();

    if ($stmtActualizar->affected_rows !== 1) {
        throw new Exception("No fue posible actualizar el stock del producto.");
    }

    $stmtActualizar->close();

    $tipoMovimiento = $tiposPermitidos[$tipoAjuste]["tipo_movimiento"];
    $sentido = $tiposPermitidos[$tipoAjuste]["sentido"];
    $referencia = generarReferenciaAjusteInventario();
    $claveOperacion = $referencia . "-" . $idProducto;

    $stmtMovimiento = $conexion->prepare(
        "INSERT INTO movimientos_inventario (
            id_producto,
            id_usuario,
            id_pedido,
            id_entrada,
            tipo_movimiento,
            sentido,
            cantidad,
            stock_anterior,
            stock_resultante,
            costo_unitario,
            motivo,
            referencia,
            clave_operacion
        )
        VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmtMovimiento->bind_param(
        "iissiiiisss",
        $idProducto,
        $idUsuario,
        $tipoMovimiento,
        $sentido,
        $cantidad,
        $stockAnterior,
        $stockResultante,
        $costoUnitario,
        $motivo,
        $referencia,
        $claveOperacion
    );

    $stmtMovimiento->execute();
    $idMovimiento = intval($conexion->insert_id);
    $stmtMovimiento->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Ajuste de inventario registrado correctamente.",
        "datos" => [
            "id_movimiento" => $idMovimiento,
            "referencia" => $referencia,
            "id_producto" => $idProducto,
            "producto" => $producto["nombre"],
            "tipo_ajuste" => $tipoAjuste,
            "cantidad" => $cantidad,
            "stock_anterior" => $stockAnterior,
            "stock_resultante" => $stockResultante
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error ajustando inventario: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible registrar el ajuste de inventario.";
    } else {
        if (http_response_code() < 400) http_response_code(400);
        $mensaje = $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexion instanceof mysqli) $conexion->close();
}

function generarReferenciaAjusteInventario(): string
{
    return substr(
        "AJ-" . date("Ymd-His") . "-" . strtoupper(bin2hex(random_bytes(3))),
        0,
        30
    );
}

?>
