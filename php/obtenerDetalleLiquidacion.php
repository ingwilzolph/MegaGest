<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

require_once __DIR__ .
    "/verificarSesionAjax.php";

require_once __DIR__ .
    "/conexion.php";

require_once __DIR__ .
    "/configQr.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;

try {
    /*
    |--------------------------------------------------------------------------
    | VALIDAR SOLICITUD
    |--------------------------------------------------------------------------
    */

    if (
        ($_SERVER["REQUEST_METHOD"] ?? "")
        !== "GET"
    ) {
        http_response_code(405);

        throw new RuntimeException(
            "Método no permitido."
        );
    }

    $idUsuarioSesion = intval(
        $_SESSION["id_usuario"] ?? 0
    );

    $idLiquidacion = intval(
        $_GET["id_liquidacion"] ?? 0
    );

    if (
        $idUsuarioSesion <= 0 ||
        $idLiquidacion <= 0
    ) {
        throw new RuntimeException(
            "La liquidación seleccionada no es válida."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONECTAR CON LA BASE DE DATOS
    |--------------------------------------------------------------------------
    */

    $conexion = conexion();

    $conexion->set_charset(
        "utf8mb4"
    );

    /*
    |--------------------------------------------------------------------------
    | COMPROBAR USUARIO DE LA SESIÓN
    |--------------------------------------------------------------------------
    */

    $stmt = $conexion->prepare("
        SELECT
            rol,
            estado

        FROM login_admin

        WHERE id_usuario = ?

        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $idUsuarioSesion
    );

    $stmt->execute();

    $usuarioSesion =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (
        !$usuarioSesion ||
        strtolower(
            trim(
                (string) $usuarioSesion["estado"]
            )
        ) !== "activo"
    ) {
        http_response_code(403);

        throw new RuntimeException(
            "El usuario no está autorizado."
        );
    }

    $esAdministrador =
        strtolower(
            trim(
                (string) $usuarioSesion["rol"]
            )
        ) === "administrador";

    /*
    |--------------------------------------------------------------------------
    | OBTENER LIQUIDACIÓN
    |--------------------------------------------------------------------------
    */

    $stmt = $conexion->prepare("
        SELECT
            lr.id_liquidacion,
            lr.id_usuario,

            CONCAT(
                u.nombre,
                ' ',
                u.apellido
            ) AS usuario,

            u.correo,

            LOWER(
                TRIM(u.rol)
            ) AS rol,

            lr.periodo_desde,
            lr.periodo_hasta,
            lr.sueldo_base,
            lr.total_comisiones,
            lr.bonos,
            lr.descuentos,
            lr.total_liquidacion,
            lr.estado,
            lr.observaciones,
            lr.fecha_pago,
            lr.fecha_registro,
            lr.fecha_actualizacion,

            CONCAT(
                r.nombre,
                ' ',
                r.apellido
            ) AS responsable

        FROM liquidaciones_remuneraciones lr

        INNER JOIN login_admin u
            ON u.id_usuario =
                lr.id_usuario

        INNER JOIN login_admin r
            ON r.id_usuario =
                lr.id_usuario_responsable

        WHERE lr.id_liquidacion = ?

        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $idLiquidacion
    );

    $stmt->execute();

    $liquidacion =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$liquidacion) {
        http_response_code(404);

        throw new RuntimeException(
            "La liquidación no existe."
        );
    }

    /*
     * Un administrador puede consultar cualquier
     * liquidación. Los demás usuarios solamente
     * pueden consultar la liquidación propia.
     */

    if (
        !$esAdministrador &&
        intval(
            $liquidacion["id_usuario"]
        ) !== $idUsuarioSesion
    ) {
        http_response_code(403);

        throw new RuntimeException(
            "No tiene permiso para consultar esta liquidación."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONVERTIR CAMPOS NUMÉRICOS
    |--------------------------------------------------------------------------
    */

    $camposEnteros = [
        "id_liquidacion",
        "id_usuario",
        "sueldo_base",
        "total_comisiones",
        "bonos",
        "descuentos",
        "total_liquidacion"
    ];

    foreach (
        $camposEnteros as $campo
    ) {
        $liquidacion[$campo] = intval(
            $liquidacion[$campo] ?? 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERAR ENLACE SEGURO PARA EL QR
    |--------------------------------------------------------------------------
    |
    | Este enlace será incluido dentro de la impresión.
    | Al escanear el QR abrirá verLiquidacion.php.
    |
    */

    $liquidacion["url_qr"] =
        generarUrlLiquidacionQr(
            $idLiquidacion
        );

    /*
    |--------------------------------------------------------------------------
    | OBTENER COMISIONES
    |--------------------------------------------------------------------------
    */

    $stmt = $conexion->prepare("
        SELECT
            cu.id_comision,
            cu.tipo_comision,
            cu.origen,
            cu.id_pedido,
            cu.id_ot,
            cu.base_calculo,
            cu.porcentaje_aplicado,
            dlc.monto_comision,
            cu.estado,
            cu.observaciones,
            cu.fecha_generacion

        FROM detalle_liquidacion_comisiones dlc

        INNER JOIN comisiones_usuarios cu
            ON cu.id_comision =
                dlc.id_comision

        WHERE dlc.id_liquidacion = ?

        ORDER BY
            cu.fecha_generacion,
            cu.id_comision
    ");

    $stmt->bind_param(
        "i",
        $idLiquidacion
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $comisiones = [];

    while (
        $fila =
        $resultado->fetch_assoc()
    ) {
        $camposComision = [
            "id_comision",
            "id_pedido",
            "id_ot",
            "base_calculo",
            "monto_comision"
        ];

        foreach (
            $camposComision as $campo
        ) {
            $fila[$campo] =
                $fila[$campo] === null
                    ? null
                    : intval(
                        $fila[$campo]
                    );
        }

        $fila["porcentaje_aplicado"] =
            floatval(
                $fila[
                    "porcentaje_aplicado"
                ]
            );

        $comisiones[] = $fila;
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | OBTENER AJUSTES
    |--------------------------------------------------------------------------
    */

    $stmt = $conexion->prepare("
        SELECT
            id_ajuste,
            tipo_ajuste,
            origen,
            monto,
            estado,
            observaciones,
            fecha_registro

        FROM ajustes_remuneraciones

        WHERE id_liquidacion = ?

        ORDER BY
            fecha_registro,
            id_ajuste
    ");

    $stmt->bind_param(
        "i",
        $idLiquidacion
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $ajustes = [];

    while (
        $fila =
        $resultado->fetch_assoc()
    ) {
        $fila["id_ajuste"] =
            intval(
                $fila["id_ajuste"]
            );

        $fila["monto"] =
            intval(
                $fila["monto"]
            );

        $ajustes[] = $fila;
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | OBTENER HISTORIAL
    |--------------------------------------------------------------------------
    */

    $stmt = $conexion->prepare("
        SELECT
            hl.id_historial,
            hl.accion,
            hl.estado_anterior,
            hl.estado_nuevo,
            hl.observaciones,
            hl.fecha_evento,

            CONCAT(
                u.nombre,
                ' ',
                u.apellido
            ) AS responsable

        FROM historial_liquidaciones hl

        INNER JOIN login_admin u
            ON u.id_usuario =
                hl.id_usuario_responsable

        WHERE hl.id_liquidacion = ?

        ORDER BY
            hl.fecha_evento,
            hl.id_historial
    ");

    $stmt->bind_param(
        "i",
        $idLiquidacion
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $historial = [];

    while (
        $fila =
        $resultado->fetch_assoc()
    ) {
        $fila["id_historial"] =
            intval(
                $fila["id_historial"]
            );

        $historial[] = $fila;
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | RESPUESTA CORRECTA
    |--------------------------------------------------------------------------
    */

    echo json_encode(
        [
            "ok" => true,

            "datos" => [
                "liquidacion" =>
                    $liquidacion,

                "comisiones" =>
                    $comisiones,

                "ajustes" =>
                    $ajustes,

                "historial" =>
                    $historial
            ],

            "permisos" => [
                "procesar" =>
                    $esAdministrador,

                "imprimir" => true
            ]
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $error) {
    error_log(
        "Error obteniendo detalle de liquidación: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {
        http_response_code(500);

        $mensaje =
            "No fue posible cargar el detalle de la liquidación.";
    } else {
        if (
            http_response_code() < 400
        ) {
            http_response_code(400);
        }

        $mensaje =
            $error->getMessage();
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensaje
        ],
        JSON_UNESCAPED_UNICODE
    );
} finally {
    if (
        $conexion instanceof mysqli
    ) {
        $conexion->close();
    }
}