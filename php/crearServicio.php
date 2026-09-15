<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";
require_once "imagenWebp.php";

error_reporting(E_ALL);
ini_set("display_errors", "0");

$conexion = null;
$rutasCreadas = [];

function crearImagenServicio(
    string $archivo,
    string $mime
) {

    switch ($mime) {

        case "image/jpeg":
            return imagecreatefromjpeg($archivo);

        case "image/png":
            return imagecreatefrompng($archivo);

        case "image/webp":

            if (!function_exists("imagecreatefromwebp")) {
                throw new Exception(
                    "WEBP no está soportado por esta instalación."
                );
            }

            return imagecreatefromwebp($archivo);

        case "image/gif":
            return imagecreatefromgif($archivo);

        case "image/bmp":
        case "image/x-ms-bmp":

            if (!function_exists("imagecreatefrombmp")) {
                throw new Exception(
                    "BMP no está soportado por esta instalación."
                );
            }

            return imagecreatefrombmp($archivo);

        default:
            throw new Exception(
                "El formato de imagen no está permitido."
            );
    }
}

function guardarImagenServicioAntigua(
    string $archivo,
    string $ruta,
    int $anchoMaximo,
    int $altoMaximo
): void {

    if (!extension_loaded("gd")) {
        throw new Exception("GD no está habilitado.");
    }

    $informacion = getimagesize($archivo);

    if ($informacion === false) {
        throw new Exception(
            "No fue posible leer la imagen."
        );
    }

    $origen = crearImagenServicio(
        $archivo,
        $informacion["mime"]
    );

    if (!$origen) {
        throw new Exception(
            "No fue posible procesar la imagen."
        );
    }

    $anchoOriginal = imagesx($origen);
    $altoOriginal = imagesy($origen);

    $factor = min(
        $anchoMaximo / $anchoOriginal,
        $altoMaximo / $altoOriginal,
        1
    );

    $nuevoAncho = max(
        1,
        (int)round($anchoOriginal * $factor)
    );

    $nuevoAlto = max(
        1,
        (int)round($altoOriginal * $factor)
    );

    $destino = imagecreatetruecolor(
        $nuevoAncho,
        $nuevoAlto
    );

    imagealphablending($destino, false);
    imagesavealpha($destino, true);

    $transparente = imagecolorallocatealpha(
        $destino,
        0,
        0,
        0,
        127
    );

    imagefill(
        $destino,
        0,
        0,
        $transparente
    );

    imagecopyresampled(
        $destino,
        $origen,
        0,
        0,
        0,
        0,
        $nuevoAncho,
        $nuevoAlto,
        $anchoOriginal,
        $altoOriginal
    );

    $guardado = imagepng(
        $destino,
        $ruta,
        6
    );

    imagedestroy($origen);
    imagedestroy($destino);

    if (!$guardado || !file_exists($ruta)) {
        throw new Exception(
            "No fue posible guardar la imagen."
        );
    }
}

