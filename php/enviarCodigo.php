<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";
require_once "enviarCorreo.php";
require_once "config.php";

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

    if (
        !filter_var(
            $correo,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        throw new Exception(
            "Ingrese un correo electrónico válido."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /* =====================================================
       BUSCAR USUARIO ADMINISTRATIVO
    ===================================================== */

    $stmtUsuario = $conexion->prepare(
        "SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            estado
         FROM login_admin
         WHERE correo = ?
         LIMIT 1"
    );

    $stmtUsuario->bind_param(
        "s",
        $correo
    );

    $stmtUsuario->execute();

    $usuario =
        $stmtUsuario
            ->get_result()
            ->fetch_assoc();

    $stmtUsuario->close();

    /*
     * Nunca revelamos si el correo existe,
     * está inactivo o está bloqueado.
     */

    if (
        !$usuario ||
        strtolower($usuario["estado"]) !==
            "activo"
    ) {
        responderGenerico();
    }

    /* =====================================================
       EVITAR ENVÍOS REPETIDOS
    ===================================================== */

    $stmtUltimo = $conexion->prepare(
        "SELECT fecha_creacion
         FROM codigos_verificacion
         WHERE correo = ?
         ORDER BY id_codigo DESC
         LIMIT 1"
    );

    $stmtUltimo->bind_param(
        "s",
        $correo
    );

    $stmtUltimo->execute();

    $ultimoCodigo =
        $stmtUltimo
            ->get_result()
            ->fetch_assoc();

    $stmtUltimo->close();

    if ($ultimoCodigo) {

        $fechaAnterior = strtotime(
            $ultimoCodigo[
                "fecha_creacion"
            ]
        );

        if (
            $fechaAnterior !== false &&
            time() - $fechaAnterior < 60
        ) {
            responderGenerico();
        }
    }

    /* =====================================================
       GENERAR CÓDIGO CIFRADO
    ===================================================== */

    $codigo = str_pad(
        strval(
            random_int(0, 999999)
        ),
        6,
        "0",
        STR_PAD_LEFT
    );

    $codigoHash = password_hash(
        $codigo,
        PASSWORD_DEFAULT
    );

    if ($codigoHash === false) {
        throw new Exception(
            "No fue posible generar el código."
        );
    }

    $fechaCreacion = date(
        "Y-m-d H:i:s"
    );

    $expiracion = date(
        "Y-m-d H:i:s",
        time() + 600
    );

    /* =====================================================
       INVALIDAR CÓDIGOS ANTERIORES
    ===================================================== */

    $stmtInvalidar = $conexion->prepare(
        "UPDATE codigos_verificacion
         SET usado = 1
         WHERE correo = ?
           AND usado = 0"
    );

    $stmtInvalidar->bind_param(
        "s",
        $correo
    );

    $stmtInvalidar->execute();
    $stmtInvalidar->close();

    /* =====================================================
       GUARDAR NUEVO CÓDIGO
    ===================================================== */

    $stmtCodigo = $conexion->prepare(
        "INSERT INTO codigos_verificacion (
            correo,
            codigo_hash,
            fecha_creacion,
            expiracion,
            intentos,
            usado,
            token_recuperacion_hash,
            token_expiracion
         )
         VALUES (
            ?,
            ?,
            ?,
            ?,
            0,
            0,
            NULL,
            NULL
         )"
    );

    $stmtCodigo->bind_param(
        "ssss",
        $correo,
        $codigoHash,
        $fechaCreacion,
        $expiracion
    );

    $stmtCodigo->execute();

    $idCodigo =
        $conexion->insert_id;

    $stmtCodigo->close();

    /* =====================================================
       PREPARAR CORREO
    ===================================================== */

    $nombreCompleto = trim(
        $usuario["nombre"] .
        " " .
        $usuario["apellido"]
    );

    $nombreSeguro = htmlspecialchars(
        $nombreCompleto,
        ENT_QUOTES,
        "UTF-8"
    );

    $asunto =
        "Código de recuperación administrativa - AlianzaPro";

    $mensaje = "
        <div style='
            max-width:600px;
            margin:auto;
            padding:28px;
            font-family:Arial,sans-serif;
            color:#263548;
        '>

            <div style='
                padding-bottom:18px;
                border-bottom:2px solid #1e5aa0;
            '>
                <h2 style='
                    margin:0;
                    color:#1e5aa0;
                '>
                    Recuperación administrativa
                </h2>
            </div>

            <p style='margin-top:24px;'>
                Hola <strong>{$nombreSeguro}</strong>:
            </p>

            <p>
                Recibimos una solicitud para recuperar
                su acceso administrativo a AlianzaPro.
            </p>

            <p>
                Ingrese el siguiente código:
            </p>

            <div style='
                margin:28px 0;
                padding:20px;
                border-radius:10px;
                background:#edf5ff;
                color:#1e5aa0;
                font-size:38px;
                font-weight:bold;
                letter-spacing:10px;
                text-align:center;
            '>
                {$codigo}
            </div>

            <p>
                El código expirará en
                <strong>10 minutos</strong>.
            </p>

            <p style='
                margin-top:25px;
                padding-top:18px;
                border-top:1px solid #dddddd;
                color:#6d7785;
                font-size:13px;
            '>
                Si no realizó esta solicitud,
                informe al administrador del sistema.
            </p>

        </div>
    ";

    /* =====================================================
       ENVIAR CORREO
    ===================================================== */

    $correoEnviado =
        enviarCorreo(
            $correo,
            $asunto,
            $mensaje
        );

    if (!$correoEnviado) {

        $stmtAnular = $conexion->prepare(
            "UPDATE codigos_verificacion
             SET usado = 1
             WHERE id_codigo = ?"
        );

        $stmtAnular->bind_param(
            "i",
            $idCodigo
        );

        $stmtAnular->execute();
        $stmtAnular->close();

        error_log(
            "No fue posible enviar código administrativo al usuario ID: " .
            $usuario["id_usuario"]
        );
    }

    responderGenerico();

} catch (Throwable $error) {

    error_log(
        "Error enviando código administrativo: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {
        http_response_code(500);

        $mensaje =
            "No fue posible procesar la solicitud.";

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

function responderGenerico(): void
{
    echo json_encode([
        "ok" => true,

        "mensaje" =>
            "Si el correo pertenece a un usuario activo, recibirá un código de recuperación."
    ]);

    exit;
}

?>