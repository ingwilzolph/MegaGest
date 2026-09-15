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

    $idManoObra = intval($_POST["id_mano_obra"] ?? 0);
    $idUsuario = intval($_SESSION["id_usuario"] ?? 0);

    if ($idManoObra <= 0) {
        throw new RuntimeException("La mano de obra seleccionada no es válida.");
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

    $stmtManoObra = $conexion->prepare(
        "SELECT
            mo.id_mano_obra,
            mo.id_ot,
            mo.id_usuario,
            mo.descripcion,
            ot.estado
         FROM mano_obra_ot mo
         INNER JOIN orden_trabajo ot
            ON ot.id_ot = mo.id_ot
         WHERE mo.id_mano_obra = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtManoObra->bind_param("i", $idManoObra);
    $stmtManoObra->execute();

    $manoObra = $stmtManoObra->get_result()->fetch_assoc();
    $stmtManoObra->close();

    if (!$manoObra) {
        throw new RuntimeException("La mano de obra no fue encontrada.");
    }

    $estadoOT = strtolower(trim((string) $manoObra["estado"]));

    if (!in_array($estadoOT, ["abierta", "en proceso"], true)) {
        throw new RuntimeException(
            "No se puede eliminar mano de obra de una orden terminada o facturada."
        );
    }

    $idCreador = intval($manoObra["id_usuario"] ?? 0);

    if ($rol !== "administrador" && $idCreador !== $idUsuario) {
        http_response_code(403);
        throw new RuntimeException(
            "Solo puede eliminar la mano de obra que usted agregó a la orden."
        );
    }

    $stmtEliminar = $conexion->prepare(
        "DELETE FROM mano_obra_ot
         WHERE id_mano_obra = ?"
    );

    $stmtEliminar->bind_param("i", $idManoObra);
    $stmtEliminar->execute();

    if ($stmtEliminar->affected_rows !== 1) {
        $stmtEliminar->close();
        throw new RuntimeException("No fue posible eliminar la mano de obra.");
    }

    $stmtEliminar->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Mano de obra eliminada correctamente.",
        "datos" => [
            "id_mano_obra" => $idManoObra,
            "id_ot" => intval($manoObra["id_ot"])
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {

    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error eliminando mano de obra OT: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible eliminar la mano de obra.";
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
