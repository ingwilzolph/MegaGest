<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "verificarAdministrador.php";
require_once "conexion.php";

$conexion = null;
$stmt = null;

try {

    $conexion = conexion();

    $sql = "
        SELECT
            COUNT(*) AS totalUsuarios,

            SUM(
                CASE
                    WHEN LOWER(estado) = 'activo'
                    THEN 1
                    ELSE 0
                END
            ) AS usuariosActivos,

            SUM(
                CASE
                    WHEN LOWER(estado) = 'inactivo'
                    THEN 1
                    ELSE 0
                END
            ) AS usuariosInactivos,

            SUM(
                CASE
                    WHEN LOWER(rol) = 'administrador'
                    THEN 1
                    ELSE 0
                END
            ) AS administradores,

            SUM(
                CASE
                    WHEN LOWER(rol) <> 'accionista' OR LOWER(rol) <> 'dueño'
                    THEN 1
                    ELSE 0
                END
            ) AS empleados

        FROM login_admin
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "No fue posible preparar el resumen de usuarios."
        );
    }

    if (!$stmt->execute()) {

        throw new Exception(
            "No fue posible obtener el resumen de usuarios."
        );
    }

    $resultado = $stmt->get_result();

    $datos = $resultado->fetch_assoc();

    echo json_encode([
        "ok" => true,
        "datos" => [
            "totalUsuarios" =>
                (int) ($datos["totalUsuarios"] ?? 0),

            "usuariosActivos" =>
                (int) ($datos["usuariosActivos"] ?? 0),

            "usuariosInactivos" =>
                (int) ($datos["usuariosInactivos"] ?? 0),

            "administradores" =>
                (int) ($datos["administradores"] ?? 0),

            "empleados" =>
                (int) ($datos["empleados"] ?? 0)
        ]
    ]);

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>