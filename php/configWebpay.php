<?php

use Transbank\Webpay\WebpayPlus\Transaction;

require_once __DIR__ . "/../vendor/autoload.php";

/*
|--------------------------------------------------------------------------
| AMBIENTE DE INTEGRACIÓN
|--------------------------------------------------------------------------
|
| Estas son credenciales públicas proporcionadas por Transbank
| exclusivamente para pruebas.
|
*/

const WEBPAY_AMBIENTE = "integracion";

const WEBPAY_CODIGO_COMERCIO = "597055555532";

const WEBPAY_API_KEY =
    "579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C";

/*
|--------------------------------------------------------------------------
| URL DEL PROYECTO
|--------------------------------------------------------------------------
|
| En XAMPP conserva la ruta local. En AlwaysData Webpay debe regresar al
| dominio público; nunca debe recibir localhost como URL de retorno.
|
*/

$hostWebpay = strtolower((string) ($_SERVER["HTTP_HOST"] ?? ""));
$esLocalWebpay =
    str_contains($hostWebpay, "localhost") ||
    str_contains($hostWebpay, "127.0.0.1");

define(
    "URL_BASE_SITIO",
    $esLocalWebpay
        ? "http://localhost/Website-Alianzapro1"
        : "https://alianzapro.alwaysdata.net"
);

define(
    "WEBPAY_URL_RETORNO",
    URL_BASE_SITIO . "/php/retornoWebpay.php"
);

/* =====================================================
   CREAR OBJETO WEBPAY
===================================================== */

function obtenerTransaccionWebpay(): Transaction
{
    if (WEBPAY_AMBIENTE === "produccion") {

        /*
         * En producción deben utilizarse las credenciales
         * reales entregadas a AlianzaPro por Transbank.
         */

        return Transaction::buildForProduction(
            WEBPAY_API_KEY,
            WEBPAY_CODIGO_COMERCIO
        );
    }

    return Transaction::buildForIntegration(
        WEBPAY_API_KEY,
        WEBPAY_CODIGO_COMERCIO
    );
}

?>
