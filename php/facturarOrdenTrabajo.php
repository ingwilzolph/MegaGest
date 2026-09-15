<?php

error_reporting(E_ALL);
ini_set("display_errors", "0");
ini_set("log_errors", "1");

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";
require_once "registrarComisiones.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new RuntimeException("Método no permitido.");
    }

    /*
     * Permite recibir FormData o JSON.
     */
    $datos = $_POST;

    $tipoContenido = strtolower(
        (string) ($_SERVER["CONTENT_TYPE"] ?? "")
    );

    if (str_contains($tipoContenido, "application/json")) {

        $json = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($json)) {
            throw new RuntimeException("Los datos enviados no son válidos.");
        }

        $datos = $json;
    }

    $idOT = intval($datos["id_ot"] ?? 0);

    $metodoPago = strtolower(
        trim((string) ($datos["metodo_pago"] ?? ""))
    );

    $observaciones = trim(
        (string) ($datos["observaciones"] ?? "")
    );

    $idUsuario = intval(
        $_SESSION["id_usuario"] ?? 0
    );

    if ($idOT <= 0) {
        throw new RuntimeException("La orden de trabajo no es válida.");
    }

    if ($idUsuario <= 0) {
        http_response_code(401);
        throw new RuntimeException(
            "No se pudo identificar al usuario responsable."
        );
    }

    $metodosPermitidos = [
        "efectivo",
        "debito",
        "credito",
        "transferencia"
    ];

    if (!in_array($metodoPago, $metodosPermitidos, true)) {
        throw new RuntimeException(
            "El método de pago seleccionado no es válido."
        );
    }

    if (mb_strlen($observaciones) > 500) {
        throw new RuntimeException(
            "Las observaciones no pueden superar los 500 caracteres."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    /* =====================================================
       VALIDAR AL RESPONSABLE DESDE LA BASE DE DATOS
    ===================================================== */

    $stmtUsuario = $conexion->prepare(
        "SELECT id_usuario, nombre, apellido, rol, estado
         FROM login_admin
         WHERE id_usuario = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtUsuario->bind_param("i", $idUsuario);
    $stmtUsuario->execute();

    $usuario = $stmtUsuario
        ->get_result()
        ->fetch_assoc();

    $stmtUsuario->close();

    if (!$usuario) {
        http_response_code(403);
        throw new RuntimeException("El usuario responsable no existe.");
    }

    if (strtolower(trim((string) $usuario["estado"])) !== "activo") {
        http_response_code(403);
        throw new RuntimeException("El usuario responsable está inactivo.");
    }

    $rol = strtolower(trim((string) $usuario["rol"]));

    if (!in_array($rol, ["administrador", "cajero"], true)) {
        http_response_code(403);
        throw new RuntimeException(
            "Solo el administrador o cajero puede cobrar y facturar una orden."
        );
    }

    /* =====================================================
       OBTENER Y BLOQUEAR LA ORDEN
    ===================================================== */

    $stmtOT = $conexion->prepare(
        "SELECT id_ot, numeroOT, estado
         FROM orden_trabajo
         WHERE id_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtOT->bind_param("i", $idOT);
    $stmtOT->execute();

    $orden = $stmtOT
        ->get_result()
        ->fetch_assoc();

    $stmtOT->close();

    if (!$orden) {
        throw new RuntimeException("La orden de trabajo no fue encontrada.");
    }

    /*
     * Respuesta idempotente: repetir la petición no genera otro cobro.
     */
    if (strtolower(trim((string) $orden["estado"])) === "facturada") {

        $stmtPagoExistente = $conexion->prepare(
            "SELECT id_pago_ot, referencia, total, metodo_pago, fecha_pago
             FROM pagos_orden_trabajo
             WHERE id_ot = ?
             LIMIT 1"
        );

        $stmtPagoExistente->bind_param("i", $idOT);
        $stmtPagoExistente->execute();

        $pagoExistente = $stmtPagoExistente
            ->get_result()
            ->fetch_assoc();

        $stmtPagoExistente->close();

        if (!$pagoExistente) {
            throw new RuntimeException(
                "La orden figura como facturada, pero no tiene un pago registrado. Requiere revisión administrativa."
            );
        }

        $conexion->commit();
        $transaccionIniciada = false;

        echo json_encode([
            "ok" => true,
            "mensaje" => "La orden ya se encuentra facturada.",
            "datos" => [
                "id_ot" => $idOT,
                "numero_ot" => $orden["numeroOT"],
                "id_pago_ot" => intval($pagoExistente["id_pago_ot"]),
                "referencia" => $pagoExistente["referencia"],
                "metodo_pago" => $pagoExistente["metodo_pago"],
                "total" => intval($pagoExistente["total"]),
                "fecha_pago" => $pagoExistente["fecha_pago"],
                "ya_facturada" => true
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (strtolower(trim((string) $orden["estado"])) !== "terminada") {
        throw new RuntimeException(
            "La orden debe estar terminada antes de cobrarla."
        );
    }

    /* =====================================================
       CALCULAR TOTALES DIRECTAMENTE DESDE LA BASE DE DATOS
    ===================================================== */

    $stmtTotales = $conexion->prepare(
        "SELECT
            COALESCE((
                SELECT SUM(total)
                FROM orden_trabajo_servicios
                WHERE id_ot = ?
            ), 0) AS subtotal_servicios,

            COALESCE((
                SELECT SUM(total)
                FROM orden_trabajo_productos
                WHERE id_ot = ?
            ), 0) AS subtotal_productos,

            COALESCE((
                SELECT SUM(total)
                FROM mano_obra_ot
                WHERE id_ot = ?
            ), 0) AS subtotal_mano_obra"
    );

    $stmtTotales->bind_param(
        "iii",
        $idOT,
        $idOT,
        $idOT
    );

    $stmtTotales->execute();

    $totales = $stmtTotales
        ->get_result()
        ->fetch_assoc();

    $stmtTotales->close();

    $subtotalServicios = intval(
        $totales["subtotal_servicios"] ?? 0
    );

    $subtotalProductos = intval(
        $totales["subtotal_productos"] ?? 0
    );

    $subtotalManoObra = intval(
        $totales["subtotal_mano_obra"] ?? 0
    );

    $total =
        $subtotalServicios +
        $subtotalProductos +
        $subtotalManoObra;

    if ($total <= 0) {
        throw new RuntimeException(
            "La orden no contiene conceptos cobrables."
        );
    }

    /* =====================================================
       COMPROBAR QUE TODAVÍA NO EXISTA UN PAGO
    ===================================================== */

    $stmtComprobarPago = $conexion->prepare(
        "SELECT id_pago_ot
         FROM pagos_orden_trabajo
         WHERE id_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtComprobarPago->bind_param("i", $idOT);
    $stmtComprobarPago->execute();

    if ($stmtComprobarPago->get_result()->num_rows > 0) {
        $stmtComprobarPago->close();
        throw new RuntimeException(
            "Esta orden ya tiene un pago registrado."
        );
    }

    $stmtComprobarPago->close();

    /* =====================================================
       REGISTRAR EL PAGO
    ===================================================== */

    $referencia = substr(
        "OT-" .
        $idOT .
        "-" .
        date("YmdHis") .
        "-" .
        strtoupper(bin2hex(random_bytes(3))),
        0,
        50
    );

    $observacionesBD = $observaciones !== ""
        ? $observaciones
        : null;

    $stmtPago = $conexion->prepare(
        "INSERT INTO pagos_orden_trabajo (
            id_ot,
            id_usuario,
            metodo_pago,
            subtotal_servicios,
            subtotal_productos,
            subtotal_mano_obra,
            total,
            estado,
            referencia,
            observaciones
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            'aprobado', ?, ?
        )"
    );

    $stmtPago->bind_param(
        "iisiiiiss",
        $idOT,
        $idUsuario,
        $metodoPago,
        $subtotalServicios,
        $subtotalProductos,
        $subtotalManoObra,
        $total,
        $referencia,
        $observacionesBD
    );

    $stmtPago->execute();

    $idPagoOT = intval($conexion->insert_id);

    $stmtPago->close();

    /* =====================================================
       MARCAR LA ORDEN COMO FACTURADA
    ===================================================== */

    $estadoFacturada = "Facturada";
    $estadoTerminada = "Terminada";

    $stmtActualizar = $conexion->prepare(
        "UPDATE orden_trabajo
         SET estado = ?
         WHERE id_ot = ?
           AND estado = ?"
    );

    $stmtActualizar->bind_param(
        "sis",
        $estadoFacturada,
        $idOT,
        $estadoTerminada
    );

    $stmtActualizar->execute();

    if ($stmtActualizar->affected_rows !== 1) {
        $stmtActualizar->close();
        throw new RuntimeException(
            "No fue posible cambiar la orden al estado Facturada."
        );
    }

    $stmtActualizar->close();

    /* =====================================================
       GENERAR COMISIONES DE SERVICIOS Y MANO DE OBRA

       Se ejecuta dentro de esta misma transacción. Si una
       comisión falla, también se revierte el cobro y el
       cambio de estado de la orden.
    ===================================================== */

    $comisionesGeneradas = registrarComisionesOrdenTrabajo(
        $conexion,
        $idOT
    );

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Orden cobrada y facturada correctamente.",
        "datos" => [
            "id_ot" => $idOT,
            "numero_ot" => $orden["numeroOT"],
            "id_pago_ot" => $idPagoOT,
            "referencia" => $referencia,
            "metodo_pago" => $metodoPago,
            "subtotal_servicios" => $subtotalServicios,
            "subtotal_productos" => $subtotalProductos,
            "subtotal_mano_obra" => $subtotalManoObra,
            "total" => $total,
            "estado" => "Facturada",
            "comisiones" => $comisionesGeneradas
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error facturando orden de trabajo: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje = "No fue posible cobrar y facturar la orden.";
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

?>
