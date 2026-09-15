<?php
header('Content-Type: text/plain; charset=utf-8');

echo "PHP: " . PHP_VERSION . PHP_EOL;
echo "SAPI: " . PHP_SAPI . PHP_EOL;
echo "php.ini: " . (php_ini_loaded_file() ?: 'NINGUNO') . PHP_EOL;
echo "Imagick: " . (extension_loaded('imagick') ? 'SI' : 'NO') . PHP_EOL;
echo "GD: " . (extension_loaded('gd') ? 'SI' : 'NO') . PHP_EOL;
echo "imagewebp(): " . (function_exists('imagewebp') ? 'SI' : 'NO') . PHP_EOL;

if (class_exists('Imagick')) {
    echo "Clase Imagick: SI" . PHP_EOL;
    echo "Imagick version: " . phpversion('imagick') . PHP_EOL;
    echo "WEBP Imagick: " .
        (!empty(Imagick::queryFormats('WEBP')) ? 'SI' : 'NO') .
        PHP_EOL;
} else {
    echo "Clase Imagick: NO" . PHP_EOL;
}
