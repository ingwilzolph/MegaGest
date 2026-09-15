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

    $nombreDireccion = trim(
        $datos["nombre_direccion"] ?? ""
    );

    if ($nombreDireccion === "") {
        $nombreDireccion = "Dirección";
    }

    $region = trim(
        $datos["region"] ?? ""
    );

    $comuna = trim(
        $datos["comuna"] ?? ""
    );

    $calle = trim(
        $datos["calle"] ?? ""
    );

    $numero = trim(
        $datos["numero"] ?? ""
    );

    $departamento = trim(
        $datos["departamento"] ?? ""
    );

    $referencia = trim(
        $datos["referencia"] ?? ""
    );

    $solicitaPrincipal =
        filter_var(
            $datos["es_principal"] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

    /* =====================================================
       VALIDACIONES
    ===================================================== */

    if (
        mb_strlen($nombreDireccion) < 2 ||
        mb_strlen($nombreDireccion) > 50
    ) {
        throw new Exception(
            "El nombre de la dirección debe contener entre 2 y 50 caracteres."
        );
    }

    if (
        mb_strtolower(
            $region,
            "UTF-8"
        ) !==
        mb_strtolower(
            "Región Metropolitana",
            "UTF-8"
        )
    ) {
        throw new Exception(
            "Por ahora el despacho está disponible solamente en la Región Metropolitana."
        );
    }

    if (
        mb_strlen($comuna) < 2 ||
        mb_strlen($comuna) > 100
    ) {
        throw new Exception(
            "La comuna ingresada no es válida."
        );
    }

    if (
        mb_strlen($calle) < 2 ||
        mb_strlen($calle) > 150
    ) {
        throw new Exception(
            "La calle ingresada no es válida."
        );
    }

    if (
        $numero === "" ||
        mb_strlen($numero) > 20
    ) {
        throw new Exception(
            "El número de dirección no es válido."
        );
    }

    if (mb_strlen($departamento) > 30) {
        throw new Exception(
            "El departamento es demasiado extenso."
        );
    }

    if (mb_strlen($referencia) > 250) {
        throw new Exception(
            "La referencia es demasiado extensa."
        );
    }

    /* =====================================================
       TRANSACCIÓN
    ===================================================== */

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    $direccionExistente = null;

    if ($idDireccion > 0) {

        $stmtExiste = $conexion->prepare(
            "SELECT
                id_direccion,
                es_principal
             FROM direcciones_cliente
             WHERE id_direccion = ?
               AND id_cliente = ?
               AND activa = 1
             LIMIT 1
             FOR UPDATE"
        );

        $stmtExiste->bind_param(
            "ii",
            $idDireccion,
            $idCliente
        );

        $stmtExiste->execute();

        $direccionExistente =
            $stmtExiste
                ->get_result()
                ->fetch_assoc();

        $stmtExiste->close();

        if (!$direccionExistente) {
            throw new Exception(
                "La dirección no existe o no pertenece a su cuenta."
            );
        }
    }

    /* =====================================================
       COMPROBAR DIRECCIÓN PRINCIPAL
    ===================================================== */

    $stmtCantidad = $conexion->prepare(
        "SELECT COUNT(*) AS cantidad
         FROM direcciones_cliente
         WHERE id_cliente = ?
           AND activa = 1"
    );

    $stmtCantidad->bind_param(
        "i",
        $idCliente
    );

    $stmtCantidad->execute();

    $cantidadDirecciones = intval(
        $stmtCantidad
            ->get_result()
            ->fetch_assoc()["cantidad"] ?? 0
    );

    $stmtCantidad->close();

        /*
    * Una cuenta puede tener como máximo
    * 20 direcciones activas.
    */

    if (
        $idDireccion === 0 &&
        $cantidadDirecciones >= 20
    ) {
        throw new Exception(
            "Ha alcanzado el máximo de 20 direcciones registradas."
        );
    }

    /*
     * La primera dirección siempre será principal.
     * Si se edita la dirección principal, continuará
     * siéndolo aunque se desmarque accidentalmente.
     */

    $esPrincipal =
        $solicitaPrincipal ||
        $cantidadDirecciones === 0 ||
        (
            $direccionExistente &&
            intval(
                $direccionExistente[
                    "es_principal"
                ]
            ) === 1
        );

    if ($esPrincipal) {

        $stmtQuitarPrincipal =
            $conexion->prepare(
                "UPDATE direcciones_cliente
                 SET es_principal = 0
                 WHERE id_cliente = ?"
            );

        $stmtQuitarPrincipal->bind_param(
            "i",
            $idCliente
        );

        $stmtQuitarPrincipal->execute();
        $stmtQuitarPrincipal->close();
    }

    /* =====================================================
       INSERTAR O ACTUALIZAR
    ===================================================== */

    $valorPrincipal =
        $esPrincipal ? 1 : 0;

    if ($idDireccion > 0) {

        $sql = "
            UPDATE direcciones_cliente
            SET
                nombre_direccion = ?,
                region = ?,
                comuna = ?,
                calle = ?,
                numero = ?,
                departamento =
                    NULLIF(?, ''),
                referencia =
                    NULLIF(?, ''),
                es_principal = ?,
                activa = 1
            WHERE id_direccion = ?
              AND id_cliente = ?
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "sssssssiii",
            $nombreDireccion,
            $region,
            $comuna,
            $calle,
            $numero,
            $departamento,
            $referencia,
            $valorPrincipal,
            $idDireccion,
            $idCliente
        );

        $stmt->execute();
        $stmt->close();

    } else {

        $sql = "
            INSERT INTO direcciones_cliente (
                id_cliente,
                nombre_direccion,
                region,
                comuna,
                calle,
                numero,
                departamento,
                referencia,
                es_principal,
                activa
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NULLIF(?, ''),
                NULLIF(?, ''),
                ?,
                1
            )
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "isssssssi",
            $idCliente,
            $nombreDireccion,
            $region,
            $comuna,
            $calle,
            $numero,
            $departamento,
            $referencia,
            $valorPrincipal
        );

        $stmt->execute();

        $idDireccion =
            $conexion->insert_id;

        $stmt->close();
    }

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "La dirección fue guardada correctamente.",

        "direccion" => [
            "id_direccion" =>
                $idDireccion,

            "es_principal" =>
                $esPrincipal
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
        "Error guardando dirección: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {
        http_response_code(500);

        $mensaje =
            "No fue posible guardar la dirección.";

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