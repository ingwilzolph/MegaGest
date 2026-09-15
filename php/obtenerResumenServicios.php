<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

try {

    $sql = "
        SELECT
            COUNT(*) AS totalServicios,

            COALESCE(
                SUM(estado = 'Activo'),
                0
            ) AS serviciosActivos,

            COALESCE(
                SUM(visible_citas = 0),
                0
            ) AS serviciosOcultos,

            COALESCE(
                SUM(destacado = 1),
                0
            ) AS serviciosDestacados,

            COALESCE(
                ROUND(AVG(precio_min)),
                0
            ) AS precioPromedio

        FROM servicios
    ";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception($conexion->error);
    }

    $datos = $resultado->fetch_assoc();

    echo json_encode([
        "ok" => true,
        "datos" => [
            "totalServicios" =>
                (int)$datos["totalServicios"],

            "serviciosActivos" =>
                (int)$datos["serviciosActivos"],

            "serviciosOcultos" =>
                (int)$datos["serviciosOcultos"],

            "serviciosDestacados" =>
                (int)$datos["serviciosDestacados"],

            "precioPromedio" =>
                (int)$datos["precioPromedio"]
        ]
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