<?php

header(
    "Content-Type: application/json; charset=utf-8"
);

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

        throw new Exception(
            "Método no permitido."
        );
    }

    /* =====================================================
       PERMISOS
    ===================================================== */

    $rolUsuario = strtolower(
        trim(
            (string) (
                $_SESSION["rol"] ?? ""
            )
        )
    );

    if (
        !in_array(
            $rolUsuario,
            [
                "administrador",
                "vendedor"
            ],
            true
        )
    ) {

        http_response_code(403);

        throw new Exception(
            "No tiene permisos para guardar proveedores."
        );
    }

    /* =====================================================
       RECIBIR DATOS
    ===================================================== */

    $idProveedor = intval(
        $_POST["id_proveedor"] ?? 0
    );

    $rutRecibido = trim(
        (string) (
            $_POST["rut"] ?? ""
        )
    );

    $razonSocial = trim(
        (string) (
            $_POST["razon_social"] ?? ""
        )
    );

    $nombreContacto = trim(
        (string) (
            $_POST["nombre_contacto"] ?? ""
        )
    );

    $correoRecibido = strtolower(
        trim(
            (string) (
                $_POST["correo"] ?? ""
            )
        )
    );

    $telefonoRecibido = trim(
        (string) (
            $_POST["telefono"] ?? ""
        )
    );

    $direccion = trim(
        (string) (
            $_POST["direccion"] ?? ""
        )
    );

    $estado = strtolower(
        trim(
            (string) (
                $_POST["estado"] ?? "activo"
            )
        )
    );

    /* =====================================================
       VALIDACIONES
    ===================================================== */

    if (
        mb_strlen($razonSocial) < 2 ||
        mb_strlen($razonSocial) > 120
    ) {
        throw new Exception(
            "La razón social debe contener entre 2 y 120 caracteres."
        );
    }

    if (mb_strlen($nombreContacto) > 100) {
        throw new Exception(
            "El nombre de contacto no puede superar los 100 caracteres."
        );
    }

    if (
        $correoRecibido !== "" &&
        !filter_var(
            $correoRecibido,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        throw new Exception(
            "El correo electrónico no es válido."
        );
    }

    if (mb_strlen($correoRecibido) > 150) {
        throw new Exception(
            "El correo no puede superar los 150 caracteres."
        );
    }

    if (mb_strlen($telefonoRecibido) > 20) {
        throw new Exception(
            "El teléfono no puede superar los 20 caracteres."
        );
    }

    if (mb_strlen($direccion) > 200) {
        throw new Exception(
            "La dirección no puede superar los 200 caracteres."
        );
    }

    if (
        !in_array(
            $estado,
            [
                "activo",
                "inactivo"
            ],
            true
        )
    ) {
        throw new Exception(
            "El estado seleccionado no es válido."
        );
    }

    /* =====================================================
       NORMALIZAR CAMPOS OPCIONALES
    ===================================================== */

    $rut = null;

    if ($rutRecibido !== "") {

        $rutLimpio = strtoupper(
            preg_replace(
                "/[^0-9Kk]/",
                "",
                $rutRecibido
            )
        );

        if (!validarRutProveedor($rutLimpio)) {
            throw new Exception(
                "El RUT del proveedor no es válido."
            );
        }

        $rut = formatearRutProveedor(
            $rutLimpio
        );
    }

    $nombreContacto =
        $nombreContacto !== ""
            ? $nombreContacto
            : null;

    $correo =
        $correoRecibido !== ""
            ? $correoRecibido
            : null;

    $telefono =
        $telefonoRecibido !== ""
            ? $telefonoRecibido
            : null;

    $direccion =
        $direccion !== ""
            ? $direccion
            : null;

    /* =====================================================
       CONEXIÓN
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $conexion->begin_transaction();

    $transaccionIniciada = true;

    /* =====================================================
       COMPROBAR RUT DUPLICADO
    ===================================================== */

    if ($rut !== null) {

        $sqlRut = "
            SELECT id_proveedor
            FROM proveedores
            WHERE rut = ?
            AND id_proveedor <> ?
            LIMIT 1
        ";

        $stmtRut = $conexion->prepare(
            $sqlRut
        );

        $stmtRut->bind_param(
            "si",
            $rut,
            $idProveedor
        );

        $stmtRut->execute();

        if (
            $stmtRut
                ->get_result()
                ->num_rows > 0
        ) {

            $stmtRut->close();

            throw new Exception(
                "Ya existe un proveedor con ese RUT."
            );
        }

        $stmtRut->close();
    }

    /* =====================================================
       CREAR PROVEEDOR
    ===================================================== */

    if ($idProveedor <= 0) {

        $sqlGuardar = "
            INSERT INTO proveedores (
                rut,
                razon_social,
                nombre_contacto,
                correo,
                telefono,
                direccion,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmtGuardar = $conexion->prepare(
            $sqlGuardar
        );

        $stmtGuardar->bind_param(
            "sssssss",
            $rut,
            $razonSocial,
            $nombreContacto,
            $correo,
            $telefono,
            $direccion,
            $estado
        );

        $stmtGuardar->execute();

        $idProveedor =
            intval(
                $conexion->insert_id
            );

        $mensaje =
            "Proveedor registrado correctamente.";

    /* =====================================================
       MODIFICAR PROVEEDOR
    ===================================================== */

    } else {

        $sqlExiste = "
            SELECT id_proveedor
            FROM proveedores
            WHERE id_proveedor = ?
            LIMIT 1
            FOR UPDATE
        ";

        $stmtExiste = $conexion->prepare(
            $sqlExiste
        );

        $stmtExiste->bind_param(
            "i",
            $idProveedor
        );

        $stmtExiste->execute();

        if (
            $stmtExiste
                ->get_result()
                ->num_rows === 0
        ) {

            $stmtExiste->close();

            throw new Exception(
                "El proveedor no fue encontrado."
            );
        }

        $stmtExiste->close();

        $sqlGuardar = "
            UPDATE proveedores
            SET
                rut = ?,
                razon_social = ?,
                nombre_contacto = ?,
                correo = ?,
                telefono = ?,
                direccion = ?,
                estado = ?
            WHERE id_proveedor = ?
        ";

        $stmtGuardar = $conexion->prepare(
            $sqlGuardar
        );

        $stmtGuardar->bind_param(
            "sssssssi",
            $rut,
            $razonSocial,
            $nombreContacto,
            $correo,
            $telefono,
            $direccion,
            $estado,
            $idProveedor
        );

        $stmtGuardar->execute();

        $mensaje =
            "Proveedor actualizado correctamente.";
    }

    $stmtGuardar->close();

    $conexion->commit();

    $transaccionIniciada = false;

    echo json_encode(
        [
            "ok" => true,
            "mensaje" => $mensaje,
            "datos" => [
                "id_proveedor" =>
                    $idProveedor
            ]
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $error) {

    if (
        $transaccionIniciada &&
        $conexion instanceof mysqli
    ) {
        $conexion->rollback();
    }

    error_log(
        "Error guardando proveedor: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensaje =
            "No fue posible guardar el proveedor.";

    } else {

        if (http_response_code() < 400) {
            http_response_code(400);
        }

        $mensaje = $error->getMessage();
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensaje
        ],
        JSON_UNESCAPED_UNICODE
    );

} finally {

    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

/* =====================================================
   VALIDAR RUT CHILENO
===================================================== */

function validarRutProveedor(
    string $rut
): bool {

    $rut = strtoupper(
        preg_replace(
            "/[^0-9K]/",
            "",
            $rut
        )
    );

    if (
        !preg_match(
            "/^[0-9]+[0-9K]$/",
            $rut
        )
    ) {
        return false;
    }

    $cuerpo = substr(
        $rut,
        0,
        -1
    );

    $digitoRecibido = substr(
        $rut,
        -1
    );

    $suma = 0;
    $multiplicador = 2;

    for (
        $i = strlen($cuerpo) - 1;
        $i >= 0;
        $i--
    ) {

        $suma +=
            intval($cuerpo[$i]) *
            $multiplicador;

        $multiplicador =
            $multiplicador === 7
                ? 2
                : $multiplicador + 1;
    }

    $resultado =
        11 - ($suma % 11);

    if ($resultado === 11) {
        $digitoCalculado = "0";
    } elseif ($resultado === 10) {
        $digitoCalculado = "K";
    } else {
        $digitoCalculado =
            strval($resultado);
    }

    return
        $digitoCalculado ===
        $digitoRecibido;
}

/* =====================================================
   FORMATEAR RUT
===================================================== */

function formatearRutProveedor(
    string $rut
): string {

    $rut = strtoupper(
        preg_replace(
            "/[^0-9K]/",
            "",
            $rut
        )
    );

    $cuerpo = substr(
        $rut,
        0,
        -1
    );

    $digito = substr(
        $rut,
        -1
    );

    return
        number_format(
            intval($cuerpo),
            0,
            "",
            "."
        ) .
        "-" .
        $digito;
}

?>