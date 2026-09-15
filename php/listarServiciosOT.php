<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

try {
    $idOT = intval($_GET["id_ot"] ?? 0);
    $idUsuario = intval($_SESSION["id_usuario"] ?? 0);
    $rol = strtolower(trim((string) ($_SESSION["rol"] ?? "")));

    if ($idOT <= 0 || $idUsuario <= 0) {
        throw new RuntimeException("Solicitud no válida.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $stmt = $conexion->prepare(
        "SELECT
            ots.id_servicio_ot,
            ots.id_ot,
            ots.id_usuario,
            ots.descripcion,
            ots.cantidad,
            ots.precio,
            ots.total,
            ots.costo_unitario,
            ots.costo_total,
            COALESCE(us.nombre, 'Usuario') AS nombre,
            COALESCE(us.apellido, 'no disponible') AS apellido,
            ot.estado AS estado_ot
         FROM orden_trabajo_servicios ots
         INNER JOIN orden_trabajo ot
            ON ot.id_ot = ots.id_ot
         LEFT JOIN login_admin us
            ON us.id_usuario = ots.id_usuario
         WHERE ots.id_ot = ?
         ORDER BY ots.id_servicio_ot ASC"
    );

    $stmt->bind_param("i", $idOT);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $datos = [];

    while ($fila = $resultado->fetch_assoc()) {
        $estadoEditable = in_array(
            strtolower(trim((string) $fila["estado_ot"])),
            ["abierta", "en proceso"],
            true
        );

        $fila["id_servicio_ot"] = intval($fila["id_servicio_ot"]);
        $fila["id_ot"] = intval($fila["id_ot"]);
        $fila["id_usuario"] = $fila["id_usuario"] !== null
            ? intval($fila["id_usuario"])
            : null;
        $fila["cantidad"] = intval($fila["cantidad"]);
        $fila["precio"] = intval($fila["precio"]);
        $fila["total"] = intval($fila["total"]);
        $fila["puede_eliminar"] = $estadoEditable && (
            $rol === "administrador" ||
            $fila["id_usuario"] === $idUsuario
        );

        if ($rol === "administrador") {
            $fila["costo_unitario"] = $fila["costo_unitario"] !== null
                ? intval($fila["costo_unitario"])
                : null;
            $fila["costo_total"] = $fila["costo_total"] !== null
                ? intval($fila["costo_total"])
                : null;
        } else {
            unset($fila["costo_unitario"], $fila["costo_total"]);
        }

        unset($fila["estado_ot"]);
        $datos[] = $fila;
    }

    $stmt->close();
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    error_log("Error listando servicios OT: " . $error->getMessage());
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "No fue posible cargar los servicios."
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>
