<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {
    $conexion = conexion();

    $sql = "
        SELECT
            id_servicio,
            nombre,
            descripcion,
            precio_min,
            estado,
            visible_citas
        FROM servicios
        WHERE visible_citas = 1
          AND LOWER(TRIM(estado)) = 'activo'
        ORDER BY nombre ASC
    ";

    $resultado = $conexion->query($sql);
    $datos = [];

    while ($fila = $resultado->fetch_assoc()) {
        $fila["id_servicio"] = (int)$fila["id_servicio"];
        $fila["precio_min"] = (int)($fila["precio_min"] ?? 0);
        $fila["visible_citas"] = (int)$fila["visible_citas"];
        $datos[] = $fila;
    }

    echo json_encode([
        "ok" => true,
        "datos" => $datos
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No fue posible cargar los servicios.",
        "detalle" => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}
