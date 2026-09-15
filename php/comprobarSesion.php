<?php

require_once "verificarSesionAjax.php";

echo json_encode([
    "ok" => true,
    "mensaje" => "Sesión vigente."
]);

?>