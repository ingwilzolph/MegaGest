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

try {

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {

        http_response_code(405);

        throw new Exception(
            "Método no permitido."
        );
    }

    /* =====================================================
       VALIDAR PERMISOS
    ===================================================== */

    $rolUsuario = strtolower(
        trim(
            (string) (
                $_SESSION["rol"] ?? ""
            )
        )
    );

    $rolesPermitidos = [
        "administrador",
        "vendedor",
        "cajero"
    ];

    if (
        !in_array(
            $rolUsuario,
            $rolesPermitidos,
            true
        )
    ) {

        http_response_code(403);

        throw new Exception(
            "No tiene permisos para consultar proveedores."
        );
    }

    /* =====================================================
       CONSULTAR PROVEEDORES
    ===================================================== */

    $conexion = conexion();

    $conexion->set_charset("utf8mb4");

    $sql = "
        SELECT
            pr.id_proveedor,
            pr.rut,
            pr.razon_social,
            pr.nombre_contacto,
            pr.correo,
            pr.telefono,
            pr.direccion,
            pr.estado,
            pr.fecha_registro,
            pr.fecha_actualizacion,

            COUNT(
                DISTINCT ei.id_entrada
            ) AS cantidad_entradas,

            COALESCE(
                SUM(
                    CASE
                        WHEN ei.estado = 'confirmada'
                        THEN ei.total
                        ELSE 0
                    END
                ),
                0
            ) AS total_compras

        FROM proveedores pr

        LEFT JOIN entradas_inventario ei
            ON ei.id_proveedor =
                pr.id_proveedor

        GROUP BY
            pr.id_proveedor,
            pr.rut,
            pr.razon_social,
            pr.nombre_contacto,
            pr.correo,
            pr.telefono,
            pr.direccion,
            pr.estado,
            pr.fecha_registro,
            pr.fecha_actualizacion

        ORDER BY
            pr.razon_social ASC,
            pr.id_proveedor ASC
    ";

    $resultado = $conexion->query($sql);

    $proveedores = [];

    while (
        $proveedor =
        $resultado->fetch_assoc()
    ) {

        $proveedor["id_proveedor"] =
            intval(
                $proveedor["id_proveedor"]
            );

        $proveedor["cantidad_entradas"] =
            intval(
                $proveedor["cantidad_entradas"]
            );

        $proveedor["total_compras"] =
            intval(
                $proveedor["total_compras"]
            );

        $proveedores[] = $proveedor;
    }

    echo json_encode(
        [
            "ok" => true,

            "datos" => $proveedores,

            "permisos" => [
                "crear" =>
                    in_array(
                        $rolUsuario,
                        [
                            "administrador",
                            "vendedor"
                        ],
                        true
                    ),

                "editar" =>
                    in_array(
                        $rolUsuario,
                        [
                            "administrador",
                            "vendedor"
                        ],
                        true
                    ),

                "consultar" => true
            ]
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $error) {

    error_log(
        "Error obteniendo proveedores: " .
        $error->getMessage()
    );

    if (
        $error instanceof
        mysqli_sql_exception
    ) {

        http_response_code(500);

        $mensaje =
            "No fue posible cargar los proveedores.";

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

?>