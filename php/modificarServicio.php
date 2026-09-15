<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";
require_once "imagenWebp.php";

error_reporting(E_ALL);
ini_set("display_errors", "0");

$conexion = null;
$archivosTemporales = [];

function crearRecursoImagenServicioModificar(
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

function guardarImagenTemporalServicio(
    string $archivo,
    string $ruta,
    int $anchoMaximo,
    int $altoMaximo
): void {

    guardarComoWebP(
        $archivo,
        $ruta,
        $anchoMaximo,
        $altoMaximo,
        80
    );
}

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido.");
    }

    $conexion = conexion();
    $conexion->begin_transaction();

    /* =========================================
       RECIBIR INFORMACIÓN
    ========================================= */

    $idServicio = filter_var(
        $_POST["id_servicio"] ?? null,
        FILTER_VALIDATE_INT
    );

    $nombre = trim(
        $_POST["nombre"] ?? ""
    );

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

    /* =========================================
       VALIDACIONES
    ========================================= */

    if (!$idServicio || $idServicio <= 0) {
        throw new Exception(
            "El ID del servicio no es válido."
        );
    }

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
            "La duración no es válida."
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
            "El estado no es válido."
        );
    }

    /* =========================================
       COMPROBAR SERVICIO
    ========================================= */

    $stmtExiste = $conexion->prepare(
        "SELECT id_servicio
         FROM servicios
         WHERE id_servicio = ?
         LIMIT 1"
    );

    $stmtExiste->bind_param(
        "i",
        $idServicio
    );

    $stmtExiste->execute();

    if ($stmtExiste->get_result()->num_rows === 0) {
        throw new Exception(
            "El servicio no existe."
        );
    }

    $stmtExiste->close();

    /* =========================================
       VALIDAR NOMBRE DUPLICADO
    ========================================= */

    $stmtDuplicado = $conexion->prepare(
        "SELECT id_servicio
         FROM servicios
         WHERE nombre = ?
           AND id_servicio <> ?
         LIMIT 1"
    );

    $stmtDuplicado->bind_param(
        "si",
        $nombre,
        $idServicio
    );

    $stmtDuplicado->execute();

    if (
        $stmtDuplicado->get_result()->num_rows > 0
    ) {
        throw new Exception(
            "Ya existe otro servicio con ese nombre."
        );
    }

    $stmtDuplicado->close();

    /* =========================================
       PREPARAR IMAGEN NUEVA
    ========================================= */

    $cambiarImagen = (
        isset($_FILES["imagen"]) &&
        $_FILES["imagen"]["error"] === UPLOAD_ERR_OK
    );

    $rutaTemporalPrincipal = null;
    $rutaTemporalMiniatura = null;
    $rutaFinalPrincipal = null;
    $rutaFinalMiniatura = null;

    if ($cambiarImagen) {

        validarImagenSubida(
            $_FILES["imagen"]["tmp_name"],
            intval($_FILES["imagen"]["size"]),
            20
        );

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
                    "No fue posible crear las carpetas de imágenes."
                );
            }

            if (!is_writable($carpeta)) {
                throw new Exception(
                    "La carpeta no tiene permisos de escritura: " .
                    $carpeta
                );
            }
        }

        $codigoTemporal =
            bin2hex(random_bytes(6));

        $rutaTemporalPrincipal =
            $carpetaServicios .
            "/temp_" .
            $codigoTemporal .
            ".webp";

        $rutaTemporalMiniatura =
            $carpetaMiniaturas .
            "/temp_" .
            $codigoTemporal .
            ".webp";

        $archivosTemporales[] =
            $rutaTemporalPrincipal;

        $archivosTemporales[] =
            $rutaTemporalMiniatura;

        guardarImagenTemporalServicio(
            $_FILES["imagen"]["tmp_name"],
            $rutaTemporalPrincipal,
            1500,
            1300
        );

        guardarImagenTemporalServicio(
            $_FILES["imagen"]["tmp_name"],
            $rutaTemporalMiniatura,
            1000,
            800
        );

        $rutaFinalPrincipal =
            $carpetaServicios .
            "/" .
            $idServicio .
            ".webp";

        $rutaFinalMiniatura =
            $carpetaMiniaturas .
            "/" .
            $idServicio .
            ".webp";
    }

    /* =========================================
       ACTUALIZAR SERVICIO
    ========================================= */

    $stmt = $conexion->prepare(
        "UPDATE servicios
         SET
            nombre = ?,
            descripcion = ?,
            precio_min = ?,
            duracion_minutos = ?,
            visible_citas = ?,
            destacado = ?,
            estado = ?
         WHERE id_servicio = ?"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "ssiiiisi",
        $nombre,
        $descripcion,
        $precioMinimo,
        $duracion,
        $visibleCitas,
        $destacado,
        $estado,
        $idServicio
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    /* =========================================
       REEMPLAZAR IMÁGENES
    ========================================= */

    if ($cambiarImagen) {

        if (
            !rename(
                $rutaTemporalPrincipal,
                $rutaFinalPrincipal
            )
        ) {
            throw new Exception(
                "No fue posible actualizar la imagen principal."
            );
        }

        if (
            !rename(
                $rutaTemporalMiniatura,
                $rutaFinalMiniatura
            )
        ) {
            throw new Exception(
                "No fue posible actualizar la miniatura."
            );
        }
    }

    $conexion->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Servicio actualizado correctamente."
    ]);

} catch (Throwable $error) {

    if ($conexion instanceof mysqli) {
        $conexion->rollback();
    }

    foreach ($archivosTemporales as $archivo) {

        if (is_file($archivo)) {
            unlink($archivo);
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