<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;
$transaccionIniciada = false;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    $idAdministrador = intval($_SESSION["id_usuario"] ?? 0);
    $idUsuario = intval($_POST["id_usuario"] ?? 0);
    $sueldoBase = intval($_POST["sueldo_base_mensual"] ?? 0);
    $porcentajeVentas = normalizarPorcentajeRemuneracion(
        $_POST["porcentaje_ventas"] ?? null,
        "El porcentaje de ventas no es válido."
    );
    $porcentajeServicios = normalizarPorcentajeRemuneracion(
        $_POST["porcentaje_servicios"] ?? null,
        "El porcentaje de servicios no es válido."
    );
    $porcentajeManoObra = normalizarPorcentajeRemuneracion(
        $_POST["porcentaje_mano_obra"] ?? null,
        "El porcentaje de mano de obra no es válido."
    );
    $estado = strtolower(trim((string) ($_POST["estado"] ?? "activo")));

    if ($idAdministrador <= 0) {
        http_response_code(401);
        throw new RuntimeException("No se pudo identificar al administrador.");
    }

    if ($idUsuario <= 0) {
        throw new RuntimeException("El usuario seleccionado no es válido.");
    }

    if ($sueldoBase < 0 || $sueldoBase > 999999999999) {
        throw new RuntimeException("El sueldo base no es válido.");
    }

    if (!in_array($estado, ["activo", "inactivo"], true)) {
        throw new RuntimeException("El estado de la configuración no es válido.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    /* Validar administrador con datos actuales de la base de datos. */
    $stmtAdministrador = $conexion->prepare(
        "SELECT rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtAdministrador->bind_param("i", $idAdministrador);
    $stmtAdministrador->execute();
    $administrador = $stmtAdministrador->get_result()->fetch_assoc();
    $stmtAdministrador->close();

    if (
        !$administrador ||
        strtolower(trim((string) $administrador["estado"])) !== "activo" ||
        strtolower(trim((string) $administrador["rol"])) !== "administrador"
    ) {
        http_response_code(403);
        throw new RuntimeException(
            "Solo el administrador puede modificar salarios y porcentajes."
        );
    }

    /* Bloquear y comprobar el usuario beneficiario. */
    $stmtUsuario = $conexion->prepare(
        "SELECT id_usuario, nombre, apellido, rol, estado
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
        throw new RuntimeException("El usuario seleccionado no existe.");
    }

    $sqlGuardar = "
        INSERT INTO configuracion_remuneraciones (
            id_usuario,
            sueldo_base_mensual,
            porcentaje_ventas,
            porcentaje_servicios,
            porcentaje_mano_obra,
            estado,
            id_usuario_actualizacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            sueldo_base_mensual = VALUES(sueldo_base_mensual),
            porcentaje_ventas = VALUES(porcentaje_ventas),
            porcentaje_servicios = VALUES(porcentaje_servicios),
            porcentaje_mano_obra = VALUES(porcentaje_mano_obra),
            estado = VALUES(estado),
            id_usuario_actualizacion = VALUES(id_usuario_actualizacion)
    ";

    $stmtGuardar = $conexion->prepare($sqlGuardar);
    $stmtGuardar->bind_param(
        "iidddsi",
        $idUsuario,
        $sueldoBase,
        $porcentajeVentas,
        $porcentajeServicios,
        $porcentajeManoObra,
        $estado,
        $idAdministrador
    );
    $stmtGuardar->execute();
    $stmtGuardar->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Configuración de remuneración guardada correctamente.",
        "datos" => [
            "id_usuario" => $idUsuario,
            "usuario" => trim($usuario["nombre"] . " " . $usuario["apellido"]),
            "rol" => strtolower(trim((string) $usuario["rol"])),
            "sueldo_base_mensual" => $sueldoBase,
            "porcentaje_ventas" => $porcentajeVentas,
            "porcentaje_servicios" => $porcentajeServicios,
            "porcentaje_mano_obra" => $porcentajeManoObra,
            "estado" => $estado
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error guardando remuneración: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible guardar la configuración de remuneración.";
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

function normalizarPorcentajeRemuneracion($valor, string $mensaje): float
{
    if ($valor === null || $valor === "" || !is_numeric($valor)) {
        throw new RuntimeException($mensaje);
    }

    $porcentaje = round((float) $valor, 2);

    if ($porcentaje < 0 || $porcentaje > 100) {
        throw new RuntimeException(
            "Los porcentajes deben estar entre 0 y 100."
        );
    }

    return $porcentaje;
}

?>
