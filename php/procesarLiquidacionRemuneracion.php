<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/verificarSesionAjax.php";
require_once __DIR__ . "/conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;
$transaccionIniciada = false;

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    $idAdministrador = intval($_SESSION["id_usuario"] ?? 0);
    $idLiquidacion = intval($_POST["id_liquidacion"] ?? 0);
    $accion = strtolower(trim((string) ($_POST["accion"] ?? "")));

    if ($idAdministrador <= 0 || $idLiquidacion <= 0) {
        throw new RuntimeException("La liquidación seleccionada no es válida.");
    }

    if (!in_array($accion, ["cerrar", "pagar", "anular"], true)) {
        throw new RuntimeException("La acción seleccionada no es válida.");
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmt = $conexion->prepare(
        "SELECT rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("i", $idAdministrador);
    $stmt->execute();
    $administrador = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$administrador ||
        strtolower(trim((string) $administrador["estado"])) !== "activo" ||
        strtolower(trim((string) $administrador["rol"])) !== "administrador"
    ) {
        http_response_code(403);
        throw new RuntimeException(
            "Solo el administrador puede procesar liquidaciones."
        );
    }

    $stmt = $conexion->prepare(
        "SELECT
            lr.id_liquidacion,
            lr.id_usuario,
            lr.estado,
            lr.total_liquidacion,
            CONCAT(u.nombre, ' ', u.apellido) AS usuario
         FROM liquidaciones_remuneraciones lr
         INNER JOIN login_admin u
            ON u.id_usuario = lr.id_usuario
         WHERE lr.id_liquidacion = ?
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("i", $idLiquidacion);
    $stmt->execute();
    $liquidacion = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$liquidacion) {
        http_response_code(404);
        throw new RuntimeException("La liquidación no existe.");
    }

    $estadoActual = strtolower(trim((string) $liquidacion["estado"]));

    if ($accion === "cerrar") {
        if ($estadoActual === "cerrada") {
            confirmarRespuestaLiquidacion(
                $conexion,
                $transaccionIniciada,
                $liquidacion,
                "La liquidación ya estaba cerrada."
            );
        }

        if ($estadoActual !== "borrador") {
            throw new RuntimeException(
                "Solo una liquidación en borrador puede cerrarse."
            );
        }

        $stmt = $conexion->prepare(
            "UPDATE liquidaciones_remuneraciones
             SET estado = 'cerrada'
             WHERE id_liquidacion = ? AND estado = 'borrador'"
        );
        $stmt->bind_param("i", $idLiquidacion);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new RuntimeException(
                "La liquidación cambió durante la operación."
            );
        }

        $stmt->close();
        $liquidacion["estado"] = "cerrada";
        $mensaje = "Liquidación cerrada y lista para pagar.";

    } elseif ($accion === "pagar") {
        if ($estadoActual === "pagada") {
            confirmarRespuestaLiquidacion(
                $conexion,
                $transaccionIniciada,
                $liquidacion,
                "La liquidación ya estaba pagada."
            );
        }

        if ($estadoActual !== "cerrada") {
            throw new RuntimeException(
                "Debe cerrar la liquidación antes de registrar el pago."
            );
        }

        $stmt = $conexion->prepare(
            "UPDATE comisiones_usuarios cu
             INNER JOIN detalle_liquidacion_comisiones dlc
                ON dlc.id_comision = cu.id_comision
             SET cu.estado = 'pagada'
             WHERE dlc.id_liquidacion = ?
               AND cu.estado = 'liquidada'"
        );
        $stmt->bind_param("i", $idLiquidacion);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare(
            "UPDATE liquidaciones_remuneraciones
             SET estado = 'pagada',
                 fecha_pago = NOW(),
                 id_usuario_responsable = ?
             WHERE id_liquidacion = ? AND estado = 'cerrada'"
        );
        $stmt->bind_param("ii", $idAdministrador, $idLiquidacion);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new RuntimeException(
                "No fue posible confirmar el pago de la liquidación."
            );
        }

        $stmt->close();
        $liquidacion["estado"] = "pagada";
        $mensaje = "Pago de liquidación registrado correctamente.";

    } else {
        if ($estadoActual === "anulada") {
            confirmarRespuestaLiquidacion(
                $conexion,
                $transaccionIniciada,
                $liquidacion,
                "La liquidación ya estaba anulada."
            );
        }

        if ($estadoActual === "pagada") {
            throw new RuntimeException(
                "Una liquidación pagada no puede anularse."
            );
        }

        if (!in_array($estadoActual, ["borrador", "cerrada"], true)) {
            throw new RuntimeException(
                "El estado actual no permite anular la liquidación."
            );
        }

        $stmt = $conexion->prepare(
            "UPDATE comisiones_usuarios cu
             INNER JOIN detalle_liquidacion_comisiones dlc
                ON dlc.id_comision = cu.id_comision
             SET cu.estado = 'pendiente'
             WHERE dlc.id_liquidacion = ?
               AND cu.estado = 'liquidada'"
        );
        $stmt->bind_param("i", $idLiquidacion);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare(
            "UPDATE ajustes_remuneraciones
             SET estado = 'pendiente', id_liquidacion = NULL
             WHERE id_liquidacion = ? AND estado = 'aplicado'"
        );
        $stmt->bind_param("i", $idLiquidacion);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare(
            "DELETE FROM detalle_liquidacion_comisiones
             WHERE id_liquidacion = ?"
        );
        $stmt->bind_param("i", $idLiquidacion);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare(
            "UPDATE liquidaciones_remuneraciones
             SET estado = 'anulada'
             WHERE id_liquidacion = ?
               AND estado IN ('borrador', 'cerrada')"
        );
        $stmt->bind_param("i", $idLiquidacion);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new RuntimeException(
                "No fue posible anular la liquidación."
            );
        }

        $stmt->close();
        $liquidacion["estado"] = "anulada";
        $mensaje = "Liquidación anulada. Sus comisiones quedaron disponibles.";
    }

    $estadoNuevo = (string) $liquidacion["estado"];
    $accionHistorial = match ($accion) {
        "cerrar" => "cerrada",
        "pagar" => "pagada",
        "anular" => "anulada"
    };

    $stmt = $conexion->prepare(
        "INSERT INTO historial_liquidaciones (
            id_liquidacion,
            id_usuario_responsable,
            accion,
            estado_anterior,
            estado_nuevo,
            observaciones
         ) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "iissss",
        $idLiquidacion,
        $idAdministrador,
        $accionHistorial,
        $estadoActual,
        $estadoNuevo,
        $mensaje
    );
    $stmt->execute();
    $stmt->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => $mensaje,
        "datos" => [
            "id_liquidacion" => $idLiquidacion,
            "id_usuario" => intval($liquidacion["id_usuario"]),
            "usuario" => $liquidacion["usuario"],
            "total_liquidacion" => intval(
                $liquidacion["total_liquidacion"]
            ),
            "estado" => $liquidacion["estado"]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error procesando liquidación: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible procesar la liquidación.";
    } else {
        if (http_response_code() < 400) {
            http_response_code(400);
        }
        $mensaje = $error->getMessage();
    }

    echo json_encode([
        "ok" => false,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

function confirmarRespuestaLiquidacion(
    mysqli $conexion,
    bool &$transaccionIniciada,
    array $liquidacion,
    string $mensaje
): void {
    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => $mensaje,
        "datos" => [
            "id_liquidacion" => intval($liquidacion["id_liquidacion"]),
            "id_usuario" => intval($liquidacion["id_usuario"]),
            "usuario" => $liquidacion["usuario"],
            "total_liquidacion" => intval(
                $liquidacion["total_liquidacion"]
            ),
            "estado" => $liquidacion["estado"]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

?>
