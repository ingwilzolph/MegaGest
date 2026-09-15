<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";
require_once "imagenWebp.php";

error_reporting(E_ALL);
ini_set("display_errors", "0");

$conexion = null;
$rutasCreadas = [];

function responderError(string $mensaje): void
{
    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ]);

    exit;
}

function convertirFechaOferta(?string $fecha): ?string
{
    $fecha = trim((string)$fecha);

    if ($fecha === "") {
        return null;
    }

    $fecha = str_replace("T", " ", $fecha);

    $objetoFecha = DateTime::createFromFormat(
        "Y-m-d H:i",
        $fecha
    );

    if (!$objetoFecha) {
        throw new Exception("La fecha de oferta no es válida.");
    }

    return $objetoFecha->format("Y-m-d H:i:s");
}

function crearImagenDesdeArchivo(
    string $archivo,
    string $mime
) {

    switch ($mime) {

        case "image/jpeg":
            return imagecreatefromjpeg($archivo);

        case "image/png":
            return imagecreatefrompng($archivo);

        case "image/gif":
            return imagecreatefromgif($archivo);

        case "image/webp":

            if (!function_exists("imagecreatefromwebp")) {
                throw new Exception(
                    "WEBP no está soportado por esta instalación."
                );
            }

            return imagecreatefromwebp($archivo);

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

function guardarImagenProductoAntigua(
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
        throw new Exception("No fue posible leer la imagen.");
    }

    $origen = crearImagenDesdeArchivo(
        $archivo,
        $informacion["mime"]
    );

    if (!$origen) {
        throw new Exception("No fue posible procesar la imagen.");
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

    if (!imagepng($destino, $ruta, 6)) {

        imagedestroy($origen);
        imagedestroy($destino);

        throw new Exception(
            "No fue posible guardar la imagen."
        );
    }

    imagedestroy($origen);
    imagedestroy($destino);
}

try {

    $conexion = conexion();
    $conexion->begin_transaction();

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

    $inicioOferta = convertirFechaOferta(
        $_POST["inicio_oferta"] ?? null
    );

    $finOferta = convertirFechaOferta(
        $_POST["fin_oferta"] ?? null
    );

    $ubicacion = trim($_POST["ubicacion"] ?? "");
    $garantia = trim($_POST["garantia"] ?? "");
    $compatibilidad = trim(
        $_POST["compatibilidad"] ?? ""
    );

    $visibleTienda =
        isset($_POST["visible_tienda"]) ? 1 : 0;

    $destacado =
        isset($_POST["destacado"]) ? 1 : 0;

    if (
        $categoria === "" ||
        $nombre === "" ||
        $descripcion === "" ||
        $marca === ""
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

    if ($cantidad === false || $cantidad < 0) {
        throw new Exception("El stock no es válido.");
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
            "Debe indicar el inicio y el fin de la oferta."
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

    if (
        $sku !== "" &&
        !preg_match('/^[A-Z0-9_-]{3,40}$/', $sku)
    ) {
        throw new Exception(
            "El SKU solo puede contener letras, números, guiones y guion bajo."
        );
    }

    if ($sku !== "") {

        $stmtSku = $conexion->prepare(
            "SELECT id_producto
             FROM productos
             WHERE sku = ?
             LIMIT 1"
        );

        $stmtSku->bind_param("s", $sku);
        $stmtSku->execute();

        if ($stmtSku->get_result()->num_rows > 0) {
            throw new Exception(
                "Ya existe un producto con ese SKU."
            );
        }

        $stmtSku->close();
    } else {
        $sku = null;
    }

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

    if (!in_array($tipoImagen, $tiposPermitidos, true)) {
        throw new Exception(
            "El formato de imagen no está permitido."
        );
    }

    $sql = "
        INSERT INTO productos (
            categoria,
            nombre,
            descripcion,
            marca,
            cantidad,
            compra,
            precio,
            sku,
            stock_minimo,
            visible_tienda,
            destacado,
            precio_oferta,
            inicio_oferta,
            fin_oferta,
            ubicacion,
            garantia,
            compatibilidad
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    $stmt->bind_param(
        "ssssiiisiiiisssss",
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
        $compatibilidad
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $idProducto = $conexion->insert_id;

    if ($sku === null) {

        $prefijo = strtoupper(
            preg_replace(
                "/[^A-Za-z0-9]/",
                "",
                $categoria
            )
        );

        $prefijo = substr($prefijo, 0, 3);

        if (strlen($prefijo) < 3) {
            $prefijo = "PRO";
        }

        $sku = $prefijo . "-" .
            str_pad(
                (string)$idProducto,
                6,
                "0",
                STR_PAD_LEFT
            );

        $stmtSku = $conexion->prepare(
            "UPDATE productos
             SET sku = ?
             WHERE id_producto = ?"
        );

        $stmtSku->bind_param(
            "si",
            $sku,
            $idProducto
        );

        if (!$stmtSku->execute()) {
            throw new Exception($stmtSku->error);
        }

        $stmtSku->close();
    }

    $directorioProducto = "../images/productos";

    if (
        !is_dir($directorioProducto) &&
        !mkdir($directorioProducto, 0775, true)
    ) {
        throw new Exception(
            "No fue posible crear la carpeta de imágenes de productos."
        );
    }

    $rutaProducto =
        $directorioProducto . "/" . $idProducto . ".webp";

    guardarComoWebP(
        $_FILES["imagen"]["tmp_name"],
        $rutaProducto,
        1000,
        800,
        80
    );

    $rutasCreadas[] = $rutaProducto;

    $conexion->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Producto registrado correctamente.",
        "id_producto" => $idProducto,
        "sku" => $sku
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