<?php

header("Content-Type: application/json");

require_once "conexion.php";

$conexion = conexion();

date_default_timezone_set("America/Santiago");

$anio = date("Y");
$mes  = date("m");

$diasCompletos = [];

/*
|--------------------------------------------------------------------------
| Obtener todas las reservas del mes de una sola vez
|--------------------------------------------------------------------------
*/

$stmt = $conexion->prepare("
    SELECT fecha, hora, COUNT(*) AS total
    FROM citas
    WHERE YEAR(fecha)=?
      AND MONTH(fecha)=?
    GROUP BY fecha, hora
");

$stmt->bind_param("ii", $anio, $mes);
$stmt->execute();

$resultado = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| Organizar reservas por fecha y hora
|--------------------------------------------------------------------------
*/

$reservas = [];

while($fila = $resultado->fetch_assoc()){

    $reservas[$fila["fecha"]][$fila["hora"]] = (int)$fila["total"];

}

/*
|--------------------------------------------------------------------------
| Revisar cada día del mes
|--------------------------------------------------------------------------
*/

$ultimoDia = cal_days_in_month(CAL_GREGORIAN,$mes,$anio);

for($d=1;$d<=$ultimoDia;$d++){

    $fecha = sprintf("%04d-%02d-%02d",$anio,$mes,$d);

    $diaSemana = date("N",strtotime($fecha));

    // Domingos no cuentan
    if($diaSemana==7){
        continue;
    }

    // Horarios según el día
    $horarios=[];

    if($diaSemana<=5){

        for($h=9;$h<=18;$h++){
            $horarios[]=sprintf("%02d:00:00",$h);
        }

    }else{

        for($h=9;$h<=14;$h++){
            $horarios[]=sprintf("%02d:00:00",$h);
        }

    }

    $completo=true;

    foreach($horarios as $hora){

        $ocupados = $reservas[$fecha][$hora] ?? 0;

        if($ocupados < 2){

            $completo=false;
            break;

        }

    }

    if($completo){

        $diasCompletos[]=$fecha;

    }

}

echo json_encode($diasCompletos);

?>