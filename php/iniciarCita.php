<?php

header("Content-Type: application/json");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = conexion();

$id = intval($_GET["id"] ?? 0);

if($id <= 0){

    echo json_encode(["ok"=>false, "mensaje"=>"ID inválido."]);

    exit;
}

$conexion->begin_transaction();

try{

    //====================================
    // Cambiar estado de la cita
    //====================================

    $stmt = $conexion->prepare("UPDATE citas SET estado ='En proceso' WHERE id_cita=?");

    $stmt->bind_param("i",$id);

    if(!$stmt->execute()){
        throw new Exception("No fue posible iniciar la cita.");
    }

    $stmt->close();


    //====================================
    // Crear Orden de Trabajo
    //====================================

    $stmt = $conexion->prepare("INSERT INTO orden_trabajo(id_cita, fechaInicio, estado) VALUES(?, NOW(), 'En proceso')");

    $stmt->bind_param("i",$id);

    if(!$stmt->execute()){
        throw new Exception("No fue posible crear la Orden de Trabajo.");
    }

    $idOT = $conexion->insert_id;

    $stmt->close();

    //====================================
    // Generar número OT
    //====================================

    $numeroOT = sprintf("OT-%s-%06d", date("Ymd"), $idOT);

    $stmt = $conexion->prepare("UPDATE orden_trabajo SET numeroOT=? WHERE id_ot=?");

    $stmt->bind_param("si", $numeroOT, $idOT);

    if(!$stmt->execute()){
        throw new Exception("No fue posible generar el número OT.");
    }

    $stmt->close();

    $conexion->commit();

    echo json_encode([
        "ok"=>true,
        "mensaje"=>"La cita fue iniciada correctamente."
    ]);

}catch(Throwable $e){

    $conexion->rollback();

    echo json_encode([
        "ok"=>false,
        "mensaje"=>$e->getMessage()
    ]);

}

$conexion->close();