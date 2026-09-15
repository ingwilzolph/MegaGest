<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "verificarAdministrador.php";
require_once "conexion.php";

$conexion = null;
$transaccionIniciada = false;
$stmtUsuario = null;
$stmtCorreo = null;
$stmtAdministradores = null;
$stmtActualizar = null;

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

    $nombre = trim(
        $_POST["nombre"] ?? ""
    );

    $apellido = trim(
        $_POST["apellido"] ?? ""
    );

    $correo = strtolower(
        trim($_POST["correo"] ?? "")
    );

    $rol = strtolower(
        trim($_POST["rol"] ?? "")
    );

    $estado = strtolower(
        trim($_POST["estado"] ?? "")
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
        !$idUsuario ||
        $idUsuario <= 0 ||
        $nombre === "" ||
        $apellido === "" ||
        $correo === "" ||
        $rol === "" ||
        $estado === ""
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

    $rolesValidos = [
    "administrador",
    "vendedor",
    "cajero",
    "bodeguero",
    "chofer",
    "mecanico"
    ];

    if (!in_array($rol, $rolesValidos, true)) {

        echo json_encode([
            "ok" => false,
            "mensaje" =>
                "El rol seleccionado no es válido."
        ]);

        exit;
    }

    $idUsuarioSesion =
        (int) ($_SESSION["id_usuario"] ?? 0);

    $conexion = conexion();

    $conexion->begin_transaction();
    $transaccionIniciada = true;

    /*
     * Obtener el usuario original y bloquearlo
     * durante la actualización.
     */
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

    $usuarioActual =
        $resultadoUsuario->fetch_assoc();

    $rolActual = strtolower(
        $usuarioActual["rol"]
    );

    $estadoActual = strtolower(
        $usuarioActual["estado"]
    );

    /*
     * El usuario conectado no puede quitarse
     * sus propios permisos ni desactivarse.
     */
    if ($idUsuario === $idUsuarioSesion) {

        if ($rol !== "administrador") {

            throw new Exception(
                "No puede quitarse su propio rol de administrador."
            );
        }

        if ($estado !== "activo") {

            throw new Exception(
                "No puede desactivar su propia cuenta."
            );
        }
    }

    /*
     * Evitar correos duplicados.
     */
    $sqlCorreo = "
        SELECT id_usuario
        FROM login_admin
        WHERE LOWER(correo) = LOWER(?)
        AND id_usuario <> ?
        LIMIT 1
    ";

    $stmtCorreo =
        $conexion->prepare($sqlCorreo);

    if (!$stmtCorreo) {

        throw new Exception(
            "No fue posible validar el correo."
        );
    }

    $stmtCorreo->bind_param(
        "si",
        $correo,
        $idUsuario
    );

    $stmtCorreo->execute();

    if (
        $stmtCorreo
            ->get_result()
            ->num_rows > 0
    ) {

        throw new Exception(
            "Ya existe otro usuario con ese correo."
        );
    }

    /*
     * Comprobar si el cambio quitaría al último
     * administrador activo.
     */
    $eraAdministradorActivo =
        $rolActual === "administrador" &&
        $estadoActual === "activo";

    $seguiraAdministradorActivo =
        $rol === "administrador" &&
        $estado === "activo";

    if (
        $eraAdministradorActivo &&
        !$seguiraAdministradorActivo
    ) {

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

        $filaAdministradores =
            $stmtAdministradores
                ->get_result()
                ->fetch_assoc();

        if (
            (int) $filaAdministradores["total"] <= 1
        ) {

            throw new Exception(
                "No puede modificar al último administrador activo."
            );
        }
    }

    $sqlActualizar = "
        UPDATE login_admin
        SET
            nombre = ?,
            apellido = ?,
            correo = ?,
            rol = ?,
            estado = ?
        WHERE id_usuario = ?
    ";

    $stmtActualizar =
        $conexion->prepare($sqlActualizar);

    if (!$stmtActualizar) {

        throw new Exception(
            "No fue posible preparar la actualización."
        );
    }

    $stmtActualizar->bind_param(
        "sssssi",
        $nombre,
        $apellido,
        $correo,
        $rol,
        $estado,
        $idUsuario
    );

    if (!$stmtActualizar->execute()) {

        throw new Exception(
            "No fue posible actualizar el usuario."
        );
    }

    $conexion->commit();
    $transaccionIniciada = false;

    /*
     * Si el administrador modificó sus propios
     * datos, actualizar también la sesión.
     */
    if ($idUsuario === $idUsuarioSesion) {

        $_SESSION["nombre"] = $nombre;
        $_SESSION["apellido"] = $apellido;
        $_SESSION["correo"] = $correo;
        $_SESSION["rol"] = $rol;
        $_SESSION["estado"] = $estado;
    }

    echo json_encode([
        "ok" => true,
        "mensaje" =>
            "Usuario actualizado correctamente."
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
        $stmtCorreo,
        $stmtAdministradores,
        $stmtActualizar
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