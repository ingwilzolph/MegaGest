<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "verificarAdministrador.php";
require_once "conexion.php";

$conexion = null;
$stmt = null;

try {

    $idUsuario = filter_input(
        INPUT_GET,
        "id",
        FILTER_VALIDATE_INT
    );

    if (!$idUsuario || $idUsuario <= 0) {

        throw new Exception(
            "El ID del usuario no es válido."
        );
    }

    $conexion = conexion();

    $sql = "
        SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            rol,
            fechaRegistro,
            estado
        FROM login_admin
        WHERE id_usuario = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "No fue posible preparar la consulta."
        );
    }

    $stmt->bind_param(
        "i",
        $idUsuario
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "No fue posible consultar el usuario."
        );
    }

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {

        throw new Exception(
            "Usuario no encontrado."
        );
    }

    $usuario = $resultado->fetch_assoc();

    $usuario["id_usuario"] =
        (int) $usuario["id_usuario"];

    echo json_encode([
        "ok" => true,
        "datos" => $usuario
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