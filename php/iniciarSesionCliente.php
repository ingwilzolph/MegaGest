<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "conexion.php";
require_once "sesionCliente.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}

$conexion = null;

try {

    $contenido =
        file_get_contents("php://input");

    $datos =
        json_decode($contenido, true);

    if (!is_array($datos)) {

        /*
         * También permite FormData.
         */

        $datos = $_POST;
    }

    $correo = strtolower(
        trim($datos["correo"] ?? "")
    );

    $password =
        $datos["password"] ?? "";

    if (
        $correo === "" ||
        $password === ""
    ) {
        throw new Exception(
            "Ingrese su correo y contraseña."
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
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            id_cliente,
            nombre,
            apellido,
            correo,
            password_hash,
            estado
        FROM clientes
        WHERE correo = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "s",
        $correo
    );

    $stmt->execute();

    $cliente =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    /*
     * Usamos el mismo mensaje cuando el correo
     * no existe o la contraseña es incorrecta.
     */

    if (
        !$cliente ||
        empty($cliente["password_hash"]) ||
        !password_verify(
            $password,
            $cliente["password_hash"]
        )
    ) {

        http_response_code(401);

        echo json_encode([
            "ok" => false,
            "mensaje" =>
                "El correo o la contraseña son incorrectos."
        ]);

        exit;
    }

    if ($cliente["estado"] === "inactivo") {

        http_response_code(403);

        echo json_encode([
            "ok" => false,
            "mensaje" =>
                "La cuenta se encuentra inactiva."
        ]);

        exit;
    }

    if ($cliente["estado"] === "bloqueado") {

        http_response_code(403);

        echo json_encode([
            "ok" => false,
            "mensaje" =>
                "La cuenta se encuentra bloqueada."
        ]);

        exit;
    }

    /*
     * Si PHP cambia en el futuro su algoritmo
     * recomendado, renovamos el hash.
     */

    if (
        password_needs_rehash(
            $cliente["password_hash"],
            PASSWORD_DEFAULT
        )
    ) {

        $nuevoHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmtHash = $conexion->prepare(
            "UPDATE clientes
             SET password_hash = ?
             WHERE id_cliente = ?"
        );

        $stmtHash->bind_param(
            "si",
            $nuevoHash,
            $cliente["id_cliente"]
        );

        $stmtHash->execute();
        $stmtHash->close();
    }

    guardarSesionCliente($cliente);

    $stmtAcceso = $conexion->prepare(
        "UPDATE clientes
         SET ultimo_acceso = NOW()
         WHERE id_cliente = ?"
    );

    $stmtAcceso->bind_param(
        "i",
        $cliente["id_cliente"]
    );

    $stmtAcceso->execute();
    $stmtAcceso->close();

    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "Sesión iniciada correctamente.",

        "cliente" => [
            "id_cliente" =>
                intval($cliente["id_cliente"]),

            "nombre" =>
                $cliente["nombre"],

            "apellido" =>
                $cliente["apellido"],

            "correo" =>
                $cliente["correo"]
        ]
    ]);

} catch (Throwable $error) {

    error_log(
        "Error iniciando sesión cliente: " .
        $error->getMessage()
    );

    $mensaje =
        $error instanceof mysqli_sql_exception
            ? "No fue posible iniciar sesión."
            : $error->getMessage();

    http_response_code(400);

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