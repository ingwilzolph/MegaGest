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

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception(
            "Método no permitido."
        );
    }

    $datos = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($datos)) {
        throw new Exception(
            "Los datos no son válidos."
        );
    }

    $region = trim(
        $datos["region"] ?? ""
    );

    $comuna = trim(
        $datos["comuna"] ?? ""
    );

    if (
        $region === "" ||
        $comuna === ""
    ) {
        throw new Exception(
            "Seleccione la región e ingrese la comuna."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /*
     * Primero busca la comuna exacta.
     * Si no existe, utiliza la tarifa regional.
     */

    $sql = "
        SELECT
            id_tarifa,
            region,
            comuna,
            costo,
            plazo_minimo_dias,
            plazo_maximo_dias
        FROM tarifas_despacho
        WHERE region = ?
          AND activa = 1
          AND (
                LOWER(TRIM(comuna)) =
                    LOWER(TRIM(?))
                OR comuna = ''
          )
        ORDER BY
            CASE
                WHEN LOWER(TRIM(comuna)) =
                     LOWER(TRIM(?))
                THEN 0
                ELSE 1
            END
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "sss",
        $region,
        $comuna,
        $comuna
    );

    $stmt->execute();

    $tarifa =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$tarifa) {

        http_response_code(404);

        echo json_encode([
            "ok" => false,
            "sinCobertura" => true,
            "mensaje" =>
                "Por ahora no tenemos cobertura de despacho para la ubicación seleccionada."
        ]);

        exit;
    }

    $costo = intval(
        $tarifa["costo"]
    );

    if ($costo < 5000) {
        throw new Exception(
            "La tarifa de despacho configurada no es válida."
        );
    }

    echo json_encode([
        "ok" => true,

        "tarifa" => [
            "id_tarifa" =>
                intval($tarifa["id_tarifa"]),

            "region" =>
                $tarifa["region"],

            "comuna" =>
                $tarifa["comuna"],

            "costo" =>
                $costo,

            "plazo_minimo_dias" =>
                intval(
                    $tarifa[
                        "plazo_minimo_dias"
                    ]
                ),

            "plazo_maximo_dias" =>
                intval(
                    $tarifa[
                        "plazo_maximo_dias"
                    ]
                )
        ]
    ]);

} catch (Throwable $error) {

    error_log(
        "Error calculando despacho: " .
        $error->getMessage()
    );

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" =>
            $error instanceof mysqli_sql_exception
                ? "No fue posible calcular el despacho."
                : $error->getMessage()
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>