try {

    $conexion = conexion();
    $conexion->begin_transaction();

    $nombre = trim($_POST["nombre"] ?? "");

    $descripcion = trim(
        $_POST["descripcion"] ?? ""
    );

    $precioMinimo = filter_var(
        $_POST["precio_min"] ?? null,
        FILTER_VALIDATE_INT
    );

    $duracion = filter_var(
        $_POST["duracion_minutos"] ?? null,
        FILTER_VALIDATE_INT
    );

    $estado = trim(
        $_POST["estado"] ?? ""
    );

    $visibleCitas =
        isset($_POST["visible_citas"]) ? 1 : 0;

    $destacado =
        isset($_POST["destacado"]) ? 1 : 0;

    if (
        $nombre === "" ||
        $descripcion === "" ||
        $estado === ""
    ) {
        throw new Exception(
            "Debe completar todos los campos obligatorios."
        );
    }

    if (
        mb_strlen($nombre) < 3 ||
        mb_strlen($nombre) > 100
    ) {
        throw new Exception(
            "El nombre debe tener entre 3 y 100 caracteres."
        );
    }

    if (mb_strlen($descripcion) < 10) {
        throw new Exception(
            "La descripción debe tener al menos 10 caracteres."
        );
    }

    if (
        $precioMinimo === false ||
        $precioMinimo <= 0
    ) {
        throw new Exception(
            "El precio mínimo no es válido."
        );
    }

    if (
        $duracion === false ||
        $duracion <= 0 ||
        $duracion > 1440
    ) {
        throw new Exception(
            "La duración del servicio no es válida."
        );
    }

    if (
        !in_array(
            $estado,
            ["Activo", "Inactivo"],
            true
        )
    ) {
        throw new Exception(
            "El estado del servicio no es válido."
        );
    }

    /* Validar servicio duplicado */

    $stmtDuplicado = $conexion->prepare(
        "SELECT id_servicio
         FROM servicios
         WHERE nombre = ?
         LIMIT 1"
    );

    $stmtDuplicado->bind_param(
        "s",
        $nombre
    );

    $stmtDuplicado->execute();

    if (
        $stmtDuplicado->get_result()->num_rows > 0
    ) {
        throw new Exception(
            "Ya existe un servicio con ese nombre."
        );
    }

    $stmtDuplicado->close();

    /* Validar imagen */

    if (
        !isset($_FILES["imagen"]) ||
        $_FILES["imagen"]["error"] !== UPLOAD_ERR_OK
    ) {
        throw new Exception(
            "Debe seleccionar una imagen."
        );
    }

    if ($_FILES["imagen"]["size"] > 5 * 1024 * 1024) {
        throw new Exception(
            "La imagen no puede superar los 5 MB."
        );
    }

    $tipoImagen = mime_content_type(
        $_FILES["imagen"]["tmp_name"]
    );

    $tiposPermitidos = [
        "image/jpeg",
        "image/png",
        "image/webp",
        "image/gif",
        "image/bmp",
        "image/x-ms-bmp"
    ];

    if (
        !in_array(
            $tipoImagen,
            $tiposPermitidos,
            true
        )
    ) {
        throw new Exception(
            "El formato de imagen no está permitido."
        );
    }

    /* Insertar servicio */

    $stmt = $conexion->prepare(
        "INSERT INTO servicios (
            nombre,
            descripcion,
            precio_min,
            duracion_minutos,
            visible_citas,
            destacado,
            estado
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "ssiiiis",
        $nombre,
        $descripcion,
        $precioMinimo,
        $duracion,
        $visibleCitas,
        $destacado,
        $estado
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $idServicio = $conexion->insert_id;

    /* Crear carpetas */

    $baseImagenes =
        dirname(__DIR__) . "/images";

    $carpetaServicios =
        $baseImagenes . "/servicios";

    $carpetaMiniaturas =
        $baseImagenes . "/thumbs";

    foreach (
        [
            $baseImagenes,
            $carpetaServicios,
            $carpetaMiniaturas
        ] as $carpeta
    ) {

        if (
            !is_dir($carpeta) &&
            !mkdir($carpeta, 0775, true)
        ) {
            throw new Exception(
                "No fue posible crear la carpeta de imágenes."
            );
        }
    }

    $rutaPrincipal =
        $carpetaServicios .
        "/" .
        $idServicio .
        ".webp";

    $rutaMiniatura =
        $carpetaMiniaturas .
        "/" .
        $idServicio .
        ".webp";

    guardarComoWebP($_FILES["imagen"]["tmp_name"], $rutaPrincipal, 1500, 1300, 80);

    $rutasCreadas[] = $rutaPrincipal;

    guardarComoWebP($_FILES["imagen"]["tmp_name"], $rutaMiniatura, 1000, 800, 80);

    $rutasCreadas[] = $rutaMiniatura;

    $conexion->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Servicio registrado correctamente.",
        "id_servicio" => $idServicio
    ]);

} catch (Throwable $error) {

    if ($conexion instanceof mysqli) {
        $conexion->rollback();
    }

    foreach ($rutasCreadas as $ruta) {

        if (is_file($ruta)) {
            unlink($ruta);
        }
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}
?>