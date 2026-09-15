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

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /* El rol se comprueba en la base de datos. */
    $stmtSesion = $conexion->prepare(
        "SELECT id_usuario, rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1"
    );

    $stmtSesion->bind_param("i", $idUsuarioSesion);
    $stmtSesion->execute();
    $usuarioSesion = $stmtSesion->get_result()->fetch_assoc();
    $stmtSesion->close();

    if (!$usuarioSesion) {
        http_response_code(403);
        throw new RuntimeException("El usuario no existe.");
    }

    if (strtolower(trim((string) $usuarioSesion["estado"])) !== "activo") {
        http_response_code(403);
        throw new RuntimeException("El usuario está inactivo.");
    }

    $rolSesion = strtolower(trim((string) $usuarioSesion["rol"]));
    $esAdministrador = $rolSesion === "administrador";

    $sql = "
        SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            LOWER(TRIM(u.rol)) AS rol,
            LOWER(TRIM(u.estado)) AS estado_usuario,
            COALESCE(cr.sueldo_base_mensual, 0) AS sueldo_base_mensual,
            COALESCE(cr.porcentaje_ventas, 0) AS porcentaje_ventas,
            COALESCE(cr.porcentaje_servicios, 0) AS porcentaje_servicios,
            COALESCE(cr.porcentaje_mano_obra, 0) AS porcentaje_mano_obra,
            COALESCE(cr.estado, 'inactivo') AS estado_configuracion,
            cr.fecha_actualizacion,
            COALESCE(SUM(
                CASE
                    WHEN cu.estado = 'pendiente'
                    THEN cu.monto_comision
                    ELSE 0
                END
            ), 0) AS comisiones_pendientes,
            COALESCE(SUM(
                CASE
                    WHEN cu.estado IN ('liquidada', 'pagada')
                    THEN cu.monto_comision
                    ELSE 0
                END
            ), 0) AS comisiones_liquidadas,
            COALESCE(SUM(
                CASE
                    WHEN cu.estado <> 'anulada'
                    THEN cu.monto_comision
                    ELSE 0
                END
            ), 0) AS comisiones_historicas
        FROM login_admin u
        LEFT JOIN configuracion_remuneraciones cr
            ON cr.id_usuario = u.id_usuario
        LEFT JOIN comisiones_usuarios cu
            ON cu.id_usuario = u.id_usuario
    ";

    if (!$esAdministrador) {
        $sql .= " WHERE u.id_usuario = ? ";
    }

    $sql .= "
        GROUP BY
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.rol,
            u.estado,
            cr.sueldo_base_mensual,
            cr.porcentaje_ventas,
            cr.porcentaje_servicios,
            cr.porcentaje_mano_obra,
            cr.estado,
            cr.fecha_actualizacion
        ORDER BY u.nombre ASC, u.apellido ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$esAdministrador) {
        $stmt->bind_param("i", $idUsuarioSesion);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuarios = [];

    while ($fila = $resultado->fetch_assoc()) {
        $sueldoBase = intval($fila["sueldo_base_mensual"] ?? 0);
        $comisionesPendientes = intval($fila["comisiones_pendientes"] ?? 0);

        $usuarios[] = [
            "id_usuario" => intval($fila["id_usuario"]),
            "nombre" => $fila["nombre"],
            "apellido" => $fila["apellido"],
            "usuario" => trim($fila["nombre"] . " " . $fila["apellido"]),
            "correo" => $fila["correo"],
            "rol" => $fila["rol"],
            "estado_usuario" => $fila["estado_usuario"],
            "sueldo_base_mensual" => $sueldoBase,
            "porcentaje_ventas" => round((float) $fila["porcentaje_ventas"], 2),
            "porcentaje_servicios" => round((float) $fila["porcentaje_servicios"], 2),
            "porcentaje_mano_obra" => round((float) $fila["porcentaje_mano_obra"], 2),
            "estado_configuracion" => $fila["estado_configuracion"],
            "comisiones_pendientes" => $comisionesPendientes,
            "comisiones_liquidadas" => intval($fila["comisiones_liquidadas"] ?? 0),
            "comisiones_historicas" => intval($fila["comisiones_historicas"] ?? 0),
            "estimado_actual" => $sueldoBase + $comisionesPendientes,
            "fecha_actualizacion" => $fila["fecha_actualizacion"]
        ];
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,
        "datos" => $usuarios,
        "permisos" => [
            "gestionar" => $esAdministrador,
            "consultar_todos" => $esAdministrador,
            "consultar_propio" => true
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    error_log("Error obteniendo remuneraciones: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar salarios y comisiones.";
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

?>
