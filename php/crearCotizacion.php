<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/guardarDocumentoImpresion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;
$transaccionIniciada = false;

// Vigencia inicial configurable.
$diasVigencia = 7;

try {
    /* MÉTODO Y SOLICITUD */

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new RuntimeException("Método no permitido.", 405);
    }

    $idUsuario = (int) ($_SESSION["id_usuario"] ?? 0);

    if ($idUsuario <= 0) {
        throw new RuntimeException(
            "No se pudo identificar al usuario responsable.",
            403
        );
    }

    try {
        $datos = json_decode(
            file_get_contents("php://input"),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $error) {
        throw new RuntimeException("El JSON recibido no es válido.", 400);
    }

    if (!is_array($datos)) {
        throw new RuntimeException("Los datos no son válidos.", 400);
    }

    $cliente = $datos["cliente"] ?? null;
    $productos = $datos["productos"] ?? null;

    if (!is_array($cliente)) {
        throw new RuntimeException("Debe indicar los datos del cliente.", 400);
    }

    if (
        !is_array($productos) ||
        count($productos) === 0 ||
        count($productos) > 100
    ) {
        throw new RuntimeException(
            "Debe incluir entre 1 y 100 líneas de productos.",
            400
        );
    }

    /* CLIENTE */

    $nombre = textoCotizacion($cliente, "nombre");
    $apellido = textoCotizacion($cliente, "apellido");
    $correo = strtolower(textoCotizacion($cliente, "correo"));
    $telefono = textoCotizacion($cliente, "telefono");
    $rut = textoCotizacion($cliente, "rut");
    $observaciones = textoCotizacion($datos, "observaciones");

    if (
        mb_strlen($nombre) < 2 ||
        mb_strlen($nombre) > 60 ||
        mb_strlen($apellido) < 2 ||
        mb_strlen($apellido) > 60
    ) {
        throw new RuntimeException(
            "Nombre y apellido deben tener entre 2 y 60 caracteres.",
            400
        );
    }

    if (
        $correo !== "" &&
        (
            mb_strlen($correo) > 100 ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL)
        )
    ) {
        throw new RuntimeException("El correo no es válido.", 400);
    }

    if (!preg_match('/^[0-9]{9}$/', $telefono)) {
        throw new RuntimeException(
            "El teléfono debe contener 9 números.",
            400
        );
    }

    if (mb_strlen($observaciones) > 500) {
        throw new RuntimeException(
            "Las observaciones no pueden superar los 500 caracteres.",
            400
        );
    }

    $correo = $correo !== "" ? $correo : null;
    $rut = $rut !== "" ? normalizarRutCotizacion($rut) : null;

    /* AGRUPAR PRODUCTOS Y VALIDAR CANTIDADES */

    $agrupados = [];

    foreach ($productos as $producto) {
        if (!is_array($producto)) {
            throw new RuntimeException("Producto no válido.", 400);
        }

        $idProducto = filter_var(
            $producto["id_producto"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        $cantidad = filter_var(
            $producto["cantidad"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => 1000]]
        );

        if ($idProducto === false || $cantidad === false) {
            throw new RuntimeException(
                "El producto o su cantidad no son válidos.",
                400
            );
        }

        $agrupados[$idProducto] =
            ($agrupados[$idProducto] ?? 0) + $cantidad;

        if ($agrupados[$idProducto] > 1000) {
            throw new RuntimeException(
                "No puede cotizar más de 1000 unidades de un producto.",
                400
            );
        }
    }

    ksort($agrupados, SORT_NUMERIC);

    /* TRANSACCIÓN Y PERMISOS */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmt = $conexion->prepare("
        SELECT rol, estado
        FROM login_admin
        WHERE id_usuario = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$usuario ||
        strtolower(trim($usuario["estado"])) !== "activo"
    ) {
        throw new RuntimeException(
            "El usuario no existe o está inactivo.",
            403
        );
    }

    $rol = strtolower(trim($usuario["rol"]));

    if (
        !in_array(
            $rol,
            ["administrador", "vendedor", "cajero"],
            true
        )
    ) {
        throw new RuntimeException(
            "No tiene permiso para crear cotizaciones.",
            403
        );
    }

    /* OBTENER PRECIOS DESDE LA BASE DE DATOS */

    $stmtProducto = $conexion->prepare("
        SELECT
            id_producto,
            sku,
            nombre,
            marca,
            precio,
            precio_oferta,
            inicio_oferta,
            fin_oferta
        FROM productos
        WHERE id_producto = ?
        LIMIT 1
    ");

    $lineas = [];
    $total = 0;
    $ahora = new DateTimeImmutable();

    foreach ($agrupados as $idProducto => $cantidad) {
        $stmtProducto->bind_param("i", $idProducto);
        $stmtProducto->execute();

        $producto = $stmtProducto->get_result()->fetch_assoc();

        if (!$producto) {
            throw new RuntimeException(
                "No se encontró el producto N.º " . $idProducto . ".",
                400
            );
        }

        $precio = precioActualCotizacion($producto, $ahora);

        if ($precio <= 0) {
            throw new RuntimeException(
                'El producto "' . $producto["nombre"] .
                '" no tiene un precio válido.',
                400
            );
        }

        // Evita desbordamientos al multiplicar y sumar.
        if ($precio > intdiv(PHP_INT_MAX - $total, $cantidad)) {
            throw new RuntimeException(
                "El importe de la cotización es demasiado alto.",
                400
            );
        }

        $totalLinea = $precio * $cantidad;
        $total += $totalLinea;

        $lineas[] = [
            "id_producto" => $idProducto,
            "sku" => $producto["sku"],
            "nombre" => $producto["nombre"],
            "marca" => $producto["marca"],
            "precio" => $precio,
            "cantidad" => $cantidad,
            "total" => $totalLinea
        ];
    }

    $stmtProducto->close();

    /* BUSCAR CLIENTE POR RUT Y CORREO */

    /*
 * Comparamos el RUT sin puntos, guion ni espacios.
 * No cambiamos su valor almacenado.
 */

$rutBusqueda = $rut !== null
    ? strtoupper(
        str_replace([".", "-", " "], "", $rut)
    )
    : null;

$stmt = $conexion->prepare("
    SELECT id_cliente, rut
    FROM clientes
    WHERE
        (
            ? IS NOT NULL
            AND UPPER(
                REPLACE(
                    REPLACE(
                        REPLACE(TRIM(rut), '.', ''),
                        '-',
                        ''
                    ),
                    ' ',
                    ''
                )
            ) = ?
        )
        OR
        (
            ? IS NOT NULL
            AND correo = ?
        )
    FOR UPDATE
");

$stmt->bind_param(
    "ssss",
    $rutBusqueda,
    $rutBusqueda,
    $correo,
    $correo
);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $coincidencias = [];

    while ($fila = $resultado->fetch_assoc()) {
        $coincidencias[] = $fila;
    }

    $stmt->close();

    if (count($coincidencias) > 1) {
        throw new RuntimeException(
            "El RUT y el correo identifican clientes distintos. " .
            "Revise los datos antes de continuar.",
            409
        );
    }

    if (count($coincidencias) === 1) {
        $existente = $coincidencias[0];

        if (
            $rut !== null &&
            !empty($existente["rut"]) &&
            normalizarRutCotizacion($existente["rut"]) !== $rut
        ) {
            throw new RuntimeException(
                "El correo pertenece a un cliente con otro RUT.",
                409
            );
        }

        $idCliente = (int) $existente["id_cliente"];

        // Cotizar no modifica la ficha del cliente existente.
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO clientes (
                rut, nombre, apellido, correo, telefono
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssss",
            $rut,
            $nombre,
            $apellido,
            $correo,
            $telefono
        );

        $stmt->execute();
        $idCliente = $conexion->insert_id;
        $stmt->close();
    }

    /* CREAR COTIZACIÓN */

    $numeroCotizacion =
        "COT-" . $ahora->format("Ymd") . "-" .
        strtoupper(bin2hex(random_bytes(6)));

    // numero_pedido es obligatorio en la estructura actual.
    // Se usa un identificador interno distinto del número de cotización.
    $numeroPedido =
        "P-" . $ahora->format("Ymd") . "-" .
        strtoupper(bin2hex(random_bytes(6)));

    $fechaCotizacion = $ahora->format("Y-m-d H:i:s");

    $fechaExpiracion = $ahora
        ->modify("+{$diasVigencia} days")
        ->format("Y-m-d H:i:s");

    $neto = (int) round($total / 1.19);
    $iva = $total - $neto;

    $stmt = $conexion->prepare("
        INSERT INTO pedidos (
            numero_pedido,
            numero_cotizacion,
            fecha_cotizacion,
            fecha_expiracion_cotizacion,
            ajustes_aceptados,
            id_cliente,
            id_usuario,
            canal,
            tipo_entrega,
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
            ?, ?, ?, ?, 0, ?, ?,
            'presencial', 'retiro',
            ?, ?, ?, 0, ?,
            'cotizacion', 'pendiente', ?
        )
    ");

    $stmt->bind_param(
        "ssssiiiiiis",
        $numeroPedido,
        $numeroCotizacion,
        $fechaCotizacion,
        $fechaExpiracion,
        $idCliente,
        $idUsuario,
        $neto,
        $iva,
        $total,
        $total,
        $observaciones
    );

    $stmt->execute();
    $idPedido = $conexion->insert_id;
    $stmt->close();

    /* GUARDAR DETALLE */

    $stmt = $conexion->prepare("
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
    ");

    foreach ($lineas as $linea) {
        $idProducto = $linea["id_producto"];
        $sku = $linea["sku"];
        $nombreProducto = $linea["nombre"];
        $marca = $linea["marca"];
        $precio = $linea["precio"];
        $cantidad = $linea["cantidad"];
        $totalLinea = $linea["total"];

        $stmt->bind_param(
            "iisssiii",
            $idPedido,
            $idProducto,
            $sku,
            $nombreProducto,
            $marca,
            $precio,
            $cantidad,
            $totalLinea
        );

        $stmt->execute();
    }

    $stmt->close();

    guardarDocumentoImpresion($conexion, $idPedido, "cotizacion");

    // No se inserta en pagos y no se modifica productos.cantidad.

    $conexion->commit();
    $transaccionIniciada = false;

    http_response_code(201);

    echo json_encode([
        "ok" => true,
        "mensaje" => "Cotización guardada correctamente.",
        "datos" => [
            "id_pedido" => $idPedido,
            "numero_cotizacion" => $numeroCotizacion,
            "fecha_expiracion_cotizacion" => $fechaExpiracion,
            "estado" => "cotizacion",
            "neto" => $neto,
            "iva" => $iva,
            "total" => $total
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error creando cotización: " . $error->getMessage());

    $codigo = 500;
    $mensaje = "No fue posible guardar la cotización.";

    if (
        $error instanceof RuntimeException &&
        !($error instanceof mysqli_sql_exception) &&
        in_array($error->getCode(), [400, 403, 405, 409], true)
    ) {
        $codigo = $error->getCode();
        $mensaje = $error->getMessage();
    }

    http_response_code($codigo);

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

/* AUXILIARES */

function textoCotizacion(array $datos, string $campo): string
{
    $valor = $datos[$campo] ?? "";

    if (!is_string($valor)) {
        throw new RuntimeException(
            "El campo " . $campo . " no es válido.",
            400
        );
    }

    return trim($valor);
}

function precioActualCotizacion(
    array $producto,
    DateTimeImmutable $ahora
): int {
    $normal = (int) $producto["precio"];
    $oferta = (int) ($producto["precio_oferta"] ?? 0);

    if ($oferta <= 0 || $oferta >= $normal) {
        return $normal;
    }

    $inicio = !empty($producto["inicio_oferta"])
        ? new DateTimeImmutable($producto["inicio_oferta"])
        : null;

    $fin = !empty($producto["fin_oferta"])
        ? new DateTimeImmutable($producto["fin_oferta"])
        : null;

    if (
        ($inicio === null || $ahora >= $inicio) &&
        ($fin === null || $ahora <= $fin)
    ) {
        return $oferta;
    }

    return $normal;
}

function normalizarRutCotizacion(string $valor): string
{
    $rut = strtoupper(
        str_replace([".", "-", " "], "", $valor)
    );

    if (!preg_match('/^[0-9]{7,8}[0-9K]$/', $rut)) {
        throw new RuntimeException("El RUT no es válido.", 400);
    }

    $cuerpo = substr($rut, 0, -1);
    $digito = substr($rut, -1);

    if ((int) $cuerpo === 0) {
        throw new RuntimeException("El RUT no es válido.", 400);
    }

    $suma = 0;
    $multiplicador = 2;

    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
        $suma += (int) $cuerpo[$i] * $multiplicador;
        $multiplicador = $multiplicador === 7
            ? 2
            : $multiplicador + 1;
    }

    $resultado = 11 - ($suma % 11);

    $esperado = $resultado === 11
        ? "0"
        : ($resultado === 10 ? "K" : (string) $resultado);

    if ($digito !== $esperado) {
        throw new RuntimeException("El RUT no es válido.", 400);
    }

    return number_format((int) $cuerpo, 0, "", ".") .
        "-" . $digito;
}

?>