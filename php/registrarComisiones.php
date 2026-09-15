<?php

/*
 * Funciones de comisiones de MegaGest.
 * Deben ejecutarse dentro de la misma transacción del proceso
 * que confirma el ingreso. No realizan commit ni rollback.
 */

function registrarComisionesOrdenTrabajo(
    mysqli $conexion,
    int $idOT
): array {

    if ($idOT <= 0) {
        throw new RuntimeException(
            "La orden de trabajo para calcular comisiones no es válida."
        );
    }

    $resultado = [
        "comisiones_servicios" => 0,
        "monto_servicios" => 0,
        "comisiones_mano_obra" => 0,
        "monto_mano_obra" => 0
    ];

    /* =====================================================
       SERVICIOS DECLARADOS
       Su precio ya incluye la mano de obra.
    ===================================================== */

    $stmtServicios = $conexion->prepare(
        "SELECT
            ots.id_servicio_ot,
            ots.id_usuario,
            ots.total,
            cr.porcentaje_servicios
         FROM orden_trabajo_servicios ots
         INNER JOIN login_admin u
            ON u.id_usuario = ots.id_usuario
           AND LOWER(TRIM(u.estado)) = 'activo'
         INNER JOIN configuracion_remuneraciones cr
            ON cr.id_usuario = ots.id_usuario
           AND cr.estado = 'activo'
           AND cr.porcentaje_servicios > 0
         WHERE ots.id_ot = ?
         ORDER BY ots.id_servicio_ot ASC
         FOR UPDATE"
    );

    $stmtServicios->bind_param("i", $idOT);
    $stmtServicios->execute();
    $servicios = $stmtServicios->get_result();

    while ($linea = $servicios->fetch_assoc()) {
        $idServicioOT = intval($linea["id_servicio_ot"] ?? 0);
        $idUsuario = intval($linea["id_usuario"] ?? 0);
        $baseCalculo = intval($linea["total"] ?? 0);
        $porcentaje = round(
            (float) ($linea["porcentaje_servicios"] ?? 0),
            2
        );

        if (
            $idServicioOT <= 0 ||
            $idUsuario <= 0 ||
            $baseCalculo <= 0 ||
            $porcentaje <= 0
        ) {
            continue;
        }

        $monto = calcularMontoComision(
            $baseCalculo,
            $porcentaje
        );

        if ($monto <= 0) {
            continue;
        }

        $clave = "COM-OT-SERVICIO-" . $idServicioOT;

        $creada = insertarComisionHistorica(
            $conexion,
            $idUsuario,
            "servicio",
            "orden_trabajo",
            null,
            $idOT,
            $idServicioOT,
            null,
            $baseCalculo,
            $porcentaje,
            $monto,
            $clave,
            "Comisión por servicio declarado en orden de trabajo."
        );

        if ($creada) {
            $resultado["comisiones_servicios"]++;
            $resultado["monto_servicios"] += $monto;
        }
    }

    $stmtServicios->close();

    /* =====================================================
       OTROS SERVICIOS / MANO DE OBRA
    ===================================================== */

    $stmtManoObra = $conexion->prepare(
        "SELECT
            mo.id_mano_obra,
            mo.id_usuario,
            mo.total,
            cr.porcentaje_mano_obra
         FROM mano_obra_ot mo
         INNER JOIN login_admin u
            ON u.id_usuario = mo.id_usuario
           AND LOWER(TRIM(u.estado)) = 'activo'
         INNER JOIN configuracion_remuneraciones cr
            ON cr.id_usuario = mo.id_usuario
           AND cr.estado = 'activo'
           AND cr.porcentaje_mano_obra > 0
         WHERE mo.id_ot = ?
         ORDER BY mo.id_mano_obra ASC
         FOR UPDATE"
    );

    $stmtManoObra->bind_param("i", $idOT);
    $stmtManoObra->execute();
    $manoObra = $stmtManoObra->get_result();

    while ($linea = $manoObra->fetch_assoc()) {
        $idManoObra = intval($linea["id_mano_obra"] ?? 0);
        $idUsuario = intval($linea["id_usuario"] ?? 0);
        $baseCalculo = intval($linea["total"] ?? 0);
        $porcentaje = round(
            (float) ($linea["porcentaje_mano_obra"] ?? 0),
            2
        );

        if (
            $idManoObra <= 0 ||
            $idUsuario <= 0 ||
            $baseCalculo <= 0 ||
            $porcentaje <= 0
        ) {
            continue;
        }

        $monto = calcularMontoComision(
            $baseCalculo,
            $porcentaje
        );

        if ($monto <= 0) {
            continue;
        }

        $clave = "COM-OT-MANO-OBRA-" . $idManoObra;

        $creada = insertarComisionHistorica(
            $conexion,
            $idUsuario,
            "mano_obra",
            "orden_trabajo",
            null,
            $idOT,
            null,
            $idManoObra,
            $baseCalculo,
            $porcentaje,
            $monto,
            $clave,
            "Comisión por otro servicio o mano de obra en orden de trabajo."
        );

        if ($creada) {
            $resultado["comisiones_mano_obra"]++;
            $resultado["monto_mano_obra"] += $monto;
        }
    }

    $stmtManoObra->close();

    return $resultado;
}


function calcularMontoComision(
    int $baseCalculo,
    float $porcentaje
): int {
    if ($baseCalculo <= 0 || $porcentaje <= 0) {
        return 0;
    }

    return intval(round(
        $baseCalculo * ($porcentaje / 100),
        0,
        PHP_ROUND_HALF_UP
    ));
}


function insertarComisionHistorica(
    mysqli $conexion,
    int $idUsuario,
    string $tipoComision,
    string $origen,
    ?int $idPedido,
    ?int $idOT,
    ?int $idServicioOT,
    ?int $idManoObra,
    int $baseCalculo,
    float $porcentaje,
    int $monto,
    string $claveOperacion,
    string $observaciones
): bool {

    /* Saber si ya existía evita sumarla de nuevo al resumen. */
    $stmtExiste = $conexion->prepare(
        "SELECT id_comision
         FROM comisiones_usuarios
         WHERE clave_operacion = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtExiste->bind_param("s", $claveOperacion);
    $stmtExiste->execute();
    $yaExiste = $stmtExiste->get_result()->num_rows > 0;
    $stmtExiste->close();

    if ($yaExiste) {
        return false;
    }

    $stmt = $conexion->prepare(
        "INSERT INTO comisiones_usuarios (
            id_usuario,
            tipo_comision,
            origen,
            id_pedido,
            id_ot,
            id_servicio_ot,
            id_mano_obra,
            base_calculo,
            porcentaje_aplicado,
            monto_comision,
            estado,
            clave_operacion,
            observaciones
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            'pendiente', ?, ?
        )"
    );

    $stmt->bind_param(
        "issiiiiidiss",
        $idUsuario,
        $tipoComision,
        $origen,
        $idPedido,
        $idOT,
        $idServicioOT,
        $idManoObra,
        $baseCalculo,
        $porcentaje,
        $monto,
        $claveOperacion,
        $observaciones
    );

    $stmt->execute();
    $stmt->close();

    return true;
}

?>
