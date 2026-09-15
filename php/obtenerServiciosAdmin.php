<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

try {

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
        ORDER BY nombre ASC
    ";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception($conexion->error);
    }

    $servicios = [];

    while ($servicio = $resultado->fetch_assoc()) {

        $servicio["id_servicio"] =
            (int)$servicio["id_servicio"];

        $servicio["precio_min"] =
            (int)$servicio["precio_min"];

        $servicio["duracion_minutos"] =
            (int)$servicio["duracion_minutos"];

        $servicio["visible_citas"] =
            (int)$servicio["visible_citas"];

        $servicio["destacado"] =
            (int)$servicio["destacado"];

        if ($servicio["estado"] === "Inactivo") {

            $servicio["estado_visual"] = "Inactivo";

        } elseif ($servicio["visible_citas"] === 0) {

            $servicio["estado_visual"] = "Oculto";

        } else {

            $servicio["estado_visual"] = "Activo";
        }

        $servicios[] = $servicio;
    }

    echo json_encode([
        "ok" => true,
        "datos" => $servicios
    ]);

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    $conexion->close();
}
?>