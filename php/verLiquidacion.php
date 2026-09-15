<?php

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/configQr.php";

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");
header("X-Robots-Tag: noindex, nofollow");

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

$conexion = null;
$liquidacion = null;

$mensajeError =
    "El enlace de la liquidación no es válido.";

/*
|--------------------------------------------------------------------------
| FUNCIONES AUXILIARES
|--------------------------------------------------------------------------
*/

function escaparLiquidacionQr($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

function dineroLiquidacionQr($valor): string
{
    return "$" . number_format(
        (int) $valor,
        0,
        ",",
        "."
    );
}

function fechaLiquidacionQr($fecha): string
{
    $fecha = trim(
        (string) ($fecha ?? "")
    );

    if ($fecha === "") {
        return "Pendiente";
    }

    try {
        $objetoFecha = new DateTime($fecha);

        return $objetoFecha->format(
            "d/m/Y H:i"
        );
    } catch (Throwable $error) {
        return $fecha;
    }
}

/*
|--------------------------------------------------------------------------
| CONSULTAR LIQUIDACIÓN
|--------------------------------------------------------------------------
*/

try {
    if (
        ($_SERVER["REQUEST_METHOD"] ?? "") !== "GET"
    ) {
        throw new RuntimeException(
            "Método no permitido.",
            405
        );
    }

    $idLiquidacion = filter_var(
        $_GET["id"] ?? null,
        FILTER_VALIDATE_INT,
        [
            "options" => [
                "min_range" => 1
            ]
        ]
    );

    $tokenQr = trim(
        (string) ($_GET["qr"] ?? "")
    );

    if (
        $idLiquidacion === false ||
        $tokenQr === ""
    ) {
        throw new RuntimeException(
            $mensajeError,
            403
        );
    }

    $tokenEsperado =
        generarTokenLiquidacionQr(
            (int) $idLiquidacion
        );

    if (
        !hash_equals(
            $tokenEsperado,
            $tokenQr
        )
    ) {
        throw new RuntimeException(
            $mensajeError,
            403
        );
    }

    $conexion = conexion();

    $conexion->set_charset(
        "utf8mb4"
    );

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
            u.rol,

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

            CONCAT(
                r.nombre,
                ' ',
                r.apellido
            ) AS responsable

        FROM liquidaciones_remuneraciones lr

        INNER JOIN login_admin u
            ON u.id_usuario = lr.id_usuario

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
        throw new RuntimeException(
            "No se encontró la liquidación.",
            404
        );
    }
} catch (Throwable $error) {
    error_log(
        "Error consultando QR de liquidación: " .
        $error->getMessage()
    );

    $codigo = (int) $error->getCode();

    if (
        in_array(
            $codigo,
            [403, 404, 405],
            true
        )
    ) {
        http_response_code($codigo);
        $mensajeError = $error->getMessage();
    } else {
        http_response_code(500);

        $mensajeError =
            "No fue posible consultar la liquidación.";
    }
} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?php if ($liquidacion): ?>
            Liquidación
            <?= (int) $liquidacion[
                "id_liquidacion"
            ] ?>
        <?php else: ?>
            Liquidación no disponible
        <?php endif; ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: #202938;
            background: #eef2f6;
        }

        main {
            width: 100%;
            max-width: 850px;
            margin: auto;
            padding: 32px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow:
                0 10px 35px
                rgba(32, 41, 56, 0.10);
        }

        .cabecera {
            padding-bottom: 18px;
            border-bottom: 3px solid #2479ed;
        }

        .marca {
            color: #2479ed;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
        }

        h1 {
            margin: 7px 0 12px;
            font-size: 27px;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 17px;
        }

        .estado {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            color: #145bbb;
            background: #e8f1ff;
            font-weight: 700;
            text-transform: capitalize;
        }

        .datos {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }

        .dato {
            min-width: 0;
            padding: 13px;
            border: 1px solid #dce2e9;
            border-radius: 8px;
        }

        .dato small {
            display: block;
            margin-bottom: 5px;
            color: #6d7786;
            font-weight: 700;
        }

        .dato strong {
            display: block;
            overflow-wrap: anywhere;
        }

        .totales {
            display: grid;
            grid-template-columns:
                repeat(5, minmax(0, 1fr));
            gap: 9px;
            margin-top: 24px;
        }

        .total {
            min-width: 0;
            padding: 12px;
            border: 1px solid #dce2e9;
            border-radius: 8px;
        }

        .total small {
            display: block;
            margin-bottom: 6px;
            color: #6d7786;
            font-weight: 700;
        }

        .total strong {
            overflow-wrap: anywhere;
        }

        .total-final {
            color: #1768d3;
            background: #eaf3ff;
            border-color: #bcd5f8;
            font-weight: 800;
        }

        .observaciones {
            margin-top: 22px;
            padding: 14px;
            border-left: 4px solid #2479ed;
            background: #f7f9fc;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .mensaje-seguridad {
            margin-top: 22px;
            color: #6d7786;
            font-size: 12px;
            text-align: center;
        }

        .error {
            color: #b4232d;
        }

        .acciones {
            margin-top: 22px;
            text-align: center;
        }

        .acciones button {
            padding: 11px 19px;
            border: 0;
            border-radius: 7px;
            color: #ffffff;
            background: #1768d3;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 700px) {
            body {
                padding: 12px;
            }

            main {
                padding: 21px;
            }

            h1 {
                font-size: 22px;
            }

            .datos,
            .totales {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }

            main {
                max-width: none;
                padding: 0;
                box-shadow: none;
            }

            .acciones {
                display: none;
            }
        }
    </style>
