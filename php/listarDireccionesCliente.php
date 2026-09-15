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

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    $idCliente =
        exigirSesionCliente();

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            id_direccion,
            nombre_direccion,
            region,
            comuna,
            calle,
            numero,
            departamento,
            referencia,
            es_principal,
            activa,
            fecha_registro,
            fecha_actualizacion
        FROM direcciones_cliente
        WHERE id_cliente = ?
          AND activa = 1
        ORDER BY
            es_principal DESC,
            fecha_actualizacion DESC,
            id_direccion DESC
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "i",
        $idCliente
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $direcciones = [];

    while (
        $direccion =
            $resultado->fetch_assoc()
    ) {

        $direcciones[] = [
            "id_direccion" =>
                intval(
                    $direccion[
                        "id_direccion"
                    ]
                ),

            "nombre" =>
                $direccion[
                    "nombre_direccion"
                ],

            "region" =>
                $direccion["region"],

            "comuna" =>
                $direccion["comuna"],

            "calle" =>
                $direccion["calle"],

            "numero" =>
                $direccion["numero"],

            "departamento" =>
                $direccion[
                    "departamento"
                ],

            "referencia" =>
                $direccion[
                    "referencia"
                ],

            "es_principal" =>
                intval(
                    $direccion[
                        "es_principal"
                    ]
                ) === 1,

            "activa" =>
                intval(
                    $direccion["activa"]
                ) === 1,

            "fecha_registro" =>
                $direccion[
                    "fecha_registro"
                ],

            "fecha_actualizacion" =>
                $direccion[
                    "fecha_actualizacion"
                ]
        ];
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,

        "total_direcciones" =>
            count($direcciones),

        "direcciones" =>
            $direcciones
    ]);

} catch (Throwable $error) {

    error_log(
        "Error listando direcciones: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {
        http_response_code(500);

        $mensaje =
            "No fue posible cargar las direcciones.";

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