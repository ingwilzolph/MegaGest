<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "conexion.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    /* =====================================================
       VALIDAR MÉTODO
    ===================================================== */

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    /* =====================================================
       LEER DATOS JSON O FORMULARIO
    ===================================================== */

    $tipoContenido =
        $_SERVER["CONTENT_TYPE"] ?? "";

    if (
        stripos(
            $tipoContenido,
            "application/json"
        ) !== false
    ) {

        $contenido =
            file_get_contents("php://input");

        $datos =
            json_decode($contenido, true);

        if (!is_array($datos)) {
            throw new Exception(
                "Los datos recibidos no son válidos."
            );
        }

    } else {

        $datos = $_POST;
    }

    $correo = strtolower(
        trim($datos["correo"] ?? "")
    );

    $tokenRecuperacion = strtolower(
        trim(
            $datos["token_recuperacion"] ?? ""
        )
    );

    /*
     * Aceptamos los nombres nuevos y temporalmente
     * los antiguos para facilitar la actualización
     * del JavaScript.
     */

    $password = strval(
        $datos["password"] ??
        $datos["password1"] ??
        ""
    );

    $confirmarPassword = strval(
        $datos["confirmarPassword"] ??
        $datos["password2"] ??
        ""
    );

    /* =====================================================
       VALIDACIONES GENERALES
    ===================================================== */

    if (
        $correo === "" ||
        $tokenRecuperacion === "" ||
        $password === "" ||
        $confirmarPassword === ""
    ) {
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

    /*
     * El token original contiene 64 caracteres
     * hexadecimales.
     */

    if (
        !preg_match(
            "/^[a-f0-9]{64}$/",
            $tokenRecuperacion
        )
    ) {
        throw new Exception(
            "La autorización de recuperación no es válida."
        );
    }

    if ($password !== $confirmarPassword) {
        throw new Exception(
            "Las contraseñas no coinciden."
        );
    }

    $largoPassword =
        mb_strlen($password, "UTF-8");

    if (
        $largoPassword < 8 ||
        $largoPassword > 64
    ) {
        throw new Exception(
            "La contraseña debe tener entre 8 y 64 caracteres."
        );
    }

    if (!preg_match("/[A-Z]/", $password)) {
        throw new Exception(
            "La contraseña debe contener al menos una letra mayúscula."
        );
    }

    if (!preg_match("/[a-z]/", $password)) {
        throw new Exception(
            "La contraseña debe contener al menos una letra minúscula."
        );
    }

    if (!preg_match("/[0-9]/", $password)) {
        throw new Exception(
            "La contraseña debe contener al menos un número."
        );
    }

    if (
        !preg_match(
            "/[^A-Za-z0-9]/",
            $password
        )
    ) {
        throw new Exception(
            "La contraseña debe contener al menos un carácter especial."
        );
    }

    /* =====================================================
       CONEXIÓN Y TRANSACCIÓN
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       BUSCAR AUTORIZACIÓN DE RECUPERACIÓN
    ===================================================== */

    $sqlRecuperacion = "
        SELECT
            cv.id_codigo,
            cv.token_recuperacion_hash,
            cv.token_expiracion,
            la.id_usuario,
            la.contraseña,
            la.estado
        FROM codigos_verificacion cv
        INNER JOIN login_admin la
            ON LOWER(la.correo) =
               LOWER(cv.correo)
        WHERE LOWER(cv.correo) =
              LOWER(?)
        AND cv.usado = 1
        AND cv.token_recuperacion_hash
            IS NOT NULL
        ORDER BY cv.id_codigo DESC
        LIMIT 1
        FOR UPDATE
    ";

    $stmtRecuperacion =
        $conexion->prepare(
            $sqlRecuperacion
        );

    $stmtRecuperacion->bind_param(
        "s",
        $correo
    );

    $stmtRecuperacion->execute();

    $recuperacion =
        $stmtRecuperacion
            ->get_result()
            ->fetch_assoc();

    $stmtRecuperacion->close();

    /*
     * Mantenemos un mensaje general para no revelar
     * detalles internos de la recuperación.
     */

    if (!$recuperacion) {

        http_response_code(403);

        throw new Exception(
            "La recuperación no está autorizada o ha expirado."
        );
    }

    if (
        strtolower(
            trim($recuperacion["estado"])
        ) !== "activo"
    ) {

        http_response_code(403);

        throw new Exception(
            "La cuenta administrativa no está activa."
        );
    }

    /* =====================================================
       VERIFICAR EXPIRACIÓN DEL TOKEN
    ===================================================== */

    $tokenExpiracion =
        $recuperacion[
            "token_expiracion"
        ];

    if (
        empty($tokenExpiracion) ||
        strtotime($tokenExpiracion) < time()
    ) {

        invalidarRecuperacionesAdmin(
            $conexion,
            $correo
        );

        $conexion->commit();

        $transaccionIniciada = false;

        http_response_code(403);

        throw new Exception(
            "La autorización ha expirado. Solicite un código nuevo."
        );
    }

    /* =====================================================
       COMPROBAR TOKEN
    ===================================================== */

    $tokenHashRecibido = hash(
        "sha256",
        $tokenRecuperacion
    );

    $tokenHashGuardado = strval(
        $recuperacion[
            "token_recuperacion_hash"
        ]
    );

    if (
        !hash_equals(
            $tokenHashGuardado,
            $tokenHashRecibido
        )
    ) {

        http_response_code(403);

        throw new Exception(
            "La recuperación no está autorizada o ha expirado."
        );
    }

    /* =====================================================
       EVITAR REPETIR LA CONTRASEÑA ACTUAL
    ===================================================== */

    $passwordActual =
        $recuperacion["contraseña"];

    if (
        password_verify(
            $password,
            $passwordActual
        )
    ) {
        throw new Exception(
            "La nueva contraseña debe ser diferente de la contraseña actual."
        );
    }

    /* =====================================================
       GENERAR CONTRASEÑA SEGURA
    ===================================================== */

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {
        throw new Exception(
            "No fue posible proteger la nueva contraseña."
        );
    }

    /* =====================================================
       ACTUALIZAR CONTRASEÑA
    ===================================================== */

    $idUsuario = intval(
        $recuperacion["id_usuario"]
    );

    $sqlActualizar = "
        UPDATE login_admin
        SET contraseña = ?
        WHERE id_usuario = ?
        AND estado = 'activo'
    ";

    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );

    $stmtActualizar->bind_param(
        "si",
        $passwordHash,
        $idUsuario
    );

    $stmtActualizar->execute();

    if (
        $stmtActualizar->affected_rows !== 1
    ) {

        $stmtActualizar->close();

        throw new Exception(
            "No fue posible actualizar la contraseña."
        );
    }

    $stmtActualizar->close();

    /* =====================================================
       INVALIDAR CÓDIGOS Y TOKENS
    ===================================================== */

    invalidarRecuperacionesAdmin(
        $conexion,
        $correo
    );

    /* =====================================================
       CONFIRMAR CAMBIOS
    ===================================================== */

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" =>
            "Contraseña actualizada correctamente. Ya puede iniciar sesión."
    ]);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error cambiando contraseña administrativa: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensaje =
            "No fue posible actualizar la contraseña.";

    } else {

        if (http_response_code() < 400) {
            http_response_code(400);
        }

        $mensaje = $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

/* =====================================================
   INVALIDAR RECUPERACIONES
===================================================== */

function invalidarRecuperacionesAdmin(
    mysqli $conexion,
    string $correo
): void {

    $sql = "
        UPDATE codigos_verificacion
        SET
            usado = 1,
            token_recuperacion_hash = NULL,
            token_expiracion = NULL
        WHERE LOWER(correo) = LOWER(?)
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "s",
        $correo
    );

    $stmt->execute();
    $stmt->close();
}

?>