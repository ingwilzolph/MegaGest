<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";
require_once "sesionCliente.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    $contenido =
        file_get_contents("php://input");

    $datos =
        json_decode($contenido, true);

    if (!is_array($datos)) {
        $datos = $_POST;
    }

    $correo = strtolower(
        trim($datos["correo"] ?? "")
    );

    $tokenRecuperacion = trim(
        $datos["token_recuperacion"] ?? ""
    );

    $password =
        $datos["password"] ?? "";

    $confirmarPassword =
        $datos["confirmarPassword"] ?? "";

    /* =====================================================
       VALIDACIONES
    ===================================================== */

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

    if (strlen($password) < 8) {
        throw new Exception(
            "La contraseña debe tener al menos 8 caracteres."
        );
    }

    if (
        !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[a-z]/", $password) ||
        !preg_match("/[0-9]/", $password)
    ) {
        throw new Exception(
            "La contraseña debe incluir mayúscula, minúscula y número."
        );
    }

    if ($password !== $confirmarPassword) {
        throw new Exception(
            "Las contraseñas no coinciden."
        );
    }

    $tokenHash = hash(
        "sha256",
        $tokenRecuperacion
    );

    /* =====================================================
       TRANSACCIÓN
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    $sql = "
        SELECT
            cv.id_codigo,
            cv.id_cliente,
            cv.token_expiracion,
            c.nombre,
            c.apellido,
            c.correo,
            c.estado,
            c.password_hash
        FROM codigos_verificacion_cliente cv
        INNER JOIN clientes c
            ON c.id_cliente = cv.id_cliente
        WHERE cv.correo = ?
          AND cv.token_recuperacion_hash = ?
          AND cv.usado = 1
        ORDER BY cv.id_codigo DESC
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "ss",
        $correo,
        $tokenHash
    );

    $stmt->execute();

    $registro =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$registro) {
        throw new Exception(
            "La autorización de recuperación no es válida."
        );
    }

    if ($registro["estado"] !== "activo") {
        throw new Exception(
            "La cuenta del cliente no está disponible."
        );
    }

    if (
        empty(
            $registro["token_expiracion"]
        ) ||
        strtotime(
            $registro["token_expiracion"]
        ) < time()
    ) {

        $stmtVencido =
            $conexion->prepare(
                "UPDATE codigos_verificacion_cliente
                 SET
                    token_recuperacion_hash = NULL,
                    token_expiracion = NULL
                 WHERE id_codigo = ?"
            );

        $stmtVencido->bind_param(
            "i",
            $registro["id_codigo"]
        );

        $stmtVencido->execute();
        $stmtVencido->close();

        $conexion->commit();

        $transaccionIniciada = false;

        throw new Exception(
            "La autorización ha expirado. Solicite un código nuevo."
        );
    }

    $idCliente = intval(
        $registro["id_cliente"]
    );

    /*
     * Evitamos reutilizar exactamente la misma
     * contraseña cuando ya existe una.
     */

    if (
        !empty($registro["password_hash"]) &&
        password_verify(
            $password,
            $registro["password_hash"]
        )
    ) {
        throw new Exception(
            "La contraseña nueva debe ser diferente de la anterior."
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

    /* =====================================================
       ACTUALIZAR CONTRASEÑA
    ===================================================== */

    $stmtPassword =
        $conexion->prepare(
            "UPDATE clientes
             SET
                password_hash = ?,
                ultimo_acceso = NOW()
             WHERE id_cliente = ?
               AND estado = 'activo'"
        );

    $stmtPassword->bind_param(
        "si",
        $passwordHash,
        $idCliente
    );

    $stmtPassword->execute();

    if (
        $stmtPassword->affected_rows !== 1
    ) {
        throw new Exception(
            "No fue posible actualizar la contraseña."
        );
    }

    $stmtPassword->close();

    /* =====================================================
       INVALIDAR CÓDIGOS Y TOKENS
    ===================================================== */

    $stmtInvalidar =
        $conexion->prepare(
            "UPDATE codigos_verificacion_cliente
             SET
                usado = 1,
                token_recuperacion_hash = NULL,
                token_expiracion = NULL
             WHERE id_cliente = ?"
        );

    $stmtInvalidar->bind_param(
        "i",
        $idCliente
    );

    $stmtInvalidar->execute();
    $stmtInvalidar->close();

    $conexion->commit();

    $transaccionIniciada = false;

    /* =====================================================
       INICIAR SESIÓN
    ===================================================== */

    guardarSesionCliente([
        "id_cliente" =>
            $idCliente,

        "nombre" =>
            $registro["nombre"],

        "apellido" =>
            $registro["apellido"],

        "correo" =>
            $registro["correo"]
    ]);

    echo json_encode([
        "ok" => true,

        "autenticado" => true,

        "mensaje" =>
            "La contraseña fue actualizada correctamente.",

        "cliente" => [
            "id_cliente" =>
                $idCliente,

            "nombre" =>
                $registro["nombre"],

            "apellido" =>
                $registro["apellido"],

            "correo" =>
                $registro["correo"]
        ]
    ]);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error cambiando contraseña cliente: " .
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

        if (
            http_response_code() < 400
        ) {
            http_response_code(400);
        }

        $mensaje =
            $error->getMessage();
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

?>