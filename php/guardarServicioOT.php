<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$transaccionIniciada = false;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        throw new Exception("Método no permitido.");
    }

    $idOT = intval($_POST["id_ot"] ?? 0);

    /*
     * El responsable se obtiene de la sesión.
     * Nunca se confía en id_usuario enviado por el navegador.
     */
    $idUsuario = intval(
        $_SESSION["id_usuario"] ?? 0
    );

    $rolUsuario = strtolower(
        trim((string) ($_SESSION["rol"] ?? ""))
    );

    $descripcion = trim(
        (string) ($_POST["descripcion"] ?? "")
    );

    $cantidad = intval(
        $_POST["cantidad"] ?? 0
    );

    $precio = intval(
        $_POST["precio"] ?? 0
    );

    $costoRecibido = trim(
        (string) ($_POST["costo_unitario"] ?? "")
    );

    $rolesPermitidos = [
        "administrador",
        "mecanico"
    ];

    if (!in_array(
        $rolUsuario,
        $rolesPermitidos,
        true
    )) {
        http_response_code(403);
        throw new Exception(
            "No tiene permisos para agregar servicios a la orden."
        );
    }

    if ($idOT <= 0) {
        throw new Exception(
            "La orden de trabajo no es válida."
        );
    }

    if ($idUsuario <= 0) {
        throw new Exception(
            "No se pudo identificar al usuario responsable."
        );
    }

    if (
        $descripcion === "" ||
        mb_strlen($descripcion) < 3 ||
        mb_strlen($descripcion) > 200
    ) {
        throw new Exception(
            "La descripción del servicio debe contener entre 3 y 200 caracteres."
        );
    }

    if ($cantidad <= 0 || $cantidad > 1000) {
        throw new Exception(
            "La cantidad del servicio no es válida."
        );
    }

    if ($precio < 0) {
        throw new Exception(
            "El precio del servicio no es válido."
        );
    }

    /*
     * El costo interno solo puede ser registrado por el
     * administrador. Para otros roles queda pendiente (NULL).
     */
    $costoUnitario = null;

    if (
        $rolUsuario === "administrador" &&
        $costoRecibido !== ""
    ) {
        if (!ctype_digit($costoRecibido)) {
            throw new Exception(
                "El costo interno del servicio no es válido."
            );
        }

        $costoUnitario = intval($costoRecibido);
    }

    $total = $cantidad * $precio;

    $costoTotal = $costoUnitario !== null
        ? $cantidad * $costoUnitario
        : null;

    if ($total > 2147483647) {
        throw new Exception(
            "El total del servicio supera el valor permitido."
        );
    }

    if (
        $costoTotal !== null &&
        $costoTotal > 9223372036854775807
    ) {
        throw new Exception(
            "El costo total del servicio no es válido."
        );
    }

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");
    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* Bloquear la OT para impedir cambios durante su cierre. */
    $stmtOT = $conexion->prepare(
        "SELECT id_ot, estado
         FROM orden_trabajo
         WHERE id_ot = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmtOT->bind_param("i", $idOT);
    $stmtOT->execute();

    $resultadoOT = $stmtOT->get_result();

    if ($resultadoOT->num_rows === 0) {
        $stmtOT->close();
        throw new Exception(
            "La orden de trabajo no fue encontrada."
        );
    }

    $orden = $resultadoOT->fetch_assoc();
    $stmtOT->close();

    $estadoOT = strtolower(
        trim((string) ($orden["estado"] ?? ""))
    );

    if (!in_array(
        $estadoOT,
        ["abierta", "en proceso"],
        true
    )) {
        throw new Exception(
            "No se pueden agregar servicios a una orden cerrada."
        );
    }

    /* La OT está bloqueada, por lo que esta comprobación es estable. */
    $stmtExiste = $conexion->prepare(
        "SELECT id_servicio_ot
         FROM orden_trabajo_servicios
         WHERE id_ot = ?
           AND LOWER(TRIM(descripcion)) = LOWER(?)
         LIMIT 1"
    );

    $stmtExiste->bind_param(
        "is",
        $idOT,
        $descripcion
    );

    $stmtExiste->execute();

    if ($stmtExiste->get_result()->num_rows > 0) {
        $stmtExiste->close();
        throw new Exception(
            "Este servicio ya se encuentra en la orden de trabajo."
        );
    }

    $stmtExiste->close();

    $stmtInsertar = $conexion->prepare(
        "INSERT INTO orden_trabajo_servicios (
            id_ot,
            id_usuario,
            descripcion,
            cantidad,
            precio,
            costo_unitario,
            total,
            costo_total
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmtInsertar->bind_param(
        "iisiiiii",
        $idOT,
        $idUsuario,
        $descripcion,
        $cantidad,
        $precio,
        $costoUnitario,
        $total,
        $costoTotal
    );

    $stmtInsertar->execute();

    $idServicioOT = intval(
        $conexion->insert_id
    );

    $stmtInsertar->close();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Servicio agregado correctamente.",
        "datos" => [
            "id_servicio_ot" => $idServicioOT,
            "id_ot" => $idOT,
            "id_usuario" => $idUsuario,
            "descripcion" => $descripcion,
            "cantidad" => $cantidad,
            "precio" => $precio,
            "costo_unitario" => $costoUnitario,
            "total" => $total,
            "costo_total" => $costoTotal
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
        "Error agregando servicio a OT: " .
        $error->getMessage()
    );

    if ($error instanceof mysqli_sql_exception) {
        http_response_code(500);
        $mensaje =
            "No fue posible agregar el servicio a la orden.";
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
