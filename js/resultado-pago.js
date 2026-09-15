const parametros =
    new URLSearchParams(
        window.location.search
    );

const estadoUrl =
    parametros.get("estado") || "error";

const numeroPedidoUrl =
    parametros.get("pedido") || "";

const elementos = {

    tarjeta: document.getElementById(
        "tarjetaResultadoPago"
    ),

    icono: document.getElementById(
        "iconoResultadoPago"
    ),

    etiqueta: document.getElementById(
        "etiquetaResultadoPago"
    ),

    titulo: document.getElementById(
        "tituloResultadoPago"
    ),

    descripcion: document.getElementById(
        "descripcionResultadoPago"
    ),

    contenedorPedido: document.getElementById(
        "contenedorPedidoResultado"
    ),

    numeroPedido: document.getElementById(
        "numeroPedidoResultado"
    ),

    informacionRetiro: document.getElementById(
        "informacionRetiroResultado"
    ),

    informacionDespacho: document.getElementById(
        "informacionDespachoResultado"
    ),

    direccionDespacho: document.getElementById(
        "direccionDespachoResultado"
    ),

    detalleDespacho: document.getElementById(
        "detalleDespachoResultado"
    ),

    mensajeImportante: document.getElementById(
        "mensajeImportanteResultado"
    ),

    textoImportante: document.getElementById(
        "textoImportanteResultado"
    ),

    botonTienda: document.getElementById(
        "btnVolverTiendaResultado"
    ),

    botonImprimir: document.getElementById(
    "btnImprimirResultado"
)
};

const configuraciones = {

    aprobado: {
        clase: "aprobado",

        icono:
            '<i class="fa-solid fa-circle-check"></i>',

        etiqueta:
            "Pago aprobado",

        titulo:
            "¡Su compra fue realizada!",

        descripcion:
            "El pago fue autorizado correctamente y su pedido quedó registrado.",

        mensaje:
            "Conserve su número de pedido. Puede consultar su avance desde su cuenta."
    },

    rechazado: {
        clase: "rechazado",

        icono:
            '<i class="fa-solid fa-circle-xmark"></i>',

        etiqueta:
            "Pago rechazado",

        titulo:
            "El pago no fue autorizado",

        descripcion:
            "Webpay no pudo autorizar la transacción.",

        mensaje:
            "Los productos continúan en el carrito. Puede volver a intentarlo."
    },

    anulado: {
        clase: "anulado",

        icono:
            '<i class="fa-solid fa-circle-exclamation"></i>',

        etiqueta:
            "Pago no completado",

        titulo:
            "La operación fue cancelada",

        descripcion:
            "El pago fue anulado o se agotó el tiempo disponible en Webpay.",

        mensaje:
            "Los productos continúan guardados en el carrito."
    },

    reembolsado: {
        clase: "reembolsado",

        icono:
            '<i class="fa-solid fa-rotate-left"></i>',

        etiqueta:
            "Pago reembolsado",

        titulo:
            "La compra fue devuelta",

        descripcion:
            "El pago fue aprobado, pero no fue posible completar el pedido por falta de stock.",

        mensaje:
            "Transbank aceptó la devolución. El dinero se devolverá al mismo medio de pago utilizado."
    },

    reembolso_pendiente: {
        clase: "reembolso-pendiente",

        icono:
            '<i class="fa-solid fa-clock-rotate-left"></i>',

        etiqueta:
            "Reembolso en revisión",

        titulo:
            "Su devolución está siendo procesada",

        descripcion:
            "El pago fue aprobado, pero no fue posible completar el pedido por falta de stock.",

        mensaje:
            "La devolución automática no pudo confirmarse inmediatamente. AlianzaPro revisará la operación."
    },

    error: {
        clase: "error",

        icono:
            '<i class="fa-solid fa-triangle-exclamation"></i>',

        etiqueta:
            "No fue posible confirmar",

        titulo:
            "Ocurrió un problema",

        descripcion:
            "No fue posible mostrar el resultado definitivo de la transacción.",

        mensaje:
            "No vuelva a pagar inmediatamente. Consulte el pedido o comuníquese con AlianzaPro."
    }
};

