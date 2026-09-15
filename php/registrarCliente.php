<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";
require_once "sesionCliente.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}

$conexion = conexion();

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

function responderError(
    string $mensaje,
    int $codigoHttp = 400
): void {

    http_response_code($codigoHttp);

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ]);

    exit;
}

function validarRut(string $rut): bool
{
    $rut = strtoupper(
        preg_replace(
            "/[^0-9K]/",
            "",
            $rut
        )
    );

    if (strlen($rut) < 2) {
        return false;
    }

    $cuerpo = substr($rut, 0, -1);
    $digitoRecibido = substr($rut, -1);

    $suma = 0;
    $multiplicador = 2;

    for (
        $i = strlen($cuerpo) - 1;
        $i >= 0;
        $i--
    ) {
        $suma +=
            ((int) $cuerpo[$i]) *
            $multiplicador;

        $multiplicador++;

        if ($multiplicador > 7) {
            $multiplicador = 2;
        }
    }

    $resultado = 11 - ($suma % 11);

    if ($resultado === 11) {
        $digitoCalculado = "0";
    } elseif ($resultado === 10) {
        $digitoCalculado = "K";
    } else {
        $digitoCalculado =
            (string) $resultado;
    }

    return $digitoCalculado ===
        $digitoRecibido;
}

function formatearRut(string $rut): string
{
    $rut = strtoupper(
        preg_replace(
            "/[^0-9K]/",
            "",
            $rut
        )
    );

    $cuerpo = substr($rut, 0, -1);
    $digito = substr($rut, -1);

    return $cuerpo . "-" . $digito;
}

/*
|--------------------------------------------------------------------------
| Recibir datos
|--------------------------------------------------------------------------
*/

$rut = trim($_POST["rut"] ?? "");
$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$correo = strtolower(
    trim($_POST["correo"] ?? "")
);
$telefono = preg_replace(
    "/\D/",
    "",
    $_POST["telefono"] ?? ""
);

$password = $_POST["password"] ?? "";
$confirmarPassword =
    $_POST["confirmarPassword"] ?? "";

$region = trim($_POST["region"] ?? "");
$comuna = trim($_POST["comuna"] ?? "");
$calle = trim($_POST["calle"] ?? "");
$numero = trim($_POST["numero"] ?? "");
$departamento = trim(
    $_POST["departamento"] ?? ""
);
$referencia = trim(
    $_POST["referencia"] ?? ""
);

/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
*/

if (
    $rut === "" ||
    $nombre === "" ||
    $apellido === "" ||
    $correo === "" ||
    $telefono === "" ||
    $password === "" ||
    $confirmarPassword === ""
) {
    responderError(
        "Complete todos los datos obligatorios."
    );
}

if (!validarRut($rut)) {
    responderError(
        "El RUT ingresado no es válido."
    );
}

$rut = formatearRut($rut);

if (
    mb_strlen($nombre) < 2 ||
    mb_strlen($nombre) > 80
) {
    responderError(
        "El nombre debe contener entre 2 y 80 caracteres."
    );
}

if (
    mb_strlen($apellido) < 2 ||
    mb_strlen($apellido) > 80
) {
    responderError(
        "El apellido debe contener entre 2 y 80 caracteres."
    );
}

if (
    !filter_var(
        $correo,
        FILTER_VALIDATE_EMAIL
    )
) {
    responderError(
        "El correo electrónico no es válido."
    );
}

if (!preg_match("/^9\d{8}$/", $telefono)) {
    responderError(
        "El teléfono debe contener 9 dígitos y comenzar con 9."
    );
}

if (strlen($password) < 8) {
    responderError(
        "La contraseña debe tener al menos 8 caracteres."
    );
}

if (
    !preg_match("/[A-Z]/", $password) ||
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password)
) {
    responderError(
        "La contraseña debe incluir mayúscula, minúscula y número."
    );
}

if ($password !== $confirmarPassword) {
    responderError(
        "Las contraseñas no coinciden."
    );
}

/*
|--------------------------------------------------------------------------
| Comprobar si ya existe
|--------------------------------------------------------------------------
*/

