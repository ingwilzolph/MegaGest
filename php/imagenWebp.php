<?php

/**
 * AlianzaPro / MegaGest
 * Procesador universal de imágenes.
 *
 * Prioridad:
 * 1. ImageMagick CLI
 * 2. Imagick PHP si estuviera disponible
 * 3. GD como último respaldo
 *
 * El resultado final siempre es WebP.
 */


/* =========================================================
   FORMATOS PERMITIDOS
   ========================================================= */

function formatosImagenPermitidos(): array
{
    return [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/bmp',
        'image/x-ms-bmp',
        'image/tiff',
        'image/avif',
        'image/heic',
        'image/heif'
    ];
}


/* =========================================================
   MIME REAL
   ========================================================= */

function mimeImagenReal(string $archivo): string
{
    if (!is_file($archivo)) {
        throw new Exception(
            'El archivo de imagen no existe.'
        );
    }

    if (!class_exists('finfo')) {
        throw new Exception(
            'PHP no tiene disponible Fileinfo.'
        );
    }

    $finfo = new finfo(
        FILEINFO_MIME_TYPE
    );

    $mime = $finfo->file(
        $archivo
    );

    if (!$mime) {
        throw new Exception(
            'No fue posible identificar el formato de la imagen.'
        );
    }

    return strtolower(
        trim($mime)
    );
}


/* =========================================================
   VALIDAR SUBIDA
   ========================================================= */

function validarImagenSubida(
    string $archivo,
    int $tamanoBytes,
    int $maxMB = 20
): string {

    if (!is_file($archivo)) {
        throw new Exception(
            'No se recibió una imagen válida.'
        );
    }

    if ($tamanoBytes <= 0) {
        throw new Exception(
            'La imagen está vacía.'
        );
    }

    $maxBytes =
        $maxMB *
        1024 *
        1024;

    if ($tamanoBytes > $maxBytes) {
        throw new Exception(
            "La imagen no puede superar {$maxMB} MB."
        );
    }

    $mime =
        mimeImagenReal(
            $archivo
        );

    if (!in_array(
        $mime,
        formatosImagenPermitidos(),
        true
    )) {

        throw new Exception(
            'Formato no permitido: ' .
            $mime
        );
    }

    return $mime;
}


/* =========================================================
   BUSCAR IMAGEMAGICK
   ========================================================= */

function buscarImageMagickCLI(): ?string
{
    $rutas = [
        '/usr/local/bin/magick',
        '/opt/homebrew/bin/magick',
        '/usr/bin/magick'
    ];

    foreach ($rutas as $ruta) {

        if (
            is_file($ruta) &&
            is_executable($ruta)
        ) {
            return $ruta;
        }
    }

    if (
        function_exists('shell_exec')
    ) {

        $ruta = trim(
            (string) @shell_exec(
                'command -v magick 2>/dev/null'
            )
        );

        if (
            $ruta !== '' &&
            is_executable($ruta)
        ) {
            return $ruta;
        }
    }

    return null;
}


/* =========================================================
   IMAGEMAGICK CLI
   ========================================================= */

function convertirConImageMagick(
    string $binario,
    string $origen,
    string $destino,
    int $anchoMax,
    int $altoMax,
    int $calidad
): void {

    if (!function_exists('exec')) {
        throw new Exception(
            'PHP tiene deshabilitada la función exec().'
        );
    }

    /*
     * [0] toma el primer frame.
     * Esto funciona para:
     * GIF animado
     * HEIC multipágina
     * TIFF multipágina
     */
    $entrada =
        escapeshellarg(
            $origen . '[0]'
        );

    $salida =
        escapeshellarg(
            $destino
        );

    $tamano =
        escapeshellarg(
            $anchoMax .
            'x' .
            $altoMax .
            '>'
        );

    $comando =
        escapeshellarg($binario) .
        ' ' .
        $entrada .
        ' ' .
        '-auto-orient ' .
        '-strip ' .
        '-resize ' .
        $tamano .
        ' ' .
        '-quality ' .
        intval($calidad) .
        ' ' .
        '-define webp:method=6 ' .
        '-define webp:thread-level=1 ' .
        'webp:' .
        $salida .
        ' 2>&1';

    $salidaComando = [];
    $codigo = 0;

    exec(
        $comando,
        $salidaComando,
        $codigo
    );

    if (
        $codigo !== 0 ||
        !is_file($destino) ||
        filesize($destino) <= 0
    ) {

        throw new Exception(
            'ImageMagick no pudo convertir la imagen. ' .
            implode(
                ' ',
                $salidaComando
            )
        );
    }
}


/* =========================================================
   IMAGICK PHP
   ========================================================= */

function convertirConImagickPHP(
    string $origen,
    string $destino,
    int $anchoMax,
    int $altoMax,
    int $calidad
): void {

    $imagen =
        new Imagick();

    try {

        $imagen->readImage(
            $origen
        );

        if (
            $imagen->getNumberImages() > 1
        ) {

            $imagen->setIteratorIndex(0);

            $frame =
                $imagen->getImage();

            $imagen->clear();
            $imagen->destroy();

            $imagen = $frame;
        }

        if (
            method_exists(
                $imagen,
                'autoOrient'
            )
        ) {
            $imagen->autoOrient();
        }

        $ancho =
            $imagen->getImageWidth();

        $alto =
            $imagen->getImageHeight();

        if (
            $ancho > $anchoMax ||
            $alto > $altoMax
        ) {

            $imagen->thumbnailImage(
                $anchoMax,
                $altoMax,
                true,
                true
            );
        }

        $imagen->stripImage();

        $imagen->setImageFormat(
            'webp'
        );

        $imagen->setImageCompressionQuality(
            $calidad
        );

        $imagen->setOption(
            'webp:method',
            '6'
        );

        if (
            !$imagen->writeImage(
                $destino
            )
        ) {
            throw new Exception(
                'Imagick no pudo escribir WebP.'
            );
        }

    } finally {

        try {
            $imagen->clear();
            $imagen->destroy();
        } catch (Throwable $e) {
        }
    }
}


