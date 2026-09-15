<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

date_default_timezone_set("America/Santiago");

$conexion = conexion();

$idCita = intval($_POST["id_cita"] ?? 0);

$nombre = trim($_POST["nombre"] ?? "");

$telefono = preg_replace(
    "/\D/",
    "",
    $_POST["telefono"] ?? ""
);

$correo = trim($_POST["correo"] ?? "");

$patente = strtoupper(
    trim($_POST["patente"] ?? "")
);

$vehiculo = trim($_POST["vehiculo"] ?? "");

$servicio = trim($_POST["servicio"] ?? "");

$fecha = trim($_POST["fecha"] ?? "");

$hora = trim($_POST["hora"] ?? "");

$comentario = trim($_POST["comentario"] ?? "");

if (
    $idCita <= 0 ||
    !$nombre ||
    !$correo ||
    !$patente ||
    !$servicio ||
    !$fecha ||
    !$hora
) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Complete todos los campos obligatorios."
    ]);

    exit;
}

if (strlen($telefono) !== 9) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "El teléfono debe tener 9 dígitos."
    ]);

    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "El correo electrónico no es válido."
    ]);

    exit;
}

$fechaObjeto =
    DateTime::createFromFormat("Y-m-d", $fecha);

if (
    !$fechaObjeto ||
    $fechaObjeto->format("Y-m-d") !== $fecha
) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La fecha no es válida."
    ]);

    exit;
}

$hoy = new DateTime("today");

$finMes = new DateTime(
    date("Y-m-t")
);

if ($fechaObjeto < $hoy || $fechaObjeto > $finMes) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La fecha debe estar dentro del mes actual."
    ]);

    exit;
}

$diaSemana =
    intval($fechaObjeto->format("N"));

if ($diaSemana === 7) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "El taller no atiende los domingos."
    ]);

    exit;
}

if (!preg_match("/^\d{2}:\d{2}$/", $hora)) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La hora seleccionada no es válida."
    ]);

    exit;
}

$horaCompleta = $hora . ":00";

$horaNumero = intval(substr($hora, 0, 2));

$minutos = substr($hora, 3, 2);

if ($minutos !== "00") {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La hora debe ser exacta."
    ]);

    exit;
}

if ($diaSemana <= 5) {

    if ($horaNumero < 9 || $horaNumero > 20) {

        echo json_encode(["ok" => false, "mensaje" => "El horario es de 09:00 a 18:00." ]);

        exit;
    }

} elseif ($diaSemana === 6) {

    if ($horaNumero < 9 || $horaNumero > 14) {

        echo json_encode([
            "ok" => false,
            "mensaje" => "El sábado se atiende de 09:00 a 14:00."
        ]);

        exit;
    }
}

$fechaHoraSeleccionada =
    new DateTime($fecha . " " . $horaCompleta);

if ($fechaHoraSeleccionada <= new DateTime()) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "No puede seleccionar una fecha u hora pasada."
    ]);

    exit;
}

/* Verificar que todavía pueda modificarse */

$stmt = $conexion->prepare("
    SELECT estado
    FROM citas
    WHERE id_cita = ?
    LIMIT 1
");

$stmt->bind_param("i", $idCita);

$stmt->execute();

$citaActual =
    $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$citaActual) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "La cita no existe."
    ]);

    exit;
}

if (
    !in_array(
        $citaActual["estado"],
        ["Pendiente", "Confirmada"],
        true
    )
) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Esta cita ya no puede modificarse."
    ]);

    exit;
}

/* Verificar disponibilidad excluyendo la misma cita */

$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM citas
    WHERE fecha = ?
      AND hora = ?
      AND estado IN (
          'Pendiente',
          'Confirmada',
          'En proceso'
      )
      AND id_cita <> ?
");

$stmt->bind_param(
    "ssi",
    $fecha,
    $horaCompleta,
    $idCita
);

$stmt->execute();

$disponibilidad =
    $stmt->get_result()->fetch_assoc();

$stmt->close();

if (intval($disponibilidad["total"]) >= 2) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "El horario seleccionado ya está completo."
    ]);

    exit;
}

/* Actualizar la cita */

$stmt = $conexion->prepare("
    UPDATE citas
    SET
        nombre = ?,
        telefono = ?,
        correo = ?,
        patente = ?,
        vehiculo = ?,
        servicio = ?,
        fecha = ?,
        hora = ?,
        comentario = ?
    WHERE id_cita = ?
      AND estado IN ('Pendiente', 'Confirmada')
");

$stmt->bind_param(
    "sssssssssi",
    $nombre,
    $telefono,
    $correo,
    $patente,
    $vehiculo,
    $servicio,
    $fecha,
    $horaCompleta,
    $comentario,
    $idCita
);

$stmt->execute();

echo json_encode([
    "ok" => true,
    "mensaje" => "La cita fue modificada correctamente."
]);

$stmt->close();
$conexion->close();