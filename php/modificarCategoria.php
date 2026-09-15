<?php

header("Content-Type: application/json; charset=utf-8");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

function generarSlugCategoriaModificar(
    string $texto
): string {

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

    $idCategoria = filter_var(
        $_POST["id_categoria"] ??
        $_POST["idCategoria"] ??
        null,
        FILTER_VALIDATE_INT
    );

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

    if (!$idCategoria || $idCategoria <= 0) {
        throw new Exception(
            "El ID de la categoría no es válido."
        );
    }

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

    $slug = generarSlugCategoriaModificar(
        $slugRecibido !== ""
            ? $slugRecibido
            : $categoria
    );

    if ($slug === "") {
        throw new Exception(
            "El slug no es válido."
        );
    }

    if (strlen($slug) > 120) {
        throw new Exception(
            "El slug no puede superar los 120 caracteres."
        );
    }

    $conexion = conexion();
    $conexion->begin_transaction();

    /* Obtener nombre anterior */

    $stmtAnterior = $conexion->prepare(
        "SELECT categoria
         FROM categorias
         WHERE id_categoria = ?
         LIMIT 1"
    );

    if (!$stmtAnterior) {
        throw new Exception($conexion->error);
    }

    $stmtAnterior->bind_param(
        "i",
        $idCategoria
    );

    $stmtAnterior->execute();

    $resultadoAnterior =
        $stmtAnterior->get_result();

    if ($resultadoAnterior->num_rows === 0) {
        throw new Exception(
            "La categoría no existe."
        );
    }

    $categoriaAnterior =
        $resultadoAnterior->fetch_assoc()["categoria"];

    $stmtAnterior->close();

    /* Validar duplicados */

    $stmtDuplicado = $conexion->prepare(
        "SELECT id_categoria
         FROM categorias
         WHERE (
                LOWER(TRIM(categoria)) =
                LOWER(TRIM(?))
                OR slug = ?
               )
           AND id_categoria <> ?
         LIMIT 1"
    );

    if (!$stmtDuplicado) {
        throw new Exception($conexion->error);
    }

    $stmtDuplicado->bind_param(
        "ssi",
        $categoria,
        $slug,
        $idCategoria
    );

    $stmtDuplicado->execute();

    if ($stmtDuplicado->get_result()->num_rows > 0) {
        throw new Exception(
            "Ya existe otra categoría con ese nombre o slug."
        );
    }

    $stmtDuplicado->close();

    /* Actualizar categoría */

    $stmt = $conexion->prepare(
        "UPDATE categorias
         SET
            categoria = ?,
            descripcion = ?,
            slug = ?,
            visible_tienda = ?,
            orden = ?
         WHERE id_categoria = ?"
    );

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "sssiii",
        $categoria,
        $descripcion,
        $slug,
        $visibleTienda,
        $orden,
        $idCategoria
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $stmt->close();

    /* Actualizar productos relacionados */

    if (
        mb_strtolower(trim($categoriaAnterior)) !==
        mb_strtolower(trim($categoria))
    ) {

        $stmtProductos = $conexion->prepare(
            "UPDATE productos
             SET categoria = ?
             WHERE LOWER(TRIM(categoria)) =
                   LOWER(TRIM(?))"
        );

        if (!$stmtProductos) {
            throw new Exception($conexion->error);
        }

        $stmtProductos->bind_param(
            "ss",
            $categoria,
            $categoriaAnterior
        );

        if (!$stmtProductos->execute()) {
            throw new Exception($stmtProductos->error);
        }

        $stmtProductos->close();
    }

    $conexion->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Categoría actualizada correctamente.",
        "slug" => $slug
    ]);

} catch (Throwable $error) {

    if ($conexion instanceof mysqli) {
        $conexion->rollback();
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