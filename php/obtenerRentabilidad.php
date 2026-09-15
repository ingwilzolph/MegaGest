<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("serialize_precision", "-1");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new Exception("Método no permitido.");
    }

    $rol = strtolower(trim((string) ($_SESSION["rol"] ?? "")));

    if ($rol !== "administrador") {
        http_response_code(403);
        throw new Exception(
            "Solo el administrador puede consultar ganancias y rentabilidad."
        );
    }

    $hoy = new DateTimeImmutable("today");
    $inicioMes = $hoy->modify("first day of this month");

    $fechaInicio = trim((string) ($_GET["desde"] ?? $inicioMes->format("Y-m-d")));
    $fechaFin = trim((string) ($_GET["hasta"] ?? $hoy->format("Y-m-d")));
    $canal = strtolower(trim((string) ($_GET["canal"] ?? "")));

    $desde = validarFechaRentabilidad($fechaInicio, "La fecha inicial no es válida.");
    $hasta = validarFechaRentabilidad($fechaFin, "La fecha final no es válida.");

    if ($desde > $hasta) {
        throw new Exception("La fecha inicial no puede ser posterior a la fecha final.");
    }

    if ($desde->diff($hasta)->days > 366) {
        throw new Exception("El período consultado no puede superar 366 días.");
    }

    if (!in_array($canal, ["", "online", "presencial", "taller"], true)) {
        throw new Exception("El canal seleccionado no es válido.");
    }

    $hastaExclusivo = $hasta->modify("+1 day");

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $desdeSql = $conexion->real_escape_string($desde->format("Y-m-d") . " 00:00:00");
    $hastaSql = $conexion->real_escape_string($hastaExclusivo->format("Y-m-d") . " 00:00:00");
    $filtroCanal = in_array($canal, ["online", "presencial"], true)
        ? " AND pe.canal = '" . $conexion->real_escape_string($canal) . "'"
        : "";

    $incluirPedidos = $canal !== "taller";
    $incluirTaller = $canal === "" || $canal === "taller";

    $filtroVentas = "
        pe.fecha_pedido >= '{$desdeSql}'
        AND pe.fecha_pedido < '{$hastaSql}'
        AND pe.estado_pago = 'aprobado'
        AND pe.estado NOT IN ('cancelacion_solicitada', 'cancelado')
        AND EXISTS (
            SELECT 1
            FROM detalle_pedido dp_validacion
            WHERE dp_validacion.id_pedido = pe.id_pedido
        )
        {$filtroCanal}
    ";

    /* RESUMEN DE PEDIDOS VÁLIDOS */

    $sqlVentas = "
        SELECT
            COUNT(*) AS cantidad_pedidos,
            COALESCE(SUM(pe.total), 0) AS ventas_totales,
            COALESCE(SUM(pe.subtotal_productos), 0) AS ventas_productos,
            COALESCE(SUM(pe.costo_despacho), 0) AS ingresos_despacho,
            COALESCE(AVG(pe.total), 0) AS ticket_promedio
        FROM pedidos pe
        WHERE {$filtroVentas}
    ";

    $ventas = $incluirPedidos
        ? $conexion->query($sqlVentas)->fetch_assoc()
        : [];

    /* COSTO HISTÓRICO DE LOS PRODUCTOS VENDIDOS */

    $sqlCostos = "
        SELECT
            COALESCE(SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad), 0)
                AS costo_productos,
            SUM(dp.costo_unitario IS NULL) AS lineas_sin_costo,
            COALESCE(SUM(dp.cantidad), 0) AS unidades_vendidas
        FROM detalle_pedido dp
        INNER JOIN pedidos pe
            ON pe.id_pedido = dp.id_pedido
        WHERE {$filtroVentas}
    ";

    $costos = $incluirPedidos
        ? $conexion->query($sqlCostos)->fetch_assoc()
        : [];

    $ventasProductos = intval($ventas["ventas_productos"] ?? 0);
    $costoProductos = intval($costos["costo_productos"] ?? 0);
    $gananciaBruta = $ventasProductos - $costoProductos;
    $margenBruto = $ventasProductos > 0
        ? round(($gananciaBruta / $ventasProductos) * 100, 2)
        : 0;

    /* RESULTADO POR CANAL */

    $sqlCanales = "
        SELECT
            pe.canal,
            COUNT(DISTINCT pe.id_pedido) AS cantidad_pedidos,
            COALESCE(SUM(dp.total_linea), 0) AS ventas_productos,
            COALESCE(SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad), 0)
                AS costo_productos,
            COALESCE(
                SUM(dp.total_linea) -
                SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad),
                0
            ) AS ganancia_bruta
        FROM pedidos pe
        INNER JOIN detalle_pedido dp
            ON dp.id_pedido = pe.id_pedido
        WHERE {$filtroVentas}
        GROUP BY pe.canal
        ORDER BY ventas_productos DESC
    ";

    $resultadoCanales = $incluirPedidos
        ? $conexion->query($sqlCanales)
        : null;
    $canales = [];

    while ($resultadoCanales && $fila = $resultadoCanales->fetch_assoc()) {
        $ventaCanal = intval($fila["ventas_productos"] ?? 0);
        $gananciaCanal = intval($fila["ganancia_bruta"] ?? 0);

        $canales[] = [
            "canal" => $fila["canal"],
            "cantidad_pedidos" => intval($fila["cantidad_pedidos"] ?? 0),
            "ventas_productos" => $ventaCanal,
            "costo_productos" => intval($fila["costo_productos"] ?? 0),
            "ganancia_bruta" => $gananciaCanal,
            "margen" => $ventaCanal > 0
                ? round(($gananciaCanal / $ventaCanal) * 100, 2)
                : 0
        ];
    }

    /* PRODUCTOS MÁS RENTABLES */

    $sqlProductos = "
        SELECT
            dp.id_producto,
            dp.sku,
            dp.nombre_producto,
            dp.marca_producto,
            SUM(dp.cantidad) AS unidades,
            SUM(dp.total_linea) AS ventas,
            SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad) AS costo,
            SUM(dp.total_linea) -
                SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad) AS ganancia
        FROM detalle_pedido dp
        INNER JOIN pedidos pe
            ON pe.id_pedido = dp.id_pedido
        WHERE {$filtroVentas}
        GROUP BY
            dp.id_producto,
            dp.sku,
            dp.nombre_producto,
            dp.marca_producto
        ORDER BY ganancia DESC, ventas DESC
        LIMIT 15
    ";

    $resultadoProductos = $incluirPedidos
        ? $conexion->query($sqlProductos)
        : null;
    $productos = [];

    while ($resultadoProductos && $fila = $resultadoProductos->fetch_assoc()) {
        $ventaProducto = intval($fila["ventas"] ?? 0);
        $gananciaProducto = intval($fila["ganancia"] ?? 0);

        $productos[] = [
            "id_producto" => $fila["id_producto"] !== null
                ? intval($fila["id_producto"])
                : null,
            "sku" => $fila["sku"],
            "nombre_producto" => $fila["nombre_producto"],
            "marca_producto" => $fila["marca_producto"],
            "unidades" => intval($fila["unidades"] ?? 0),
            "ventas" => $ventaProducto,
            "costo" => intval($fila["costo"] ?? 0),
            "ganancia" => $gananciaProducto,
            "margen" => $ventaProducto > 0
                ? round(($gananciaProducto / $ventaProducto) * 100, 2)
                : 0
        ];
    }

    /* EVOLUCIÓN DIARIA */

    $sqlDiario = "
        SELECT
            DATE(pe.fecha_pedido) AS fecha,
            COUNT(DISTINCT pe.id_pedido) AS pedidos,
            SUM(dp.total_linea) AS ventas,
            SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad) AS costo,
            SUM(dp.total_linea) -
                SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad) AS ganancia
        FROM pedidos pe
        INNER JOIN detalle_pedido dp
            ON dp.id_pedido = pe.id_pedido
        WHERE {$filtroVentas}
        GROUP BY DATE(pe.fecha_pedido)
        ORDER BY fecha ASC
    ";

    $resultadoDiario = $incluirPedidos
        ? $conexion->query($sqlDiario)
        : null;
    $evolucion = [];

    while ($resultadoDiario && $fila = $resultadoDiario->fetch_assoc()) {
        $evolucion[] = [
            "fecha" => $fila["fecha"],
            "pedidos" => intval($fila["pedidos"] ?? 0),
            "ventas" => intval($fila["ventas"] ?? 0),
            "costo" => intval($fila["costo"] ?? 0),
            "ganancia" => intval($fila["ganancia"] ?? 0)
        ];
    }

    /* REEMBOLSOS PROCESADOS EN EL PERÍODO */

    $filtroCanalReembolso = in_array($canal, ["online", "presencial"], true)
        ? " AND pe.canal = '" . $conexion->real_escape_string($canal) . "'"
        : "";

    $sqlReembolsos = "
        SELECT
            COUNT(*) AS cantidad_reembolsos,
            COALESCE(SUM(pe.total), 0) AS monto_reembolsado
        FROM pedidos pe
        WHERE pe.fecha_actualizacion >= '{$desdeSql}'
          AND pe.fecha_actualizacion < '{$hastaSql}'
          AND pe.estado_pago = 'reembolsado'
          {$filtroCanalReembolso}
    ";

    $reembolsos = $incluirPedidos
        ? $conexion->query($sqlReembolsos)->fetch_assoc()
        : [];

    /* =====================================================
       RENTABILIDAD DEL TALLER

       Una OT genera ingresos solamente cuando tiene un pago
       aprobado. La fecha contable es la fecha real del cobro.
    ===================================================== */

    $taller = [
        "cantidad_ordenes" => 0,
        "ingresos_servicios" => 0,
        "ventas_repuestos" => 0,
        "ingresos_mano_obra" => 0,
        "costo_servicios" => 0,
        "costo_repuestos" => 0,
        "costo_mano_obra" => 0,
        "unidades_repuestos" => 0,
        "lineas_sin_costo" => 0
    ];

    if ($incluirTaller) {

        $filtroOT = "
            pot.fecha_pago >= '{$desdeSql}'
            AND pot.fecha_pago < '{$hastaSql}'
            AND LOWER(pot.estado) = 'aprobado'
            AND LOWER(ot.estado) = 'facturada'
        ";

        $sqlTaller = "
            SELECT
                COUNT(DISTINCT pot.id_ot) AS cantidad_ordenes,
                COALESCE(SUM(pot.subtotal_servicios), 0)
                    AS ingresos_servicios,
                COALESCE(SUM(pot.subtotal_productos), 0)
                    AS ventas_repuestos,
                COALESCE(SUM(pot.subtotal_mano_obra), 0)
                    AS ingresos_mano_obra,

                COALESCE((
                    SELECT SUM(COALESCE(ots.costo_total,
                        ots.costo_unitario * ots.cantidad, 0))
                    FROM orden_trabajo_servicios ots
                    INNER JOIN pagos_orden_trabajo pot_s
                        ON pot_s.id_ot = ots.id_ot
                    INNER JOIN orden_trabajo ot_s
                        ON ot_s.id_ot = pot_s.id_ot
                    WHERE pot_s.fecha_pago >= '{$desdeSql}'
                      AND pot_s.fecha_pago < '{$hastaSql}'
                      AND LOWER(pot_s.estado) = 'aprobado'
                      AND LOWER(ot_s.estado) = 'facturada'
                ), 0) AS costo_servicios,

                COALESCE((
                    SELECT SUM(COALESCE(otp.costo_total,
                        otp.costo_unitario * otp.cantidad, 0))
                    FROM orden_trabajo_productos otp
                    INNER JOIN pagos_orden_trabajo pot_p
                        ON pot_p.id_ot = otp.id_ot
                    INNER JOIN orden_trabajo ot_p
                        ON ot_p.id_ot = pot_p.id_ot
                    WHERE pot_p.fecha_pago >= '{$desdeSql}'
                      AND pot_p.fecha_pago < '{$hastaSql}'
                      AND LOWER(pot_p.estado) = 'aprobado'
                      AND LOWER(ot_p.estado) = 'facturada'
                ), 0) AS costo_repuestos,

                COALESCE((
                    SELECT SUM(COALESCE(mo.costo_total,
                        mo.costo_unitario * mo.cantidad, 0))
                    FROM mano_obra_ot mo
                    INNER JOIN pagos_orden_trabajo pot_m
                        ON pot_m.id_ot = mo.id_ot
                    INNER JOIN orden_trabajo ot_m
                        ON ot_m.id_ot = pot_m.id_ot
                    WHERE pot_m.fecha_pago >= '{$desdeSql}'
                      AND pot_m.fecha_pago < '{$hastaSql}'
                      AND LOWER(pot_m.estado) = 'aprobado'
                      AND LOWER(ot_m.estado) = 'facturada'
                ), 0) AS costo_mano_obra,

                COALESCE((
                    SELECT SUM(otp.cantidad)
                    FROM orden_trabajo_productos otp
                    INNER JOIN pagos_orden_trabajo pot_p
                        ON pot_p.id_ot = otp.id_ot
                    INNER JOIN orden_trabajo ot_p
                        ON ot_p.id_ot = pot_p.id_ot
                    WHERE pot_p.fecha_pago >= '{$desdeSql}'
                      AND pot_p.fecha_pago < '{$hastaSql}'
                      AND LOWER(pot_p.estado) = 'aprobado'
                      AND LOWER(ot_p.estado) = 'facturada'
                ), 0) AS unidades_repuestos,

                COALESCE((
                    SELECT COUNT(*)
                    FROM orden_trabajo_servicios ots
                    INNER JOIN pagos_orden_trabajo pot_s
                        ON pot_s.id_ot = ots.id_ot
                    INNER JOIN orden_trabajo ot_s
                        ON ot_s.id_ot = pot_s.id_ot
                    WHERE pot_s.fecha_pago >= '{$desdeSql}'
                      AND pot_s.fecha_pago < '{$hastaSql}'
                      AND LOWER(pot_s.estado) = 'aprobado'
                      AND LOWER(ot_s.estado) = 'facturada'
                      AND ots.costo_unitario IS NULL
                ), 0) +
                COALESCE((
                    SELECT COUNT(*)
                    FROM orden_trabajo_productos otp
                    INNER JOIN pagos_orden_trabajo pot_p
                        ON pot_p.id_ot = otp.id_ot
                    INNER JOIN orden_trabajo ot_p
                        ON ot_p.id_ot = pot_p.id_ot
                    WHERE pot_p.fecha_pago >= '{$desdeSql}'
                      AND pot_p.fecha_pago < '{$hastaSql}'
                      AND LOWER(pot_p.estado) = 'aprobado'
                      AND LOWER(ot_p.estado) = 'facturada'
                      AND otp.costo_unitario IS NULL
                ), 0) +
                COALESCE((
                    SELECT COUNT(*)
                    FROM mano_obra_ot mo
                    INNER JOIN pagos_orden_trabajo pot_m
                        ON pot_m.id_ot = mo.id_ot
                    INNER JOIN orden_trabajo ot_m
                        ON ot_m.id_ot = pot_m.id_ot
                    WHERE pot_m.fecha_pago >= '{$desdeSql}'
                      AND pot_m.fecha_pago < '{$hastaSql}'
                      AND LOWER(pot_m.estado) = 'aprobado'
                      AND LOWER(ot_m.estado) = 'facturada'
                      AND mo.costo_unitario IS NULL
                ), 0) AS lineas_sin_costo

            FROM pagos_orden_trabajo pot
            INNER JOIN orden_trabajo ot
                ON ot.id_ot = pot.id_ot
            WHERE {$filtroOT}
        ";

        $taller = $conexion->query($sqlTaller)->fetch_assoc();

        $ingresosTaller =
            intval($taller["ingresos_servicios"] ?? 0) +
            intval($taller["ventas_repuestos"] ?? 0) +
            intval($taller["ingresos_mano_obra"] ?? 0);

        $costosTaller =
            intval($taller["costo_servicios"] ?? 0) +
            intval($taller["costo_repuestos"] ?? 0) +
            intval($taller["costo_mano_obra"] ?? 0);

        $gananciaTaller = $ingresosTaller - $costosTaller;

        $canales[] = [
            "canal" => "taller",
            "cantidad_pedidos" =>
                intval($taller["cantidad_ordenes"] ?? 0),
            "ventas_productos" => $ingresosTaller,
            "costo_productos" => $costosTaller,
            "ganancia_bruta" => $gananciaTaller,
            "margen" => $ingresosTaller > 0
                ? round(($gananciaTaller / $ingresosTaller) * 100, 2)
                : 0
        ];

        /* Repuestos utilizados en órdenes facturadas */

        $sqlProductosOT = "
            SELECT
                otp.id_producto,
                p.sku,
                COALESCE(p.nombre, otp.descripcion) AS nombre_producto,
                p.marca AS marca_producto,
                SUM(otp.cantidad) AS unidades,
                SUM(otp.total) AS ventas,
                SUM(COALESCE(otp.costo_total,
                    otp.costo_unitario * otp.cantidad, 0)) AS costo
            FROM orden_trabajo_productos otp
            INNER JOIN pagos_orden_trabajo pot
                ON pot.id_ot = otp.id_ot
            INNER JOIN orden_trabajo ot
                ON ot.id_ot = otp.id_ot
            LEFT JOIN productos p
                ON p.id_producto = otp.id_producto
            WHERE {$filtroOT}
            GROUP BY
                otp.id_producto,
                p.sku,
                COALESCE(p.nombre, otp.descripcion),
                p.marca
        ";

        $resultadoProductosOT =
            $conexion->query($sqlProductosOT);

        $productosCombinados = [];

        foreach ($productos as $producto) {
            $clave = "P-" . ($producto["id_producto"] ?? $producto["sku"]);
            $productosCombinados[$clave] = $producto;
        }

        while ($fila = $resultadoProductosOT->fetch_assoc()) {

            $clave = "P-" . ($fila["id_producto"] ?? $fila["sku"]);
            $ventasLinea = intval($fila["ventas"] ?? 0);
            $costoLinea = intval($fila["costo"] ?? 0);

            if (!isset($productosCombinados[$clave])) {
                $productosCombinados[$clave] = [
                    "id_producto" => intval($fila["id_producto"]),
                    "sku" => $fila["sku"],
                    "nombre_producto" => $fila["nombre_producto"],
                    "marca_producto" => $fila["marca_producto"],
                    "unidades" => 0,
                    "ventas" => 0,
                    "costo" => 0,
                    "ganancia" => 0,
                    "margen" => 0
                ];
            }

            $productosCombinados[$clave]["unidades"] +=
                intval($fila["unidades"] ?? 0);
            $productosCombinados[$clave]["ventas"] += $ventasLinea;
            $productosCombinados[$clave]["costo"] += $costoLinea;
        }

        foreach ($productosCombinados as &$producto) {
            $producto["ganancia"] =
                $producto["ventas"] - $producto["costo"];
            $producto["margen"] = $producto["ventas"] > 0
                ? round(($producto["ganancia"] / $producto["ventas"]) * 100, 2)
                : 0;
        }
        unset($producto);

        $productos = array_values($productosCombinados);

        usort($productos, function (array $a, array $b): int {
            return $b["ganancia"] <=> $a["ganancia"];
        });

        $productos = array_slice($productos, 0, 15);

        /* Evolución diaria del taller */

        $sqlDiarioOT = "
            SELECT
                DATE(pot.fecha_pago) AS fecha,
                COUNT(DISTINCT pot.id_ot) AS operaciones,
                COALESCE(SUM(pot.total), 0) AS ventas,
                COALESCE(SUM(datos.costos), 0) AS costo
            FROM pagos_orden_trabajo pot
            INNER JOIN orden_trabajo ot
                ON ot.id_ot = pot.id_ot
            LEFT JOIN (
                SELECT
                    costos_ot.id_ot,
                    SUM(costos_ot.costos) AS costos
                FROM (
                    SELECT
                        id_ot,
                        SUM(COALESCE(costo_total,
                            costo_unitario * cantidad, 0)) AS costos
                    FROM orden_trabajo_servicios
                    GROUP BY id_ot

                    UNION ALL

                    SELECT
                        id_ot,
                        SUM(COALESCE(costo_total,
                            costo_unitario * cantidad, 0)) AS costos
                    FROM orden_trabajo_productos
                    GROUP BY id_ot

                    UNION ALL

                    SELECT
                        id_ot,
                        SUM(COALESCE(costo_total,
                            costo_unitario * cantidad, 0)) AS costos
                    FROM mano_obra_ot
                    GROUP BY id_ot
                ) costos_ot
                GROUP BY costos_ot.id_ot
            ) datos ON datos.id_ot = ot.id_ot
            WHERE {$filtroOT}
            GROUP BY DATE(pot.fecha_pago)
            ORDER BY fecha ASC
        ";

        $resultadoDiarioOT =
            $conexion->query($sqlDiarioOT);

        $evolucionCombinada = [];

        foreach ($evolucion as $dia) {
            $evolucionCombinada[$dia["fecha"]] = $dia;
        }

        while ($fila = $resultadoDiarioOT->fetch_assoc()) {
            $fecha = $fila["fecha"];

            if (!isset($evolucionCombinada[$fecha])) {
                $evolucionCombinada[$fecha] = [
                    "fecha" => $fecha,
                    "pedidos" => 0,
                    "ventas" => 0,
                    "costo" => 0,
                    "ganancia" => 0
                ];
            }

            $evolucionCombinada[$fecha]["pedidos"] +=
                intval($fila["operaciones"] ?? 0);
            $evolucionCombinada[$fecha]["ventas"] +=
                intval($fila["ventas"] ?? 0);
            $evolucionCombinada[$fecha]["costo"] +=
                intval($fila["costo"] ?? 0);
            $evolucionCombinada[$fecha]["ganancia"] =
                $evolucionCombinada[$fecha]["ventas"] -
                $evolucionCombinada[$fecha]["costo"];
        }

        ksort($evolucionCombinada);
        $evolucion = array_values($evolucionCombinada);
    }

    /* TOTALES GENERALES: PEDIDOS + TALLER */

    $cantidadPedidos = intval($ventas["cantidad_pedidos"] ?? 0);
    $cantidadOrdenes = intval($taller["cantidad_ordenes"] ?? 0);
    $cantidadOperaciones = $cantidadPedidos + $cantidadOrdenes;

    $ingresosPedidos = intval($ventas["ventas_totales"] ?? 0);
    $ingresosServicios = intval($taller["ingresos_servicios"] ?? 0);
    $ventasRepuestosOT = intval($taller["ventas_repuestos"] ?? 0);
    $ingresosManoObra = intval($taller["ingresos_mano_obra"] ?? 0);
    $ingresosTotales =
        $ingresosPedidos +
        $ingresosServicios +
        $ventasRepuestosOT +
        $ingresosManoObra;

    $costoPedidos = intval($costos["costo_productos"] ?? 0);
    $costoServicios = intval($taller["costo_servicios"] ?? 0);
    $costoRepuestosOT = intval($taller["costo_repuestos"] ?? 0);
    $costoManoObra = intval($taller["costo_mano_obra"] ?? 0);
    $costosTotales =
        $costoPedidos +
        $costoServicios +
        $costoRepuestosOT +
        $costoManoObra;

    $gananciaTotal = $ingresosTotales - $costosTotales;
    $margenTotal = $ingresosTotales > 0
        ? round(($gananciaTotal / $ingresosTotales) * 100, 2)
        : 0;

    $lineasSinCosto =
        intval($costos["lineas_sin_costo"] ?? 0) +
        intval($taller["lineas_sin_costo"] ?? 0);

    echo json_encode([
        "ok" => true,
        "filtros" => [
            "desde" => $desde->format("Y-m-d"),
            "hasta" => $hasta->format("Y-m-d"),
            "canal" => $canal
        ],
        "resumen" => [
            "cantidad_pedidos" => $cantidadPedidos,
            "cantidad_ordenes" => $cantidadOrdenes,
            "cantidad_operaciones" => $cantidadOperaciones,
            "ventas_totales" => $ingresosTotales,
            "ingresos_totales" => $ingresosTotales,
            "ventas_productos" => $ventasProductos,
            "ingresos_servicios" => $ingresosServicios,
            "ventas_repuestos_ot" => $ventasRepuestosOT,
            "ingresos_mano_obra" => $ingresosManoObra,
            "ingresos_taller" =>
                $ingresosServicios +
                $ventasRepuestosOT +
                $ingresosManoObra,
            "ingresos_despacho" => intval($ventas["ingresos_despacho"] ?? 0),
            "costo_productos" => $costoPedidos + $costoRepuestosOT,
            "costo_servicios" => $costoServicios,
            "costo_mano_obra" => $costoManoObra,
            "costos_totales" => $costosTotales,
            "ganancia_bruta" => $gananciaTotal,
            "margen_bruto" => $margenTotal,
            "ticket_promedio" => $cantidadOperaciones > 0
                ? intval(round($ingresosTotales / $cantidadOperaciones))
                : 0,
            "unidades_vendidas" =>
                intval($costos["unidades_vendidas"] ?? 0) +
                intval($taller["unidades_repuestos"] ?? 0),
            "lineas_sin_costo" => $lineasSinCosto,
            "cantidad_reembolsos" => intval($reembolsos["cantidad_reembolsos"] ?? 0),
            "monto_reembolsado" => intval($reembolsos["monto_reembolsado"] ?? 0)
        ],
        "canales" => $canales,
        "productos" => $productos,
        "evolucion" => $evolucion
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    error_log("Error obteniendo rentabilidad: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible calcular la rentabilidad.";
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

function validarFechaRentabilidad(string $fecha, string $mensaje): DateTimeImmutable
{
    $resultado = DateTimeImmutable::createFromFormat("!Y-m-d", $fecha);

    if (!$resultado || $resultado->format("Y-m-d") !== $fecha) {
        throw new Exception($mensaje);
    }

    return $resultado;
}

?>
