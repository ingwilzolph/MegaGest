<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "verificarAdministrador.php";
require_once "conexion.php";

$conexion = null;
$stmtUsuario = null;
$stmtAdministradores = null;
$stmtEliminar = null;
$transaccionIniciada = false;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        throw new Exception(
            "Método no permitido."
        );
    }

    $idUsuario = filter_input(
        INPUT_POST,
        "id_usuario",
        FILTER_VALIDATE_INT
    );

    if (!$idUsuario || $idUsuario <= 0) {

        throw new Exception(
            "El ID del usuario no es válido."
        );
    }

    $idUsuarioSesion =
        (int) ($_SESSION["id_usuario"] ?? 0);

    if ($idUsuario === $idUsuarioSesion) {

        throw new Exception(
            "No puede eliminar su propia cuenta."
        );
    }

    $conexion = conexion();

    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $sqlUsuario = "
        SELECT
            id_usuario,
            rol,
            estado
        FROM login_admin
        WHERE id_usuario = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmtUsuario =
        $conexion->prepare($sqlUsuario);

    if (!$stmtUsuario) {

        throw new Exception(
            "No fue posible consultar el usuario."
        );
    }

    $stmtUsuario->bind_param(
        "i",
        $idUsuario
    );

    $stmtUsuario->execute();

    $resultadoUsuario =
        $stmtUsuario->get_result();

    if ($resultadoUsuario->num_rows === 0) {

        throw new Exception(
            "El usuario no existe."
        );
    }

    $usuario =
        $resultadoUsuario->fetch_assoc();

    $esAdministradorActivo =
        strtolower($usuario["rol"]) ===
            "administrador" &&
        strtolower($usuario["estado"]) ===
            "activo";

    if ($esAdministradorActivo) {

        $sqlAdministradores = "
            SELECT COUNT(*) AS total
            FROM login_admin
            WHERE LOWER(rol) = 'administrador'
            AND LOWER(estado) = 'activo'
        ";

        $stmtAdministradores =
            $conexion->prepare(
                $sqlAdministradores
            );

        if (!$stmtAdministradores) {

            throw new Exception(
                "No fue posible validar los administradores."
            );
        }

        $stmtAdministradores->execute();

        $fila =
            $stmtAdministradores
                ->get_result()
                ->fetch_assoc();

        if ((int) $fila["total"] <= 1) {

            throw new Exception(
                "No puede eliminar al último administrador activo."
            );
        }
    }

    $sqlEliminar = "
        DELETE FROM login_admin
        WHERE id_usuario = ?
    ";

    $stmtEliminar =
        $conexion->prepare($sqlEliminar);

    if (!$stmtEliminar) {

        throw new Exception(
            "No fue posible preparar la eliminación."
        );
    }

    $stmtEliminar->bind_param(
        "i",
        $idUsuario
    );

    if (!$stmtEliminar->execute()) {

        throw new Exception(
            "No fue posible eliminar el usuario."
        );
    }

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" =>
            "Usuario eliminado correctamente."
    ]);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    foreach ([
        $stmtUsuario,
        $stmtAdministradores,
        $stmtEliminar
    ] as $statement) {

        if ($statement instanceof mysqli_stmt) {
            $statement->close();
        }
    }

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>