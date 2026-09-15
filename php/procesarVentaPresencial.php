<?php

if (!isset($rutaCotizacion) || !is_bool($rutaCotizacion)) {
    http_response_code(404);
    exit;
}

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/venta_motor_helper.php";
header("Cache-Control: no-store");
require_once __DIR__ . "/guardarDocumentoImpresion.php";
require_once __DIR__ . "/registrarComisionVenta.php";

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

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    /* =====================================================
       USUARIO RESPONSABLE
    ===================================================== */

    $idUsuario = intval(
        $_SESSION["id_usuario"] ?? 0
    );

    if ($idUsuario <= 0) {
        throw new Exception(
            "No se pudo identificar al usuario responsable."
        );
    }

    /* =====================================================
       LEER JSON
    ===================================================== */

    $contenido = file_get_contents(
        "php://input"
    );

    $datos = json_decode(
        $contenido,
        true
    );

    if (!is_array($datos)) {
        throw new Exception(
            "Los datos de la venta no son válidos."
        );
    }

    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') ventaFallo('Se requiere JSON.',415);
    ventaValidarSolicitud($datos);
    $cliente = $datos["cliente"] ?? [];
    $productosRecibidos =
        $datos["productos"] ?? [];

    $metodoPago = strtolower(
        trim($datos["metodo_pago"] ?? "")
    );

    $observaciones = trim(
        $datos["observaciones"] ?? ""
    );

    /* =====================================================
       DATOS DEL CLIENTE
    ===================================================== */

    $rut = trim(
        $cliente["rut"] ?? ""
    );

    $nombre = trim(
        $cliente["nombre"] ?? ""
    );

    $apellido = trim(
        $cliente["apellido"] ?? ""
    );

    $correoRecibido = strtolower(
        trim($cliente["correo"] ?? "")
    );

    $correo = $correoRecibido !== ""
        ? $correoRecibido
        : null;

    $telefono = preg_replace(
        "/[^0-9]/",
        "",
        $cliente["telefono"] ?? ""
    );

    /* =====================================================
       VALIDACIONES
    ===================================================== */

    if (
        $nombre === "" ||
        $apellido === "" ||
        $telefono === ""
    ) {
        throw new Exception(
            "Debe completar los datos obligatorios del cliente."
        );
    }

    if (mb_strlen($nombre) < 2) {
        throw new Exception(
            "El nombre del cliente no es válido."
        );
    }

    if (mb_strlen($apellido) < 2) {
        throw new Exception(
            "El apellido del cliente no es válido."
        );
    }

    if ($correo !== null && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception(
            "El correo electrónico no es válido."
        );
    }

    if (!preg_match(
        "/^[0-9]{9}$/",
        $telefono
    )) {
        throw new Exception(
            "El teléfono debe contener 9 números."
        );
    }

    if ($rut !== "") {

        $rut = strtoupper(
            preg_replace(
                "/[^0-9Kk]/",
                "",
                $rut
            )
        );

        if (!validarRutVentaPresencial($rut)) {
            throw new Exception(
                "El RUT ingresado no es válido."
            );
        }

        $rut = formatearRutVentaPresencial(
            $rut
        );

    } else {

        $rut = null;
    }

    if (mb_strlen($observaciones) > 500) {
        throw new Exception(
            "Las observaciones no pueden superar los 500 caracteres."
        );
    }

    /* =====================================================
       MÉTODOS DE PAGO
    ===================================================== */

    $metodosPermitidos = [
        "efectivo",
        "debito",
        "credito",
        "transferencia"
    ];

    if (
        !in_array(
            $metodoPago,
            $metodosPermitidos,
            true
        )
    ) {
        throw new Exception(
            "El método de pago seleccionado no es válido."
        );
    }

    /* =====================================================
       VALIDAR PRODUCTOS RECIBIDOS
    ===================================================== */

    if (
        !is_array($productosRecibidos) ||
        count($productosRecibidos) === 0
    ) {
        throw new Exception(
            "Debe agregar al menos un producto."
        );
    }

    $productosAgrupados = [];

    foreach (
        $productosRecibidos
        as $producto
    ) {

        $idProducto = intval(
            $producto["id_producto"] ?? 0
        );

        $cantidad = intval(
            $producto["cantidad"] ?? 0
        );

        if ($idProducto <= 0) {
            throw new Exception(
                "Uno de los productos no es válido."
            );
        }

        if ($cantidad <= 0) {
            throw new Exception(
                "La cantidad de un producto no es válida."
            );
        }

        if ($cantidad > 1000) {
            throw new Exception(
                "La cantidad solicitada es demasiado alta."
            );
        }

        if (!isset(
            $productosAgrupados[$idProducto]
        )) {
            $productosAgrupados[$idProducto] = 0;
        }

        $productosAgrupados[$idProducto] +=
            $cantidad;
    }

    foreach ($productosAgrupados as $cantidadAgrupada) {
        if ($cantidadAgrupada > 1000) ventaFallo('La cantidad acumulada supera 1000 unidades.');
    }
    ksort($productosAgrupados, SORT_NUMERIC);
    /* =====================================================
       CONEXIÓN Y TRANSACCIÓN
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    ventaValidarMotor($conexion);

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       VALIDAR USUARIO
    ===================================================== */

        $sqlUsuario = "
        SELECT
            id_usuario,
            nombre,
            apellido,
            estado,
            rol
        FROM login_admin
        WHERE id_usuario = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtUsuario = $conexion->prepare(
        $sqlUsuario
    );

    $stmtUsuario->bind_param(
        "i",
        $idUsuario
    );

    $stmtUsuario->execute();

    $resultadoUsuario =
        $stmtUsuario->get_result();

    if ($resultadoUsuario->num_rows === 0) {
        throw new Exception(
            "El usuario responsable no existe."
        );
    }

    $usuario = $resultadoUsuario->fetch_assoc();

    $stmtUsuario->close();

    if ( strtolower($usuario["estado"]) !== "activo") {
        throw new Exception(
            "El usuario responsable está inactivo."
        );
    }

    /* Solo administrador y cajero pueden confirmar y cobrar. */

    $rolUsuario = strtolower(trim((string) $usuario["rol"]));

    if (!in_array($rolUsuario, ["administrador", "cajero"], true)) {
        
        http_response_code(403);

        throw new Exception(
            "No tiene permiso para confirmar ventas ni registrar cobros."
        );
    }

    $reintento = ventaReintento($conexion, $datos, $idUsuario);
    if ($reintento !== null) {
        $conexion->rollback();
        $transaccionIniciada = false;
        echo json_encode($reintento, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pedidoOrigen = ventaOrigen($conexion, $datos, $rutaCotizacion);

    /* =====================================================
       CONSULTAR Y BLOQUEAR PRODUCTOS
    ===================================================== */

    $sqlProducto = "
        SELECT
            id_producto,
            sku,
            nombre,
            marca,
            cantidad,
            precio,
            precio_oferta,
            inicio_oferta,
            fin_oferta
        FROM productos
        WHERE id_producto = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtProducto = $conexion->prepare(
        $sqlProducto
    );

    $productosValidados = [];

    $subtotalProductos = 0;

    foreach (
        $productosAgrupados
        as $idProducto => $cantidadSolicitada
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
                "Uno de los productos no fue encontrado."
            );
        }

        $producto =
            $resultadoProducto->fetch_assoc();

        $stockDisponible = intval(
            $producto["cantidad"]
        );

        if (
            $cantidadSolicitada >
            $stockDisponible
        ) {
            throw new Exception(
                'Solo quedan ' .
                $stockDisponible .
                ' unidades de "' .
                $producto["nombre"] .
                '".'
            );
        }

        $precioUnitario =
            obtenerPrecioVentaPresencial(
                $producto
            );

        if ($precioUnitario <= 0) {
            throw new Exception(
                'El producto "' .
                $producto["nombre"] .
                '" no tiene un precio válido.'
            );
        }

        if ($precioUnitario > intdiv(PHP_INT_MAX - $subtotalProductos, $cantidadSolicitada)) ventaFallo('Importe demasiado alto.');
        $totalLinea =
            $precioUnitario *
            $cantidadSolicitada;

        $subtotalProductos +=
            $totalLinea;

        $productosValidados[] = [
            "stock_actual" => $stockDisponible,
            "id_producto" =>
                intval(
                    $producto["id_producto"]
                ),

            "sku" =>
                $producto["sku"],

            "nombre" =>
                $producto["nombre"],

            "marca" =>
                $producto["marca"],

            "precio_unitario" =>
                $precioUnitario,

            "cantidad" =>
                $cantidadSolicitada,

            "total_linea" =>
                $totalLinea
        ];
    }

    $stmtProducto->close();

    if ($subtotalProductos <= 0) {
        throw new Exception(
            "El total de la venta no es válido."
        );
    }

    $idSeleccionado = (int)($cliente['id_cliente'] ?? 0);
    $rutSimple = $rut !== null ? strtoupper(str_replace(['.','-',' '], '', $rut)) : null;
    if ($rutaCotizacion) $idSeleccionado = (int)$pedidoOrigen['id_cliente'];
    if ($idSeleccionado > 0) {
        $s = $conexion->prepare('SELECT id_cliente, rut, nombre, apellido, correo, telefono FROM clientes WHERE id_cliente=? LIMIT 1 FOR UPDATE');
        $s->bind_param('i',$idSeleccionado);
    } else {
        $s = $conexion->prepare("SELECT id_cliente, rut, nombre, apellido, correo, telefono FROM clientes WHERE (? IS NOT NULL AND UPPER(REPLACE(REPLACE(REPLACE(rut,'.',''),'-',''),' ',''))=?) OR (? IS NOT NULL AND correo=?) FOR UPDATE");
        $s->bind_param('ssss',$rutSimple,$rutSimple,$correo,$correo);
    }
    $s->execute(); $r=$s->get_result();
    if ($r->num_rows>1) ventaFallo('El RUT y correo corresponden a clientes distintos.',409);
    $existente=$r->fetch_assoc(); $s->close();
    if ($idSeleccionado>0 && !$existente) ventaFallo('El cliente seleccionado ya no existe.',409);
    if ($existente) {
        $rutExistente = strtoupper(str_replace(['.','-',' '],'',(string)$existente['rut']));
        if ($rutSimple !== null && $rutExistente !== '' && $rutSimple !== $rutExistente) ventaFallo('El cliente seleccionado tiene otro RUT.',409);
        // La edición de fichas se realiza mediante Actualizar cliente, no al cobrar.
        if ($nombre !== trim($existente['nombre']) || $apellido !== trim($existente['apellido']) ||
            (string)$correo !== strtolower(trim((string)$existente['correo'])) || $telefono !== preg_replace('/[^0-9]/','',(string)$existente['telefono'])) {
            ventaFallo('Los datos no coinciden con la ficha del cliente. Actualícela con el botón Actualizar cliente o vuelva a seleccionarlo antes de cobrar.',409);
        }
        $idCliente=(int)$existente['id_cliente'];
    } else {
        $idCliente=0;
    }

    $revision = ventaRevision($datos, $productosValidados, $subtotalProductos, $pedidoOrigen);
    if ($datos['accion'] === 'revisar' || !hash_equals($revision['token_revision'], $datos['token_revision'])) {
        $esRevision = $datos['accion'] === 'revisar';
        $conexion->rollback();
        $transaccionIniciada = false;
        http_response_code($esRevision ? 200 : 409);
        echo json_encode(['ok'=>$esRevision, 'codigo'=>'revision_requerida',
            'mensaje'=>$esRevision ? 'Revise los importes antes de registrar el pago.' : 'Cambió el precio o el documento. Revise y confirme nuevamente.',
            'revision'=>$revision], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($idCliente === 0) {
        $s=$conexion->prepare('INSERT INTO clientes (rut,nombre,apellido,correo,telefono) VALUES (?,?,?,?,?)');
        $s->bind_param('sssss',$rut,$nombre,$apellido,$correo,$telefono);
        $s->execute(); $idCliente=$conexion->insert_id; $s->close();
    }

    /* =====================================================
       CALCULAR TOTALES
    ===================================================== */

    $neto = intval(
        round(
            $subtotalProductos / 1.19
        )
    );

    $iva =
        $subtotalProductos - $neto;

    $costoDespacho = 0;

    $total =
        $subtotalProductos;

    /* =====================================================
       CREAR PEDIDO
    ===================================================== */

    if ($rutaCotizacion) {
        $idPedido = (int)$pedidoOrigen['id_pedido'];
        $numeroPedido = $pedidoOrigen['numero_pedido'];
        // La copia histórica ya existe y nunca se actualiza.
        $s=$conexion->prepare("UPDATE pedidos SET estado='pagado', estado_pago='aprobado', id_usuario_confirmacion=?, neto=?, iva=?, subtotal_productos=?, total=?, observaciones=?, ajustes_aceptados=1, fecha_pedido=NOW() WHERE id_pedido=? AND estado='cotizacion' AND fecha_expiracion_cotizacion>NOW()");
        $s->bind_param('iiiiisi',$idUsuario,$neto,$iva,$subtotalProductos,$total,$observaciones,$idPedido);
        $s->execute();
        if ($s->affected_rows!==1) ventaFallo('La cotización cambió o venció. No se registró el cobro.',409);
        $s->close();
        $s=$conexion->prepare('DELETE FROM detalle_pedido WHERE id_pedido=?');
        $s->bind_param('i',$idPedido); $s->execute(); $s->close();
    } else {
    $numeroPedido =
        generarNumeroVentaPresencial();

    $canal = "presencial";
    $tipoEntrega = "retiro";
    $estado = "pagado";
    $estadoPago = "aprobado";

    $sqlPedido = "
        INSERT INTO pedidos (
            numero_pedido,
            id_cliente,
            id_usuario,
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
            observaciones
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            NULL,
            NULL,
            NULL,
            NULL,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $stmtPedido = $conexion->prepare(
        $sqlPedido
    );

    $stmtPedido->bind_param(
        "siissiiiiisss",
        $numeroPedido,
        $idCliente,
        $idUsuario,
        $canal,
        $tipoEntrega,
        $neto,
        $iva,
        $subtotalProductos,
        $costoDespacho,
        $total,
        $estado,
        $estadoPago,
        $observaciones
    );

    $stmtPedido->execute();

    $idPedido = $conexion->insert_id;

    $stmtPedido->close();

        $s=$conexion->prepare('UPDATE pedidos SET id_usuario_confirmacion=? WHERE id_pedido=?');
        $s->bind_param('ii',$idUsuario,$idPedido); $s->execute(); $s->close();
    }

    /* =====================================================
       DETALLE DEL PEDIDO
    ===================================================== */

    $sqlDetalle = "
        INSERT INTO detalle_pedido (
            id_pedido,
            id_producto,
            sku,
            nombre_producto,
            marca_producto,
            precio_unitario,
            cantidad,
            total_linea
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmtDetalle = $conexion->prepare(
        $sqlDetalle
    );

    foreach (
        $productosValidados
        as $producto
    ) {

        $idProducto =
            $producto["id_producto"];

        $sku =
            $producto["sku"];

        $nombreProducto =
            $producto["nombre"];

        $marcaProducto =
            $producto["marca"];

        $precioUnitario =
            $producto["precio_unitario"];

        $cantidad =
            $producto["cantidad"];

        $totalLinea =
            $producto["total_linea"];

        $stmtDetalle->bind_param(
            "iisssiii",
            $idPedido,
            $idProducto,
            $sku,
            $nombreProducto,
            $marcaProducto,
            $precioUnitario,
            $cantidad,
            $totalLinea
        );

        $stmtDetalle->execute();
    }

    $stmtDetalle->close();

    /* =====================================================
       DESCONTAR STOCK
    ===================================================== */

    $sqlStock = "
        UPDATE productos
        SET cantidad = cantidad - ?
        WHERE id_producto = ?
        AND cantidad >= ?
    ";

    $stmtStock = $conexion->prepare(
        $sqlStock
    );

    foreach (
        $productosValidados
        as $producto
    ) {

        $idProducto =
            $producto["id_producto"];

        $cantidad =
            $producto["cantidad"];

        $stmtStock->bind_param(
            "iii",
            $cantidad,
            $idProducto,
            $cantidad
        );

        $stmtStock->execute();

        if ($stmtStock->affected_rows !== 1) {
            throw new Exception(
                "No fue posible actualizar el stock."
            );
        }
    }

    $stmtStock->close();

    /* =====================================================
       REGISTRAR PAGO
    ===================================================== */

    $buyOrder = 'VP-' . $datos['operacion'];

    $respuestaProveedor = json_encode(
        [
            "tipo" => $rutaCotizacion ? "conversion_cotizacion" : "venta_presencial",
            "hash_solicitud" => ventaHash($datos),
            "origen" => $datos['origen'] ?? null,
            "verificacion" => "manual_por_operador",
            "metodo_pago" => $metodoPago,
            "id_usuario" => $idUsuario,
            "usuario" =>
                $usuario["nombre"] .
                " " .
                $usuario["apellido"]
        ],
        JSON_UNESCAPED_UNICODE
    );

    $sqlPago = "
        INSERT INTO pagos (
            id_pedido,
            proveedor,
            buy_order,
            monto,
            estado,
            response_code,
            respuesta_proveedor,
            transaction_date
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            'aprobado',
            NULL,
            ?,
            NOW()
        )
    ";

    $stmtPago = $conexion->prepare(
        $sqlPago
    );

    $stmtPago->bind_param(
        "issis",
        $idPedido,
        $metodoPago,
        $buyOrder,
        $total,
        $respuestaProveedor
    );

    $stmtPago->execute();
    $stmtPago->close();

    guardarDocumentoImpresion($conexion, $idPedido, "venta");

    /* La comisión queda confirmada o revertida junto con la venta. */
    $comisionVenta = registrarComisionVenta(
        $conexion,
        $idPedido
    );

    /* =====================================================
       CONFIRMAR
    ===================================================== */

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "Venta presencial registrada correctamente.",

        "datos" => [
            "id_pedido" => $idPedido,
            "numero_pedido" => $numeroPedido,
            "id_usuario" => $idUsuario,
            "cliente" =>
                $nombre . " " . $apellido,
            "metodo_pago" => $metodoPago,
            "neto" => $neto,
            "iva" => $iva,
            "total" => $total,
            "comision" => $comisionVenta
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
        "Error creando venta presencial: " .
        $error->getMessage()
    );

    $mensaje = $error instanceof mysqli_sql_exception
        ? "No fue posible registrar la venta."
        : $error->getMessage();

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
    } elseif (http_response_code() < 400) {
        http_response_code(400);
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
   PRECIO ACTUAL
===================================================== */

function obtenerPrecioVentaPresencial(
    array $producto
): int {

    $precioNormal = intval(
        $producto["precio"] ?? 0
    );

    $precioOferta = intval(
        $producto["precio_oferta"] ?? 0
    );

    if (
        $precioOferta <= 0 ||
        $precioOferta >= $precioNormal
    ) {
        return $precioNormal;
    }

    $ahora = new DateTime();

    $inicio = !empty(
        $producto["inicio_oferta"]
    )
        ? new DateTime(
            $producto["inicio_oferta"]
        )
        : null;

    $fin = !empty(
        $producto["fin_oferta"]
    )
        ? new DateTime(
            $producto["fin_oferta"]
        )
        : null;

    $ofertaIniciada =
        $inicio === null ||
        $ahora >= $inicio;

    $ofertaVigente =
        $fin === null ||
        $ahora <= $fin;

    return (
        $ofertaIniciada &&
        $ofertaVigente
    )
        ? $precioOferta
        : $precioNormal;
}

/* =====================================================
   COMPROBAR RUT
===================================================== */

function comprobarRutClienteVenta(
    mysqli $conexion,
    string $rut,
    int $idClienteActual
): void {

    $sql = "
        SELECT id_cliente
        FROM clientes
        WHERE rut = ?
        AND id_cliente <> ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "si",
        $rut,
        $idClienteActual
    );

    $stmt->execute();

    if (
        $stmt->get_result()->num_rows > 0
    ) {

        $stmt->close();

        throw new Exception(
            "El RUT pertenece a otro cliente."
        );
    }

    $stmt->close();
}

/* =====================================================
   NÚMERO DE VENTA
===================================================== */

function generarNumeroVentaPresencial(): string
{
    return substr(
        "VP-" .
        date("Ymd-His") .
        "-" .
        strtoupper(
            bin2hex(random_bytes(3))
        ),
        0,
        30
    );
}

/* =====================================================
   VALIDAR RUT
===================================================== */

function validarRutVentaPresencial(
    string $rut
): bool {

    $rut = strtoupper(
        preg_replace(
            "/[^0-9K]/",
            "",
            $rut
        )
    );

    if (!preg_match(
        "/^[0-9]+[0-9K]$/",
        $rut
    )) {
        return false;
    }

    $cuerpo = substr($rut, 0, -1);
    $digitoRecibido = substr($rut, -1);

    $suma = 0;
    $multiplicador = 2;

    for (
        $i = strlen($cuerpo) - 1;
        $i >= 0;
        $i--
    ) {

        $suma +=
            intval($cuerpo[$i]) *
            $multiplicador;

        $multiplicador =
            $multiplicador === 7
                ? 2
                : $multiplicador + 1;
    }

    $resultado = 11 - ($suma % 11);

    if ($resultado === 11) {
        $digitoCalculado = "0";
    } elseif ($resultado === 10) {
        $digitoCalculado = "K";
    } else {
        $digitoCalculado =
            strval($resultado);
    }

    return
        $digitoCalculado ===
        $digitoRecibido;
}

function formatearRutVentaPresencial(
    string $rut
): string {

    $rut = strtoupper(
        preg_replace(
            "/[^0-9K]/",
            "",
            $rut
        )
    );

    $cuerpo = substr($rut, 0, -1);
    $digito = substr($rut, -1);

    return
        number_format(
            intval($cuerpo),
            0,
            "",
            "."
        ) .
        "-" .
        $digito;
}
