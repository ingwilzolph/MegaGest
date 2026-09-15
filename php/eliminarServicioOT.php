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

    $idServicioOT = intval($_POST["id_servicio_ot"] ?? 0);
    $idUsuario = intval($_SESSION["id_usuario"] ?? 0);

    if ($idServicioOT <= 0) {
        throw new RuntimeException("El servicio seleccionado no es válido.");
    }

    if ($idUsuario <= 0) {
        http_response_code(401);
        throw new RuntimeException("No se pudo identificar al usuario.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmtUsuario = $conexion->prepare(
        "SELECT rol, estado
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
        http_response_code(403);
        throw new RuntimeException("El usuario no existe.");
    }

    if (strtolower(trim((string) $usuario["estado"])) !== "activo") {
        http_response_code(403);
        throw new RuntimeException("El usuario está inactivo.");
    }

    $rol = strtolower(trim((string) $usuario["rol"]));

    $stmtServicio = $conexion->prepare(
        "SELECT
            ots.id_servicio_ot,
            ots.id_ot,
            ots.id_usuario,
            ots.descripcion,
            ot.estado
         FROM orden_trabajo_servicios ots
         INNER JOIN orden_trabajo ot
            ON ot.id_ot = ots.id_ot
         WHERE ots.id_servicio_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtServicio->bind_param("i", $idServicioOT);
    $stmtServicio->execute();

    $servicio = $stmtServicio->get_result()->fetch_assoc();
    $stmtServicio->close();

    if (!$servicio) {
        throw new RuntimeException("El servicio no fue encontrado.");
    }

    $estadoOT = strtolower(trim((string) $servicio["estado"]));

    if (!in_array($estadoOT, ["abierta", "en proceso"], true)) {
        throw new RuntimeException(
            "No se pueden eliminar servicios de una orden terminada o facturada."
        );
    }

    $idCreador = intval($servicio["id_usuario"] ?? 0);

    if ($rol !== "administrador" && $idCreador !== $idUsuario) {
        http_response_code(403);
        throw new RuntimeException(
            "Solo puede eliminar los servicios que usted agregó a la orden."
        );
    }

    $stmtEliminar = $conexion->prepare(
        "DELETE FROM orden_trabajo_servicios
         WHERE id_servicio_ot = ?"
    );

    $stmtEliminar->bind_param("i", $idServicioOT);
    $stmtEliminar->execute();

    if ($stmtEliminar->affected_rows !== 1) {
        $stmtEliminar->close();
        throw new RuntimeException("No fue posible eliminar el servicio.");
    }

    $stmtEliminar->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Servicio eliminado correctamente.",
        "datos" => [
            "id_servicio_ot" => $idServicioOT,
            "id_ot" => intval($servicio["id_ot"])
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {

    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error eliminando servicio OT: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible eliminar el servicio.";
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
