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
        throw new Exception("Método no permitido.");
    }

    $rolUsuario = strtolower(
        trim((string) ($_SESSION["rol"] ?? ""))
    );

    if (!in_array(
        $rolUsuario,
        ["administrador", "vendedor"],
        true
    )) {
        http_response_code(403);
        throw new Exception(
            "No tiene permisos para registrar entradas de inventario."
        );
    }

    $idUsuario = intval(
        $_SESSION["id_usuario"] ?? 0
    );

    if ($idUsuario <= 0) {
        throw new Exception(
            "No se pudo identificar al usuario responsable."
        );
    }

    $contenido = file_get_contents("php://input");
    $datos = json_decode($contenido, true);

    if (!is_array($datos)) {
        throw new Exception(
            "Los datos de la entrada no son válidos."
        );
    }

    $idProveedor = intval(
        $datos["id_proveedor"] ?? 0
    );

    $tipoDocumento = strtolower(
        trim((string) ($datos["tipo_documento"] ?? ""))
    );

    $numeroDocumento = trim(
        (string) ($datos["numero_documento"] ?? "")
    );

    $fechaDocumento = trim(
        (string) ($datos["fecha_documento"] ?? "")
    );

    $observaciones = trim(
        (string) ($datos["observaciones"] ?? "")
    );

    $productosRecibidos = $datos["productos"] ?? [];

    $tiposPermitidos = [
        "factura",
        "boleta",
        "guia_despacho",
        "sin_documento"
    ];

    if (!in_array(
        $tipoDocumento,
        $tiposPermitidos,
        true
    )) {
        throw new Exception(
            "El tipo de documento seleccionado no es válido."
        );
    }

    if (
        $tipoDocumento !== "sin_documento" &&
        $numeroDocumento === ""
    ) {
        throw new Exception(
            "Debe ingresar el número del documento."
        );
    }

    if (mb_strlen($numeroDocumento) > 60) {
        throw new Exception(
            "El número del documento no puede superar los 60 caracteres."
        );
    }

    if ($fechaDocumento !== "") {

        $fechaValida = DateTime::createFromFormat(
            "!Y-m-d",
            $fechaDocumento
        );

        if (
            !$fechaValida ||
            $fechaValida->format("Y-m-d") !== $fechaDocumento
        ) {
            throw new Exception(
                "La fecha del documento no es válida."
            );
        }

    } else {
        $fechaDocumento = null;
    }

    if (mb_strlen($observaciones) > 500) {
        throw new Exception(
            "Las observaciones no pueden superar los 500 caracteres."
        );
    }

    if (
        !is_array($productosRecibidos) ||
        count($productosRecibidos) === 0
    ) {
        throw new Exception(
            "Debe agregar al menos un producto a la entrada."
        );
    }

    $productosEntrada = [];
    $totalEntrada = 0;

    foreach ($productosRecibidos as $productoRecibido) {

        if (!is_array($productoRecibido)) {
            throw new Exception(
                "Uno de los productos no es válido."
            );
        }

        $idProducto = intval(
            $productoRecibido["id_producto"] ?? 0
        );

        $cantidad = intval(
            $productoRecibido["cantidad"] ?? 0
        );

        $costoUnitario = intval(
            $productoRecibido["costo_unitario"] ?? 0
        );

        if ($idProducto <= 0) {
            throw new Exception(
                "Uno de los productos no es válido."
            );
        }

        if (isset($productosEntrada[$idProducto])) {
            throw new Exception(
                "Un producto aparece más de una vez en la entrada."
            );
        }

        if ($cantidad <= 0 || $cantidad > 100000) {
            throw new Exception(
                "La cantidad de uno de los productos no es válida."
            );
        }

        if ($costoUnitario <= 0 || $costoUnitario > 2147483647) {
            throw new Exception(
                "El costo de uno de los productos no es válido."
            );
        }

        $totalLinea = $cantidad * $costoUnitario;

        if (
            !is_int($totalLinea) ||
            $totalLinea <= 0 ||
            $totalLinea > PHP_INT_MAX - $totalEntrada
        ) {
            throw new Exception(
                "El total de la entrada supera el límite permitido."
            );
        }

        $totalEntrada += $totalLinea;

        $productosEntrada[$idProducto] = [
            "id_producto" => $idProducto,
            "cantidad" => $cantidad,
            "costo_unitario" => $costoUnitario,
            "total_linea" => $totalLinea
        ];
    }

    ksort($productosEntrada, SORT_NUMERIC);

    $neto = intval(round($totalEntrada / 1.19));
    $iva = $totalEntrada - $neto;

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

    if (!$usuario) {
        throw new Exception(
            "El usuario responsable no existe."
        );
    }

    if (strtolower((string) $usuario["estado"]) !== "activo") {
        throw new Exception(
            "El usuario responsable está inactivo."
        );
    }

    $idProveedorParametro = null;

    if ($idProveedor > 0) {

        $stmtProveedor = $conexion->prepare(
            "SELECT id_proveedor, estado
             FROM proveedores
             WHERE id_proveedor = ?
             LIMIT 1
             FOR UPDATE"
        );

        $stmtProveedor->bind_param("i", $idProveedor);
        $stmtProveedor->execute();
        $proveedor = $stmtProveedor
            ->get_result()
            ->fetch_assoc();
        $stmtProveedor->close();

        if (!$proveedor) {
            throw new Exception(
                "El proveedor seleccionado no existe."
            );
        }

        if (strtolower((string) $proveedor["estado"]) !== "activo") {
            throw new Exception(
                "El proveedor seleccionado está inactivo."
            );
        }

        $idProveedorParametro = $idProveedor;
    }

    if ($tipoDocumento === "sin_documento") {
        $numeroDocumento = null;
    }

    $numeroEntrada = generarNumeroEntradaInventario();

    $stmtEntrada = $conexion->prepare(
        "INSERT INTO entradas_inventario (
            numero_entrada,
            id_proveedor,
            id_usuario,
            tipo_documento,
            numero_documento,
            fecha_documento,
            neto,
            iva,
            total,
            estado,
            observaciones,
            fecha_confirmacion
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            'confirmada', ?, NOW()
        )"
    );

    $stmtEntrada->bind_param(
        "siisssiiis",
        $numeroEntrada,
        $idProveedorParametro,
        $idUsuario,
        $tipoDocumento,
        $numeroDocumento,
        $fechaDocumento,
        $neto,
        $iva,
        $totalEntrada,
        $observaciones
    );

    $stmtEntrada->execute();
    $idEntrada = intval($conexion->insert_id);
    $stmtEntrada->close();

    $stmtProducto = $conexion->prepare(
        "SELECT id_producto, nombre, cantidad, compra
         FROM productos
         WHERE id_producto = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtActualizarProducto = $conexion->prepare(
        "UPDATE productos
         SET cantidad = ?, compra = ?
         WHERE id_producto = ?"
    );

    $stmtDetalle = $conexion->prepare(
        "INSERT INTO detalle_entrada_inventario (
            id_entrada,
            id_producto,
            cantidad,
            costo_unitario,
            total_linea
        )
        VALUES (?, ?, ?, ?, ?)"
    );

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
        VALUES (
            ?, ?, NULL, ?,
            'entrada_compra', 'entrada',
            ?, ?, ?, ?, ?, ?, ?
        )"
    );

    $productosProcesados = [];

    foreach ($productosEntrada as $productoEntrada) {

        $idProducto = $productoEntrada["id_producto"];
        $cantidadEntrada = $productoEntrada["cantidad"];
        $costoEntrada = $productoEntrada["costo_unitario"];
        $totalLinea = $productoEntrada["total_linea"];

        $stmtProducto->bind_param("i", $idProducto);
        $stmtProducto->execute();
        $producto = $stmtProducto
            ->get_result()
            ->fetch_assoc();

        if (!$producto) {
            throw new Exception(
                "Uno de los productos no fue encontrado."
            );
        }

        $stockAnterior = intval($producto["cantidad"]);
        $costoAnterior = intval($producto["compra"] ?? 0);

        if (
            $stockAnterior < 0 ||
            $stockAnterior > 2147483647 - $cantidadEntrada
        ) {
            throw new Exception(
                "El stock resultante de uno de los productos no es válido."
            );
        }

        $stockResultante = $stockAnterior + $cantidadEntrada;

        if ($stockAnterior > 0 && $costoAnterior > 0) {
            $valorAnterior = $stockAnterior * $costoAnterior;
            $valorEntrada = $cantidadEntrada * $costoEntrada;
            $costoPromedio = intval(round(
                ($valorAnterior + $valorEntrada) /
                $stockResultante
            ));
        } else {
            $costoPromedio = $costoEntrada;
        }

        $stmtActualizarProducto->bind_param(
            "iii",
            $stockResultante,
            $costoPromedio,
            $idProducto
        );
        $stmtActualizarProducto->execute();

        if ($stmtActualizarProducto->affected_rows !== 1) {
            throw new Exception(
                "No fue posible actualizar uno de los productos."
            );
        }

        $stmtDetalle->bind_param(
            "iiiii",
            $idEntrada,
            $idProducto,
            $cantidadEntrada,
            $costoEntrada,
            $totalLinea
        );
        $stmtDetalle->execute();

        $motivoMovimiento = $observaciones !== ""
            ? $observaciones
            : "Entrada de mercadería";

        $claveOperacion =
            "ENTRADA-" . $idEntrada . "-" . $idProducto;

        $stmtMovimiento->bind_param(
            "iiiiiiisss",
            $idProducto,
            $idUsuario,
            $idEntrada,
            $cantidadEntrada,
            $stockAnterior,
            $stockResultante,
            $costoEntrada,
            $motivoMovimiento,
            $numeroEntrada,
            $claveOperacion
        );
        $stmtMovimiento->execute();

        $productosProcesados[] = [
            "id_producto" => $idProducto,
            "nombre" => $producto["nombre"],
            "cantidad" => $cantidadEntrada,
            "stock_anterior" => $stockAnterior,
            "stock_resultante" => $stockResultante,
            "costo_entrada" => $costoEntrada,
            "costo_promedio" => $costoPromedio
        ];
    }

    $stmtProducto->close();
    $stmtActualizarProducto->close();
    $stmtDetalle->close();
    $stmtMovimiento->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode(
        [
            "ok" => true,
            "mensaje" =>
                "Entrada de mercadería registrada correctamente.",
            "datos" => [
                "id_entrada" => $idEntrada,
                "numero_entrada" => $numeroEntrada,
                "neto" => $neto,
                "iva" => $iva,
                "total" => $totalEntrada,
                "productos" => $productosProcesados
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
        "Error registrando entrada de inventario: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje =
            "No fue posible registrar la entrada de inventario.";
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

function generarNumeroEntradaInventario(): string
{
    return substr(
        "ENT-" .
        date("Ymd-His") .
        "-" .
        strtoupper(bin2hex(random_bytes(3))),
        0,
        30
    );
}

?>
