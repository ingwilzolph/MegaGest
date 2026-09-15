<?php

session_start();

header("Content-Type: image/png");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

/*
========================================
CONFIGURACIÓN
========================================
*/

$ancho = 180;
$alto = 60;

$fuente = __DIR__ . "/fonts/zxxnoise.otf";

$caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
$captcha = "";

/*
========================================
GENERAR CÓDIGO DE 6 CARACTERES
========================================
*/

for ($i = 0; $i < 6; $i++) {
    $captcha .= $caracteres[
        random_int(0, strlen($caracteres) - 1)
    ];
}

$_SESSION["gfm_captcha"] = $captcha;

/*
========================================
CREAR IMAGEN
========================================
*/

$imagen = imagecreatetruecolor($ancho, $alto);

imageantialias($imagen, true);

/*
========================================
COLORES
========================================
*/

$fondo = imagecolorallocate($imagen, 242, 247, 255);
$azul = imagecolorallocate($imagen, 24, 105, 232);
$azulOscuro = imagecolorallocate($imagen, 18, 43, 78);
$naranja = imagecolorallocate($imagen, 245, 139, 48);
$linea = imagecolorallocate($imagen, 177, 199, 228);

imagefilledrectangle(
    $imagen,
    0,
    0,
    $ancho,
    $alto,
    $fondo
);

/*
========================================
LÍNEAS DECORATIVAS
========================================
*/

for ($i = 0; $i < 5; $i++) {
    imageline(
        $imagen,
        random_int(0, $ancho),
        random_int(5, $alto - 5),
        random_int(0, $ancho),
        random_int(5, $alto - 5),
        $i % 2 === 0 ? $linea : $naranja
    );
}

/*
========================================
PUNTOS DE SEGURIDAD
========================================
*/

for ($i = 0; $i < 65; $i++) {
    imagesetpixel(
        $imagen,
        random_int(2, $ancho - 3),
        random_int(2, $alto - 3),
        $linea
    );
}

/*
========================================
DIBUJAR CARACTERES
========================================
*/

if (file_exists($fuente)) {

    $posicionX = 16;

    for ($i = 0; $i < strlen($captcha); $i++) {

        $colorTexto = $i % 2 === 0
            ? $azulOscuro
            : $azul;

        imagettftext(
            $imagen,
            24,
            random_int(-8, 8),
            $posicionX,
            random_int(40, 47),
            $colorTexto,
            $fuente,
            $captcha[$i]
        );

        $posicionX += 25;
    }

} else {

    /*
    Texto alternativo si no se encuentra la fuente.
    */

    imagestring(
        $imagen,
        5,
        32,
        22,
        $captcha,
        $azulOscuro
    );
}

/*
========================================
BORDE
========================================
*/

imagerectangle(
    $imagen,
    0,
    0,
    $ancho - 1,
    $alto - 1,
    $linea
);

/*
========================================
MOSTRAR Y LIBERAR
========================================
*/

imagepng($imagen);
imagedestroy($imagen);