/* =========================================================
   GD
   ========================================================= */

function convertirConGD(
    string $origen,
    string $destino,
    int $anchoMax,
    int $altoMax,
    int $calidad
): void {

    if (
        !extension_loaded('gd') ||
        !function_exists('imagewebp')
    ) {
        throw new Exception(
            'GD no tiene soporte WebP.'
        );
    }

    $mime =
        mimeImagenReal(
            $origen
        );

    switch ($mime) {

        case 'image/jpeg':
        case 'image/pjpeg':
            $imagen =
                imagecreatefromjpeg(
                    $origen
                );
            break;

        case 'image/png':
            $imagen =
                imagecreatefrompng(
                    $origen
                );
            break;

        case 'image/webp':
            $imagen =
                imagecreatefromwebp(
                    $origen
                );
            break;

        case 'image/gif':
            $imagen =
                imagecreatefromgif(
                    $origen
                );
            break;

        case 'image/bmp':
        case 'image/x-ms-bmp':

            if (!function_exists(
                'imagecreatefrombmp'
            )) {
                throw new Exception(
                    'GD no soporta BMP.'
                );
            }

            $imagen =
                imagecreatefrombmp(
                    $origen
                );
            break;

        case 'image/avif':

            if (!function_exists(
                'imagecreatefromavif'
            )) {
                throw new Exception(
                    'GD no soporta AVIF.'
                );
            }

            $imagen =
                imagecreatefromavif(
                    $origen
                );
            break;

        default:

            throw new Exception(
                'Este formato requiere ImageMagick: ' .
                $mime
            );
    }

    if (!$imagen) {
        throw new Exception(
            'GD no pudo abrir la imagen.'
        );
    }

    $anchoOriginal =
        imagesx($imagen);

    $altoOriginal =
        imagesy($imagen);

    $factor = min(
        $anchoMax / $anchoOriginal,
        $altoMax / $altoOriginal,
        1
    );

    $nuevoAncho = max(
        1,
        (int) round(
            $anchoOriginal *
            $factor
        )
    );

    $nuevoAlto = max(
        1,
        (int) round(
            $altoOriginal *
            $factor
        )
    );

    $destinoImagen =
        imagecreatetruecolor(
            $nuevoAncho,
            $nuevoAlto
        );

    imagealphablending(
        $destinoImagen,
        false
    );

    imagesavealpha(
        $destinoImagen,
        true
    );

    $transparente =
        imagecolorallocatealpha(
            $destinoImagen,
            0,
            0,
            0,
            127
        );

    imagefill(
        $destinoImagen,
        0,
        0,
        $transparente
    );

    imagecopyresampled(
        $destinoImagen,
        $imagen,
        0,
        0,
        0,
        0,
        $nuevoAncho,
        $nuevoAlto,
        $anchoOriginal,
        $altoOriginal
    );

    $ok =
        imagewebp(
            $destinoImagen,
            $destino,
            $calidad
        );

    imagedestroy(
        $imagen
    );

    imagedestroy(
        $destinoImagen
    );

    if (!$ok) {
        throw new Exception(
            'GD no pudo guardar WebP.'
        );
    }
}


/* =========================================================
   FUNCIÓN PRINCIPAL
   ========================================================= */

function guardarComoWebP(
    string $origen,
    string $destino,
    int $anchoMax = 1600,
    int $altoMax = 1600,
    int $calidad = 80
): void {

    if (!is_file($origen)) {
        throw new Exception(
            'La imagen de origen no existe.'
        );
    }

    $calidad = max(
        1,
        min(
            100,
            $calidad
        )
    );

    $directorio =
        dirname(
            $destino
        );

    if (!is_dir($directorio)) {

        if (!mkdir(
            $directorio,
            0755,
            true
        )) {
            throw new Exception(
                'No se pudo crear la carpeta de destino.'
            );
        }
    }

    /*
     * 1. ImageMagick CLI
     */
    $magick =
        buscarImageMagickCLI();

    if ($magick !== null) {

        try {

            convertirConImageMagick(
                $magick,
                $origen,
                $destino,
                $anchoMax,
                $altoMax,
                $calidad
            );

            return;

        } catch (Throwable $e) {

            $errorMagick =
                $e->getMessage();
        }
    }


    /*
     * 2. Imagick PHP
     */
    if (
        extension_loaded('imagick') &&
        class_exists('Imagick')
    ) {

        try {

            convertirConImagickPHP(
                $origen,
                $destino,
                $anchoMax,
                $altoMax,
                $calidad
            );

            return;

        } catch (Throwable $e) {

            $errorImagick =
                $e->getMessage();
        }
    }


    /*
     * 3. GD
     */
    try {

        convertirConGD(
            $origen,
            $destino,
            $anchoMax,
            $altoMax,
            $calidad
        );

        return;

    } catch (Throwable $e) {

        $mensaje =
            'No fue posible convertir la imagen a WebP. ';

        if (isset($errorMagick)) {
            $mensaje .=
                'ImageMagick: ' .
                $errorMagick .
                ' ';
        }

        if (isset($errorImagick)) {
            $mensaje .=
                'Imagick PHP: ' .
                $errorImagick .
                ' ';
        }

        $mensaje .=
            'GD: ' .
            $e->getMessage();

        throw new Exception(
            $mensaje
        );
    }
}
