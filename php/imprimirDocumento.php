<?php

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/configQr.php";

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");
header("X-Robots-Tag: noindex, nofollow");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = null;

function escaparDocumento($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

function precioDocumento($valor): string
{
    return "$" . number_format((int) $valor, 0, ",", ".");
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        throw new RuntimeException(
            "Método no permitido.",
            405
        );
    }

    $idPedido = filter_var(
        $_GET["id"] ?? null,
        FILTER_VALIDATE_INT,
        [
            "options" => [
                "min_range" => 1
            ]
        ]
    );

    $tipo = $_GET["tipo"] ?? "";

    if (
        $idPedido === false ||
        !is_string($tipo) ||
        !in_array(
            $tipo,
            ["cotizacion", "venta"],
            true
        )
    ) {
        throw new RuntimeException(
            "Documento no válido.",
            400
        );
    }

    /*
     * COMPROBAR ACCESO MEDIANTE QR
     */

    $tokenQr = trim(
        (string) ($_GET["qr"] ?? "")
    );

    $accesoPorQr =
        $tokenQr !== "" &&
        hash_equals(
            generarTokenDocumentoQr(
                (int) $idPedido,
                $tipo
            ),
            $tokenQr
        );

    /*
     * Si no se abrió mediante un QR válido,
     * debe existir una sesión administrativa.
     */

    if (!$accesoPorQr) {
        require_once __DIR__ .
            "/verificarSesionAjax.php";
    }

    /*
     * verificarSesionAjax.php configura la respuesta
     * como JSON. Esta página debe enviarse como HTML.
     */

    header(
        "Content-Type: text/html; charset=utf-8"
    );

    header("Cache-Control: no-store");
    header("X-Content-Type-Options: nosniff");
    header("X-Robots-Tag: noindex, nofollow");

    $conexion = conexion();
    $conexion->set_charset("utf8mb4");

    /*
     * COMPROBAR PERMISOS DEL USUARIO
     */

    $idUsuario = (int) (
        $_SESSION["id_usuario"] ?? 0
    );

    $usuario = null;

    if (!$accesoPorQr) {
        $stmt = $conexion->prepare("
            SELECT
                rol,
                estado
            FROM login_admin
            WHERE id_usuario = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $idUsuario
        );

        $stmt->execute();

        $usuario =
            $stmt
                ->get_result()
                ->fetch_assoc();

        $stmt->close();
    }

    $rolesPermitidos =
        $tipo === "cotizacion"
            ? [
                "administrador",
                "vendedor",
                "cajero"
            ]
            : [
                "administrador",
                "cajero"
            ];

    if (
        !$accesoPorQr &&
        (
            !$usuario ||
            strtolower(
                trim($usuario["estado"])
            ) !== "activo" ||
            !in_array(
                strtolower(
                    trim($usuario["rol"])
                ),
                $rolesPermitidos,
                true
            )
        )
    ) {
        throw new RuntimeException(
            "No tiene permiso para imprimir este documento.",
            403
        );
    }

    /*
     * LEER LA COPIA HISTÓRICA
     */

    $stmt = $conexion->prepare("
        SELECT
            di.contenido,
            pe.estado AS estado_actual,
            pe.estado_pago AS pago_actual
        FROM documentos_impresion di

        INNER JOIN pedidos pe
            ON pe.id_pedido = di.id_pedido

        WHERE di.id_pedido = ?
          AND di.tipo = ?

        LIMIT 1
    ");

    $stmt->bind_param(
        "is",
        $idPedido,
        $tipo
    );

    $stmt->execute();

    $registro =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$registro) {
        throw new RuntimeException(
            "Este registro no tiene una copia de impresión guardada. " .
            "No se reconstruirá usando datos que pudieron cambiar.",
            404
        );
    }

    $documento = json_decode(
        $registro["contenido"],
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (
        !is_array($documento) ||
        ($documento["tipo"] ?? null) !== $tipo ||
        !is_array(
            $documento["cliente"] ?? null
        ) ||
        !is_array(
            $documento["productos"] ?? null
        ) ||
        !is_array(
            $documento["totales"] ?? null
        )
    ) {
        throw new RuntimeException(
            "La copia del documento no tiene el formato esperado.",
            500
        );
    }
} catch (Throwable $error) {
    error_log(
        "Error imprimiendo documento: " .
        $error->getMessage()
    );

    $controlado =
        $error instanceof RuntimeException &&
        !(
            $error instanceof
            mysqli_sql_exception
        ) &&
        in_array(
            $error->getCode(),
            [400, 403, 404, 405],
            true
        );

    http_response_code(
        $controlado
            ? $error->getCode()
            : 500
    );

    $mensaje =
        $controlado
            ? $error->getMessage()
            : "No fue posible abrir el documento.";

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Documento no disponible</title>';
    echo '</head>';
    echo '<body>';
    echo '<h1>No se pudo abrir el documento</h1>';
    echo '<p>' .
        escaparDocumento($mensaje) .
        '</p>';
    echo '</body>';
    echo '</html>';

    exit;
} finally {
    if ($conexion instanceof mysqli) {
        $conexion->close();
    }
}

/*
 * PREPARAR DATOS DEL DOCUMENTO
 */

$cliente = $documento["cliente"];
$totales = $documento["totales"];
$pago = $documento["pago"] ?? null;

$titulo =
    $tipo === "cotizacion"
        ? "Cotización"
        : "Comprobante interno de venta";

$nombreCliente = trim(
    ($cliente["nombre"] ?? "") .
    " " .
    ($cliente["apellido"] ?? "")
);

/*
 * CREAR EL ENLACE QUE CONTENDRÁ EL QR
 */

$textoQr = generarUrlDocumentoQr(
    (int) $idPedido,
    $tipo
);

$urlQr =
    "https://api.qrserver.com/v1/create-qr-code/" .
    "?size=220x220&margin=8&data=" .
    rawurlencode($textoQr);

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
        <?= escaparDocumento(
            $documento["numero"]
        ) ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: #172033;
            background: #eef2f6;
        }

        main {
            max-width: 850px;
            margin: auto;
            padding: 32px;
            background: white;
        }

        h1 {
            margin-bottom: 8px;
        }

        p {
            line-height: 1.5;
        }

        .cabecera-documento {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
        }

        .datos-cabecera {
            min-width: 0;
            flex: 1;
        }

        .qr-documento {
            width: 132px;
            flex: 0 0 132px;
            text-align: center;
        }

        .qr-documento img {
            display: block;
            width: 132px;
            height: 132px;
            border: 1px solid #d8dee8;
        }

        .qr-documento small {
            display: block;
            margin-top: 5px;
            color: #555;
            font-size: 10px;
            line-height: 1.25;
        }

        .acciones {
            max-width: 850px;
            margin: 0 auto 16px;
        }

        button {
            padding: 10px 18px;
            cursor: pointer;
        }

        table {
            width: 100%;
            margin-top: 24px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        .sku {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #555;
        }

        .totales {
            margin-top: 24px;
            text-align: right;
        }

        .observaciones {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .aviso {
            padding: 12px;
            border: 1px solid #888;
        }

        @media (max-width: 520px) {
            body {
                padding: 12px;
            }

            main {
                padding: 20px;
            }

            .cabecera-documento {
                flex-direction: column;
            }

            .qr-documento {
                align-self: center;
            }
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }

            main {
                max-width: none;
                padding: 0;
            }

            .acciones {
                display: none;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="acciones">
        <button
            type="button"
            onclick="window.print()"
        >
            Imprimir / Guardar PDF
        </button>
    </div>

    <main>

        <section class="cabecera-documento">

            <div class="datos-cabecera">

                <h1>
                    <?= escaparDocumento(
                        $documento["empresa"]
                    ) ?>
                </h1>

                <h2>
                    <?= escaparDocumento($titulo) ?>
                </h2>

                <p>
                    <strong>Número:</strong>

                    <?= escaparDocumento(
                        $documento["numero"]
                    ) ?>

                    <br>

                    <strong>
                        Fecha de emisión:
                    </strong>

                    <?= escaparDocumento(
                        $documento["fecha_documento"]
                    ) ?>
                </p>

                <?php if (
                    $tipo === "cotizacion"
                ): ?>

                    <p>
                        <strong>
                            Válida hasta:
                        </strong>

                        <?= escaparDocumento(
                            $documento["fecha_expiracion"]
                        ) ?>
                    </p>

                <?php endif; ?>

            </div>

            <div class="qr-documento">

                <img
                    id="qrDocumento"
                    src="<?= escaparDocumento(
                        $urlQr
                    ) ?>"
                    alt="Código QR del documento <?= escaparDocumento(
                        $documento["numero"]
                    ) ?>"
                    width="132"
                    height="132"
                >

                <small>
                    Escanee para consultar los
                    datos del documento
                </small>

            </div>

        </section>

        <hr>

        <p>
            <strong>Cliente:</strong>

            <?= escaparDocumento(
                $nombreCliente
            ) ?>

            <br>

            <strong>RUT:</strong>

            <?= escaparDocumento(
                $cliente["rut"]
                    ?: "No informado"
            ) ?>

            <br>

            <strong>Correo:</strong>

            <?= escaparDocumento(
                $cliente["correo"]
                    ?: "No informado"
            ) ?>

            <br>

            <strong>Teléfono:</strong>

            <?= escaparDocumento(
                $cliente["telefono"]
                    ?: "No informado"
            ) ?>
        </p>

        <table>

            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach (
                    $documento["productos"]
                    as $producto
                ): ?>

                    <tr>
                        <td>
                            <?= escaparDocumento(
                                $producto[
                                    "nombre_producto"
                                ]
                            ) ?>

                            <small class="sku">
                                SKU:

                                <?= escaparDocumento(
                                    $producto["sku"]
                                        ?: "Sin SKU"
                                ) ?>
                            </small>
                        </td>

                        <td>
                            <?= (int) $producto[
                                "cantidad"
                            ] ?>
                        </td>

                        <td>
                            <?= precioDocumento(
                                $producto[
                                    "precio_unitario"
                                ]
                            ) ?>
                        </td>

                        <td>
                            <?= precioDocumento(
                                $producto[
                                    "total_linea"
                                ]
                            ) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

        <div class="totales">

            <p>
                Neto:
                <?= precioDocumento(
                    $totales["neto"]
                ) ?>
            </p>

            <p>
                IVA:
                <?= precioDocumento(
                    $totales["iva"]
                ) ?>
            </p>

            <p>
                Despacho:
                <?= precioDocumento(
                    $totales[
                        "costo_despacho"
                    ]
                ) ?>
            </p>

            <h3>
                Total:
                <?= precioDocumento(
                    $totales["total"]
                ) ?>
            </h3>

        </div>

        <?php if (
            !empty(
                $documento["observaciones"]
            )
        ): ?>

            <h3>Observaciones</h3>

            <p class="observaciones">
                <?= escaparDocumento(
                    $documento["observaciones"]
                ) ?>
            </p>

        <?php endif; ?>

        <?php if (
            $tipo === "cotizacion"
        ): ?>

            <p class="aviso">
                Esta cotización no acredita pago
                ni reserva existencias. Al recuperar
                el documento se revisarán disponibilidad
                y precios actuales; cualquier diferencia
                deberá aceptarse antes de confirmar
                la venta.
            </p>

        <?php else: ?>

            <p>
                <strong>
                    Método de pago registrado:
                </strong>

                <?= escaparDocumento(
                    $pago["proveedor"]
                        ?? "No informado"
                ) ?>
            </p>

            <p>
                Comprobante interno del sistema.
                No sustituye una boleta ni una factura.
            </p>

            <?php if (
                $registro["pago_actual"]
                    !== "aprobado" ||
                in_array(
                    $registro["estado_actual"],
                    [
                        "cancelacion_solicitada",
                        "cancelado"
                    ],
                    true
                )
            ): ?>

                <p class="aviso">
                    Copia histórica.
                    Estado actual del pedido:

                    <?= escaparDocumento(
                        $registro["estado_actual"]
                    ) ?>.

                    Estado actual del pago:

                    <?= escaparDocumento(
                        $registro["pago_actual"]
                    ) ?>.
                </p>

            <?php endif; ?>

        <?php endif; ?>

    </main>

    <script>
        window.addEventListener(
            "load",
            () => {
                const parametros =
                    new URLSearchParams(
                        location.search
                    );

                if (
                    parametros.get("auto") !== "1"
                ) {
                    return;
                }

                const qr =
                    document.getElementById(
                        "qrDocumento"
                    );

                if (qr && !qr.complete) {
                    let impreso = false;
                    let temporizador;

                    const imprimir = () => {
                        if (impreso) {
                            return;
                        }

                        impreso = true;

                        clearTimeout(
                            temporizador
                        );

                        window.print();
                    };

                    qr.addEventListener(
                        "load",
                        imprimir,
                        { once: true }
                    );

                    qr.addEventListener(
                        "error",
                        imprimir,
                        { once: true }
                    );

                    temporizador =
                        setTimeout(
                            imprimir,
                            3000
                        );

                    return;
                }

                window.print();
            }
        );
    </script>

</body>
</html>