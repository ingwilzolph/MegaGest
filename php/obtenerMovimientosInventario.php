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
        throw new Exception("No tiene permisos para consultar movimientos.");
    }

    $puedeVerCostos = $rol === "administrador";

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            mo.id_movimiento,
            mo.id_producto,
            mo.id_usuario,
            mo.id_pedido,
            mo.id_entrada,
            mo.tipo_movimiento,
            mo.sentido,
            mo.cantidad,
            mo.stock_anterior,
            mo.stock_resultante,
            mo.costo_unitario,
            mo.motivo,
            mo.referencia,
            mo.fecha_movimiento,
            pr.nombre AS producto,
            pr.sku,
            CONCAT(us.nombre, ' ', us.apellido) AS usuario_responsable
        FROM movimientos_inventario mo
        INNER JOIN productos pr
            ON pr.id_producto = mo.id_producto
        LEFT JOIN login_admin us
            ON us.id_usuario = mo.id_usuario
        ORDER BY mo.id_movimiento DESC
        LIMIT 1000
    ";

    $resultado = $conexion->query($sql);
    $movimientos = [];

    while ($movimiento = $resultado->fetch_assoc()) {
        foreach ([
            "id_movimiento",
            "id_producto",
            "id_usuario",
            "id_pedido",
            "id_entrada",
            "cantidad",
            "stock_anterior",
            "stock_resultante"
        ] as $campo) {
            $movimiento[$campo] = $movimiento[$campo] !== null
                ? intval($movimiento[$campo])
                : null;
        }

        $movimiento["costo_unitario"] = $puedeVerCostos
            ? intval($movimiento["costo_unitario"] ?? 0)
            : null;

        $movimientos[] = $movimiento;
    }

    $sqlResumen = "
        SELECT
            SUM(DATE(fecha_movimiento) = CURDATE()) AS movimientos_hoy,
            SUM(
                sentido = 'salida'
                AND YEAR(fecha_movimiento) = YEAR(CURDATE())
                AND MONTH(fecha_movimiento) = MONTH(CURDATE())
            ) AS salidas_mes,
            SUM(
                tipo_movimiento IN ('ajuste_entrada', 'ajuste_salida', 'merma')
                AND YEAR(fecha_movimiento) = YEAR(CURDATE())
                AND MONTH(fecha_movimiento) = MONTH(CURDATE())
            ) AS ajustes_mes
        FROM movimientos_inventario
    ";

    $resumen = $conexion->query($sqlResumen)->fetch_assoc();

    echo json_encode([
        "ok" => true,
        "datos" => $movimientos,
        "resumen" => [
            "movimientos_hoy" => intval($resumen["movimientos_hoy"] ?? 0),
            "salidas_mes" => intval($resumen["salidas_mes"] ?? 0),
            "ajustes_mes" => intval($resumen["ajustes_mes"] ?? 0)
        ],
        "permisos" => [
            "ver_costos" => $puedeVerCostos
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {
    error_log("Error obteniendo movimientos: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cargar los movimientos de inventario.";
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
