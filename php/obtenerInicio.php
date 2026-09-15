<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";

date_default_timezone_set("America/Santiago");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    $idUsuario = intval($_SESSION["id_usuario"] ?? 0);

    if ($idUsuario <= 0) {
        http_response_code(401);
        throw new RuntimeException("No se pudo identificar al usuario.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /* La autorización siempre se comprueba en la base de datos. */
    $stmt = $conexion->prepare(
        "SELECT id_usuario, nombre, apellido, rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        http_response_code(403);
        throw new RuntimeException("El usuario no existe.");
    }

    if (strtolower(trim((string) $usuario["estado"])) !== "activo") {
        http_response_code(403);
        throw new RuntimeException("El usuario está inactivo.");
    }

    $rol = strtolower(trim((string) $usuario["rol"]));
    $rolesValidos = ["administrador", "cajero", "vendedor", "mecanico"];

    if (!in_array($rol, $rolesValidos, true)) {
        http_response_code(403);
        throw new RuntimeException("El rol del usuario no tiene acceso al Inicio.");
    }

    $esAdministrador = $rol === "administrador";
    $esCajero = $rol === "cajero";
    $esVendedor = $rol === "vendedor";
    $esMecanico = $rol === "mecanico";

    $permisos = [
        "ver_ventas" => $esAdministrador || $esCajero || $esVendedor,
        "ver_costos" => $esAdministrador,
        "ver_ganancia" => $esAdministrador,
        "ver_agenda_completa" => $esAdministrador || $esCajero || $esVendedor,
        "ver_ot_completas" => $esAdministrador || $esCajero,
        "ver_stock" => $esAdministrador || $esVendedor,
        "ver_remuneracion_propia" => true,
        "ver_remuneraciones" => $esAdministrador
    ];

    /* =====================================================
       INDICADORES GENERALES DEL DÍA
    ===================================================== */

    if ($esMecanico) {
        $citasHoy = enteroConsulta(
            $conexion,
            "SELECT COUNT(DISTINCT c.id_cita)
             FROM citas c
             INNER JOIN orden_trabajo ot ON ot.id_cita = c.id_cita
             WHERE c.fecha = CURDATE()
               AND ot.id_usuario = {$idUsuario}
               AND LOWER(c.estado) NOT IN ('cancelada', 'finalizada')"
        );
    } else {
        $citasHoy = enteroConsulta(
            $conexion,
            "SELECT COUNT(*)
             FROM citas
             WHERE fecha = CURDATE()
               AND LOWER(estado) NOT IN ('cancelada', 'finalizada')"
        );
    }

    $ordenesActivasSql =
        "SELECT COUNT(*)
         FROM orden_trabajo
         WHERE LOWER(estado) IN ('abierta', 'en proceso')";

    if ($esMecanico) {
        $ordenesActivasSql .= " AND id_usuario = " . $idUsuario;
    }

    $ordenesActivas = enteroConsulta($conexion, $ordenesActivasSql);

    $pendientesCobro = enteroConsulta(
        $conexion,
        "SELECT COUNT(*)
         FROM orden_trabajo ot
         WHERE LOWER(ot.estado) = 'terminada'
           AND NOT EXISTS (
               SELECT 1
               FROM pagos_orden_trabajo pot
               WHERE pot.id_ot = ot.id_ot
                 AND LOWER(pot.estado) = 'aprobado'
           )"
    );

    $filtroPedidosUsuario = $esVendedor
        ? " AND id_usuario = {$idUsuario}"
        : "";

    $pedidosPendientes = enteroConsulta(
        $conexion,
        "SELECT COUNT(*)
         FROM pedidos
         WHERE estado IN (
             'pagado', 'preparando', 'listo_retiro',
             'enviado', 'cancelacion_solicitada'
         ){$filtroPedidosUsuario}"
    );

    $stockCritico = enteroConsulta(
        $conexion,
        "SELECT COUNT(*)
         FROM productos
         WHERE visible_tienda = 1
           AND cantidad <= stock_minimo"
    );

    $ventasPedidosHoy = enteroConsulta(
        $conexion,
        "SELECT COALESCE(SUM(total), 0)
         FROM pedidos
         WHERE fecha_pedido >= CURDATE()
           AND fecha_pedido < CURDATE() + INTERVAL 1 DAY
           AND estado_pago = 'aprobado'
           AND estado NOT IN ('cancelacion_solicitada', 'cancelado')
           {$filtroPedidosUsuario}"
    );

    $ventasTallerHoy = ($esAdministrador || $esCajero)
        ? enteroConsulta(
            $conexion,
            "SELECT COALESCE(SUM(total), 0)
             FROM pagos_orden_trabajo
             WHERE fecha_pago >= CURDATE()
               AND fecha_pago < CURDATE() + INTERVAL 1 DAY
               AND LOWER(estado) = 'aprobado'"
        )
        : 0;

    $ventasHoy = $ventasPedidosHoy + $ventasTallerHoy;

    $comisionesPropias = enteroConsulta(
        $conexion,
        "SELECT COALESCE(SUM(monto_comision), 0)
         FROM comisiones_usuarios
         WHERE id_usuario = {$idUsuario}
           AND estado = 'pendiente'"
    );

    $gananciaHoy = null;
    $costosHoy = null;

    if ($esAdministrador) {
        $costoPedidos = enteroConsulta(
            $conexion,
            "SELECT COALESCE(SUM(COALESCE(dp.costo_unitario, 0) * dp.cantidad), 0)
             FROM detalle_pedido dp
             INNER JOIN pedidos pe ON pe.id_pedido = dp.id_pedido
             WHERE pe.fecha_pedido >= CURDATE()
               AND pe.fecha_pedido < CURDATE() + INTERVAL 1 DAY
               AND pe.estado_pago = 'aprobado'
               AND pe.estado NOT IN ('cancelacion_solicitada', 'cancelado')"
        );

        $costoTaller = enteroConsulta(
            $conexion,
            "SELECT COALESCE(SUM(costos.costo), 0)
             FROM pagos_orden_trabajo pot
             INNER JOIN (
                 SELECT id_ot, SUM(costo) AS costo
                 FROM (
                     SELECT id_ot,
                            SUM(COALESCE(costo_total, costo_unitario * cantidad, 0)) AS costo
                     FROM orden_trabajo_servicios
                     GROUP BY id_ot
                     UNION ALL
                     SELECT id_ot,
                            SUM(COALESCE(costo_total, costo_unitario * cantidad, 0)) AS costo
                     FROM orden_trabajo_productos
                     GROUP BY id_ot
                     UNION ALL
                     SELECT id_ot,
                            SUM(COALESCE(costo_total, costo_unitario * cantidad, 0)) AS costo
                     FROM mano_obra_ot
                     GROUP BY id_ot
                 ) costos_linea
                 GROUP BY id_ot
             ) costos ON costos.id_ot = pot.id_ot
             WHERE pot.fecha_pago >= CURDATE()
               AND pot.fecha_pago < CURDATE() + INTERVAL 1 DAY
               AND LOWER(pot.estado) = 'aprobado'"
        );

        $costosHoy = $costoPedidos + $costoTaller;
        $gananciaHoy = $ventasHoy - $costosHoy;
    }

    /* =====================================================
       AGENDA DEL DÍA
    ===================================================== */

    $sqlAgenda = "
        SELECT
            c.id_cita,
            c.numeroReserva,
            c.nombre,
            c.telefono,
            c.patente,
            c.vehiculo,
            c.servicio,
            TIME_FORMAT(c.hora, '%H:%i') AS hora,
            c.estado,
            ot.id_ot,
            ot.numeroOT,
            ot.id_usuario AS id_mecanico,
            TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')))
                AS mecanico
        FROM citas c
        LEFT JOIN orden_trabajo ot ON ot.id_cita = c.id_cita
        LEFT JOIN login_admin u ON u.id_usuario = ot.id_usuario
        WHERE c.fecha = CURDATE()
          AND LOWER(c.estado) <> 'cancelada'
    ";

    if ($esMecanico) {
        $sqlAgenda .= " AND ot.id_usuario = {$idUsuario} ";
    }

    $sqlAgenda .= " ORDER BY c.hora ASC LIMIT 10";
    $agenda = filasConsulta($conexion, $sqlAgenda);

    foreach ($agenda as &$fila) {
        $fila["id_cita"] = intval($fila["id_cita"]);
        $fila["id_ot"] = $fila["id_ot"] !== null ? intval($fila["id_ot"]) : null;
        $fila["id_mecanico"] = $fila["id_mecanico"] !== null
            ? intval($fila["id_mecanico"])
            : null;
    }
    unset($fila);

    /* =====================================================
       ÓRDENES RECIENTES O PENDIENTES
    ===================================================== */

    $sqlOrdenes = "
        SELECT
            ot.id_ot,
            ot.id_cita,
            ot.numeroOT,
            ot.estado,
            ot.fechaInicio,
            ot.fechaFin,
            ot.id_usuario AS id_mecanico,
            c.nombre AS cliente,
            c.vehiculo,
            c.patente,
            TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')))
                AS mecanico
        FROM orden_trabajo ot
        INNER JOIN citas c ON c.id_cita = ot.id_cita
        LEFT JOIN login_admin u ON u.id_usuario = ot.id_usuario
        WHERE LOWER(ot.estado) IN ('abierta', 'en proceso', 'terminada')
    ";

    if ($esMecanico) {
        $sqlOrdenes .= " AND ot.id_usuario = {$idUsuario} ";
    }

    $sqlOrdenes .= "
        ORDER BY
            CASE LOWER(ot.estado)
                WHEN 'en proceso' THEN 1
                WHEN 'abierta' THEN 2
                WHEN 'terminada' THEN 3
                ELSE 4
            END,
            COALESCE(ot.fechaInicio, '9999-12-31') ASC
        LIMIT 8
    ";

    $ordenes = filasConsulta($conexion, $sqlOrdenes);

    foreach ($ordenes as &$fila) {
        $fila["id_ot"] = intval($fila["id_ot"]);
        $fila["id_cita"] = intval($fila["id_cita"]);
        $fila["id_mecanico"] = $fila["id_mecanico"] !== null
            ? intval($fila["id_mecanico"])
            : null;
    }
    unset($fila);

    /* =====================================================
       EVOLUCIÓN DE INGRESOS: ÚLTIMOS SIETE DÍAS
    ===================================================== */

    $inicioGrafico = (new DateTimeImmutable("today"))->modify("-6 days");
    $mapaGrafico = [];

    for ($i = 0; $i < 7; $i++) {
        $fechaGrafico = $inicioGrafico->modify("+{$i} days")->format("Y-m-d");
        $mapaGrafico[$fechaGrafico] = [
            "fecha" => $fechaGrafico,
            "ventas" => 0,
            "taller" => 0,
            "total" => 0
        ];
    }

    $ventasDiarias = !$esMecanico ? filasConsulta(
        $conexion,
        "SELECT DATE(fecha_pedido) AS fecha, COALESCE(SUM(total), 0) AS total
         FROM pedidos
         WHERE fecha_pedido >= CURDATE() - INTERVAL 6 DAY
           AND fecha_pedido < CURDATE() + INTERVAL 1 DAY
           AND estado_pago = 'aprobado'
           AND estado NOT IN ('cancelacion_solicitada', 'cancelado')
           {$filtroPedidosUsuario}
         GROUP BY DATE(fecha_pedido)"
    ) : [];

    foreach ($ventasDiarias as $dia) {
        if (isset($mapaGrafico[$dia["fecha"]])) {
            $mapaGrafico[$dia["fecha"]]["ventas"] = intval($dia["total"]);
        }
    }

    $tallerDiario = ($esAdministrador || $esCajero) ? filasConsulta(
        $conexion,
        "SELECT DATE(fecha_pago) AS fecha, COALESCE(SUM(total), 0) AS total
         FROM pagos_orden_trabajo
         WHERE fecha_pago >= CURDATE() - INTERVAL 6 DAY
           AND fecha_pago < CURDATE() + INTERVAL 1 DAY
           AND LOWER(estado) = 'aprobado'
         GROUP BY DATE(fecha_pago)"
    ) : [];

    foreach ($tallerDiario as $dia) {
        if (isset($mapaGrafico[$dia["fecha"]])) {
            $mapaGrafico[$dia["fecha"]]["taller"] = intval($dia["total"]);
        }
    }

    foreach ($mapaGrafico as &$dia) {
        $dia["total"] = $dia["ventas"] + $dia["taller"];
    }
    unset($dia);

    /* =====================================================
       ALERTAS PRIORITARIAS
    ===================================================== */

    $alertas = [];

    if ($esAdministrador || $esCajero) {
        agregarAlerta(
            $alertas,
            $pendientesCobro,
            "cobro",
            "Órdenes pendientes de cobro",
            "Hay órdenes terminadas listas para cobrar y facturar.",
            "citas",
            "alta"
        );
    }

    if ($esAdministrador || $esCajero) {
        agregarAlerta(
            $alertas,
            enteroConsulta(
                $conexion,
                "SELECT COUNT(*) FROM pedidos WHERE estado = 'cancelacion_solicitada'"
            ),
            "cancelacion",
            "Cancelaciones solicitadas",
            "Hay pedidos que requieren revisión administrativa.",
            "pedidos",
            "alta"
        );
    }

    if ($permisos["ver_stock"]) {
        agregarAlerta(
            $alertas,
            enteroConsulta(
                $conexion,
                "SELECT COUNT(*) FROM productos WHERE visible_tienda = 1 AND cantidad = 0"
            ),
            "agotado",
            "Productos agotados",
            "Estos productos necesitan reposición de inventario.",
            "inventario",
            "alta"
        );

        agregarAlerta(
            $alertas,
            enteroConsulta(
                $conexion,
                "SELECT COUNT(*)
                 FROM productos
                 WHERE visible_tienda = 1
                   AND cantidad > 0
                   AND cantidad <= stock_minimo"
            ),
            "stock",
            "Productos con bajo stock",
            "Las existencias están cerca del mínimo configurado.",
            "inventario",
            "media"
        );
    }

    agregarAlerta(
        $alertas,
        enteroConsulta(
            $conexion,
            "SELECT COUNT(*)
             FROM citas
             WHERE fecha = CURDATE()
               AND LOWER(estado) IN ('pendiente', 'confirmada')"
        ),
        "cita",
        "Citas pendientes para hoy",
        "Revise la agenda y prepare la recepción de los vehículos.",
        "citas",
        "normal"
    );

    usort($alertas, static function (array $a, array $b): int {
        $orden = ["alta" => 1, "media" => 2, "normal" => 3];
        return ($orden[$a["prioridad"]] ?? 9) <=> ($orden[$b["prioridad"]] ?? 9);
    });

    /* =====================================================
       ACTIVIDAD RECIENTE
    ===================================================== */

    $actividad = [];

    foreach (!$esMecanico ? filasConsulta(
        $conexion,
        "SELECT id_pedido AS id, fecha_pedido AS fecha, canal, estado, total
         FROM pedidos
         WHERE 1 = 1 {$filtroPedidosUsuario}
         ORDER BY fecha_pedido DESC
         LIMIT 5"
    ) : [] as $fila) {
        $actividad[] = [
            "tipo" => "pedido",
            "id" => intval($fila["id"]),
            "fecha" => $fila["fecha"],
            "titulo" => "Pedido #" . intval($fila["id"]),
            "detalle" => ucfirst((string) $fila["canal"]) . " · " .
                ucfirst(str_replace("_", " ", (string) $fila["estado"])),
            "monto" => intval($fila["total"]),
            "destino" => "pedidos"
        ];
    }

    foreach (($esAdministrador || $esCajero) ? filasConsulta(
        $conexion,
        "SELECT pot.id_pago_ot AS id, pot.fecha_pago AS fecha,
                pot.total, ot.numeroOT
         FROM pagos_orden_trabajo pot
         INNER JOIN orden_trabajo ot ON ot.id_ot = pot.id_ot
         WHERE LOWER(pot.estado) = 'aprobado'
         ORDER BY pot.fecha_pago DESC
         LIMIT 5"
    ) : [] as $fila) {
        $actividad[] = [
            "tipo" => "taller",
            "id" => intval($fila["id"]),
            "fecha" => $fila["fecha"],
            "titulo" => "OT " . $fila["numeroOT"] . " facturada",
            "detalle" => "Pago del taller aprobado",
            "monto" => intval($fila["total"]),
            "destino" => "citas"
        ];
    }

    usort($actividad, static function (array $a, array $b): int {
        return strcmp((string) $b["fecha"], (string) $a["fecha"]);
    });
    $actividad = array_slice($actividad, 0, 8);

    /* El mecánico no necesita movimientos comerciales generales. */
    if ($esMecanico) {
        $actividad = [];
    }

    $tarjetas = [
        "ventas_hoy" => $permisos["ver_ventas"] ? $ventasHoy : null,
        "ganancia_hoy" => $gananciaHoy,
        "costos_hoy" => $costosHoy,
        "citas_hoy" => $citasHoy,
        "ordenes_activas" => $ordenesActivas,
        "pendientes_cobro" => ($esAdministrador || $esCajero)
            ? $pendientesCobro
            : null,
        "pedidos_pendientes" => ($esAdministrador || $esCajero || $esVendedor)
            ? $pedidosPendientes
            : null,
        "stock_critico" => $permisos["ver_stock"] ? $stockCritico : null,
        "comisiones_propias" => $comisionesPropias,
        "alertas" => count($alertas)
    ];

    echo json_encode([
        "ok" => true,
        "generado_en" => date("Y-m-d H:i:s"),
        "usuario" => [
            "id_usuario" => intval($usuario["id_usuario"]),
            "nombre" => trim($usuario["nombre"] . " " . $usuario["apellido"]),
            "rol" => $rol
        ],
        "permisos" => $permisos,
        "tarjetas" => $tarjetas,
        "grafico" => array_values($mapaGrafico),
        "agenda" => $agenda,
        "ordenes" => $ordenes,
        "alertas" => $alertas,
        "actividad" => $actividad
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    error_log("Error cargando Inicio: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar la información de Inicio.";
    } else {
        if (http_response_code() < 400) {
            http_response_code(400);
        }
        $mensaje = $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

function enteroConsulta(mysqli $conexion, string $sql): int
{
    $resultado = $conexion->query($sql);
    $fila = $resultado->fetch_row();
    return intval($fila[0] ?? 0);
}

function filasConsulta(mysqli $conexion, string $sql): array
{
    $resultado = $conexion->query($sql);
    $filas = [];

    while ($fila = $resultado->fetch_assoc()) {
        $filas[] = $fila;
    }

    return $filas;
}

function agregarAlerta(
    array &$alertas,
    int $cantidad,
    string $tipo,
    string $titulo,
    string $detalle,
    string $destino,
    string $prioridad
): void {
    if ($cantidad <= 0) {
        return;
    }

    $alertas[] = [
        "tipo" => $tipo,
        "titulo" => $titulo,
        "detalle" => $detalle,
        "cantidad" => $cantidad,
        "destino" => $destino,
        "prioridad" => $prioridad
    ];
}

?>
