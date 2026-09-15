<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "verificarSesionAjax.php";
require_once "conexion.php";

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

    /* =====================================================
       VALIDAR ROL
    ===================================================== */

    $rolUsuario = strtolower(
        trim(
            (string) (
                $_SESSION["rol"] ?? ""
            )
        )
    );

    $rolesPermitidos = [
        "administrador",
        "vendedor",
        "cajero"
    ];

    if (
        !in_array(
            $rolUsuario,
            $rolesPermitidos,
            true
        )
    ) {

        http_response_code(403);

        throw new Exception(
            "No tiene permisos para consultar el inventario."
        );
    }

    /*
     * El cajero puede consultar existencias,
     * pero no verá costos ni valor del inventario.
     */

    $puedeGestionar = in_array(
        $rolUsuario,
        [
            "administrador",
            "vendedor"
        ],
        true
    );

    $puedeVerCostos = $puedeGestionar;

    /* =====================================================
       CONSULTAR PRODUCTOS
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            id_producto,
            sku,
            categoria,
            nombre,
            descripcion,
            marca,
            cantidad,
            stock_minimo,
            visible_tienda,
            destacado,
            compra,
            precio,
            precio_oferta,
            inicio_oferta,
            fin_oferta,
            ubicacion,
            fecha_actualizacion
        FROM productos
        ORDER BY
            nombre ASC,
            id_producto ASC
    ";

    $resultado = $conexion->query($sql);

    $productos = [];

    $totalProductos = 0;
    $totalUnidades = 0;
    $productosBajoStock = 0;
    $productosAgotados = 0;
    $valorInventario = 0;

    while (
        $producto =
        $resultado->fetch_assoc()
    ) {

        $idProducto = intval(
            $producto["id_producto"]
        );

        $cantidad = intval(
            $producto["cantidad"] ?? 0
        );

        $stockMinimo = intval(
            $producto["stock_minimo"] ?? 0
        );

        $visibleTienda = intval(
            $producto["visible_tienda"] ?? 0
        );

        $destacado = intval(
            $producto["destacado"] ?? 0
        );

        $costoUnitario = intval(
            $producto["compra"] ?? 0
        );

        $precioVenta = intval(
            $producto["precio"] ?? 0
        );

        $precioOferta =
            $producto["precio_oferta"] !== null
                ? intval(
                    $producto["precio_oferta"]
                )
                : null;

        /* =================================================
           ESTADO DEL STOCK
        ================================================= */

        if ($visibleTienda === 0) {

            $estado = "oculto";
            $estadoVisual = "Oculto";

        } elseif ($cantidad <= 0) {

            $estado = "agotado";
            $estadoVisual = "Agotado";

            $productosAgotados++;

        } elseif ($cantidad <= $stockMinimo) {

            $estado = "bajo_stock";
            $estadoVisual = "Bajo stock";

            $productosBajoStock++;

        } else {

            $estado = "disponible";
            $estadoVisual = "Disponible";
        }

        $valorStock =
            $cantidad * $costoUnitario;

        $totalProductos++;
        $totalUnidades += $cantidad;
        $valorInventario += $valorStock;

        /* =================================================
                     PRECIO EFECTIVO DE VENTA
        ================================================= */

        $ofertaVigente = false;

        if (
            $precioOferta !== null &&
            $precioOferta > 0 &&
            $precioOferta < $precioVenta
        ) {

            $ahora = new DateTime();

            $inicioOferta =
                !empty(
                    $producto["inicio_oferta"]
                )
                    ? new DateTime(
                        $producto["inicio_oferta"]
                    )
                    : null;

            $finOferta =
                !empty(
                    $producto["fin_oferta"]
                )
                    ? new DateTime(
                        $producto["fin_oferta"]
                    )
                    : null;

            $ofertaIniciada =
                $inicioOferta === null ||
                $ahora >= $inicioOferta;

            $ofertaNoFinalizada =
                $finOferta === null ||
                $ahora <= $finOferta;

            $ofertaVigente =
                $ofertaIniciada &&
                $ofertaNoFinalizada;
        }

        $precioEfectivo =
            $ofertaVigente
                ? $precioOferta
                : $precioVenta;

        /* =================================================
           CONSTRUIR RESPUESTA
        ================================================= */

        $productos[] = [

            "id_producto" => $idProducto,

            "sku" =>
                $producto["sku"] ??
                "Sin SKU",

            "categoria" =>
                $producto["categoria"],

            "nombre" =>
                $producto["nombre"] ??
                "Producto sin nombre",

            "descripcion" =>
                $producto["descripcion"],

            "marca" =>
                $producto["marca"],

            "cantidad" => $cantidad,

            "stock_minimo" => $stockMinimo,

            "visible_tienda" =>
                $visibleTienda,

            "destacado" => $destacado,

            /*
             * Los costos se ocultan al cajero
             * desde el servidor.
             */

            "compra" =>
                $puedeVerCostos
                    ? $costoUnitario
                    : null,

            "valor_stock" =>
                $puedeVerCostos
                    ? $valorStock
                    : null,

            "precio" => $precioVenta,

            "precio_oferta" =>
                $precioOferta,

            "oferta_vigente" =>
                $ofertaVigente,

            "precio_efectivo" =>
                $precioEfectivo,

            "inicio_oferta" =>
                $producto["inicio_oferta"],

            "fin_oferta" =>
                $producto["fin_oferta"],

            "ubicacion" =>
                $producto["ubicacion"],

            "estado" => $estado,

            "estado_visual" =>
                $estadoVisual,

            "fecha_actualizacion" =>
                $producto[
                    "fecha_actualizacion"
                ]
        ];
    }

    /* =====================================================
       RESPUESTA
    ===================================================== */

    echo json_encode(
        [
            "ok" => true,

            "resumen" => [
                "total_productos" =>
                    $totalProductos,

                "total_unidades" =>
                    $totalUnidades,

                "productos_bajo_stock" =>
                    $productosBajoStock,

                "productos_agotados" =>
                    $productosAgotados,

                "valor_inventario" =>
                    $puedeVerCostos
                        ? $valorInventario
                        : null
            ],

            "datos" => $productos,

            "permisos" => [
                "consultar" => true,

                "ver_costos" =>
                    $puedeVerCostos,

                "registrar_entrada" =>
                    $puedeGestionar,

                "ajustar_stock" =>
                    $puedeGestionar,

                "gestionar_proveedores" =>
                    $puedeGestionar
            ]
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $error) {

    error_log(
        "Error obteniendo inventario: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensaje =
            "No fue posible cargar el inventario.";

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