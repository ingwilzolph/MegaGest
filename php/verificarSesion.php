<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tiempoMaximo = 1800; // 30 minutos

if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {

    header("Location: ../page-login.html");
    exit;
}

if (!isset($_SESSION["ultimo_acceso"])) {

    session_destroy();

    header("Location: ../page-login.html");
    exit;
}

if (time() - $_SESSION["ultimo_acceso"] > $tiempoMaximo) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: ../page-login.html?sesion=expirada");
    exit;
}

// Renovar el tiempo de actividad
$_SESSION["ultimo_acceso"] = time();