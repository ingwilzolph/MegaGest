<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";
require_once "imagenWebp.php";

error_reporting(E_ALL);
ini_set("display_errors", "0");

$conexion = null;
$archivosTemporales = [];

function convertirFechaModificar(?string $fecha): ?string
{
    $fecha = trim((string)$fecha);

    if ($fecha === "") {
        return null;
    }

    $fecha = str_replace("T", " ", $fecha);

    $fechaObjeto = DateTime::createFromFormat(
        "Y-m-d H:i",
        $fecha
    );

    if (!$fechaObjeto) {
        throw new Exception(
            "La fecha de oferta no es válida."
        );
    }

    return $fechaObjeto->format("Y-m-d H:i:s");
}

function crearRecursoImagenModificar(
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

function guardarImagenTemporalModificar(
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

    $origen = crearRecursoImagenModificar(
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
            "No fue posible preparar la nueva imagen."
        );
    }
}

try {

    $conexion = conexion();
    $conexion->begin_transaction();

    /* =========================================
       RECIBIR CAMPOS
    ========================================= */

    $idProducto = filter_var(
        $_POST["idProducto"] ?? null,
        FILTER_VALIDATE_INT
    );

    $categoria = trim($_POST["categoria"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $marca = trim($_POST["marca"] ?? "");

    $sku = strtoupper(
        trim($_POST["sku"] ?? "")
    );

    $cantidad = filter_var(
        $_POST["cantidad"] ?? null,
        FILTER_VALIDATE_INT
    );

    $stockMinimo = filter_var(
        $_POST["stock_minimo"] ?? null,
        FILTER_VALIDATE_INT
    );

    $compra = filter_var(
        $_POST["compra"] ?? null,
        FILTER_VALIDATE_INT
    );

    $precio = filter_var(
        $_POST["precio"] ?? null,
        FILTER_VALIDATE_INT
    );

    $precioOfertaTexto =
        trim($_POST["precio_oferta"] ?? "");

    $precioOferta = null;

    if ($precioOfertaTexto !== "") {

        $precioOferta = filter_var(
            $precioOfertaTexto,
            FILTER_VALIDATE_INT
        );

        if ($precioOferta === false) {
            throw new Exception(
                "El precio de oferta no es válido."
            );
        }
    }

    $inicioOferta = convertirFechaModificar(
        $_POST["inicio_oferta"] ?? null
    );

    $finOferta = convertirFechaModificar(
        $_POST["fin_oferta"] ?? null
    );

    $ubicacion = trim(
        $_POST["ubicacion"] ?? ""
    );

    $garantia = trim(
        $_POST["garantia"] ?? ""
    );

    $compatibilidad = trim(
        $_POST["compatibilidad"] ?? ""
    );

    $visibleTienda =
        isset($_POST["visible_tienda"]) ? 1 : 0;

    $destacado =
        isset($_POST["destacado"]) ? 1 : 0;

    /* =========================================
       VALIDACIONES
    ========================================= */

    if (!$idProducto || $idProducto <= 0) {
        throw new Exception(
            "El ID del producto no es válido."
        );
    }

    if (
        $categoria === "" ||
        $nombre === "" ||
        $descripcion === "" ||
        $marca === "" ||
        $sku === ""
    ) {
        throw new Exception(
            "Debe completar todos los campos obligatorios."
        );
    }

    if (
        mb_strlen($categoria) < 3 ||
        mb_strlen($categoria) > 50
    ) {
        throw new Exception(
            "La categoría debe tener entre 3 y 50 caracteres."
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
        mb_strlen($marca) < 2 ||
        mb_strlen($marca) > 50
    ) {
        throw new Exception(
            "La marca debe tener entre 2 y 50 caracteres."
        );
    }

    if (
        !preg_match('/^[A-Z0-9_-]{3,40}$/', $sku)
    ) {
        throw new Exception(
            "El SKU solo puede contener letras, números, guiones y guion bajo."
        );
    }

    if ($cantidad === false || $cantidad < 0) {
        throw new Exception(
            "El stock no es válido."
        );
    }

    if ($stockMinimo === false || $stockMinimo < 0) {
        throw new Exception(
            "El stock mínimo no es válido."
        );
    }

    if ($compra === false || $compra <= 0) {
        throw new Exception(
            "El precio de compra no es válido."
        );
    }

    if ($precio === false || $precio <= 0) {
        throw new Exception(
            "El precio de venta no es válido."
        );
    }

    if (
        $precioOferta !== null &&
        ($precioOferta <= 0 || $precioOferta >= $precio)
    ) {
        throw new Exception(
            "El precio de oferta debe ser menor que el precio normal."
        );
    }

    if (
        $precioOferta !== null &&
        (!$inicioOferta || !$finOferta)
    ) {
        throw new Exception(
            "Debe completar ambas fechas de la oferta."
        );
    }

    if (
        ($inicioOferta && !$finOferta) ||
        (!$inicioOferta && $finOferta)
    ) {
        throw new Exception(
            "Debe completar ambas fechas de la oferta."
        );
    }

    if (
        $inicioOferta &&
        $finOferta &&
        strtotime($finOferta) <= strtotime($inicioOferta)
    ) {
        throw new Exception(
            "El fin de la oferta debe ser posterior al inicio."
        );
    }

    /* =========================================
       COMPROBAR QUE EL PRODUCTO EXISTE
    ========================================= */

    $stmtExiste = $conexion->prepare(
        "SELECT id_producto
         FROM productos
         WHERE id_producto = ?
         LIMIT 1"
    );

    $stmtExiste->bind_param(
        "i",
        $idProducto
    );

    $stmtExiste->execute();

    if ($stmtExiste->get_result()->num_rows === 0) {
        throw new Exception(
            "El producto no existe."
        );
    }

    $stmtExiste->close();

    /* =========================================
       VALIDAR SKU ÚNICO
    ========================================= */

    $stmtSku = $conexion->prepare(
        "SELECT id_producto
         FROM productos
         WHERE sku = ?
           AND id_producto <> ?
         LIMIT 1"
    );

    $stmtSku->bind_param(
        "si",
        $sku,
        $idProducto
    );

    $stmtSku->execute();

    if ($stmtSku->get_result()->num_rows > 0) {
        throw new Exception(
            "Ya existe otro producto con ese SKU."
        );
    }

    $stmtSku->close();

    /* =========================================
       VALIDAR PRODUCTO DUPLICADO
    ========================================= */

    $stmtDuplicado = $conexion->prepare(
        "SELECT id_producto
         FROM productos
         WHERE nombre = ?
           AND marca = ?
           AND id_producto <> ?
         LIMIT 1"
    );

    $stmtDuplicado->bind_param(
        "ssi",
        $nombre,
        $marca,
        $idProducto
    );

    $stmtDuplicado->execute();

    if (
        $stmtDuplicado->get_result()->num_rows > 0
    ) {
        throw new Exception(
            "Ya existe un producto con ese nombre y marca."
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

    $rutaTemporalProducto = null;
    $rutaFinalProducto = null;

    if ($cambiarImagen) {

        validarImagenSubida(
            $_FILES["imagen"]["tmp_name"],
            intval($_FILES["imagen"]["size"]),
            20
        );

        $carpetaProductos =
            dirname(__DIR__) .
            "/images/productos";

        if (
            !is_dir($carpetaProductos) &&
            !mkdir(
                $carpetaProductos,
                0775,
                true
            )
        ) {
            throw new Exception(
                "No fue posible crear la carpeta de productos."
            );
        }

        $codigoTemporal =
            bin2hex(
                random_bytes(6)
            );

        $rutaTemporalProducto =
            $carpetaProductos .
            "/temp_" .
            $codigoTemporal .
            ".webp";

        $archivosTemporales[] =
            $rutaTemporalProducto;

        guardarComoWebP(
            $_FILES["imagen"]["tmp_name"],
            $rutaTemporalProducto,
            1000,
            800,
            80
        );

        $rutaFinalProducto =
            $carpetaProductos .
            "/" .
            $idProducto .
            ".webp";
    }

    /* =========================================
       ACTUALIZAR BASE DE DATOS
    ========================================= */

    $sql = "
        UPDATE productos
        SET
            categoria = ?,
            nombre = ?,
            descripcion = ?,
            marca = ?,
            cantidad = ?,
            compra = ?,
            precio = ?,
            sku = ?,
            stock_minimo = ?,
            visible_tienda = ?,
            destacado = ?,
            precio_oferta = ?,
            inicio_oferta = ?,
            fin_oferta = ?,
            ubicacion = ?,
            garantia = ?,
            compatibilidad = ?
        WHERE id_producto = ?
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "ssssiiisiiiisssssi",
        $categoria,
        $nombre,
        $descripcion,
        $marca,
        $cantidad,
        $compra,
        $precio,
        $sku,
        $stockMinimo,
        $visibleTienda,
        $destacado,
        $precioOferta,
        $inicioOferta,
        $finOferta,
        $ubicacion,
        $garantia,
        $compatibilidad,
        $idProducto
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    /* =========================================
       REEMPLAZAR IMAGEN
    ========================================= */

    if ($cambiarImagen) {

        if (
            !rename(
                $rutaTemporalProducto,
                $rutaFinalProducto
            )
        ) {
            throw new Exception(
                "No fue posible guardar la nueva imagen del producto."
            );
        }

        $indiceTemporal = array_search(
            $rutaTemporalProducto,
            $archivosTemporales,
            true
        );

        if ($indiceTemporal !== false) {
            unset($archivosTemporales[$indiceTemporal]);
        }
    }

    $conexion->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Producto actualizado correctamente."
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