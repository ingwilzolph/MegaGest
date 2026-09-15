<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new Exception("Método no permitido.");
    }

    $rol = strtolower(trim((string) ($_SESSION["rol"] ?? "")));

    if (!in_array($rol, ["administrador", "vendedor", "cajero"], true)) {
        http_response_code(403);
        throw new Exception("No tiene permisos para consultar esta entrada.");
    }

    $idEntrada = intval($_GET["id"] ?? 0);

    if ($idEntrada <= 0) {
        throw new Exception("La entrada seleccionada no es válida.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $stmtEntrada = $conexion->prepare(
        "SELECT
            en.id_entrada,
            en.numero_entrada,
            en.id_proveedor,
            en.id_usuario,
            en.tipo_documento,
            en.numero_documento,
            en.fecha_documento,
            en.neto,
            en.iva,
            en.total,
            en.estado,
            en.observaciones,
            en.fecha_registro,
            en.fecha_confirmacion,
            COALESCE(pr.razon_social, 'Sin proveedor') AS proveedor,
            pr.rut AS rut_proveedor,
            pr.nombre_contacto,
            pr.correo AS correo_proveedor,
            pr.telefono AS telefono_proveedor,
            CONCAT(us.nombre, ' ', us.apellido) AS usuario_responsable
         FROM entradas_inventario en
         LEFT JOIN proveedores pr
            ON pr.id_proveedor = en.id_proveedor
         INNER JOIN login_admin us
            ON us.id_usuario = en.id_usuario
         WHERE en.id_entrada = ?
         LIMIT 1"
    );

    $stmtEntrada->bind_param("i", $idEntrada);
    $stmtEntrada->execute();
    $entrada = $stmtEntrada->get_result()->fetch_assoc();
    $stmtEntrada->close();

    if (!$entrada) {
        throw new Exception("La entrada de inventario no fue encontrada.");
    }

    foreach (["id_entrada", "id_proveedor", "id_usuario", "neto", "iva", "total"] as $campo) {
        $entrada[$campo] = $entrada[$campo] !== null
            ? intval($entrada[$campo])
            : null;
    }

    $stmtDetalle = $conexion->prepare(
        "SELECT
            de.id_detalle_entrada,
            de.id_producto,
            de.cantidad,
            de.costo_unitario,
            de.total_linea,
            p.sku,
            p.nombre AS producto,
            p.marca,
            p.cantidad AS stock_actual
         FROM detalle_entrada_inventario de
         INNER JOIN productos p
            ON p.id_producto = de.id_producto
         WHERE de.id_entrada = ?
         ORDER BY de.id_detalle_entrada ASC"
    );

    $stmtDetalle->bind_param("i", $idEntrada);
    $stmtDetalle->execute();
    $resultadoDetalle = $stmtDetalle->get_result();
    $productos = [];

    while ($producto = $resultadoDetalle->fetch_assoc()) {
        foreach ([
            "id_detalle_entrada",
            "id_producto",
            "cantidad",
            "costo_unitario",
            "total_linea",
            "stock_actual"
        ] as $campo) {
            $producto[$campo] = intval($producto[$campo] ?? 0);
        }

        $productos[] = $producto;
    }

    $stmtDetalle->close();

    echo json_encode([
        "ok" => true,
        "datos" => [
            "entrada" => $entrada,
            "productos" => $productos
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    error_log("Error obteniendo detalle de entrada: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar el detalle de la entrada.";
    } else {
        if (http_response_code() < 400) http_response_code(400);
        $mensaje = $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexion instanceof mysqli) $conexion->close();
}

?>
