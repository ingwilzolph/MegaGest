<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once "verificarSesionAjax.php";
require_once "conexion.php";
require_once "imagenWebp.php";

try {

    if (!isset($_POST["id_ot"])) {
        throw new Exception(
            "Falta el identificador de la orden de trabajo."
        );
    }

    $idOT = intval(
        $_POST["id_ot"]
    );

    if ($idOT <= 0) {
        throw new Exception(
            "Orden de trabajo no válida."
        );
    }

    if (
        !isset($_FILES["fotos"]) ||
        !isset($_FILES["fotos"]["tmp_name"])
    ) {
        throw new Exception(
            "No se recibieron fotografías."
        );
    }

    $carpetaFisica =
        dirname(__DIR__) .
        "/images/ordenesTrabajo/";

    if (!is_dir($carpetaFisica)) {

        if (
            !mkdir(
                $carpetaFisica,
                0755,
                true
            )
        ) {
            throw new Exception(
                "No fue posible crear la carpeta de fotografías."
            );
        }
    }

    $conexion = conexion();

    $stmt = $conexion->prepare(
        "INSERT INTO fotos_ot
        (id_ot, nombreArchivo)
        VALUES (?, ?)"
    );

    if (!$stmt) {
        throw new Exception(
            "Error preparando el registro de la fotografía."
        );
    }

    $fotosGuardadas = [];

    foreach (
        $_FILES["fotos"]["tmp_name"]
        as $i => $tmp
    ) {

        $error =
            $_FILES["fotos"]["error"][$i]
            ?? UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_OK) {
            continue;
        }

        $tamano =
            intval(
                $_FILES["fotos"]["size"][$i]
                ?? 0
            );

        validarImagenSubida(
            $tmp,
            $tamano,
            20
        );

        $nombre =
            "OT" .
            $idOT .
            "_" .
            bin2hex(
                random_bytes(8)
            ) .
            ".webp";

        $rutaRelativa =
            "images/ordenesTrabajo/" .
            $nombre;

        $rutaFisica =
            dirname(__DIR__) .
            "/" .
            $rutaRelativa;

        /*
         * Fotos OT:
         * máximo 1600 x 1600
         * calidad WebP 78
         */
        guardarComoWebP(
            $tmp,
            $rutaFisica,
            1600,
            1600,
            78
        );

        $stmt->bind_param(
            "is",
            $idOT,
            $rutaRelativa
        );

        if (!$stmt->execute()) {

            @unlink($rutaFisica);

            throw new Exception(
                "No fue posible registrar una fotografía."
            );
        }

        $fotosGuardadas[] =
            $rutaRelativa;
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,
        "fotos" => $fotosGuardadas
    ]);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ]);
}
