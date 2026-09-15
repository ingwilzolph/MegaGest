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

try {

    iniciarSesionClienteApp();

    $idCliente = obtenerIdClienteSesion();

    if ($idCliente <= 0) {

        echo json_encode([
            "ok" => true,
            "autenticado" => false,
            "cliente" => null
        ]);

        exit;
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            c.id_cliente,
            c.rut,
            c.nombre,
            c.apellido,
            c.correo,
            c.telefono,
            c.estado,
            c.correo_verificado,
            d.id_direccion,
            d.nombre_direccion,
            d.region,
            d.comuna,
            d.calle,
            d.numero,
            d.departamento,
            d.referencia
        FROM clientes c
        LEFT JOIN direcciones_cliente d
            ON d.id_cliente = c.id_cliente
            AND d.es_principal = 1
            AND d.activa = 1
        WHERE c.id_cliente = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "i",
        $idCliente
    );

    $stmt->execute();

    $cliente =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$cliente) {

        unset(
            $_SESSION["cliente"],
            $_SESSION["cliente_ultimo_acceso"]
        );

        echo json_encode([
            "ok" => true,
            "autenticado" => false,
            "cliente" => null
        ]);

        exit;
    }

    if ($cliente["estado"] !== "activo") {

        unset(
            $_SESSION["cliente"],
            $_SESSION["cliente_ultimo_acceso"]
        );

        http_response_code(403);

        echo json_encode([
            "ok" => false,
            "autenticado" => false,
            "mensaje" =>
                "La cuenta del cliente no está activa."
        ]);

        exit;
    }

    echo json_encode([
        "ok" => true,
        "autenticado" => true,

        "cliente" => [
            "id_cliente" =>
                intval($cliente["id_cliente"]),

            "rut" =>
                $cliente["rut"],

            "nombre" =>
                $cliente["nombre"],

            "apellido" =>
                $cliente["apellido"],

            "correo" =>
                $cliente["correo"],

            "telefono" =>
                $cliente["telefono"],

            "correo_verificado" =>
                intval(
                    $cliente["correo_verificado"]
                ) === 1,

            "direccion" =>
                $cliente["id_direccion"]
                    ? [
                        "id_direccion" =>
                            intval(
                                $cliente[
                                    "id_direccion"
                                ]
                            ),

                        "nombre" =>
                            $cliente[
                                "nombre_direccion"
                            ],

                        "region" =>
                            $cliente["region"],

                        "comuna" =>
                            $cliente["comuna"],

                        "calle" =>
                            $cliente["calle"],

                        "numero" =>
                            $cliente["numero"],

                        "departamento" =>
                            $cliente["departamento"],

                        "referencia" =>
                            $cliente["referencia"]
                    ]
                    : null
        ]
    ]);

} catch (Throwable $error) {

    error_log(
        "Error verificando sesión cliente: " .
        $error->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" =>
            "No fue posible comprobar la sesión del cliente."
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>