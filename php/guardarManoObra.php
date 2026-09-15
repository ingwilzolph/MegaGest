<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    $idOT = intval($_POST["id_ot"] ?? 0);
    $descripcion = trim((string) ($_POST["descripcion"] ?? ""));
    $cantidad = intval($_POST["cantidad"] ?? 0);
    $precio = intval($_POST["precio"] ?? 0);

    $costoRecibido = array_key_exists("costo_unitario", $_POST)
        ? trim((string) $_POST["costo_unitario"])
        : "";

    $idUsuario = intval($_SESSION["id_usuario"] ?? 0);

    if ($idOT <= 0) {
        throw new RuntimeException("La orden de trabajo no es válida.");
    }

    if ($idUsuario <= 0) {
        http_response_code(401);
        throw new RuntimeException("No se pudo identificar al usuario.");
    }

    if ($descripcion === "" || mb_strlen($descripcion) > 200) {
        throw new RuntimeException(
            "La descripción debe contener entre 1 y 200 caracteres."
        );
    }

    if ($cantidad <= 0 || $cantidad > 1000) {
        throw new RuntimeException("La cantidad no es válida.");
    }

    if ($precio <= 0) {
        throw new RuntimeException("El precio no es válido.");
    }

    if ($precio > intdiv(PHP_INT_MAX, $cantidad)) {
        throw new RuntimeException("El total de la mano de obra es demasiado alto.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    /* =====================================================
       VALIDAR USUARIO Y ROL DESDE LA BASE DE DATOS
    ===================================================== */

    $stmtUsuario = $conexion->prepare(
        "SELECT id_usuario, rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtUsuario->bind_param("i", $idUsuario);
    $stmtUsuario->execute();

    $usuario = $stmtUsuario
        ->get_result()
        ->fetch_assoc();

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

    if (!in_array($rol, ["administrador", "mecanico"], true)) {
        http_response_code(403);
        throw new RuntimeException(
            "No tiene permisos para registrar mano de obra."
        );
    }

    /*
     * Solo el administrador puede establecer el costo interno.
     * El valor recibido desde el navegador nunca concede permisos.
     */
    $costoUnitario = null;
    $costoTotal = null;

    if ($rol === "administrador" && $costoRecibido !== "") {

        if (!preg_match('/^\d+$/', $costoRecibido)) {
            throw new RuntimeException("El costo interno no es válido.");
        }

        $costoUnitario = intval($costoRecibido);

        if ($costoUnitario > intdiv(PHP_INT_MAX, $cantidad)) {
            throw new RuntimeException("El costo total es demasiado alto.");
        }

        $costoTotal = $costoUnitario * $cantidad;
    }

    /* =====================================================
       VALIDAR Y BLOQUEAR LA ORDEN
    ===================================================== */

    $stmtOT = $conexion->prepare(
        "SELECT id_ot, estado
         FROM orden_trabajo
         WHERE id_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtOT->bind_param("i", $idOT);
    $stmtOT->execute();

    $orden = $stmtOT
        ->get_result()
        ->fetch_assoc();

    $stmtOT->close();

    if (!$orden) {
        throw new RuntimeException("La orden de trabajo no fue encontrada.");
    }

    $estadoOT = strtolower(trim((string) $orden["estado"]));

    if (!in_array($estadoOT, ["abierta", "en proceso"], true)) {
        throw new RuntimeException(
            "No se puede agregar mano de obra a una orden terminada o facturada."
        );
    }

    /* =====================================================
       REGISTRAR MANO DE OBRA
    ===================================================== */

    $total = $precio * $cantidad;

    $stmtInsertar = $conexion->prepare(
        "INSERT INTO mano_obra_ot (
            id_ot,
            id_usuario,
            descripcion,
            cantidad,
            precio,
            costo_unitario,
            costo_total,
            total
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmtInsertar->bind_param(
        "iisiiiii",
        $idOT,
        $idUsuario,
        $descripcion,
        $cantidad,
        $precio,
        $costoUnitario,
        $costoTotal,
        $total
    );

    $stmtInsertar->execute();

    $idManoObra = intval($conexion->insert_id);

    $stmtInsertar->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Mano de obra agregada correctamente.",
        "datos" => [
            "id_mano_obra" => $idManoObra,
            "id_ot" => $idOT,
            "cantidad" => $cantidad,
            "precio" => $precio,
            "total" => $total,
            "costo_unitario" => $costoUnitario,
            "costo_total" => $costoTotal
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error guardando mano de obra: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible guardar la mano de obra.";
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
