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

    /* =====================================================
       VALIDAR MÉTODO
    ===================================================== */

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    /* =====================================================
       CLIENTE AUTENTICADO
    ===================================================== */

    $idCliente = exigirSesionCliente();

    if ($idCliente <= 0) {

        http_response_code(401);

        throw new Exception(
            "Debe iniciar sesión para consultar sus direcciones."
        );
    }

    /* =====================================================
       CONEXIÓN
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    /* =====================================================
       CONSULTAR DIRECCIONES
    ===================================================== */

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
            es_principal
        FROM direcciones_cliente
        WHERE id_cliente = ?
        AND activa = 1
        ORDER BY
            es_principal DESC,
            id_direccion DESC
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "i",
        $idCliente
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

    $direcciones = [];

    while (
        $direccion =
        $resultado->fetch_assoc()
    ) {

        $idDireccion = intval(
            $direccion["id_direccion"]
        );

        $nombreDireccion = trim(
            $direccion["nombre_direccion"] ??
            ""
        );

        $region = trim(
            $direccion["region"] ?? ""
        );

        $comuna = trim(
            $direccion["comuna"] ?? ""
        );

        $calle = trim(
            $direccion["calle"] ?? ""
        );

        $numero = trim(
            $direccion["numero"] ?? ""
        );

        $departamento = trim(
            $direccion["departamento"] ??
            ""
        );

        $referencia = trim(
            $direccion["referencia"] ??
            ""
        );

        $esPrincipal =
            intval(
                $direccion["es_principal"]
            ) === 1;

        /* =================================================
           FORMAR DIRECCIÓN PARA MOSTRAR
        ================================================= */

        $direccionCompleta =
            trim(
                $calle . " " . $numero
            );

        if ($departamento !== "") {

            $direccionCompleta .=
                ", " . $departamento;
        }

        /*
         * Por ahora solamente permitimos despachos
         * dentro de la Región Metropolitana.
         */

        $disponibleDespacho =
            mb_strtolower(
                $region,
                "UTF-8"
            ) ===
            mb_strtolower(
                "Región Metropolitana",
                "UTF-8"
            );

        $direcciones[] = [
            "id_direccion" =>
                $idDireccion,

            "nombre_direccion" =>
                $nombreDireccion !== ""
                    ? $nombreDireccion
                    : "Dirección",

            "region" =>
                $region,

            "comuna" =>
                $comuna,

            "calle" =>
                $calle,

            "numero" =>
                $numero,

            "departamento" =>
                $departamento,

            "referencia" =>
                $referencia,

            "direccion_completa" =>
                $direccionCompleta,

            "es_principal" =>
                $esPrincipal,

            "disponible_despacho" =>
                $disponibleDespacho
        ];
    }

    $stmt->close();

    /* =====================================================
       RESPUESTA
    ===================================================== */

    echo json_encode(
        [
            "ok" => true,

            "mensaje" =>
                count($direcciones) > 0
                    ? "Direcciones obtenidas correctamente."
                    : "No tiene direcciones registradas.",

            "cantidad" =>
                count($direcciones),

            "datos" =>
                $direcciones
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $error) {

    error_log(
        "Error obteniendo direcciones del cliente: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensaje =
            "No fue posible consultar las direcciones.";

    } else {

        if (http_response_code() < 400) {
            http_response_code(400);
        }

        $mensaje = $error->getMessage();
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensaje,
            "cantidad" => 0,
            "datos" => []
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