try {

    $stmtExiste = $conexion->prepare(
        "SELECT
            id_cliente,
            correo,
            rut,
            password_hash
         FROM clientes
         WHERE correo = ?
            OR rut = ?
         LIMIT 1"
    );

    $stmtExiste->bind_param(
        "ss",
        $correo,
        $rut
    );

    $stmtExiste->execute();

    $clienteExistente =
        $stmtExiste
            ->get_result()
            ->fetch_assoc();

    $stmtExiste->close();

    if ($clienteExistente) {

        if (
            empty(
                $clienteExistente[
                    "password_hash"
                ]
            )
        ) {
            responderError(
                "Este cliente ya está registrado en el sistema. Debe activar su cuenta mediante la opción Recuperar contraseña.",
                409
            );
        }

        responderError(
            "Ya existe una cuenta asociada a ese correo o RUT.",
            409
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar cliente y dirección
    |--------------------------------------------------------------------------
    */

    $conexion->begin_transaction();

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {
        throw new Exception(
            "No fue posible proteger la contraseña."
        );
    }

    $stmtCliente = $conexion->prepare(
        "INSERT INTO clientes (
            rut,
            nombre,
            apellido,
            correo,
            telefono,
            password_hash,
            estado,
            correo_verificado
         ) VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'activo',
            0
         )"
    );

    $stmtCliente->bind_param(
        "ssssss",
        $rut,
        $nombre,
        $apellido,
        $correo,
        $telefono,
        $passwordHash
    );

    $stmtCliente->execute();

    $idCliente =
        $conexion->insert_id;

    $stmtCliente->close();

    $nombreDireccion =
        "Dirección principal";

    $esPrincipal = 1;
    $activa = 1;

    $tieneDireccion =
    $region !== "" ||
    $comuna !== "" ||
    $calle !== "" ||
    $numero !== "";

if ($tieneDireccion) {

    if (
        $region === "" ||
        $comuna === "" ||
        $calle === "" ||
        $numero === ""
    ) {
        throw new Exception(
            "Complete la dirección de despacho."
        );
    }

    $nombreDireccion =
        "Dirección principal";

    $esPrincipal = 1;
    $activa = 1;

    $stmtDireccion = $conexion->prepare(
            "INSERT INTO direcciones_cliente (
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
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NULLIF(?, ''),
                NULLIF(?, ''),
                ?,
                ?
            )"
        );

        $stmtDireccion->bind_param(
            "isssssssii",
            $idCliente,
            $nombreDireccion,
            $region,
            $comuna,
            $calle,
            $numero,
            $departamento,
            $referencia,
            $esPrincipal,
            $activa
        );

        $stmtDireccion->execute();
        $stmtDireccion->close();
    }

    $conexion->commit();

        guardarSesionCliente([
        "id_cliente" => $idCliente,
        "nombre" => $nombre,
        "apellido" => $apellido,
        "correo" => $correo
    ]);

    $stmtAcceso = $conexion->prepare(
        "UPDATE clientes
        SET ultimo_acceso = NOW()
        WHERE id_cliente = ?"
    );

    $stmtAcceso->bind_param(
        "i",
        $idCliente
    );

    $stmtAcceso->execute();
    $stmtAcceso->close();

    echo json_encode([
        "ok" => true,

        "autenticado" => true,

        "mensaje" =>
            "Cuenta creada correctamente.",

        "cliente" => [
            "id_cliente" => $idCliente,
            "rut" => $rut,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "correo" => $correo,
            "telefono" => $telefono
        ]
    ]);

} catch (mysqli_sql_exception $error) {

    if ($conexion->errno === 0) {
        // La conexión continúa disponible.
    }

    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {
    }

    if ((int) $error->getCode() === 1062) {

        responderError(
            "El correo o RUT ya está registrado.",
            409
        );
    }

    error_log(
        "Error registrando cliente: " .
        $error->getMessage()
    );

    responderError(
        "No fue posible crear la cuenta. Intente nuevamente.",
        500
    );

} catch (Throwable $error) {

    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {
    }

    error_log(
        "Error registrando cliente: " .
        $error->getMessage()
    );

    responderError(
        "No fue posible crear la cuenta. Intente nuevamente.",
        500
    );

} finally {

    $conexion->close();
}
?>