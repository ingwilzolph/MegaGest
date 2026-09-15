<?php

header("Content-Type: application/json; charset=utf-8");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

function generarSlugCategoria(string $texto): string
{
    $texto = mb_strtolower(
        trim($texto),
        "UTF-8"
    );

    $convertido = iconv(
        "UTF-8",
        "ASCII//TRANSLIT//IGNORE",
        $texto
    );

    if ($convertido !== false) {
        $texto = $convertido;
    }

    $texto = preg_replace(
        "/[^a-z0-9]+/",
        "-",
        $texto
    );

    return trim($texto, "-");
}

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido.");
    }

    $categoria = trim(
        $_POST["categoria"] ?? ""
    );

    $descripcion = trim(
        $_POST["descripcion"] ?? ""
    );

    $slugRecibido = trim(
        $_POST["slug"] ?? ""
    );

    $orden = filter_var(
        $_POST["orden"] ?? 0,
        FILTER_VALIDATE_INT
    );

    $visibleTienda =
        isset($_POST["visible_tienda"]) ? 1 : 0;

    if (
        mb_strlen($categoria) < 3 ||
        mb_strlen($categoria) > 100
    ) {
        throw new Exception(
            "El nombre debe tener entre 3 y 100 caracteres."
        );
    }

    if (mb_strlen($descripcion) > 300) {
        throw new Exception(
            "La descripción no puede superar los 300 caracteres."
        );
    }

    if ($orden === false || $orden < 0) {
        throw new Exception(
            "El orden no es válido."
        );
    }

    $slug = generarSlugCategoria(
        $slugRecibido !== ""
            ? $slugRecibido
            : $categoria
    );

    if ($slug === "") {
        throw new Exception(
            "No fue posible generar el slug."
        );
    }

    if (strlen($slug) > 120) {
        throw new Exception(
            "El slug no puede superar los 120 caracteres."
        );
    }

    $conexion = conexion();

    /* Validar nombre y slug */

    $stmtExiste = $conexion->prepare(
        "SELECT id_categoria
         FROM categorias
         WHERE LOWER(TRIM(categoria)) =
               LOWER(TRIM(?))
            OR slug = ?
         LIMIT 1"
    );

    if (!$stmtExiste) {
        throw new Exception($conexion->error);
    }

    $stmtExiste->bind_param(
        "ss",
        $categoria,
        $slug
    );

    $stmtExiste->execute();

    if ($stmtExiste->get_result()->num_rows > 0) {
        throw new Exception(
            "Ya existe una categoría con ese nombre o slug."
        );
    }

    $stmtExiste->close();

    /* Insertar */

    $stmt = $conexion->prepare(
        "INSERT INTO categorias (
            categoria,
            descripcion,
            slug,
            visible_tienda,
            orden
         )
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "sssii",
        $categoria,
        $descripcion,
        $slug,
        $visibleTienda,
        $orden
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode([
        "ok" => true,
        "mensaje" => "Categoría registrada correctamente.",
        "id_categoria" => $conexion->insert_id,
        "slug" => $slug
    ]);

    $stmt->close();

} catch (Throwable $error) {

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