<?php

header("Content-Type: application/json; charset=utf-8");

require_once "verificarSesionAjax.php";
require_once "conexion.php";

$conexion = null;

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido.");
    }

    $idCliente = filter_var(
        $_POST["id_cliente"] ?? null,
        FILTER_VALIDATE_INT
    );

    $rut = trim($_POST["rut"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");

    if (!$idCliente || $idCliente <= 0) {
        throw new Exception(
            "El cliente seleccionado no es válido."
        );
    }

    if (
        $rut === "" ||
        $nombre === "" ||
        $apellido === "" ||
        $telefono === ""
    ) {
        throw new Exception(
            "Debe completar RUT, nombre, apellido y teléfono."
        );
    }

    if (
        $correo !== "" &&
        !filter_var($correo, FILTER_VALIDATE_EMAIL)
    ) {
        throw new Exception(
            "El correo electrónico no es válido."
        );
    }

    $conexion = conexion();

    /* Validar RUT repetido */

    $stmtRut = $conexion->prepare(
        "SELECT id_cliente
         FROM clientes
         WHERE rut = ?
           AND id_cliente <> ?
         LIMIT 1"
    );

    $stmtRut->bind_param(
        "si",
        $rut,
        $idCliente
    );

    $stmtRut->execute();

    if ($stmtRut->get_result()->num_rows > 0) {
        throw new Exception(
            "El RUT pertenece a otro cliente."
        );
    }

    $stmtRut->close();

    /* Validar correo solamente cuando fue ingresado */

    if ($correo !== "") {

        $stmtCorreo = $conexion->prepare(
            "SELECT id_cliente
             FROM clientes
             WHERE correo = ?
               AND id_cliente <> ?
             LIMIT 1"
        );

        $stmtCorreo->bind_param(
            "si",
            $correo,
            $idCliente
        );

        $stmtCorreo->execute();

        if (
            $stmtCorreo->get_result()->num_rows > 0
        ) {
            throw new Exception(
                "El correo pertenece a otro cliente."
            );
        }

        $stmtCorreo->close();
    }

    $correoBaseDatos =
        $correo === "" ? null : $correo;

    $sql = "
        UPDATE clientes
        SET
            rut = ?,
            nombre = ?,
            apellido = ?,
            correo = ?,
            telefono = ?
        WHERE id_cliente = ?
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "No fue posible preparar la actualización."
        );
    }

    $stmt->bind_param(
        "sssssi",
        $rut,
        $nombre,
        $apellido,
        $correoBaseDatos,
        $telefono,
        $idCliente
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    if ($stmt->affected_rows === 0) {

        $stmtExiste = $conexion->prepare(
            "SELECT id_cliente
             FROM clientes
             WHERE id_cliente = ?
             LIMIT 1"
        );

        $stmtExiste->bind_param(
            "i",
            $idCliente
        );

        $stmtExiste->execute();

        if (
            $stmtExiste->get_result()->num_rows === 0
        ) {
            throw new Exception(
                "El cliente ya no existe."
            );
        }

        $stmtExiste->close();
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,
        "mensaje" =>
            "Datos del cliente actualizados correctamente."
    ]);

} catch (Throwable $error) {

    echo json_encode([
        "ok" => false,
        "mensaje" => $error->getMessage()
    ]);

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>