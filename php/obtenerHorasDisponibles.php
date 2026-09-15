<?php

date_default_timezone_set("America/Santiago");

header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";

$conexion = conexion();

$fecha = trim($_GET["fecha"] ?? "");

$idCita = intval($_GET["id_cita"] ?? 0);

if (!$fecha) {
    echo json_encode([]);
    exit;
}

$fechaObjeto = DateTime::createFromFormat("Y-m-d", $fecha);

if (!$fechaObjeto || $fechaObjeto->format("Y-m-d") !== $fecha) {
    echo json_encode([]);
    exit;
}

$diaSemana = intval($fechaObjeto->format("N"));

if ($diaSemana === 7) {
    echo json_encode([]);
    exit;
}

$horarios = [];

if ($diaSemana >= 1 && $diaSemana <= 5) {

    for ($hora = 9; $hora <= 20; $hora++) {
        $horarios[] = sprintf("%02d:00:00", $hora);
    }

} elseif ($diaSemana === 6) {

    for ($hora = 9; $hora <= 14; $hora++) {
        $horarios[] = sprintf("%02d:00:00", $hora);
    }
}

if ($fecha === date("Y-m-d")) {

    $horaActual = date("H:i:s");

    $horarios = array_filter(
        $horarios,
        function ($hora) use ($horaActual) {
            return $hora > $horaActual;
        }
    );
}

$disponibles = [];

foreach ($horarios as $hora) {

    $sql = "
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
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "ssi",
        $fecha,
        $hora,
        $idCita
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result()->fetch_assoc();

    if (intval($resultado["total"]) < 2) {
        $disponibles[] = substr($hora, 0, 5);
    }

    $stmt->close();
}

echo json_encode($disponibles);

$conexion->close();