inicializarResultadoPago();

    elementos.botonImprimir
        ?.addEventListener(
            "click",
            function () {
                window.print();
            }
        );

async function inicializarResultadoPago() {

    ocultarEntrega();

    /*
     * Si tenemos número de pedido, consultamos
     * el resultado real en el servidor.
     */

    if (numeroPedidoUrl) {

        const resultadoServidor =
            await consultarPedido();

        if (resultadoServidor) {

            mostrarResultado(
                resultadoServidor.resultado,
                resultadoServidor.pedido
            );

            return;
        }
    }

    /*
     * Si no fue posible consultar, usamos el
     * estado de la URL solamente como respaldo.
     */

    mostrarResultado(
        estadoUrl,
        null
    );
}

async function consultarPedido() {

    try {

        const response = await fetch(
            "./php/obtenerResultadoPedido.php" +
            "?pedido=" +
            encodeURIComponent(
                numeroPedidoUrl
            ),
            {
                cache: "no-store",
                credentials: "same-origin"
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            return null;
        }

        return resultado;

    } catch (error) {

        console.error(
            "Error consultando pedido:",
            error
        );

        return null;
    }
}

function mostrarResultado(estado, pedido) {

    const configuracion =
        configuraciones[estado] ||
        configuraciones.error;

    elementos.tarjeta.className =
        `tarjetaResultadoPago ${configuracion.clase}`;

    elementos.icono.innerHTML =
        configuracion.icono;

    elementos.etiqueta.textContent =
        configuracion.etiqueta;

    elementos.titulo.textContent =
        configuracion.titulo;

    elementos.descripcion.textContent =
        configuracion.descripcion;

    elementos.textoImportante.textContent =
        configuracion.mensaje;

    elementos.mensajeImportante.hidden =
        false;

    const numeroPedido =
        pedido?.numero_pedido ||
        numeroPedidoUrl;

    if (numeroPedido) {

        elementos.numeroPedido.textContent =
            numeroPedido;

        elementos.contenedorPedido.hidden =
            false;
    }

    elementos.botonImprimir.hidden =
    estado !== "aprobado";

    if (estado === "aprobado") {

        limpiarCarrito();
        mostrarEntrega(pedido);
        configurarBotonSeguirComprando();

        return;
    }

    if (
        estado === "reembolsado" ||
        estado === "reembolso_pendiente"
    ) {

        limpiarCarrito();
        ocultarEntrega();
        configurarBotonSeguirComprando();

        return;
    }

    conservarCarrito();
}

function mostrarEntrega(pedido) {

    ocultarEntrega();

    if (!pedido) {
        return;
    }

    if (
        pedido.tipo_entrega === "despacho"
    ) {

        elementos.informacionDespacho.hidden =
            false;

        const direccion = [
            pedido.direccion,
            pedido.comuna,
            pedido.region
        ]
            .filter(Boolean)
            .join(", ");

        elementos.direccionDespacho.textContent =
            direccion;

        elementos.detalleDespacho.textContent =
            "Le avisaremos cuando el pedido se encuentre en camino.";

        return;
    }

    elementos.informacionRetiro.hidden =
        false;
}

function ocultarEntrega() {

    if (elementos.informacionRetiro) {
        elementos.informacionRetiro.hidden =
            true;
    }

    if (elementos.informacionDespacho) {
        elementos.informacionDespacho.hidden =
            true;
    }
}

function limpiarCarrito() {

    localStorage.removeItem("carrito");

    sessionStorage.removeItem(
        "pedidoWebpay"
    );

    actualizarContadoresCarrito();
}

function conservarCarrito() {

    ocultarEntrega();

    elementos.botonTienda.innerHTML = `
        <i class="fa-solid fa-cart-shopping"></i>
        Volver al carrito
    `;
}

function configurarBotonSeguirComprando() {

    elementos.botonTienda.innerHTML = `
        <i class="fa-solid fa-bag-shopping"></i>
        Seguir comprando
    `;
}

function actualizarContadoresCarrito() {

    document
        .querySelectorAll(
            "#contadorCarrito"
        )
        .forEach(contador => {

            contador.textContent = "0";
        });
}