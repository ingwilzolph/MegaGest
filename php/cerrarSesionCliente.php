<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "sesionCliente.php";

iniciarSesionClienteApp();

$_SESSION = [];

if (ini_get("session.use_cookies")) {

    $parametros =
        session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

session_destroy();

echo json_encode([
    "ok" => true,
    "mensaje" =>
        "Sesión cerrada correctamente."
]);

?>