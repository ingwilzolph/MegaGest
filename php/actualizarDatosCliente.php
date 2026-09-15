<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";
require_once "sesionCliente.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    $idCliente =
        exigirSesionCliente();

    $datos = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($datos)) {
        throw new Exception(
            "Los datos recibidos no son válidos."
        );
    }

    $nombre = trim(
        $datos["nombre"] ?? ""
    );

    $apellido = trim(
        $datos["apellido"] ?? ""
    );

    $telefono = preg_replace(
        "/\D/",
        "",
        $datos["telefono"] ?? ""
    );

    /* =====================================================
       VALIDACIONES
    ===================================================== */

    if (
        mb_strlen($nombre) < 2 ||
        mb_strlen($nombre) > 80
    ) {
        throw new Exception(
            "El nombre debe contener entre 2 y 80 caracteres."
        );
    }

    if (
        mb_strlen($apellido) < 2 ||
        mb_strlen($apellido) > 80
    ) {
        throw new Exception(
            "El apellido debe contener entre 2 y 80 caracteres."
        );
    }

    if (
        !preg_match(
            "/^9[0-9]{8}$/",
            $telefono
        )
    ) {
        throw new Exception(
            "El teléfono debe contener 9 números y comenzar con 9."
        );
    }

    /* =====================================================
       ACTUALIZAR CLIENTE
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        UPDATE clientes
        SET
            nombre = ?,
            apellido = ?,
            telefono = ?
        WHERE id_cliente = ?
          AND estado = 'activo'
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "sssi",
        $nombre,
        $apellido,
        $telefono,
        $idCliente
    );

    $stmt->execute();

    /*
     * affected_rows puede ser cero si el cliente
     * guardó exactamente los mismos datos.
     */

    $stmt->close();

    $stmtCliente = $conexion->prepare(
        "SELECT
            id_cliente,
            rut,
            nombre,
            apellido,
            correo,
            telefono
         FROM clientes
         WHERE id_cliente = ?
           AND estado = 'activo'
         LIMIT 1"
    );

    $stmtCliente->bind_param(
        "i",
        $idCliente
    );

    $stmtCliente->execute();

    $cliente =
        $stmtCliente
            ->get_result()
            ->fetch_assoc();

    $stmtCliente->close();

    if (!$cliente) {
        throw new Exception(
            "La cuenta del cliente no está disponible."
        );
    }

    /* =====================================================
       ACTUALIZAR DATOS DE LA SESIÓN
    ===================================================== */

    iniciarSesionClienteApp();

    $_SESSION["cliente"]["nombre"] =
        $cliente["nombre"];

    $_SESSION["cliente"]["apellido"] =
        $cliente["apellido"];

    $_SESSION["cliente"]["correo"] =
        $cliente["correo"];

    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "Sus datos fueron actualizados correctamente.",

        "cliente" => [
            "id_cliente" =>
                intval(
                    $cliente[
                        "id_cliente"
                    ]
                ),

            "rut" =>
                $cliente["rut"],

            "nombre" =>
                $cliente["nombre"],

            "apellido" =>
                $cliente["apellido"],

            "correo" =>
                $cliente["correo"],

            "telefono" =>
                $cliente["telefono"]
        ]
    ]);

} catch (Throwable $error) {

    error_log(
        "Error actualizando cliente: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {
        http_response_code(500);

        $mensaje =
            "No fue posible actualizar sus datos.";

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