<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "conexion.php";

require_once "verificarHorarioUsuarios.php";

$conexion = null;
$stmt = null;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        throw new Exception(
            "Método no permitido."
        );
    }

    $correo = strtolower(
        trim($_POST["correo"] ?? "")
    );

    $password =
        $_POST["password"] ?? "";

    if ($correo === "" || $password === "") {

        throw new Exception(
            "Debe completar todos los campos."
        );
    }

    if (
        !filter_var(
            $correo,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        throw new Exception(
            "El correo electrónico no es válido."
        );
    }

    $conexion = conexion();

    $sql = "
        SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            contraseña,
            rol,
            fechaRegistro,
            estado
        FROM login_admin
        WHERE LOWER(correo) = LOWER(?)
        AND estado = 'activo'
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "No fue posible procesar el inicio de sesión."
        );
    }

    $stmt->bind_param(
        "s",
        $correo
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "No fue posible procesar el inicio de sesión."
        );
    }

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {

        throw new Exception(
            "Correo o contraseña incorrectos, o cuenta inactiva."
        );
    }

    $usuario = $resultado->fetch_assoc();

    if (
        !password_verify(
            $password,
            $usuario["contraseña"]
        )
    ) {

        throw new Exception(
            "Correo o contraseña incorrectos."
        );
    }

    if (!usuarioDentroHorario($usuario["rol"])) {

        throw new Exception(
            "El sistema está disponible para usuarios desde las 08:45 hasta las 19:00 horas."
        );
    }

    $rolNormalizado = strtolower(
        trim($usuario["rol"])
    );

    session_regenerate_id(true);

    $_SESSION["login"] = true;

    $_SESSION["id_usuario"] =
        (int) $usuario["id_usuario"];

    $_SESSION["nombre"] =
        $usuario["nombre"];

    $_SESSION["apellido"] =
        $usuario["apellido"];

    $_SESSION["correo"] =
        $usuario["correo"];

    $_SESSION["rol"] =
        $rolNormalizado;

    $_SESSION["estado"] =
        strtolower($usuario["estado"]);

    $_SESSION["fechaRegistro"] =
        $usuario["fechaRegistro"];

    $_SESSION["ultimo_acceso"] =
        time();

    echo json_encode([
        "ok" => true,
        "mensaje" =>
            "Bienvenido ".$usuario["nombre"],
        "usuario" => [
            "id_usuario" =>
                (int) $usuario["id_usuario"],
            "nombre" =>
                $usuario["nombre"],
            "apellido" =>
                $usuario["apellido"],
            "correo" =>
                $usuario["correo"],
            "rol" =>
                $rolNormalizado,
            "fechaRegistro" =>
                $usuario["fechaRegistro"],
            "estado" =>
                strtolower($usuario["estado"])
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