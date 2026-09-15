<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    $idUsuarioSesion = intval($_SESSION["id_usuario"] ?? 0);

    if ($idUsuarioSesion <= 0) {
        http_response_code(401);
        throw new RuntimeException("No se pudo identificar al usuario.");
    }

    $desde = trim((string) ($_GET["desde"] ?? date("Y-m-01")));
    $hasta = trim((string) ($_GET["hasta"] ?? date("Y-m-d")));
    $tipo = strtolower(trim((string) ($_GET["tipo"] ?? "")));
    $estado = strtolower(trim((string) ($_GET["estado"] ?? "")));
    $idUsuarioFiltro = intval($_GET["id_usuario"] ?? 0);

    validarFechaComision($desde, "La fecha inicial no es válida.");
    validarFechaComision($hasta, "La fecha final no es válida.");

    if ($desde > $hasta) {
        throw new RuntimeException("La fecha inicial no puede ser posterior a la fecha final.");
    }

    $tiposPermitidos = ["", "venta", "servicio", "mano_obra"];
    $estadosPermitidos = ["", "pendiente", "liquidada", "pagada", "anulada"];

    if (!in_array($tipo, $tiposPermitidos, true)) {
        throw new RuntimeException("El tipo de comisión no es válido.");
    }

    if (!in_array($estado, $estadosPermitidos, true)) {
        throw new RuntimeException("El estado de comisión no es válido.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $stmtSesion = $conexion->prepare(
        "SELECT rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1"
    );

    $stmtSesion->bind_param("i", $idUsuarioSesion);
    $stmtSesion->execute();
    $usuarioSesion = $stmtSesion->get_result()->fetch_assoc();
    $stmtSesion->close();

    if (
        !$usuarioSesion ||
        strtolower(trim((string) $usuarioSesion["estado"])) !== "activo"
    ) {
        http_response_code(403);
        throw new RuntimeException("El usuario no está autorizado.");
    }

    $esAdministrador =
        strtolower(trim((string) $usuarioSesion["rol"])) === "administrador";

    /* Los usuarios no administradores solo pueden consultar sus propias comisiones. */
    if (!$esAdministrador) {
        $idUsuarioFiltro = $idUsuarioSesion;
    }

    $sql = "
        SELECT
            cu.id_comision,
            cu.id_usuario,
            CONCAT(u.nombre, ' ', u.apellido) AS usuario,
            LOWER(TRIM(u.rol)) AS rol,
            cu.tipo_comision,
            cu.origen,
            cu.id_pedido,
            cu.id_ot,
            cu.id_servicio_ot,
            cu.id_mano_obra,
            cu.base_calculo,
            cu.porcentaje_aplicado,
            cu.monto_comision,
            cu.estado,
            cu.observaciones,
            cu.fecha_generacion,
            p.numero_pedido,
            ot.numeroOT AS numero_ot,
            COALESCE(ots.descripcion, mo.descripcion) AS concepto
        FROM comisiones_usuarios cu
        INNER JOIN login_admin u
            ON u.id_usuario = cu.id_usuario
        LEFT JOIN pedidos p
            ON p.id_pedido = cu.id_pedido
        LEFT JOIN orden_trabajo ot
            ON ot.id_ot = cu.id_ot
        LEFT JOIN orden_trabajo_servicios ots
            ON ots.id_servicio_ot = cu.id_servicio_ot
        LEFT JOIN mano_obra_ot mo
            ON mo.id_mano_obra = cu.id_mano_obra
        WHERE DATE(cu.fecha_generacion) BETWEEN ? AND ?
          AND (? = '' OR cu.tipo_comision = ?)
          AND (? = '' OR cu.estado = ?)
          AND (? = 0 OR cu.id_usuario = ?)
        ORDER BY cu.fecha_generacion DESC, cu.id_comision DESC
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "ssssssii",
        $desde,
        $hasta,
        $tipo,
        $tipo,
        $estado,
        $estado,
        $idUsuarioFiltro,
        $idUsuarioFiltro
    );
    $stmt->execute();
    $resultado = $stmt->get_result();

    $comisiones = [];
    $resumen = [
        "cantidad" => 0,
        "base_total" => 0,
        "monto_total" => 0,
        "pendiente" => 0,
        "liquidada" => 0,
        "pagada" => 0,
        "anulada" => 0
    ];

    while ($fila = $resultado->fetch_assoc()) {
        $monto = intval($fila["monto_comision"]);
        $estadoFila = $fila["estado"];

        $fila["id_comision"] = intval($fila["id_comision"]);
        $fila["id_usuario"] = intval($fila["id_usuario"]);
        $fila["id_pedido"] = $fila["id_pedido"] !== null
            ? intval($fila["id_pedido"])
            : null;
        $fila["id_ot"] = $fila["id_ot"] !== null
            ? intval($fila["id_ot"])
            : null;
        $fila["id_servicio_ot"] = $fila["id_servicio_ot"] !== null
            ? intval($fila["id_servicio_ot"])
            : null;
        $fila["id_mano_obra"] = $fila["id_mano_obra"] !== null
            ? intval($fila["id_mano_obra"])
            : null;
        $fila["base_calculo"] = intval($fila["base_calculo"]);
        $fila["porcentaje_aplicado"] = round(
            (float) $fila["porcentaje_aplicado"],
            2
        );
        $fila["monto_comision"] = $monto;

        $resumen["cantidad"]++;
        $resumen["base_total"] += $fila["base_calculo"];
        $resumen["monto_total"] += $monto;

        if (array_key_exists($estadoFila, $resumen)) {
            $resumen[$estadoFila] += $monto;
        }

        $comisiones[] = $fila;
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,
        "filtros" => [
            "desde" => $desde,
            "hasta" => $hasta,
            "tipo" => $tipo,
            "estado" => $estado,
            "id_usuario" => $idUsuarioFiltro
        ],
        "resumen" => $resumen,
        "datos" => $comisiones,
        "permisos" => [
            "consultar_todos" => $esAdministrador,
            "consultar_propio" => true,
            "gestionar" => $esAdministrador
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    error_log("Error obteniendo comisiones: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar el detalle de comisiones.";
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

function validarFechaComision(string $fecha, string $mensaje): void
{
    $objetoFecha = DateTime::createFromFormat("Y-m-d", $fecha);

    if (!$objetoFecha || $objetoFecha->format("Y-m-d") !== $fecha) {
        throw new RuntimeException($mensaje);
    }
}

?>
