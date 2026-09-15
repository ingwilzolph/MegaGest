<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

try {

    $idServicio = intval($_GET["id"] ?? 0);

    if ($idServicio <= 0) {
        throw new Exception(
            "El ID del servicio no es válido."
        );
    }

    $sql = "
        SELECT
            id_servicio,
            nombre,
            descripcion,
            precio_min,
            duracion_minutos,
            visible_citas,
            destacado,
            estado,
            fecha_actualizacion
        FROM servicios
        WHERE id_servicio = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "i",
        $idServicio
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception(
            "Servicio no encontrado."
        );
    }

    $servicio = $resultado->fetch_assoc();

    echo json_encode([
        "ok" => true,
        "datos" => $servicio
    ]);

    $stmt->close();

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    $conexion->close();
}
?>