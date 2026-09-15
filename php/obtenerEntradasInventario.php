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
        throw new Exception("No tiene permisos para consultar las entradas.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
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
            CONCAT(us.nombre, ' ', us.apellido) AS usuario_responsable,
            COALESCE(SUM(de.cantidad), 0) AS cantidad_unidades,
            COUNT(de.id_detalle_entrada) AS cantidad_lineas
        FROM entradas_inventario en
        LEFT JOIN proveedores pr
            ON pr.id_proveedor = en.id_proveedor
        INNER JOIN login_admin us
            ON us.id_usuario = en.id_usuario
        LEFT JOIN detalle_entrada_inventario de
            ON de.id_entrada = en.id_entrada
        GROUP BY
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
            pr.razon_social,
            us.nombre,
            us.apellido
        ORDER BY en.id_entrada DESC
        LIMIT 500
    ";

    $resultado = $conexion->query($sql);
    $entradas = [];

    while ($entrada = $resultado->fetch_assoc()) {
        foreach ([
            "id_entrada",
            "id_proveedor",
            "id_usuario",
            "neto",
            "iva",
            "total",
            "cantidad_unidades",
            "cantidad_lineas"
        ] as $campo) {
            $entrada[$campo] = $entrada[$campo] !== null
                ? intval($entrada[$campo])
                : null;
        }

        $entradas[] = $entrada;
    }

    $sqlResumen = "
        SELECT
            COUNT(*) AS entradas_mes,
            COALESCE(SUM(total), 0) AS compras_mes
        FROM entradas_inventario
        WHERE estado = 'confirmada'
          AND YEAR(fecha_confirmacion) = YEAR(CURDATE())
          AND MONTH(fecha_confirmacion) = MONTH(CURDATE())
    ";

    $resumen = $conexion->query($sqlResumen)->fetch_assoc();

    echo json_encode([
        "ok" => true,
        "datos" => $entradas,
        "resumen" => [
            "entradas_mes" => intval($resumen["entradas_mes"] ?? 0),
            "compras_mes" => intval($resumen["compras_mes"] ?? 0)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    error_log("Error obteniendo entradas de inventario: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar las entradas de inventario.";
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
