<?php

header("Content-Type: application/json; charset=utf-8");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

header("Pragma: no-cache");
header("Expires: 0");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception(
            "Método no permitido."
        );
    }

    $idServicio = filter_var(
        $_POST["id_servicio"] ?? null,
        FILTER_VALIDATE_INT
    );

    $visibleCitas = filter_var(
        $_POST["visible_citas"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$idServicio || $idServicio <= 0) {
        throw new Exception(
            "El ID del servicio no es válido."
        );
    }

    if (
        $visibleCitas === false ||
        !in_array($visibleCitas, [0, 1], true)
    ) {
        throw new Exception(
            "El valor de visibilidad no es válido."
        );
    }

    $stmtServicio = $conexion->prepare(
        "SELECT nombre
         FROM servicios
         WHERE id_servicio = ?
         LIMIT 1"
    );

    if (!$stmtServicio) {
        throw new Exception($conexion->error);
    }

    $stmtServicio->bind_param(
        "i",
        $idServicio
    );

    $stmtServicio->execute();

    $resultado =
        $stmtServicio->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception(
            "El servicio no existe."
        );
    }

    $servicio = $resultado->fetch_assoc();

    $stmtServicio->close();

    $stmt = $conexion->prepare(
        "UPDATE servicios
         SET visible_citas = ?
         WHERE id_servicio = ?"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "ii",
        $visibleCitas,
        $idServicio
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $mensaje = $visibleCitas === 1
        ? "El servicio ahora aparece en el formulario de reservas."
        : "El servicio fue ocultado del formulario de reservas.";

    echo json_encode([
        "ok" => true,
        "mensaje" => $mensaje,
        "id_servicio" => $idServicio,
        "visible_citas" => $visibleCitas,
        "nombre" => $servicio["nombre"]
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