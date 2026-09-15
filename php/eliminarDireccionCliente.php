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

$conexion = null;
$transaccionIniciada = false;

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

    $idDireccion = intval(
        $datos["id_direccion"] ?? 0
    );

    if ($idDireccion <= 0) {
        throw new Exception(
            "La dirección seleccionada no es válida."
        );
    }

    $conexion = conexion();

    $conexion->set_charset(
        "utf8mb4"
    );

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       COMPROBAR PROPIEDAD Y ESTADO
    ===================================================== */

    $sqlDireccion = "
        SELECT
            id_direccion,
            nombre_direccion,
            es_principal
        FROM direcciones_cliente
        WHERE id_direccion = ?
        AND id_cliente = ?
        AND activa = 1
        LIMIT 1
        FOR UPDATE
    ";

    $stmtDireccion =
        $conexion->prepare(
            $sqlDireccion
        );

    $stmtDireccion->bind_param(
        "ii",
        $idDireccion,
        $idCliente
    );

    $stmtDireccion->execute();

    $direccion =
        $stmtDireccion
            ->get_result()
            ->fetch_assoc();

    $stmtDireccion->close();

    if (!$direccion) {

        http_response_code(404);

        throw new Exception(
            "La dirección no existe o no pertenece a su cuenta."
        );
    }

    /* =====================================================
       PROTEGER DIRECCIÓN PRINCIPAL
    ===================================================== */

    if (
        intval(
            $direccion["es_principal"]
        ) === 1
    ) {
        throw new Exception(
            "No puede eliminar la dirección principal. Seleccione primero otra dirección como principal."
        );
    }

    /* =====================================================
       ELIMINACIÓN LÓGICA
    ===================================================== */

    $sqlEliminar = "
        UPDATE direcciones_cliente
        SET activa = 0
        WHERE id_direccion = ?
        AND id_cliente = ?
        AND es_principal = 0
        AND activa = 1
    ";

    $stmtEliminar =
        $conexion->prepare(
            $sqlEliminar
        );

    $stmtEliminar->bind_param(
        "ii",
        $idDireccion,
        $idCliente
    );

    $stmtEliminar->execute();

    if (
        $stmtEliminar->affected_rows !== 1
    ) {

        $stmtEliminar->close();

        throw new Exception(
            "No fue posible eliminar la dirección."
        );
    }

    $stmtEliminar->close();

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode(
        [
            "ok" => true,
            "mensaje" =>
                "Dirección eliminada correctamente.",
            "id_direccion" =>
                $idDireccion
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error eliminando dirección: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensaje =
            "No fue posible eliminar la dirección.";

    } else {

        if (http_response_code() < 400) {
            http_response_code(400);
        }

        $mensaje = $error->getMessage();
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensaje
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>