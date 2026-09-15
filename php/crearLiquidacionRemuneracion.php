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
    $idUsuario = intval($_POST["id_usuario"] ?? 0);
    $desde = trim((string) ($_POST["periodo_desde"] ?? ""));
    $hasta = trim((string) ($_POST["periodo_hasta"] ?? ""));
    $bonoManual = intval($_POST["bonos"] ?? 0);
    $descuentoManual = intval($_POST["descuentos"] ?? 0);
    $observaciones = trim((string) ($_POST["observaciones"] ?? ""));

    if ($idAdministrador <= 0 || $idUsuario <= 0) {
        throw new RuntimeException("El usuario seleccionado no es válido.");
    }

    validarFechaLiquidacion($desde, "La fecha inicial no es válida.");
    validarFechaLiquidacion($hasta, "La fecha final no es válida.");

    if ($desde > $hasta) {
        throw new RuntimeException(
            "La fecha inicial no puede ser posterior a la fecha final."
        );
    }

    if ($hasta > date("Y-m-d")) {
        throw new RuntimeException(
            "No se puede liquidar un período que todavía no termina."
        );
    }

    if (
        $bonoManual < 0 ||
        $descuentoManual < 0 ||
        $bonoManual > 999999999999 ||
        $descuentoManual > 999999999999
    ) {
        throw new RuntimeException("Los bonos o descuentos no son válidos.");
    }

    if (mb_strlen($observaciones) > 500) {
        throw new RuntimeException(
            "Las observaciones no pueden superar 500 caracteres."
        );
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
            "Solo el administrador puede crear liquidaciones."
        );
    }

    $stmt = $conexion->prepare(
        "SELECT
            u.nombre,
            u.apellido,
            u.estado AS estado_usuario,
            cr.sueldo_base_mensual,
            cr.estado AS estado_configuracion
         FROM login_admin u
         LEFT JOIN configuracion_remuneraciones cr
            ON cr.id_usuario = u.id_usuario
         WHERE u.id_usuario = ?
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        throw new RuntimeException("El usuario no existe.");
    }

    if (
        strtolower(trim((string) $usuario["estado_usuario"])) !== "activo" ||
        strtolower(trim((string) ($usuario["estado_configuracion"] ?? ""))) !==
            "activo"
    ) {
        throw new RuntimeException(
            "El usuario no tiene una configuración de remuneración activa."
        );
    }

    $sueldoBaseMensual = intval(
        $usuario["sueldo_base_mensual"] ?? 0
    );

    /*
     * El valor guardado en la liquidación corresponde únicamente a los
     * días seleccionados. Si el período cruza meses, cada tramo utiliza
     * la cantidad real de días de su mes.
     */
    $sueldoBase = calcularSueldoProporcional(
        $sueldoBaseMensual,
        $desde,
        $hasta
    );

    $stmt = $conexion->prepare(
        "SELECT id_liquidacion
         FROM liquidaciones_remuneraciones
         WHERE id_usuario = ?
           AND estado <> 'anulada'
           AND NOT (periodo_hasta < ? OR periodo_desde > ?)
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->bind_param("iss", $idUsuario, $desde, $hasta);
    $stmt->execute();
    $liquidacionSolapada = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($liquidacionSolapada) {
        http_response_code(409);
        throw new RuntimeException(
            "Ya existe una liquidación que incluye parte de este período."
        );
    }

    $stmt = $conexion->prepare(
        "SELECT id_comision, monto_comision
         FROM comisiones_usuarios
         WHERE id_usuario = ?
           AND estado = 'pendiente'
           AND DATE(fecha_generacion) BETWEEN ? AND ?
         ORDER BY id_comision
         FOR UPDATE"
    );
    $stmt->bind_param("iss", $idUsuario, $desde, $hasta);
    $stmt->execute();
    $resultadoComisiones = $stmt->get_result();
    $comisiones = [];
    $totalComisiones = 0;

    while ($fila = $resultadoComisiones->fetch_assoc()) {
        $comisiones[] = [
            "id_comision" => intval($fila["id_comision"]),
            "monto" => intval($fila["monto_comision"])
        ];
        $totalComisiones += intval($fila["monto_comision"]);
    }
    $stmt->close();

    /* Los ajustes pendientes anteriores también pasan a la próxima liquidación. */
    $stmt = $conexion->prepare(
        "SELECT id_ajuste, tipo_ajuste, monto
         FROM ajustes_remuneraciones
         WHERE id_usuario = ?
           AND estado = 'pendiente'
           AND DATE(fecha_registro) <= ?
         ORDER BY id_ajuste
         FOR UPDATE"
    );
    $stmt->bind_param("is", $idUsuario, $hasta);
    $stmt->execute();
    $resultadoAjustes = $stmt->get_result();
    $ajustes = [];
    $bonosAutomaticos = 0;
    $descuentosAutomaticos = 0;

    while ($fila = $resultadoAjustes->fetch_assoc()) {
        $montoAjuste = intval($fila["monto"]);
        $ajustes[] = intval($fila["id_ajuste"]);

        if ($fila["tipo_ajuste"] === "bono") {
            $bonosAutomaticos += $montoAjuste;
        } else {
            $descuentosAutomaticos += $montoAjuste;
        }
    }
    $stmt->close();

    $bonos = $bonoManual + $bonosAutomaticos;
    $descuentos = $descuentoManual + $descuentosAutomaticos;
    $totalAntesDescuento = $sueldoBase + $totalComisiones + $bonos;
    $totalLiquidacion = max(0, $totalAntesDescuento - $descuentos);

    $stmt = $conexion->prepare(
        "INSERT INTO liquidaciones_remuneraciones (
            id_usuario,
            periodo_desde,
            periodo_hasta,
            sueldo_base,
            total_comisiones,
            bonos,
            descuentos,
            total_liquidacion,
            estado,
            id_usuario_responsable,
            observaciones
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'borrador', ?, ?)"
    );
    $stmt->bind_param(
        "issiiiiiis",
        $idUsuario,
        $desde,
        $hasta,
        $sueldoBase,
        $totalComisiones,
        $bonos,
        $descuentos,
        $totalLiquidacion,
        $idAdministrador,
        $observaciones
    );
    $stmt->execute();
    $idLiquidacion = intval($conexion->insert_id);
    $stmt->close();

    $stmt = $conexion->prepare(
        "INSERT INTO historial_liquidaciones (
            id_liquidacion,
            id_usuario_responsable,
            accion,
            estado_anterior,
            estado_nuevo,
            observaciones
         ) VALUES (?, ?, 'creada', NULL, 'borrador', ?)"
    );
    $observacionHistorial = "Liquidación creada como borrador.";
    $stmt->bind_param(
        "iis",
        $idLiquidacion,
        $idAdministrador,
        $observacionHistorial
    );
    $stmt->execute();
    $stmt->close();

    if ($comisiones) {
        $stmtDetalle = $conexion->prepare(
            "INSERT INTO detalle_liquidacion_comisiones (
                id_liquidacion,
                id_comision,
                monto_comision
             ) VALUES (?, ?, ?)"
        );
        $stmtComision = $conexion->prepare(
            "UPDATE comisiones_usuarios
             SET estado = 'liquidada'
             WHERE id_comision = ? AND estado = 'pendiente'"
        );

        foreach ($comisiones as $comision) {
            $idComision = $comision["id_comision"];
            $montoComision = $comision["monto"];
            $stmtDetalle->bind_param(
                "iii",
                $idLiquidacion,
                $idComision,
                $montoComision
            );
            $stmtDetalle->execute();
            $stmtComision->bind_param("i", $idComision);
            $stmtComision->execute();

            if ($stmtComision->affected_rows !== 1) {
                throw new RuntimeException(
                    "Una comisión cambió durante la liquidación."
                );
            }
        }

        $stmtDetalle->close();
        $stmtComision->close();
    }

    if ($ajustes) {
        $stmtAjuste = $conexion->prepare(
            "UPDATE ajustes_remuneraciones
             SET estado = 'aplicado', id_liquidacion = ?
             WHERE id_ajuste = ? AND estado = 'pendiente'"
        );

        foreach ($ajustes as $idAjuste) {
            $stmtAjuste->bind_param("ii", $idLiquidacion, $idAjuste);
            $stmtAjuste->execute();

            if ($stmtAjuste->affected_rows !== 1) {
                throw new RuntimeException(
                    "Un ajuste cambió durante la liquidación."
                );
            }
        }

        $stmtAjuste->close();
    }

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Liquidación creada como borrador.",
        "datos" => [
            "id_liquidacion" => $idLiquidacion,
            "usuario" => trim($usuario["nombre"] . " " . $usuario["apellido"]),
            "periodo_desde" => $desde,
            "periodo_hasta" => $hasta,
            "sueldo_base_mensual" => $sueldoBaseMensual,
            "sueldo_base" => $sueldoBase,
            "dias_liquidados" => calcularDiasInclusivos($desde, $hasta),
            "total_comisiones" => $totalComisiones,
            "cantidad_comisiones" => count($comisiones),
            "bonos" => $bonos,
            "descuentos" => $descuentos,
            "cantidad_ajustes" => count($ajustes),
            "total_liquidacion" => $totalLiquidacion,
            "estado" => "borrador"
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $error) {
    if ($transaccionIniciada && $conexion instanceof mysqli) {
        $conexion->rollback();
    }

    error_log("Error creando liquidación: " . $error->getMessage());

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible crear la liquidación.";
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

function validarFechaLiquidacion(string $fecha, string $mensaje): void
{
    $objeto = DateTime::createFromFormat("Y-m-d", $fecha);

    if (!$objeto || $objeto->format("Y-m-d") !== $fecha) {
        throw new RuntimeException($mensaje);
    }
}

function calcularSueldoProporcional(
    int $sueldoMensual,
    string $desde,
    string $hasta
): int {
    if ($sueldoMensual <= 0) {
        return 0;
    }

    $inicio = new DateTimeImmutable($desde);
    $fin = new DateTimeImmutable($hasta);
    $mesActual = $inicio->modify("first day of this month");
    $totalProporcional = 0.0;

    while ($mesActual <= $fin) {
        $inicioMes = $mesActual;
        $finMes = $mesActual->modify("last day of this month");

        $inicioTramo = $inicio > $inicioMes ? $inicio : $inicioMes;
        $finTramo = $fin < $finMes ? $fin : $finMes;

        if ($inicioTramo <= $finTramo) {
            $diasDelMes = intval($mesActual->format("t"));
            $diasDelTramo =
                intval($inicioTramo->diff($finTramo)->days) + 1;

            $totalProporcional +=
                ($sueldoMensual / $diasDelMes) * $diasDelTramo;
        }

        $mesActual = $mesActual->modify("first day of next month");
    }

    return (int) round($totalProporcional);
}

function calcularDiasInclusivos(string $desde, string $hasta): int
{
    $inicio = new DateTimeImmutable($desde);
    $fin = new DateTimeImmutable($hasta);

    return intval($inicio->diff($fin)->days) + 1;
}

?>
