<?php

function autorizarRoles(array $rolesPermitidos): void
{
    $rolUsuario = strtolower(
        trim($_SESSION["rol"] ?? "")
    );

    $rolesPermitidos = array_map(
        static function ($rol) {
            return strtolower(trim($rol));
        },
        $rolesPermitidos
    );

    /*
     El administrador puede realizar cualquier
     operación lógica de los demás roles.
    */

    if ($rolUsuario === "administrador") {
        return;
    }

    if (!in_array(
        $rolUsuario,
        $rolesPermitidos,
        true
    )) {

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
}

?>