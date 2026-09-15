<?php

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE ENLACES QR
|--------------------------------------------------------------------------
|
| Esta clave permite firmar los enlaces de citas, documentos y
| liquidaciones. No debe mostrarse públicamente ni cambiarse después
| de generar los códigos QR.
|
*/

const QR_LINK_SECRET = "d84f7a930bb14c61b2f496eb38b6e9f74265c817d1314d52a9e58c037c41ab96";

/*
|--------------------------------------------------------------------------
| OBTENER URL BASE
|--------------------------------------------------------------------------
|
| En XAMPP se utiliza localhost.
| En AlwaysData se utiliza el dominio público.
|
*/

function obtenerUrlBaseQr(): string
{
    $host = strtolower(
        (string) ($_SERVER["HTTP_HOST"] ?? "")
    );

    $esLocal =
        str_contains($host, "localhost") ||
        str_contains($host, "127.0.0.1");

    if ($esLocal) {
        return "http://localhost/Website-Alianzapro1";
    }

    return "https://alianzapro.alwaysdata.net";
}

/*
|--------------------------------------------------------------------------
| QR DE VENTA Y COTIZACIÓN
|--------------------------------------------------------------------------
*/

function generarTokenDocumentoQr(
    int $idPedido,
    string $tipo
): string {
    return hash_hmac(
        "sha256",
        "documento|{$tipo}|{$idPedido}",
        QR_LINK_SECRET
    );
}

function generarUrlDocumentoQr(
    int $idPedido,
    string $tipo
): string {
    $token = generarTokenDocumentoQr(
        $idPedido,
        $tipo
    );

    return obtenerUrlBaseQr() .
        "/php/imprimirDocumento.php" .
        "?id=" . $idPedido .
        "&tipo=" . rawurlencode($tipo) .
        "&qr=" . rawurlencode($token);
}

/*
|--------------------------------------------------------------------------
| QR DE CITA
|--------------------------------------------------------------------------
*/

function generarTokenCitaQr(
    string $numeroReserva
): string {
    return hash_hmac(
        "sha256",
        "cita|{$numeroReserva}",
        QR_LINK_SECRET
    );
}

function generarUrlCitaQr(
    string $numeroReserva
): string {
    $token = generarTokenCitaQr(
        $numeroReserva
    );

    return obtenerUrlBaseQr() .
        "/php/verCita.php" .
        "?reserva=" .
        rawurlencode($numeroReserva) .
        "&qr=" .
        rawurlencode($token);
}

/*
|--------------------------------------------------------------------------
| QR DE LIQUIDACIÓN
|--------------------------------------------------------------------------
*/

function generarTokenLiquidacionQr(
    int $idLiquidacion
): string {
    return hash_hmac(
        "sha256",
        "liquidacion|{$idLiquidacion}",
        QR_LINK_SECRET
    );
}

function generarUrlLiquidacionQr(
    int $idLiquidacion
): string {
    $token = generarTokenLiquidacionQr(
        $idLiquidacion
    );

    return obtenerUrlBaseQr() .
        "/php/verLiquidacion.php" .
        "?id=" . $idLiquidacion .
        "&qr=" . rawurlencode($token);
}