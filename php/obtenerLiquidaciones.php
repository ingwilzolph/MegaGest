<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";

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

    $desde = trim((string) ($_GET["desde"] ?? date("Y-01-01")));
    $hasta = trim((string) ($_GET["hasta"] ?? date("Y-m-d")));
    $estado = strtolower(trim((string) ($_GET["estado"] ?? "")));
    $idUsuarioFiltro = intval($_GET["id_usuario"] ?? 0);

    validarFechaConsultaLiquidacion(
        $desde,
        "La fecha inicial no es válida."
    );
    validarFechaConsultaLiquidacion(
        $hasta,
        "La fecha final no es válida."
    );

    if ($desde > $hasta) {
        throw new RuntimeException(
            "La fecha inicial no puede ser posterior a la fecha final."
        );
    }

    $estadosPermitidos = [
        "",
        "borrador",
        "cerrada",
        "pagada",
        "anulada"
    ];

    if (!in_array($estado, $estadosPermitidos, true)) {
        throw new RuntimeException(
            "El estado de la liquidación no es válido."
        );
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
        strtolower(trim((string) $usuarioSesion["rol"])) ===
        "administrador";

    if (!$esAdministrador) {
        $idUsuarioFiltro = $idUsuarioSesion;
    }

    $sql = "
        SELECT
            lr.id_liquidacion,
            lr.id_usuario,
            CONCAT(u.nombre, ' ', u.apellido) AS usuario,
            u.correo,
            LOWER(TRIM(u.rol)) AS rol,
            lr.periodo_desde,
            lr.periodo_hasta,
            lr.sueldo_base,
            lr.total_comisiones,
            lr.bonos,
            lr.descuentos,
            lr.total_liquidacion,
            lr.estado,
            lr.observaciones,
            lr.fecha_pago,
            lr.fecha_registro,
            CONCAT(responsable.nombre, ' ', responsable.apellido)
                AS usuario_responsable,
            COUNT(dlc.id_detalle) AS cantidad_comisiones
        FROM liquidaciones_remuneraciones lr
        INNER JOIN login_admin u
            ON u.id_usuario = lr.id_usuario
        INNER JOIN login_admin responsable
            ON responsable.id_usuario = lr.id_usuario_responsable
        LEFT JOIN detalle_liquidacion_comisiones dlc
            ON dlc.id_liquidacion = lr.id_liquidacion
        WHERE lr.periodo_hasta >= ?
          AND lr.periodo_desde <= ?
          AND (? = '' OR lr.estado = ?)
          AND (? = 0 OR lr.id_usuario = ?)
        GROUP BY
            lr.id_liquidacion,
            lr.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.rol,
            lr.periodo_desde,
            lr.periodo_hasta,
            lr.sueldo_base,
            lr.total_comisiones,
            lr.bonos,
            lr.descuentos,
            lr.total_liquidacion,
            lr.estado,
            lr.observaciones,
            lr.fecha_pago,
            lr.fecha_registro,
            responsable.nombre,
            responsable.apellido
        ORDER BY lr.periodo_hasta DESC, lr.id_liquidacion DESC
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "ssssii",
        $desde,
        $hasta,
        $estado,
        $estado,
        $idUsuarioFiltro,
        $idUsuarioFiltro
    );
    $stmt->execute();
    $resultado = $stmt->get_result();

    $liquidaciones = [];
    $resumen = [
        "cantidad" => 0,
        "borradores" => 0,
        "cerradas" => 0,
        "pagadas" => 0,
        "anuladas" => 0,
        "total_comisiones" => 0,
        "total_liquidaciones" => 0,
        "total_pagado" => 0,
        "total_pendiente_pago" => 0
    ];

    while ($fila = $resultado->fetch_assoc()) {
        $fila["id_liquidacion"] = intval($fila["id_liquidacion"]);
        $fila["id_usuario"] = intval($fila["id_usuario"]);
        $fila["sueldo_base"] = intval($fila["sueldo_base"]);
        $fila["total_comisiones"] = intval($fila["total_comisiones"]);
        $fila["bonos"] = intval($fila["bonos"]);
        $fila["descuentos"] = intval($fila["descuentos"]);
        $fila["total_liquidacion"] = intval($fila["total_liquidacion"]);
        $fila["cantidad_comisiones"] = intval(
            $fila["cantidad_comisiones"]
        );

        $resumen["cantidad"]++;
        $resumen["total_comisiones"] += $fila["total_comisiones"];
        $resumen["total_liquidaciones"] += $fila["total_liquidacion"];

        switch ($fila["estado"]) {
            case "borrador":
                $resumen["borradores"]++;
                break;
            case "cerrada":
                $resumen["cerradas"]++;
                $resumen["total_pendiente_pago"] +=
                    $fila["total_liquidacion"];
                break;
            case "pagada":
                $resumen["pagadas"]++;
                $resumen["total_pagado"] +=
                    $fila["total_liquidacion"];
                break;
            case "anulada":
                $resumen["anuladas"]++;
                break;
        }

        $liquidaciones[] = $fila;
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,
        "filtros" => [
            "desde" => $desde,
            "hasta" => $hasta,
            "estado" => $estado,
            "id_usuario" => $idUsuarioFiltro
        ],
        "resumen" => $resumen,
        "datos" => $liquidaciones,
        "permisos" => [
            "consultar_todos" => $esAdministrador,
            "consultar_propio" => true,
            "crear" => $esAdministrador,
            "cerrar" => $esAdministrador,
            "pagar" => $esAdministrador,
            "anular" => $esAdministrador
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    error_log("Error obteniendo liquidaciones: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar las liquidaciones.";
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

function validarFechaConsultaLiquidacion(
    string $fecha,
    string $mensaje
): void {
    $objeto = DateTime::createFromFormat("Y-m-d", $fecha);

    if (!$objeto || $objeto->format("Y-m-d") !== $fecha) {
        throw new RuntimeException($mensaje);
    }
}

?>
