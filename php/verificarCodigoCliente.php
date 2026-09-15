<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";

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

    $codigo = preg_replace(
        "/\D/",
        "",
        $datos["codigo"] ?? ""
    );

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
            "/^[0-9]{6}$/",
            $codigo
        )
    ) {
        throw new Exception(
            "El código debe contener 6 números."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       BUSCAR CÓDIGO VIGENTE
    ===================================================== */

    $sql = "
        SELECT
            cv.id_codigo,
            cv.id_cliente,
            cv.codigo_hash,
            cv.expiracion,
            cv.intentos,
            cv.usado,
            c.nombre,
            c.apellido,
            c.correo,
            c.estado
        FROM codigos_verificacion_cliente cv
        INNER JOIN clientes c
            ON c.id_cliente = cv.id_cliente
        WHERE cv.correo = ?
          AND cv.usado = 0
        ORDER BY cv.id_codigo DESC
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "s",
        $correo
    );

    $stmt->execute();

    $registro =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$registro) {
        throw new Exception(
            "El código no es válido o ya fue utilizado."
        );
    }

    $idCodigo = intval(
        $registro["id_codigo"]
    );

    $intentos = intval(
        $registro["intentos"]
    );

    if ($registro["estado"] !== "activo") {
        throw new Exception(
            "La cuenta no está disponible."
        );
    }

    if (
        strtotime($registro["expiracion"]) <
        time()
    ) {

        $stmtExpirado =
            $conexion->prepare(
                "UPDATE codigos_verificacion_cliente
                 SET usado = 1
                 WHERE id_codigo = ?"
            );

        $stmtExpirado->bind_param(
            "i",
            $idCodigo
        );

        $stmtExpirado->execute();
        $stmtExpirado->close();

        $conexion->commit();

        $transaccionIniciada = false;

        throw new Exception(
            "El código ha expirado. Solicite uno nuevo."
        );
    }

    if ($intentos >= 5) {

        $stmtBloquear =
            $conexion->prepare(
                "UPDATE codigos_verificacion_cliente
                 SET usado = 1
                 WHERE id_codigo = ?"
            );

        $stmtBloquear->bind_param(
            "i",
            $idCodigo
        );

        $stmtBloquear->execute();
        $stmtBloquear->close();

        $conexion->commit();

        $transaccionIniciada = false;

        throw new Exception(
            "Se superó el número de intentos. Solicite un código nuevo."
        );
    }

    /* =====================================================
       COMPROBAR CÓDIGO
    ===================================================== */

    if (
        !password_verify(
            $codigo,
            $registro["codigo_hash"]
        )
    ) {

        $nuevosIntentos =
            $intentos + 1;

        $marcarUsado =
            $nuevosIntentos >= 5
                ? 1
                : 0;

        $stmtIntento =
            $conexion->prepare(
                "UPDATE codigos_verificacion_cliente
                 SET
                    intentos = ?,
                    usado = ?
                 WHERE id_codigo = ?"
            );

        $stmtIntento->bind_param(
            "iii",
            $nuevosIntentos,
            $marcarUsado,
            $idCodigo
        );

        $stmtIntento->execute();
        $stmtIntento->close();

        $conexion->commit();

        $transaccionIniciada = false;

        $restantes =
            max(
                0,
                5 - $nuevosIntentos
            );

        throw new Exception(
            $restantes > 0
                ? "El código es incorrecto. Intentos restantes: {$restantes}."
                : "Se superó el número de intentos. Solicite un código nuevo."
        );
    }

    /* =====================================================
       CREAR TOKEN TEMPORAL
    ===================================================== */

    $tokenRecuperacion =
        bin2hex(
            random_bytes(32)
        );

    $tokenHash = hash(
        "sha256",
        $tokenRecuperacion
    );

    $tokenExpiracion = date(
        "Y-m-d H:i:s",
        time() + 600
    );

    $stmtToken =
        $conexion->prepare(
            "UPDATE codigos_verificacion_cliente
             SET
                usado = 1,
                token_recuperacion_hash = ?,
                token_expiracion = ?
             WHERE id_codigo = ?"
        );

    $stmtToken->bind_param(
        "ssi",
        $tokenHash,
        $tokenExpiracion,
        $idCodigo
    );

    $stmtToken->execute();
    $stmtToken->close();

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "Código verificado correctamente.",

        "token_recuperacion" =>
            $tokenRecuperacion,

        "expira_en" =>
            600
    ]);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error verificando código cliente: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {
        http_response_code(500);

        $mensaje =
            "No fue posible verificar el código.";

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