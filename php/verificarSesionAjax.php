<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once __DIR__ ."/verificarHorarioUsuarios.php";

$tiempoMaximo = 1800; // 30 minutos

function cerrarSesionAjax(string $mensaje, string $motivo): void {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $parametros = session_get_cookie_params();

        setcookie(session_name(), "", time() - 42000, $parametros["path"], $parametros["domain"], $parametros["secure"], $parametros["httponly"]);
    }

    session_destroy();

    echo json_encode(
        [
            "ok" => false,
            "sesionExpirada" => true,
            "motivo" => $motivo,
            "mensaje" => $mensaje
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/* =========================================
   VALIDAR SESIÓN
========================================= */

if (
    !isset($_SESSION["login"]) ||
    $_SESSION["login"] !== true
) {

    cerrarSesionAjax(
        "Debe iniciar sesión nuevamente.",
        "sin_sesion"
    );
}

/* =========================================
   VALIDAR INACTIVIDAD
========================================= */

if (
    !isset($_SESSION["ultimo_acceso"]) ||
    (time() - $_SESSION["ultimo_acceso"]) >
        $tiempoMaximo
) {

    cerrarSesionAjax(
        "La sesión ha expirado por inactividad.",
        "inactividad"
    );
}

/* =========================================
   VALIDAR HORARIO
========================================= */

$rolSesion = $_SESSION["rol"] ?? "";

if (!usuarioDentroHorario($rolSesion)) {

    cerrarSesionAjax(
        "El horario de acceso finalizó. El sistema estará disponible nuevamente a las 08:30 horas.",
        "fuera_horario"
    );
}

/* Renovar actividad */

$_SESSION["ultimo_acceso"] = time();

?>