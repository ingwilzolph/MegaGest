<?php

date_default_timezone_set("America/Santiago");

/**
 * Indica si el usuario puede utilizar el sistema
 * según su rol y la hora actual.
 */
function usuarioDentroHorario(string $rol): bool
{
    $rolNormalizado = mb_strtolower(trim($rol), "UTF-8");

    /*
     Los administradores no tienen restricción horaria.
    */

    if ($rolNormalizado === "administrador") {
        return true;
    }

    $horaActual = new DateTimeImmutable(
        "now",
        new DateTimeZone("America/Santiago")
    );

    $minutosActuales = ((int)$horaActual->format("H") * 60) + (int)$horaActual->format("i");

    $horaApertura = (1 * 60); // 08: 45
    $horaCierre = 24 * 60;         // 20:00

    return ($minutosActuales >= $horaApertura && $minutosActuales < $horaCierre);
}

?>