</head>

<body>

<main>

    <?php if ($liquidacion): ?>

        <header class="cabecera">

            <div class="marca">
                ALIANZAPRO SPA · MEGAGEST
            </div>

            <h1>
                Comprobante de liquidación N.º
                <?= (int) $liquidacion[
                    "id_liquidacion"
                ] ?>
            </h1>

            <span class="estado">
                <?= escaparLiquidacionQr(
                    $liquidacion["estado"]
                ) ?>
            </span>

        </header>

        <section class="datos">

            <div class="dato">
                <small>Usuario</small>

                <strong>
                    <?= escaparLiquidacionQr(
                        $liquidacion["usuario"]
                    ) ?>
                </strong>
            </div>

            <div class="dato">
                <small>Rol</small>

                <strong>
                    <?= escaparLiquidacionQr(
                        ucfirst(
                            strtolower(
                                $liquidacion["rol"]
                            )
                        )
                    ) ?>
                </strong>
            </div>

            <div class="dato">
                <small>Correo</small>

                <strong>
                    <?= escaparLiquidacionQr(
                        $liquidacion["correo"]
                    ) ?>
                </strong>
            </div>

            <div class="dato">
                <small>Período</small>

                <strong>
                    <?= escaparLiquidacionQr(
                        $liquidacion[
                            "periodo_desde"
                        ]
                    ) ?>

                    al

                    <?= escaparLiquidacionQr(
                        $liquidacion[
                            "periodo_hasta"
                        ]
                    ) ?>
                </strong>
            </div>

            <div class="dato">
                <small>Responsable</small>

                <strong>
                    <?= escaparLiquidacionQr(
                        $liquidacion[
                            "responsable"
                        ]
                    ) ?>
                </strong>
            </div>

            <div class="dato">
                <small>Fecha de emisión</small>

                <strong>
                    <?= escaparLiquidacionQr(
                        fechaLiquidacionQr(
                            $liquidacion[
                                "fecha_registro"
                            ]
                        )
                    ) ?>
                </strong>
            </div>

            <div class="dato">
                <small>Fecha de pago</small>

                <strong>
                    <?= escaparLiquidacionQr(
                        fechaLiquidacionQr(
                            $liquidacion[
                                "fecha_pago"
                            ]
                        )
                    ) ?>
                </strong>
            </div>

        </section>

        <section class="totales">

            <div class="total">
                <small>Sueldo base</small>

                <strong>
                    <?= dineroLiquidacionQr(
                        $liquidacion[
                            "sueldo_base"
                        ]
                    ) ?>
                </strong>
            </div>

            <div class="total">
                <small>Comisiones</small>

                <strong>
                    <?= dineroLiquidacionQr(
                        $liquidacion[
                            "total_comisiones"
                        ]
                    ) ?>
                </strong>
            </div>

            <div class="total">
                <small>Bonos</small>

                <strong>
                    <?= dineroLiquidacionQr(
                        $liquidacion["bonos"]
                    ) ?>
                </strong>
            </div>

            <div class="total">
                <small>Descuentos</small>

                <strong>
                    <?= dineroLiquidacionQr(
                        $liquidacion[
                            "descuentos"
                        ]
                    ) ?>
                </strong>
            </div>

            <div class="total total-final">
                <small>Total liquidación</small>

                <strong>
                    <?= dineroLiquidacionQr(
                        $liquidacion[
                            "total_liquidacion"
                        ]
                    ) ?>
                </strong>
            </div>

        </section>

        <div class="observaciones">
            <strong>Observaciones</strong>

            <br>

            <?= escaparLiquidacionQr(
                trim(
                    (string) $liquidacion[
                        "observaciones"
                    ]
                ) !== ""
                    ? $liquidacion[
                        "observaciones"
                    ]
                    : "Sin observaciones."
            ) ?>
        </div>

        <p class="mensaje-seguridad">
            Documento consultado mediante un enlace QR
            seguro de AlianzaPro SPA.
        </p>

        <div class="acciones">
            <button
                type="button"
                onclick="window.print()"
            >
                Imprimir
            </button>
        </div>

    <?php else: ?>

        <h1 class="error">
            Liquidación no disponible
        </h1>

        <p>
            <?= escaparLiquidacionQr(
                $mensajeError
            ) ?>
        </p>

    <?php endif; ?>

</main>

</body>
</html>