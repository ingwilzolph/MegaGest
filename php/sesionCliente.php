<?php

function iniciarSesionClienteApp(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $usaHttps =
        isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off";

    session_set_cookie_params([
        "lifetime" => 60 * 60 * 24 * 7,
        "path" => "/",
        "secure" => $usaHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

function guardarSesionCliente(
    array $cliente
): void {

    iniciarSesionClienteApp();

    session_regenerate_id(true);

    $_SESSION["cliente"] = [
        "id_cliente" =>
            intval($cliente["id_cliente"]),

        "nombre" =>
            $cliente["nombre"],

        "apellido" =>
            $cliente["apellido"],

        "correo" =>
            $cliente["correo"]
    ];

    $_SESSION["cliente_ultimo_acceso"] =
        time();
}

function obtenerIdClienteSesion(): int
{
    iniciarSesionClienteApp();

    return intval(
        $_SESSION["cliente"]["id_cliente"] ?? 0
    );
}

function exigirSesionCliente(): int
{
    $idCliente =
        obtenerIdClienteSesion();

    if ($idCliente <= 0) {

        http_response_code(401);

        echo json_encode([
            "ok" => false,
            "requiereAutenticacion" => true,
            "codigo" => "CLIENTE_NO_AUTENTICADO",
            "mensaje" =>
                "Debe iniciar sesión para continuar con la compra."
        ]);

        exit;
    }

    return $idCliente;
}

?>