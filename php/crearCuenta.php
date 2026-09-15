<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarAdministrador.php";
require_once "conexion.php";

$conexion = null;
$verificar = null;
$stmt = null;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        throw new Exception(
            "Método no permitido."
        );
    }


    $nombre = trim(
        $_POST["nombre"] ?? ""
    );

    $apellido = trim(
        $_POST["apellido"] ?? ""
    );

    $correo = strtolower(
        trim($_POST["correo"] ?? "")
    );

    $password =
        $_POST["password"] ?? "";

    $rol = strtolower(
        trim($_POST["rol"] ?? "")
    );

    require_once "autorizarRoles.php";
    
     $rolSesion = strtolower(
    trim($_SESSION["rol"] ?? "")
    );

    if ($rolSesion !== "administrador") {

        http_response_code(403);

        echo json_encode([
            "ok" => false,
            "permisoDenegado" => true,
            "codigo" => "PERMISO_DENEGADO",
            "mensaje" =>
                "Solo el administrador puede modificar usuarios."
        ]);

        exit;
    }

    if (
        $nombre === "" ||
        $apellido === "" ||
        $correo === "" ||
        $password === "" ||
        $rol === ""
    ) {

        throw new Exception(
            "Debe completar todos los campos."
        );
    }


    if (
        mb_strlen($nombre) < 2 ||
        mb_strlen($nombre) > 60
    ) {

        throw new Exception(
            "El nombre debe tener entre 2 y 60 caracteres."
        );
    }

    if (
        mb_strlen($apellido) < 2 ||
        mb_strlen($apellido) > 60
    ) {

        throw new Exception(
            "El apellido debe tener entre 2 y 60 caracteres."
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

    if (mb_strlen($correo) > 150) {

        throw new Exception(
            "El correo electrónico es demasiado largo."
        );
    }

    if (
        strlen($password) < 8 ||
        strlen($password) > 72
    ) {

        throw new Exception(
            "La contraseña debe tener entre 8 y 72 caracteres."
        );
    }

    $rolesValidos = [
    "administrador",
    "vendedor",
    "cajero",
    "bodeguero",
    "chofer",
    "mecanico"
    ];

    $rol = strtolower(
        trim($_POST["rol"] ?? "")
    );

    if (!in_array($rol, $rolesValidos, true)) {
        throw new Exception("El rol seleccionado no es válido.");
    }

    $conexion = conexion();

    $sqlVerificar = "
        SELECT id_usuario
        FROM login_admin
        WHERE LOWER(correo) = LOWER(?)
        LIMIT 1
    ";

    $verificar = $conexion->prepare(
        $sqlVerificar
    );

    if (!$verificar) {
        throw new Exception(
            "No fue posible validar el correo."
        );
    }

    $verificar->bind_param(
        "s",
        $correo
    );

    if (!$verificar->execute()) {
        throw new Exception(
            "No fue posible validar el correo."
        );
    }

    $resultadoVerificacion =
        $verificar->get_result();

    if (
        $resultadoVerificacion->num_rows > 0
    ) {

        throw new Exception(
            "El correo ya está registrado."
        );
    }

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {

        throw new Exception(
            "No fue posible proteger la contraseña."
        );
    }

    $estado = "activo";

    $sqlInsertar = "
        INSERT INTO login_admin (
            nombre,
            apellido,
            correo,
            contraseña,
            rol,
            fechaRegistro,
            estado
        )
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ";

    $stmt = $conexion->prepare(
        $sqlInsertar
    );

    if (!$stmt) {
        throw new Exception(
            "No fue posible preparar el registro."
        );
    }

    $stmt->bind_param(
        "ssssss",
        $nombre,
        $apellido,
        $correo,
        $passwordHash,
        $rol,
        $estado
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "No fue posible registrar el usuario."
        );
    }

    echo json_encode([
        "ok" => true,
        "mensaje" =>
            "Usuario registrado correctamente.",
        "idUsuario" =>
            (int) $conexion->insert_id
    ]);

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    if ($verificar instanceof mysqli_stmt) {
        $verificar->close();
    }

    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}