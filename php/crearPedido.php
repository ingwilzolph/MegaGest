<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "conexion.php";
require_once "sesionCliente.php";

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
       EXIGIR SESIÓN
    ===================================================== */

    $idClienteSesion =
        exigirSesionCliente();

    /* =====================================================
       LEER JSON
    ===================================================== */

    $contenido =
        file_get_contents("php://input");

    $datos =
        json_decode($contenido, true);

    if (!is_array($datos)) {
        throw new Exception(
            "Los datos del pedido no son válidos."
        );
    }

    $entrega =
        $datos["entrega"] ?? [];

    $productosRecibidos =
        $datos["productos"] ?? [];

    $observaciones = trim(
        $datos["observaciones"] ?? ""
    );

    /* =====================================================
       DATOS DE ENTREGA
    ===================================================== */

    $tipoEntrega = strtolower(
        trim(
            $entrega["tipo"] ?? "retiro"
        )
    );

    $idDireccion = intval(
        $entrega["id_direccion"] ?? 0
    );

    if (
        !in_array(
            $tipoEntrega,
            [
                "retiro",
                "despacho"
            ],
            true
        )
    ) {
        throw new Exception(
            "El método de entrega no es válido."
        );
    }

    if (
        $tipoEntrega === "despacho" &&
        $idDireccion <= 0
    ) {
        throw new Exception(
            "Seleccione una dirección de despacho."
        );
    }

    if (
        mb_strlen(
            $observaciones,
            "UTF-8"
        ) > 500
    ) {
        throw new Exception(
            "Las observaciones no pueden superar los 500 caracteres."
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
            "El carrito está vacío."
        );
    }

    $productosAgrupados = [];

    foreach (
        $productosRecibidos
        as $productoRecibido
    ) {

        $idProducto = intval(
            $productoRecibido[
                "id_producto"
            ] ?? 0
        );

        $cantidad = intval(
            $productoRecibido[
                "cantidad"
            ] ?? 0
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

        if ($cantidad > 100) {
            throw new Exception(
                "La cantidad solicitada es demasiado alta."
            );
        }

        if (
            !isset(
                $productosAgrupados[
                    $idProducto
                ]
            )
        ) {
            $productosAgrupados[
                $idProducto
            ] = 0;
        }

        $productosAgrupados[
            $idProducto
        ] += $cantidad;

        if (
            $productosAgrupados[
                $idProducto
            ] > 100
        ) {
            throw new Exception(
                "La cantidad total solicitada de un producto es demasiado alta."
            );
        }
    }

    /* =====================================================
       CONEXIÓN Y TRANSACCIÓN
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset(
        "utf8mb4"
    );

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       CLIENTE AUTENTICADO
    ===================================================== */

    $sqlCliente = "
        SELECT
            id_cliente,
            estado,
            password_hash
        FROM clientes
        WHERE id_cliente = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtCliente =
        $conexion->prepare(
            $sqlCliente
        );

    $stmtCliente->bind_param(
        "i",
        $idClienteSesion
    );

    $stmtCliente->execute();

    $clienteAutenticado =
        $stmtCliente
            ->get_result()
            ->fetch_assoc();

    $stmtCliente->close();

    if (!$clienteAutenticado) {

        http_response_code(404);

        throw new Exception(
            "La cuenta del cliente no existe."
        );
    }

    if (
        strtolower(
            trim(
                $clienteAutenticado[
                    "estado"
                ]
            )
        ) !== "activo"
    ) {

        http_response_code(403);

        throw new Exception(
            "La cuenta del cliente no está activa."
        );
    }

    if (
        empty(
            $clienteAutenticado[
                "password_hash"
            ]
        )
    ) {

        http_response_code(403);

        throw new Exception(
            "Debe activar su cuenta antes de comprar."
        );
    }

    $idCliente = intval(
        $clienteAutenticado[
            "id_cliente"
        ]
    );

    /* =====================================================
       DIRECCIÓN REAL DEL CLIENTE
    ===================================================== */

    $region = null;
    $comuna = null;
    $calle = null;
    $numeroDireccion = null;
    $departamento = null;
    $referenciaDireccion = null;
    $direccionPedido = null;

    if ($tipoEntrega === "despacho") {

        $sqlDireccion = "
            SELECT
                id_direccion,
                region,
                comuna,
                calle,
                numero,
                departamento,
                referencia
            FROM direcciones_cliente
            WHERE id_direccion = ?
            AND id_cliente = ?
            AND activa = 1
            LIMIT 1
            FOR UPDATE
        ";

        $stmtDireccion =
            $conexion->prepare(
                $sqlDireccion
            );

        $stmtDireccion->bind_param(
            "ii",
            $idDireccion,
            $idCliente
        );

        $stmtDireccion->execute();

        $direccion =
            $stmtDireccion
                ->get_result()
                ->fetch_assoc();

        $stmtDireccion->close();

        if (!$direccion) {

            http_response_code(404);

            throw new Exception(
                "La dirección seleccionada no existe o no pertenece a su cuenta."
            );
        }

        $region = trim(
            $direccion["region"] ?? ""
        );

        $comuna = trim(
            $direccion["comuna"] ?? ""
        );

        $calle = trim(
            $direccion["calle"] ?? ""
        );

        $numeroDireccion = trim(
            $direccion["numero"] ?? ""
        );

        $departamento = trim(
            $direccion[
                "departamento"
            ] ?? ""
        );

        $referenciaDireccion = trim(
            $direccion[
                "referencia"
            ] ?? ""
        );

        if (
            $region === "" ||
            $comuna === "" ||
            $calle === "" ||
            $numeroDireccion === ""
        ) {
            throw new Exception(
                "La dirección seleccionada está incompleta."
            );
        }

        if (
            mb_strtolower(
                $region,
                "UTF-8"
            ) !==
            mb_strtolower(
                "Región Metropolitana",
                "UTF-8"
            )
        ) {
            throw new Exception(
                "Por ahora el despacho está disponible solamente en la Región Metropolitana."
            );
        }

        /*
         * Se construye una copia de la dirección para
         * guardarla dentro del pedido.
         *
         * Si posteriormente el cliente modifica su
         * dirección registrada, el pedido conservará
         * la dirección utilizada durante la compra.
         */

        $direccionPedido = trim(
            $calle .
            " " .
            $numeroDireccion .
            (
                $departamento !== ""
                    ? ", " . $departamento
                    : ""
            )
        );
    }

    /* =====================================================
       CONSULTAR PRODUCTOS REALES
    ===================================================== */

    $sqlProducto = "
        SELECT
            id_producto,
            sku,
            nombre,
            marca,
            cantidad,
            compra,
            precio,
            precio_oferta,
            inicio_oferta,
            fin_oferta,
            visible_tienda
        FROM productos
        WHERE id_producto = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtProducto =
        $conexion->prepare(
            $sqlProducto
        );

    $productosValidados = [];
    $subtotalProductos = 0;

    foreach (
        $productosAgrupados
        as $idProducto =>
            $cantidadSolicitada
    ) {

        $stmtProducto->bind_param(
            "i",
            $idProducto
        );

        $stmtProducto->execute();

        $producto =
            $stmtProducto
                ->get_result()
                ->fetch_assoc();

        if (!$producto) {
            throw new Exception(
                "Uno de los productos ya no está disponible."
            );
        }

        if (
            intval(
                $producto[
                    "visible_tienda"
                ]
            ) !== 1
        ) {
            throw new Exception(
                'El producto "' .
                $producto["nombre"] .
                '" no está disponible en la tienda.'
            );
        }

        $stockDisponible = intval(
            $producto["cantidad"]
        );

        if ($stockDisponible <= 0) {
            throw new Exception(
                'El producto "' .
                $producto["nombre"] .
                '" está agotado.'
            );
        }

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
            obtenerPrecioActualPedido(
                $producto
            );

        if ($precioUnitario <= 0) {
            throw new Exception(
                'El producto "' .
                $producto["nombre"] .
                '" no tiene un precio válido.'
            );
        }

        $totalLinea =
            $precioUnitario *
            $cantidadSolicitada;

        $subtotalProductos +=
            $totalLinea;

        $productosValidados[] = [
            "id_producto" =>
                intval(
                    $producto[
                        "id_producto"
                    ]
                ),

            "sku" =>
                $producto["sku"],

            "nombre" =>
                $producto["nombre"],

            "marca" =>
                $producto["marca"],

            "precio_unitario" =>
                $precioUnitario,

            "costo_unitario" =>
                max(
                    0,
                    intval(
                        $producto["compra"] ?? 0
                    )
                ),

            "cantidad" =>
                $cantidadSolicitada,

            "total_linea" =>
                $totalLinea
        ];
    }

    $stmtProducto->close();

    if ($subtotalProductos <= 0) {
        throw new Exception(
            "No fue posible calcular el total del pedido."
        );
    }

    /* =====================================================
       TARIFA DE DESPACHO
    ===================================================== */

    $costoDespacho = 0;
    $idTarifaDespacho = null;

    if ($tipoEntrega === "despacho") {

        $sqlTarifa = "
            SELECT
                id_tarifa,
                costo
            FROM tarifas_despacho
            WHERE region = ?
            AND activa = 1
            AND (
                LOWER(TRIM(comuna)) =
                    LOWER(TRIM(?))
                OR TRIM(comuna) = ''
            )
            ORDER BY
                CASE
                    WHEN LOWER(TRIM(comuna)) =
                         LOWER(TRIM(?))
                    THEN 0
                    ELSE 1
                END
            LIMIT 1
            FOR UPDATE
        ";

        $stmtTarifa =
            $conexion->prepare(
                $sqlTarifa
            );

        $stmtTarifa->bind_param(
            "sss",
            $region,
            $comuna,
            $comuna
        );

        $stmtTarifa->execute();

        $tarifa =
            $stmtTarifa
                ->get_result()
                ->fetch_assoc();

        $stmtTarifa->close();

        if (!$tarifa) {
            throw new Exception(
                "No tenemos cobertura de despacho para la comuna seleccionada."
            );
        }

        $idTarifaDespacho = intval(
            $tarifa["id_tarifa"]
        );

        $costoDespacho = intval(
            $tarifa["costo"]
        );

        if ($costoDespacho < 5000) {
            throw new Exception(
                "La tarifa de despacho configurada no es válida."
            );
        }
    }

    /* =====================================================
       CALCULAR TOTALES
    ===================================================== */

    $netoProductos = intval(
        round(
            $subtotalProductos / 1.19
        )
    );

    $ivaProductos =
        $subtotalProductos -
        $netoProductos;

    /*
     * La tarifa de despacho también incluye IVA.
     */

    $netoDespacho = intval(
        round(
            $costoDespacho / 1.19
        )
    );

    $ivaDespacho =
        $costoDespacho -
        $netoDespacho;

    $neto =
        $netoProductos +
        $netoDespacho;

    $iva =
        $ivaProductos +
        $ivaDespacho;

    $total =
        $subtotalProductos +
        $costoDespacho;

    /* =====================================================
       CREAR PEDIDO
    ===================================================== */

    $numeroPedido =
        generarNumeroPedido();

    $canal = "online";
    $estado = "pendiente_pago";
    $estadoPago = "pendiente";

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
            id_tarifa_despacho,
            total,
            estado,
            estado_pago,
            observaciones
        )
        VALUES (
            ?,
            ?,
            NULL,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
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

    $stmtPedido =
        $conexion->prepare(
            $sqlPedido
        );

    $stmtPedido->bind_param(
        "sissssssiiiiiisss",
        $numeroPedido,
        $idCliente,
        $canal,
        $tipoEntrega,
        $region,
        $comuna,
        $direccionPedido,
        $referenciaDireccion,
        $neto,
        $iva,
        $subtotalProductos,
        $costoDespacho,
        $idTarifaDespacho,
        $total,
        $estado,
        $estadoPago,
        $observaciones
    );

    $stmtPedido->execute();

    $idPedido =
        $conexion->insert_id;

    $stmtPedido->close();

    /* =====================================================
       REGISTRAR DETALLES
    ===================================================== */

    $sqlDetalle = "
        INSERT INTO detalle_pedido (
            id_pedido,
            id_producto,
            sku,
            nombre_producto,
            marca_producto,
            precio_unitario,
            costo_unitario,
            cantidad,
            total_linea
        )
        VALUES (
            ?,
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

    $stmtDetalle =
        $conexion->prepare(
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
            $producto[
                "precio_unitario"
            ];

        $costoUnitario =
            $producto[
                "costo_unitario"
            ];

        $cantidad =
            $producto["cantidad"];

        $totalLinea =
            $producto["total_linea"];

        $stmtDetalle->bind_param(
            "iisssiiii",
            $idPedido,
            $idProducto,
            $sku,
            $nombreProducto,
            $marcaProducto,
            $precioUnitario,
            $costoUnitario,
            $cantidad,
            $totalLinea
        );

        $stmtDetalle->execute();
    }

    $stmtDetalle->close();

    /* =====================================================
       CONFIRMAR TRANSACCIÓN
    ===================================================== */

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode(
        [
            "ok" => true,

            "mensaje" =>
                "Pedido registrado correctamente.",

            "pedido" => [
                "id_pedido" =>
                    $idPedido,

                "numero_pedido" =>
                    $numeroPedido,

                "neto" =>
                    $neto,

                "iva" =>
                    $iva,

                "subtotal_productos" =>
                    $subtotalProductos,

                "costo_despacho" =>
                    $costoDespacho,

                "id_tarifa_despacho" =>
                    $idTarifaDespacho,

                "id_direccion" =>
                    $tipoEntrega === "despacho"
                        ? $idDireccion
                        : null,

                "total" =>
                    $total,

                "tipo_entrega" =>
                    $tipoEntrega,

                "region" =>
                    $region,

                "comuna" =>
                    $comuna,

                "direccion" =>
                    $direccionPedido,

                "estado" =>
                    $estado,

                "estado_pago" =>
                    $estadoPago
            ]
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error creando pedido: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensajePublico =
            "No fue posible registrar el pedido.";

    } else {

        if (
            http_response_code() < 400
        ) {
            http_response_code(400);
        }

        $mensajePublico =
            $error->getMessage();
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensajePublico
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

/* =====================================================
   OBTENER PRECIO ACTUAL
===================================================== */

function obtenerPrecioActualPedido(
    array $producto
): int {

    $precioNormal = intval(
        $producto["precio"] ?? 0
    );

    $precioOferta = intval(
        $producto["precio_oferta"] ?? 0
    );

    if ($precioOferta <= 0) {
        return $precioNormal;
    }

    $ahora = new DateTime();

    $inicioOferta = !empty(
        $producto["inicio_oferta"]
    )
        ? new DateTime(
            $producto["inicio_oferta"]
        )
        : null;

    $finOferta = !empty(
        $producto["fin_oferta"]
    )
        ? new DateTime(
            $producto["fin_oferta"]
        )
        : null;

    $ofertaIniciada =
        $inicioOferta === null ||
        $ahora >= $inicioOferta;

    $ofertaVigente =
        $finOferta === null ||
        $ahora <= $finOferta;

    if (
        $ofertaIniciada &&
        $ofertaVigente &&
        $precioOferta <
            $precioNormal
    ) {
        return $precioOferta;
    }

    return $precioNormal;
}

/* =====================================================
   GENERAR NÚMERO DE PEDIDO
===================================================== */

function generarNumeroPedido(): string {

    $fecha = date("Ymd-His");

    $codigo = strtoupper(
        bin2hex(
            random_bytes(3)
        )
    );

    return
        "AP-" .
        $fecha .
        "-" .
        $codigo;
}
