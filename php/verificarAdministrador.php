<?php

require_once "verificarSesionAjax.php";

$rolSesion = strtolower(
    trim($_SESSION["rol"] ?? "")
);

if ($rolSesion !== "administrador") {

    http_response_code(403);

        echo json_encode([
            "ok" => false,
            "permisoDenegado" => true,
            "codigo" => "PERMISO_DENEGADO",
            "mensaje" =>
                "No tiene permisos para realizar esta acción."
        ]);

        exit;
}

?>