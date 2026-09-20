import Carrito from "./carrito.js";

const carrito = new Carrito();

let clienteAutenticado = null;
let modoAccesoCliente = "registro";
let tarifaDespachoActual = null;
let temporizadorTarifa = null;
let consultandoTarifa = false;
let correoRecuperacionActual = "";
let tokenRecuperacionActual = "";
let temporizadorRecuperacion = null;
let segundosRecuperacion = 600;
let direccionesCliente = [];
let idDireccionSeleccionada = 0;
let direccionTemporalRegistro = null;
let guardandoDireccion = false;

const elementos = {

    formulario: document.getElementById("formCheckout"),
    productos: document.getElementById("productosResumenCheckout"),
    cantidad: document.getElementById("cantidadResumenCheckout"),
    neto: document.getElementById("netoCheckout"),
    iva: document.getElementById("ivaCheckout"),
    despacho: document.getElementById("despachoCheckout"),
    total: document.getElementById("totalCheckout"),
    botonConfirmar: document.getElementById("btnConfirmarPedido"),
    mensaje: document.getElementById("mensajeCheckout"),
    observaciones: document.getElementById("observacionesCheckout"),
    contadorObservaciones: document.getElementById(
        "contadorObservacionesCheckout"
    ),

    rut: document.getElementById("rutCheckout"),

    telefono: document.getElementById("telefonoCheckout"),

    camposDespacho: document.getElementById(
        "camposDespachoCheckout"
    ),

    clienteNoConectado: document.getElementById(
        "clienteNoConectadoCheckout"
    ),

    clienteConectado: document.getElementById(
        "clienteConectadoCheckout"
    ),

    nombreSesionCliente: document.getElementById(
        "nombreSesionClienteCheckout"
    ),

    correoSesionCliente: document.getElementById(
        "correoSesionClienteCheckout"
    ),

    panelLogin: document.getElementById(
        "panelLoginCliente"
    ),

    panelRegistro: document.getElementById(
        "panelRegistroCliente"
    ),

    btnMostrarLogin: document.getElementById(
        "btnMostrarLoginCliente"
    ),

    btnMostrarRegistro: document.getElementById(
        "btnMostrarRegistroCliente"
    ),

    btnIngresarCliente: document.getElementById(
        "btnIngresarCliente"
    ),

    btnCerrarSesionCliente: document.getElementById(
        "btnCerrarSesionCliente"
    ),

    correoLogin: document.getElementById(
        "correoLoginCliente"
    ),

    passwordLogin: document.getElementById(
        "passwordLoginCliente"
    ),

    mensajeLogin: document.getElementById(
        "mensajeLoginCliente"
    ),

    camposPasswordRegistro: document.getElementById(
        "camposPasswordRegistroCliente"
    ),

    passwordRegistro: document.getElementById(
        "passwordRegistroCliente"
    ),

    confirmarPasswordRegistro: document.getElementById(
        "confirmarPasswordRegistroCliente"
    ),

    nombre: document.getElementById(
        "nombreCheckout"
    ),

    apellido: document.getElementById(
        "apellidoCheckout"
    ),

    correo: document.getElementById(
        "correoCheckout"
    ),

    region: document.querySelector(
        '#formDireccionCheckout input[name="region"]'
    ),

    comuna: document.getElementById(
        "comunaDireccionCheckout"
    ),

    calle: document.getElementById(
        "calleDireccionCheckout"
    ),

    numeroDireccion: document.getElementById(
        "numeroDireccionCheckout"
    ),

    departamento: document.getElementById(
        "departamentoDireccionCheckout"
    ),

    referencia: document.getElementById(
        "referenciaDireccionCheckout"
    ),
    btnRecuperarPassword: document.getElementById(
    "btnRecuperarPasswordCliente"
    ),

    dialogRecuperar: document.getElementById(
        "dialogRecuperarCliente"
    ),

    btnCerrarRecuperar: document.getElementById(
        "btnCerrarRecuperarCliente"
    ),

    formEnviarCodigo: document.getElementById(
        "formEnviarCodigoCliente"
    ),

    formVerificarCodigo: document.getElementById(
        "formVerificarCodigoCliente"
    ),

    formNuevaPassword: document.getElementById(
        "formNuevaPasswordCliente"
    ),

    correoRecuperacion: document.getElementById(
        "correoRecuperacionCliente"
    ),

    correoCodigo: document.getElementById(
        "correoCodigoCliente"
    ),

    codigoRecuperacion: document.getElementById(
        "codigoRecuperacionCliente"
    ),

    nuevaPassword: document.getElementById(
        "nuevaPasswordCliente"
    ),

    confirmarNuevaPassword: document.getElementById(
        "confirmarNuevaPasswordCliente"
    ),

    btnEnviarCodigo: document.getElementById(
        "btnEnviarCodigoCliente"
    ),

    btnVerificarCodigo: document.getElementById(
        "btnVerificarCodigoCliente"
    ),

    btnCambiarPassword: document.getElementById(
        "btnCambiarPasswordCliente"
    ),

    btnReenviarCodigo: document.getElementById(
        "btnReenviarCodigoCliente"
    ),

    mensajeRecuperacion: document.getElementById(
        "mensajeRecuperacionCliente"
    ),

    tiempoCodigo: document.getElementById(
        "tiempoCodigoCliente"
    ),

    pasoCorreo: document.getElementById(
        "pasoIndicadorCorreo"
    ),

    pasoCodigo: document.getElementById(
        "pasoIndicadorCodigo"
    ),

    pasoPassword: document.getElementById(
        "pasoIndicadorPassword"
    ),

    listaDirecciones: document.getElementById(
        "listaDireccionesCheckout"
    ),
    cargandoDirecciones: document.getElementById(
        "cargandoDireccionesCheckout"
    ),
    sinDirecciones: document.getElementById(
        "sinDireccionesCheckout"
    ),
    errorDireccion: document.getElementById(
        "errorDireccionCheckout"
    ),
    btnNuevaDireccion: document.getElementById(
        "btnNuevaDireccionCheckout"
    ),
    btnPrimeraDireccion: document.getElementById(
        "btnPrimeraDireccionCheckout"
    ),
    dialogDireccion: document.getElementById(
        "dialogDireccionCheckout"
    ),
    formDireccion: document.getElementById(
        "formDireccionCheckout"
    ),
    idDireccion: document.getElementById(
        "idDireccionCheckout"
    ),
    nombreDireccion: document.getElementById(
        "nombreDireccionCheckout"
    ),
    principalDireccion: document.getElementById(
        "principalDireccionCheckout"
    ),
    tituloDialogDireccion: document.getElementById(
        "tituloDialogDireccion"
    ),
    mensajeDireccion: document.getElementById(
        "mensajeDireccionCheckout"
    ),
    btnCerrarDireccion: document.getElementById(
        "btnCerrarDialogDireccion"
    ),
    btnCancelarDireccion: document.getElementById(
        "btnCancelarDireccionCheckout"
    ),
    btnGuardarDireccion: document.getElementById(
        "btnGuardarDireccionCheckout"
    ),
    contadorReferenciaDireccion: document.getElementById(
        "contadorReferenciaDireccion"
    )

};

const formatoCLP = new Intl.NumberFormat("es-CL", {
    style: "currency",
    currency: "CLP",
    minimumFractionDigits: 0
});

/* =====================================================
   INICIALIZACIÓN
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        configurarEntregaCheckout();
        renderizarResumenCheckout();
        configurarEventosCheckout();
        configurarEventosCuentaCliente();
        configurarEventosDireccionesCheckout();

        await comprobarSesionCliente();
    }
);

/* =====================================================
   RETIRO EN TIENDA
===================================================== */

function configurarEntregaCheckout() {

    const opcionesEntrega =
        document.querySelectorAll(
            'input[name="tipo_entrega"]'
        );

    opcionesEntrega.forEach(opcion => {

        opcion.disabled = false;

        opcion.addEventListener(
            "change",
            actualizarMetodoEntrega
        );
    });

    actualizarMetodoEntrega();
}

function actualizarMetodoEntrega() {

    const tipoEntrega =
        obtenerTipoEntrega();

    const esDespacho =
        tipoEntrega === "despacho";

    const informacionRetiro =
        document.getElementById(
            "informacionRetiroCheckout"
        );

    if (informacionRetiro) {
        informacionRetiro.hidden =
            esDespacho;

        informacionRetiro.style.display =
            esDespacho ? "none" : "flex";
    }

    if (elementos.camposDespacho) {

        elementos.camposDespacho.hidden =
            !esDespacho;

        elementos.camposDespacho.style.display =
            esDespacho ? "block" : "none";
    }

    [
        elementos.region,
        elementos.comuna,
        elementos.calle,
        elementos.numeroDireccion
    ].forEach(campo => {

        if (!campo) {
            return;
        }

        campo.disabled = !esDespacho;
        campo.required = esDespacho;
    });

    [
        elementos.departamento,
        elementos.referencia
    ].forEach(campo => {

        if (campo) {
            campo.disabled = !esDespacho;
        }
    });

    document
        .querySelectorAll(
            ".opcionEntregaCheckout"
        )
        .forEach(opcion => {

            const radio =
                opcion.querySelector(
                    'input[name="tipo_entrega"]'
                );

            opcion.classList.toggle(
                "activa",
                Boolean(radio?.checked)
            );
        });

    actualizarTotalesCheckout();
    if (esDespacho) {
        calcularTarifaDespacho(false);
    } else {
        tarifaDespachoActual = null;
        actualizarTextoOpcionDespacho();
    }
}


function invalidarTarifaDespacho() {

    tarifaDespachoActual = null;

    actualizarTextoOpcionDespacho();
    actualizarTotalesCheckout();
}

async function calcularTarifaDespacho(
    mostrarError = true
) {

    if (
        obtenerTipoEntrega() !== "despacho"
    ) {
        tarifaDespachoActual = null;

        actualizarTextoOpcionDespacho();
        actualizarTotalesCheckout();

        return true;
    }

    const region =
        elementos.region?.value.trim() || "";

    const comuna =
        elementos.comuna?.value.trim() || "";

    if (
        region === "" ||
        comuna.length < 2
    ) {
        tarifaDespachoActual = null;

        actualizarTextoOpcionDespacho();
        actualizarTotalesCheckout();

        return false;
    }

    if (consultandoTarifa) {
        return false;
    }

    consultandoTarifa = true;

    mostrarEstadoTarifa(
        "Calculando..."
    );

    try {

        const response = await fetch(
            "./php/calcularTarifaDespacho.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    region,
                    comuna
                })
            }
        );

        const resultado =
            await response.json();

        /*
         * Evita utilizar una respuesta antigua si
         * el cliente cambió la ubicación mientras
         * se consultaba.
         */

        const regionActual =
            elementos.region?.value.trim() || "";

        const comunaActual =
            elementos.comuna?.value.trim() || "";

        if (
            region !== regionActual ||
            comuna !== comunaActual
        ) {
            tarifaDespachoActual = null;
            return false;
        }

        if (
            !response.ok ||
            !resultado.ok
        ) {

            tarifaDespachoActual = null;

            actualizarTextoOpcionDespacho();
            actualizarTotalesCheckout();

            if (mostrarError) {

                mostrarMensajeCheckout(
                    resultado.mensaje ||
                        "No fue posible calcular el despacho.",
                    false
                );
            }

            return false;
        }

        tarifaDespachoActual = {
            id_tarifa:
                Number(
                    resultado.tarifa.id_tarifa
                ),

            costo:
                Number(
                    resultado.tarifa.costo
                ),

            plazo_minimo_dias:
                Number(
                    resultado.tarifa
                        .plazo_minimo_dias
                ),

            plazo_maximo_dias:
                Number(
                    resultado.tarifa
                        .plazo_maximo_dias
                )
        };

        if (
            !Number.isFinite(
                tarifaDespachoActual.costo
            ) ||
            tarifaDespachoActual.costo < 5000
        ) {
            tarifaDespachoActual = null;

            throw new Error(
                "La tarifa de despacho recibida no es válida."
            );
        }

        actualizarTextoOpcionDespacho();
        actualizarTotalesCheckout();

        return true;

    } catch (error) {

        console.error(
            "Error calculando tarifa:",
            error
        );

        tarifaDespachoActual = null;

        actualizarTextoOpcionDespacho();
        actualizarTotalesCheckout();

        if (mostrarError) {

            mostrarMensajeCheckout(
                error.message ||
                    "No fue posible calcular el despacho.",
                false
            );
        }

        return false;

    } finally {

        consultandoTarifa = false;
    }
}

function mostrarEstadoTarifa(texto) {

    const opcionDespacho =
        document.querySelector(
            'input[name="tipo_entrega"]' +
            '[value="despacho"]'
        )?.closest(
            ".opcionEntregaCheckout"
        );

    const textoTarifa =
        opcionDespacho?.querySelector("em");

    if (textoTarifa) {
        textoTarifa.textContent = texto;
    }

    if (elementos.despacho) {
        elementos.despacho.textContent = texto;
    }
}

function actualizarTextoOpcionDespacho() {

    const opcionDespacho =
        document.querySelector(
            'input[name="tipo_entrega"]' +
            '[value="despacho"]'
        )?.closest(
            ".opcionEntregaCheckout"
        );

    const textoTarifa =
        opcionDespacho?.querySelector("em");

    if (!textoTarifa) {
        return;
    }

    if (!tarifaDespachoActual) {

        textoTarifa.textContent =
            "Por calcular";

        return;
    }

    const minimo =
        tarifaDespachoActual
            .plazo_minimo_dias;

    const maximo =
        tarifaDespachoActual
            .plazo_maximo_dias;

    textoTarifa.textContent =
        `${formatoCLP.format(
            tarifaDespachoActual.costo
        )} · ${minimo} a ${maximo} días`;
}



function obtenerTipoEntrega() {

    return (
        document.querySelector(
            'input[name="tipo_entrega"]:checked'
        )?.value || "retiro"
    );
}

/* =====================================================
   EVENTOS
===================================================== */

function configurarEventosCheckout() {
    if (elementos.formulario) {
        elementos.formulario.addEventListener(
            "submit",
            enviarPedido
        );
    }

    if (elementos.observaciones) {
        elementos.observaciones.addEventListener(
            "input",
            actualizarContadorObservaciones
        );

        actualizarContadorObservaciones();
    }

    if (elementos.rut) {
        elementos.rut.addEventListener("input", function () {
            this.value = formatearRut(this.value);
        });
    }

    if (elementos.telefono) {
        elementos.telefono.addEventListener("input", function () {
            this.value = this.value.replace(/\D/g, "").slice(0, 9);
        });
    }

    if (elementos.region) {

        elementos.region.addEventListener(
            "change",
            function () {

                invalidarTarifaDespacho();
                calcularTarifaDespacho();
            }
        );
    }

    if (elementos.comuna) {

        elementos.comuna.addEventListener(
            "input",
            function () {

                invalidarTarifaDespacho();

                clearTimeout(
                    temporizadorTarifa
                );

                temporizadorTarifa =
                    setTimeout(() => {
                        calcularTarifaDespacho(false);
                    }, 600);
            }
        );

        elementos.comuna.addEventListener(
            "blur",
            function () {
                calcularTarifaDespacho(false);
            }
        );
    }

}


function configurarEventosCuentaCliente() {

    elementos.btnMostrarRegistro
        ?.addEventListener(
            "click",
            mostrarRegistroCliente
        );

    elementos.btnMostrarLogin
        ?.addEventListener(
            "click",
            mostrarLoginCliente
        );

    elementos.btnIngresarCliente
        ?.addEventListener(
            "click",
            iniciarSesionDesdeCheckout
        );

    elementos.btnCerrarSesionCliente
        ?.addEventListener(
            "click",
            cerrarSesionDesdeCheckout
        );

    elementos.passwordLogin
        ?.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Enter") {
                    iniciarSesionDesdeCheckout();
                }
            }
        );

    document
        .querySelectorAll(
            ".btnVerPasswordCheckout"
        )
        .forEach(boton => {

            boton.addEventListener(
                "click",
                function () {

                    alternarVisibilidadPassword(
                        this
                    );
                }
            );
        });

        elementos.btnRecuperarPassword
    ?.addEventListener(
        "click",
        abrirRecuperacionCliente
    );

elementos.btnCerrarRecuperar
    ?.addEventListener(
        "click",
        cerrarRecuperacionCliente
    );

elementos.formEnviarCodigo
    ?.addEventListener(
        "submit",
        enviarCodigoRecuperacion
    );

elementos.formVerificarCodigo
    ?.addEventListener(
        "submit",
        verificarCodigoRecuperacion
    );

elementos.formNuevaPassword
    ?.addEventListener(
        "submit",
        cambiarPasswordRecuperacion
    );

elementos.btnReenviarCodigo
    ?.addEventListener(
        "click",
        reenviarCodigoRecuperacion
    );

    elementos.codigoRecuperacion
        ?.addEventListener(
            "input",
            function () {

                this.value = this.value
                    .replace(/\D/g, "")
                    .slice(0, 6);
            }
        );

    document
        .querySelectorAll(
            ".btnVerPasswordRecuperacion"
        )
        .forEach(boton => {

            boton.addEventListener(
                "click",
                function () {

                    alternarVisibilidadPassword(
                        this
                    );
                }
            );
        });
}



/* =====================================================
   DIRECCIONES DEL CLIENTE
===================================================== */

function configurarEventosDireccionesCheckout() {
    elementos.btnNuevaDireccion?.addEventListener(
        "click",
        () => abrirDialogDireccion()
    );

    elementos.btnPrimeraDireccion?.addEventListener(
        "click",
        () => abrirDialogDireccion()
    );

    elementos.btnCerrarDireccion?.addEventListener(
        "click",
        cerrarDialogDireccion
    );

    elementos.btnCancelarDireccion?.addEventListener(
        "click",
        cerrarDialogDireccion
    );

    elementos.formDireccion?.addEventListener(
        "submit",
        guardarDireccionDesdeCheckout
    );

    elementos.referencia?.addEventListener(
        "input",
        actualizarContadorReferencia
    );

    elementos.dialogDireccion?.addEventListener(
        "click",
        function (event) {
            if (event.target === elementos.dialogDireccion) {
                cerrarDialogDireccion();
            }
        }
    );
}

async function cargarDireccionesCliente(idPreferido = 0) {
    if (!clienteAutenticado) {
        direccionesCliente = direccionTemporalRegistro
            ? [direccionTemporalRegistro]
            : [];

        renderizarDireccionesCliente();
        return;
    }

    mostrarCargandoDirecciones(true);

    try {
        const response = await fetch(
            "./php/obtenerDireccionesCliente.php",
            {
                cache: "no-store",
                credentials: "same-origin"
            }
        );

        const resultado = await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible cargar las direcciones."
            );
        }

        direccionesCliente = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        let seleccionada = direccionesCliente.find(
            direccion =>
                Number(direccion.id_direccion) ===
                Number(idPreferido)
        );

        if (!seleccionada) {
            seleccionada = direccionesCliente.find(
                direccion => Boolean(direccion.es_principal)
            ) || direccionesCliente[0] || null;
        }

        idDireccionSeleccionada = seleccionada
            ? Number(seleccionada.id_direccion)
            : 0;

        renderizarDireccionesCliente();

        if (seleccionada) {
            aplicarDireccionSeleccionada(seleccionada, false);
        }
    } catch (error) {
        console.error("Error cargando direcciones:", error);

        direccionesCliente = [];
        idDireccionSeleccionada = 0;
        renderizarDireccionesCliente();

        mostrarMensajeCheckout(error.message, false);
    } finally {
        mostrarCargandoDirecciones(false);
    }
}

function mostrarCargandoDirecciones(mostrar) {
    if (elementos.cargandoDirecciones) {
        elementos.cargandoDirecciones.hidden = !mostrar;
    }

    if (elementos.listaDirecciones && mostrar) {
        elementos.listaDirecciones.hidden = true;
    }
}

function renderizarDireccionesCliente() {
    if (!elementos.listaDirecciones) {
        return;
    }

    elementos.listaDirecciones.innerHTML = "";

    const hayDirecciones = direccionesCliente.length > 0;

    elementos.listaDirecciones.hidden = !hayDirecciones;

    if (elementos.sinDirecciones) {
        elementos.sinDirecciones.hidden = hayDirecciones;
    }

    if (elementos.btnNuevaDireccion) {
        elementos.btnNuevaDireccion.hidden = !hayDirecciones;
    }

    direccionesCliente.forEach(direccion => {
        elementos.listaDirecciones.appendChild(
            crearTarjetaDireccion(direccion)
        );
    });

    if (!hayDirecciones) {
        invalidarTarifaDespacho();
    }
}

function crearTarjetaDireccion(direccion) {
    const esTemporal = Boolean(direccion.temporal);
    const id = esTemporal
        ? -1
        : Number(direccion.id_direccion);

    const seleccionada = idDireccionSeleccionada === id;

    const tarjeta = document.createElement("article");
    tarjeta.className = "tarjetaDireccionCheckout";
    tarjeta.classList.toggle("seleccionada", seleccionada);

    const etiqueta = document.createElement("label");
    etiqueta.className = "contenidoTarjetaDireccion";

    const radio = document.createElement("input");
    radio.type = "radio";
    radio.name = "id_direccion";
    radio.value = String(id);
    radio.checked = seleccionada;

    radio.addEventListener("change", () => {
        seleccionarDireccionCheckout(direccion);
    });

    const selector = document.createElement("span");
    selector.className = "selectorDireccionCheckout";

    const icono = document.createElement("span");
    icono.className = "iconoDireccionCheckout";
    icono.innerHTML = '<i class="fa-solid fa-house"></i>';

    const datos = document.createElement("span");
    datos.className = "datosDireccionCheckout";

    const nombre = document.createElement("strong");
    nombre.textContent = direccion.nombre_direccion || "Dirección";

    const domicilio = document.createElement("span");
    domicilio.textContent = obtenerTextoDireccion(direccion);

    const ubicacion = document.createElement("small");
    ubicacion.textContent = `${direccion.comuna}, ${direccion.region}`;

    datos.append(nombre, domicilio, ubicacion);

    if (direccion.es_principal) {
        const principal = document.createElement("em");
        principal.innerHTML =
            '<i class="fa-solid fa-star"></i> Principal';
        datos.appendChild(principal);
    }

    etiqueta.append(radio, selector, icono, datos);

    /* =================================================
   ACCIONES DE LA DIRECCIÓN
================================================= */

const acciones =
    document.createElement("div");

acciones.className =
    "accionesDireccionCheckout";

/* =================================================
   EDITAR
================================================= */

const editar =
    document.createElement("button");

editar.type = "button";

editar.className =
    "btnEditarDireccionCheckout";

editar.title =
    "Editar dirección";

editar.setAttribute(
    "aria-label",
    "Editar dirección"
);

editar.innerHTML =
    '<i class="fa-solid fa-pen"></i>';

editar.addEventListener(
    "click",
    function (evento) {

        evento.preventDefault();
        evento.stopPropagation();

        abrirDialogDireccion(
            direccion
        );
    }
);

acciones.appendChild(editar);

/* =================================================
   ELIMINAR
================================================= */

/*
 * La dirección principal no puede eliminarse.
 * La dirección temporal tampoco se elimina desde PHP.
 */

if (
    !direccion.es_principal &&
    !direccion.temporal
) {

    const eliminar =
        document.createElement("button");

    eliminar.type = "button";

    eliminar.className =
        "btnEliminarDireccionCheckout";

    eliminar.title =
        "Eliminar dirección";

    eliminar.setAttribute(
        "aria-label",
        "Eliminar dirección"
    );

    eliminar.innerHTML =
        '<i class="fa-solid fa-trash-can"></i>';

    eliminar.addEventListener(
        "click",
        function (evento) {

            evento.preventDefault();
            evento.stopPropagation();

            eliminarDireccionCheckout(
                direccion,
                eliminar
            );
        }
    );

    acciones.appendChild(eliminar);
}

tarjeta.append(
    etiqueta,
    acciones
);
    return tarjeta;
}

function obtenerTextoDireccion(direccion) {
    let texto = `${direccion.calle || ""} ${
        direccion.numero || ""
    }`.trim();

    if (direccion.departamento) {
        texto += `, ${direccion.departamento}`;
    }

    return texto;
}

function seleccionarDireccionCheckout(direccion) {
    idDireccionSeleccionada = direccion.temporal
        ? -1
        : Number(direccion.id_direccion);

    aplicarDireccionSeleccionada(direccion, true);
    renderizarDireccionesCliente();
}

function aplicarDireccionSeleccionada(direccion, recalcular = true) {
    if (elementos.region) {
        elementos.region.value = direccion.region || "";
    }

    if (elementos.comuna) {
        elementos.comuna.value = direccion.comuna || "";
    }

    if (elementos.calle) {
        elementos.calle.value = direccion.calle || "";
    }

    if (elementos.numeroDireccion) {
        elementos.numeroDireccion.value = direccion.numero || "";
    }

    if (elementos.departamento) {
        elementos.departamento.value = direccion.departamento || "";
    }

    if (elementos.referencia) {
        elementos.referencia.value = direccion.referencia || "";
    }

    if (elementos.errorDireccion) {
        elementos.errorDireccion.textContent = "";
    }

    if (recalcular) {
        invalidarTarifaDespacho();

        if (obtenerTipoEntrega() === "despacho") {
            calcularTarifaDespacho(false);
        }
    }
}

function obtenerDireccionSeleccionada() {
    if (idDireccionSeleccionada === -1) {
        return direccionTemporalRegistro;
    }

    return direccionesCliente.find(
        direccion =>
            Number(direccion.id_direccion) ===
            Number(idDireccionSeleccionada)
    ) || null;
}

function abrirDialogDireccion(
    direccion = null
) {

    if (
        !elementos.dialogDireccion ||
        !elementos.formDireccion
    ) {

        console.error(
            "No se encontró el diálogo de dirección."
        );

        return;
    }

    const camposRequeridos = {
        idDireccion:
            elementos.idDireccion,

        nombreDireccion:
            elementos.nombreDireccion,

        region:
            elementos.region,

        comuna:
            elementos.comuna,

        calle:
            elementos.calle,

        numeroDireccion:
            elementos.numeroDireccion,

        departamento:
            elementos.departamento,

        referencia:
            elementos.referencia,

        principalDireccion:
            elementos.principalDireccion,

        titulo:
            elementos.tituloDialogDireccion
    };

    const camposFaltantes =
        Object.entries(camposRequeridos)
            .filter(
                ([, elemento]) =>
                    !elemento
            )
            .map(
                ([nombre]) =>
                    nombre
            );

    if (camposFaltantes.length > 0) {

        console.error(
            "Faltan campos del diálogo:",
            camposFaltantes
        );

        mostrarMensajeCheckout(
            "No fue posible abrir el formulario de dirección.",
            false
        );

        return;
    }

    elementos.formDireccion.reset();

    elementos.idDireccion.value =
        direccion?.temporal
            ? ""
            : direccion?.id_direccion || "";

    elementos.nombreDireccion.value =
        direccion?.nombre_direccion || "";

    elementos.region.value =
        direccion?.region ||
        "Región Metropolitana";

    elementos.comuna.value =
        direccion?.comuna || "";

    elementos.calle.value =
        direccion?.calle || "";

    elementos.numeroDireccion.value =
        direccion?.numero || "";

    elementos.departamento.value =
        direccion?.departamento || "";

    elementos.referencia.value =
        direccion?.referencia || "";

    elementos.principalDireccion.checked =
        Boolean(
            direccion?.es_principal
        );

    elementos.tituloDialogDireccion
        .textContent =
            direccion
                ? "Editar dirección"
                : "Agregar dirección";

    limpiarMensajeDireccion();

    actualizarContadorReferencia();

    elementos.dialogDireccion.showModal();

    elementos.nombreDireccion.focus();
}

function cerrarDialogDireccion() {
    if (elementos.dialogDireccion?.open) {
        elementos.dialogDireccion.close();
    }

    limpiarMensajeDireccion();

    const direccionSeleccionada =
        obtenerDireccionSeleccionada();

    if (direccionSeleccionada) {
        aplicarDireccionSeleccionada(
            direccionSeleccionada,
            false
        );
    }
}

async function guardarDireccionDesdeCheckout(event) {
    event.preventDefault();

    if (guardandoDireccion) {
        return;
    }

    const direccion = {
        id_direccion: Number(elementos.idDireccion.value) || 0,
        nombre_direccion:
            elementos.nombreDireccion.value.trim() || "Dirección",
        region: "Región Metropolitana",
        comuna: elementos.comuna.value.trim(),
        calle: elementos.calle.value.trim(),
        numero: elementos.numeroDireccion.value.trim(),
        departamento: elementos.departamento.value.trim(),
        referencia: elementos.referencia.value.trim(),
        es_principal: elementos.principalDireccion.checked
    };

    if (
        direccion.comuna.length < 2 ||
        direccion.calle.length < 2 ||
        direccion.numero === ""
    ) {
        mostrarMensajeDireccion(
            "Complete la comuna, la calle y el número.",
            false
        );
        return;
    }

    if (!clienteAutenticado) {
        direccion.temporal = true;
        direccion.id_direccion = -1;
        direccion.es_principal = true;

        direccionTemporalRegistro = direccion;
        direccionesCliente = [direccion];
        idDireccionSeleccionada = -1;

        aplicarDireccionSeleccionada(direccion, true);
        renderizarDireccionesCliente();
        cerrarDialogDireccion();
        return;
    }

    guardandoDireccion = true;
    bloquearBotonGuardarDireccion(true);

    try {
        const response = await fetch(
            "./php/guardarDireccionCliente.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify(direccion)
            }
        );

        const resultado = await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible guardar la dirección."
            );
        }

        cerrarDialogDireccion();

        await cargarDireccionesCliente(
            Number(resultado.direccion?.id_direccion) || 0
        );

        mostrarMensajeCheckout(resultado.mensaje, true);
    } catch (error) {
        mostrarMensajeDireccion(error.message, false);
    } finally {
        guardandoDireccion = false;
        bloquearBotonGuardarDireccion(false);
    }
}


/* =====================================================
   ELIMINAR DIRECCIÓN
===================================================== */

async function eliminarDireccionCheckout(
    direccion,
    boton
) {

    const idDireccion =
        Number(
            direccion.id_direccion
        );

    if (idDireccion <= 0) {

        mostrarMensajeCheckout(
            "La dirección seleccionada no es válida.",
            false
        );

        return;
    }

    if (direccion.es_principal) {

        mostrarMensajeCheckout(
            "La dirección principal no puede eliminarse.",
            false
        );

        return;
    }

    const nombre =
        direccion.nombre_direccion ||
        obtenerTextoDireccion(direccion);

    const confirmar = window.confirm(
        `¿Desea eliminar la dirección "${nombre}"?`
    );

    if (!confirmar) {
        return;
    }

    const eraSeleccionada =
        Number(
            idDireccionSeleccionada
        ) === idDireccion;

    /*
     * Si no estamos eliminando la seleccionada,
     * conservamos la selección actual.
     */

    const idParaConservar =
        eraSeleccionada
            ? 0
            : idDireccionSeleccionada;

    const contenidoAnterior =
        boton.innerHTML;

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
    `;

    try {

        const response = await fetch(
            "./php/eliminarDireccionCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify({
                    id_direccion:
                        idDireccion
                })
            }
        );

        const resultado =
            await response.json();

        if (
            !response.ok ||
            !resultado.ok
        ) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible eliminar la dirección."
            );
        }

        if (eraSeleccionada) {

            idDireccionSeleccionada = 0;

            tarifaDespachoActual = null;
        }

        /*
         * Si se eliminó la dirección seleccionada,
         * cargarDireccionesCliente() elegirá la principal.
         */

        await cargarDireccionesCliente(
            idParaConservar
        );

        mostrarMensajeCheckout(
            resultado.mensaje,
            true
        );

        if (
            obtenerTipoEntrega() ===
            "despacho"
        ) {
            await calcularTarifaDespacho(
                false
            );
        }

    } catch (error) {

        console.error(
            "Error eliminando dirección:",
            error
        );

        mostrarMensajeCheckout(
            error.message ||
            "No fue posible eliminar la dirección.",
            false
        );

        boton.disabled = false;
        boton.innerHTML =
            contenidoAnterior;
    }
}


function bloquearBotonGuardarDireccion(bloquear) {
    if (!elementos.btnGuardarDireccion) {
        return;
    }

    elementos.btnGuardarDireccion.disabled = bloquear;
    elementos.btnGuardarDireccion.innerHTML = bloquear
        ? '<i class="fa-solid fa-spinner fa-spin"></i><span>Guardando...</span>'
        : '<i class="fa-solid fa-floppy-disk"></i><span>Guardar dirección</span>';
}

function actualizarContadorReferencia() {
    if (elementos.contadorReferenciaDireccion) {
        elementos.contadorReferenciaDireccion.textContent =
            elementos.referencia?.value.length || 0;
    }
}

function mostrarMensajeDireccion(mensaje, correcto) {
    if (!elementos.mensajeDireccion) {
        return;
    }

    elementos.mensajeDireccion.className = correcto
        ? "mensajeDireccionCheckout correcto"
        : "mensajeDireccionCheckout error";
    elementos.mensajeDireccion.textContent = mensaje;
}

function limpiarMensajeDireccion() {
    if (!elementos.mensajeDireccion) {
        return;
    }

    elementos.mensajeDireccion.className =
        "mensajeDireccionCheckout";
    elementos.mensajeDireccion.textContent = "";
}

/* =====================================================
   RECUPERACIÓN DE CONTRASEÑA
===================================================== */

function abrirRecuperacionCliente() {

    reiniciarRecuperacionCliente();

    /*
     * Si el cliente ya escribió su correo
     * en el login, lo copiamos.
     */

    const correoLogin =
        elementos.correoLogin
            ?.value.trim() || "";

    if (validarCorreo(correoLogin)) {
        elementos.correoRecuperacion.value =
            correoLogin;
    }

    elementos.dialogRecuperar.showModal();

    elementos.correoRecuperacion.focus();
}

function cerrarRecuperacionCliente() {

    detenerTemporizadorRecuperacion();

    elementos.dialogRecuperar.close();
}

function reiniciarRecuperacionCliente() {

    correoRecuperacionActual = "";
    tokenRecuperacionActual = "";

    detenerTemporizadorRecuperacion();

    elementos.formEnviarCodigo?.reset();
    elementos.formVerificarCodigo?.reset();
    elementos.formNuevaPassword?.reset();

    mostrarPasoRecuperacion(1);

    if (elementos.mensajeRecuperacion) {

        elementos.mensajeRecuperacion.className =
            "mensajeRecuperacionCliente";

        elementos.mensajeRecuperacion.textContent =
            "";
    }
}

function mostrarPasoRecuperacion(paso) {

    elementos.formEnviarCodigo.hidden =
        paso !== 1;

    elementos.formVerificarCodigo.hidden =
        paso !== 2;

    elementos.formNuevaPassword.hidden =
        paso !== 3;

    elementos.pasoCorreo.className =
        paso === 1
            ? "activo"
            : paso > 1
                ? "completado"
                : "";

    elementos.pasoCodigo.className =
        paso === 2
            ? "activo"
            : paso > 2
                ? "completado"
                : "";

    elementos.pasoPassword.className =
        paso === 3
            ? "activo"
            : "";

    limpiarMensajeRecuperacion();
}

/* =====================================================
   ENVIAR CÓDIGO
===================================================== */

async function enviarCodigoRecuperacion(
    event
) {

    event.preventDefault();

    const correo =
        elementos.correoRecuperacion
            .value.trim()
            .toLowerCase();

    if (!validarCorreo(correo)) {

        mostrarMensajeRecuperacion(
            "Ingrese un correo electrónico válido.",
            "error"
        );

        return;
    }

    bloquearBotonRecuperacion(
        elementos.btnEnviarCodigo,
        true,
        "Enviando..."
    );

    try {

        const response = await fetch(
            "./php/enviarCodigoCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    correo
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible enviar el código."
            );
        }

        correoRecuperacionActual =
            correo;

        elementos.correoCodigo.textContent =
            correo;

        mostrarPasoRecuperacion(2);

        iniciarTemporizadorRecuperacion();

        mostrarMensajeRecuperacion(
            resultado.mensaje,
            "correcto"
        );

        elementos.codigoRecuperacion.focus();

    } catch (error) {

        mostrarMensajeRecuperacion(
            error.message,
            "error"
        );

    } finally {

        restaurarBotonRecuperacion(
            elementos.btnEnviarCodigo,
            `
                <i class="fa-solid fa-paper-plane"></i>
                Enviar código
            `
        );
    }
}

/* =====================================================
   VERIFICAR CÓDIGO
===================================================== */

async function verificarCodigoRecuperacion(
    event
) {

    event.preventDefault();

    const codigo =
        elementos.codigoRecuperacion
            .value.trim();

    if (!/^[0-9]{6}$/.test(codigo)) {

        mostrarMensajeRecuperacion(
            "Ingrese el código de 6 números.",
            "error"
        );

        return;
    }

    bloquearBotonRecuperacion(
        elementos.btnVerificarCodigo,
        true,
        "Verificando..."
    );

    try {

        const response = await fetch(
            "./php/verificarCodigoCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    correo:
                        correoRecuperacionActual,
                    codigo
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "El código no pudo verificarse."
            );
        }

        tokenRecuperacionActual =
            resultado.token_recuperacion;

        detenerTemporizadorRecuperacion();

        mostrarPasoRecuperacion(3);

        mostrarMensajeRecuperacion(
            resultado.mensaje,
            "correcto"
        );

        elementos.nuevaPassword.focus();

    } catch (error) {

        mostrarMensajeRecuperacion(
            error.message,
            "error"
        );

    } finally {

        restaurarBotonRecuperacion(
            elementos.btnVerificarCodigo,
            `
                <i class="fa-solid fa-check"></i>
                Verificar código
            `
        );
    }
}

/* =====================================================
   CAMBIAR CONTRASEÑA
===================================================== */

async function cambiarPasswordRecuperacion(
    event
) {

    event.preventDefault();

    const password =
        elementos.nuevaPassword.value;

    const confirmarPassword =
        elementos.confirmarNuevaPassword
            .value;

    if (password.length < 8) {

        mostrarMensajeRecuperacion(
            "La contraseña debe tener al menos 8 caracteres.",
            "error"
        );

        return;
    }

    if (
        !/[A-Z]/.test(password) ||
        !/[a-z]/.test(password) ||
        !/[0-9]/.test(password)
    ) {

        mostrarMensajeRecuperacion(
            "La contraseña debe incluir mayúscula, minúscula y número.",
            "error"
        );

        return;
    }

    if (password !== confirmarPassword) {

        mostrarMensajeRecuperacion(
            "Las contraseñas no coinciden.",
            "error"
        );

        return;
    }

    if (!tokenRecuperacionActual) {

        mostrarMensajeRecuperacion(
            "La autorización ha expirado. Solicite un código nuevo.",
            "error"
        );

        return;
    }

    bloquearBotonRecuperacion(
        elementos.btnCambiarPassword,
        true,
        "Guardando..."
    );

    try {

        const response = await fetch(
            "./php/cambiarPasswordCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify({
                    correo:
                        correoRecuperacionActual,

                    token_recuperacion:
                        tokenRecuperacionActual,

                    password,

                    confirmarPassword
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible cambiar la contraseña."
            );
        }

        elementos.dialogRecuperar.close();

        await comprobarSesionCliente();

        mostrarMensajeCheckout(
            "Contraseña actualizada. Su sesión fue iniciada correctamente.",
            true
        );

        reiniciarRecuperacionCliente();

    } catch (error) {

        mostrarMensajeRecuperacion(
            error.message,
            "error"
        );

    } finally {

        restaurarBotonRecuperacion(
            elementos.btnCambiarPassword,
            `
                <i class="fa-solid fa-lock"></i>
                Guardar contraseña
            `
        );
    }
}

/* =====================================================
   REENVIAR CÓDIGO
===================================================== */

async function reenviarCodigoRecuperacion() {

    if (!correoRecuperacionActual) {
        mostrarPasoRecuperacion(1);
        return;
    }

    bloquearBotonRecuperacion(
        elementos.btnReenviarCodigo,
        true,
        "Reenviando..."
    );

    try {

        const response = await fetch(
            "./php/enviarCodigoCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    correo:
                        correoRecuperacionActual
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible reenviar el código."
            );
        }

        elementos.codigoRecuperacion.value =
            "";

        iniciarTemporizadorRecuperacion();

        mostrarMensajeRecuperacion(
            resultado.mensaje,
            "correcto"
        );

    } catch (error) {

        mostrarMensajeRecuperacion(
            error.message,
            "error"
        );

    } finally {

        restaurarBotonRecuperacion(
            elementos.btnReenviarCodigo,
            "Reenviar código"
        );
    }
}

/* =====================================================
   TEMPORIZADOR
===================================================== */

function iniciarTemporizadorRecuperacion() {

    detenerTemporizadorRecuperacion();

    segundosRecuperacion = 600;

    actualizarTiempoRecuperacion();

    temporizadorRecuperacion =
        setInterval(() => {

            segundosRecuperacion--;

            actualizarTiempoRecuperacion();

            if (segundosRecuperacion <= 0) {

                detenerTemporizadorRecuperacion();

                mostrarMensajeRecuperacion(
                    "El código ha expirado. Solicite uno nuevo.",
                    "error"
                );
            }

        }, 1000);
}

function actualizarTiempoRecuperacion() {

    const minutos = Math.floor(
        segundosRecuperacion / 60
    );

    const segundos =
        segundosRecuperacion % 60;

    elementos.tiempoCodigo.textContent =
        `${String(minutos).padStart(2, "0")}:` +
        `${String(segundos).padStart(2, "0")}`;
}

function detenerTemporizadorRecuperacion() {

    if (temporizadorRecuperacion) {

        clearInterval(
            temporizadorRecuperacion
        );

        temporizadorRecuperacion = null;
    }
}

/* =====================================================
   UTILIDADES DEL DIÁLOGO
===================================================== */

function mostrarMensajeRecuperacion(
    mensaje,
    tipo
) {

    elementos.mensajeRecuperacion.className =
        `mensajeRecuperacionCliente ${tipo}`;

    elementos.mensajeRecuperacion.textContent =
        mensaje;
}

function limpiarMensajeRecuperacion() {

    elementos.mensajeRecuperacion.className =
        "mensajeRecuperacionCliente";

    elementos.mensajeRecuperacion.textContent =
        "";
}

function bloquearBotonRecuperacion(
    boton,
    bloquear,
    texto
) {

    boton.disabled = bloquear;

    if (bloquear) {

        boton.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            ${texto}
        `;
    }
}

function restaurarBotonRecuperacion(
    boton,
    contenido
) {

    boton.disabled = false;
    boton.innerHTML = contenido;
}

function mostrarRegistroCliente() {

    modoAccesoCliente = "registro";

    elementos.panelRegistro.hidden = false;
    elementos.panelLogin.hidden = true;

    elementos.btnMostrarRegistro
        .classList.add("activo");

    elementos.btnMostrarLogin
        .classList.remove("activo");

    actualizarCamposSegunSesion();
}

function mostrarLoginCliente() {

    modoAccesoCliente = "login";

    elementos.panelRegistro.hidden = true;
    elementos.panelLogin.hidden = false;

    elementos.btnMostrarRegistro
        .classList.remove("activo");

    elementos.btnMostrarLogin
        .classList.add("activo");

    actualizarCamposSegunSesion();

    elementos.correoLogin?.focus();
}

function alternarVisibilidadPassword(boton) {

    const idCampo =
        boton.dataset.campo;

    const campo =
        document.getElementById(idCampo);

    if (!campo) {
        return;
    }

    const mostrar =
        campo.type === "password";

    campo.type =
        mostrar ? "text" : "password";

    const icono =
        boton.querySelector("i");

    if (icono) {

        icono.className = mostrar
            ? "fa-solid fa-eye-slash"
            : "fa-solid fa-eye";
    }

    boton.title = mostrar
        ? "Ocultar contraseña"
        : "Mostrar contraseña";
}

/* =====================================================
   MOSTRAR PRODUCTOS
===================================================== */

function renderizarResumenCheckout() {
    const productos = carrito.obtenerCarrito();

    if (!elementos.productos) {
        return;
    }

    elementos.productos.innerHTML = "";

    if (productos.length === 0) {
        mostrarCarritoVacio();
        return;
    }

    productos.forEach(producto => {
        elementos.productos.appendChild(
            crearProductoResumen(producto)
        );
    });

    actualizarTotalesCheckout();
}

function crearProductoResumen(producto) {
    const cantidad = Number(producto.cant) || 1;
    const precio = Number(producto.precio) || 0;
    const totalLinea = precio * cantidad;

    const contenedor = document.createElement("article");
    contenedor.className = "productoResumenCheckout";

    const imagen = document.createElement("img");
    imagen.className = "imagenProductoCheckout";
    imagen.src = `./images/productos/${producto.id_producto}.webp`;
    imagen.alt = producto.nombre || "Producto";

    imagen.onerror = function () {
        this.onerror = null;
        this.src = "./images/productos/no-image.webp";
    };

    const informacion = document.createElement("div");
    informacion.className = "informacionProductoCheckout";

    const nombre = document.createElement("strong");
    nombre.textContent = producto.nombre || "Producto";

    const marca = document.createElement("small");
    marca.textContent = producto.marca ? ` ${producto.marca}` : "Marca no especificada";

    const detalle = document.createElement("span");
    detalle.textContent =
        ` ${cantidad} × ${formatoCLP.format(precio)}`;

    informacion.append(nombre, marca, detalle);

    const precioProducto = document.createElement("strong");
    precioProducto.className = "totalProductoCheckout";
    precioProducto.textContent = formatoCLP.format(totalLinea);

    contenedor.append(
        imagen,
        informacion,
        precioProducto
    );

    return contenedor;
}

/* =====================================================
   TOTALES
===================================================== */

function actualizarTotalesCheckout() {
    const productos = carrito.obtenerCarrito();

    const cantidadProductos = productos.reduce(
        (acumulado, producto) =>
            acumulado + (Number(producto.cant) || 0),
        0
    );

    const total = productos.reduce(
        (acumulado, producto) =>
            acumulado +
            (Number(producto.precio) || 0) *
            (Number(producto.cant) || 0),
        0
    );

    /*
     * En Chile, si el precio ya incluye IVA:
     * neto = total / 1.19
     * IVA = total - neto
     */
    const neto = Math.round(total / 1.19);
    const iva = total - neto;
    const costoDespacho =
    obtenerTipoEntrega() === "despacho" && tarifaDespachoActual ? tarifaDespachoActual.costo: 0;

    if (elementos.cantidad) {
        elementos.cantidad.textContent =
            `${cantidadProductos} ${
                cantidadProductos === 1
                    ? "producto"
                    : "productos"
            }`;
    }

    if (elementos.neto) {
        elementos.neto.textContent = formatoCLP.format(neto);
    }

    if (elementos.iva) {
        elementos.iva.textContent = formatoCLP.format(iva);
    }

    if (elementos.despacho) {

        if (
            obtenerTipoEntrega() === "retiro"
        ) {
            elementos.despacho.textContent =
                "Gratis";

        } else if (tarifaDespachoActual) {

            elementos.despacho.textContent =
                formatoCLP.format(
                    costoDespacho
                );

        } else {

            elementos.despacho.textContent =
                "Por calcular";
        }
    }

    if (elementos.total) {
    elementos.total.textContent = formatoCLP.format(total + costoDespacho);
}
}

/* =====================================================
   CARRITO VACÍO
===================================================== */

function mostrarCarritoVacio() {
    elementos.productos.innerHTML = `
        <div class="checkoutVacio">
            <i class="fa-solid fa-cart-shopping"></i>

            <strong>Su carrito está vacío</strong>

            <p>
                Agregue productos antes de continuar con la compra.
            </p>

            <a href="./repuestos.html">
                Volver a productos
            </a>
        </div>
    `;

    if (elementos.cantidad) {
        elementos.cantidad.textContent = "0 productos";
    }

    if (elementos.neto) {
        elementos.neto.textContent = formatoCLP.format(0);
    }

    if (elementos.iva) {
        elementos.iva.textContent = formatoCLP.format(0);
    }

    if (elementos.despacho) {
        elementos.despacho.textContent = "Gratis";
    }

    if (elementos.total) {
        elementos.total.textContent = formatoCLP.format(0);
    }

    if (elementos.botonConfirmar) {
        elementos.botonConfirmar.disabled = true;
    }
}

/* =====================================================
   CONTADOR DE OBSERVACIONES
===================================================== */

function actualizarContadorObservaciones() {
    if (
        !elementos.observaciones ||
        !elementos.contadorObservaciones
    ) {
        return;
    }

    const cantidad = elementos.observaciones.value.length;

    elementos.contadorObservaciones.textContent = cantidad;
}

/* =====================================================
   VALIDACIONES
===================================================== */

function validarCheckout() {

    const nombre =
        elementos.nombre.value.trim();

    const apellido =
        elementos.apellido.value.trim();

    const correo =
        elementos.correo.value.trim();

    const telefono =
        elementos.telefono.value.trim();

    const rut =
        elementos.rut.value.trim();

    const aceptaCondiciones =
        document.getElementById(
            "aceptaCondicionesCheckout"
        );

    if (nombre.length < 2) {

        mostrarMensajeCheckout(
            "Ingrese un nombre válido.",
            false
        );

        elementos.nombre.focus();
        return false;
    }

    if (apellido.length < 2) {

        mostrarMensajeCheckout(
            "Ingrese un apellido válido.",
            false
        );

        elementos.apellido.focus();
        return false;
    }

    if (!rut || !validarRutChileno(rut)) {

        mostrarMensajeCheckout(
            "Ingrese un RUT válido.",
            false
        );

        elementos.rut.focus();
        return false;
    }

    if (!validarCorreo(correo)) {

        mostrarMensajeCheckout(
            "Ingrese un correo electrónico válido.",
            false
        );

        elementos.correo.focus();
        return false;
    }

    if (!/^9[0-9]{8}$/.test(telefono)) {

        mostrarMensajeCheckout(
            "El teléfono debe contener 9 números y comenzar con 9.",
            false
        );

        elementos.telefono.focus();
        return false;
    }

    /*
     * Si no existe una sesión, el cliente debe
     * registrarse o iniciar sesión.
     */

    if (!clienteAutenticado) {

        if (modoAccesoCliente === "login") {

            mostrarMensajeCheckout(
                "Inicie sesión antes de continuar con el pago.",
                false
            );

            elementos.correoLogin?.focus();
            return false;
        }

        const password =
            elementos.passwordRegistro.value;

        const confirmarPassword =
            elementos.confirmarPasswordRegistro.value;

        if (password.length < 8) {

            mostrarMensajeCheckout(
                "La contraseña debe tener al menos 8 caracteres.",
                false
            );

            elementos.passwordRegistro.focus();
            return false;
        }

        if (
            !/[A-Z]/.test(password) ||
            !/[a-z]/.test(password) ||
            !/[0-9]/.test(password)
        ) {

            mostrarMensajeCheckout(
                "La contraseña debe incluir mayúscula, minúscula y número.",
                false
            );

            elementos.passwordRegistro.focus();
            return false;
        }

        if (password !== confirmarPassword) {

            mostrarMensajeCheckout(
                "Las contraseñas no coinciden.",
                false
            );

            elementos.confirmarPasswordRegistro.focus();
            return false;
        }
    }

    if (obtenerTipoEntrega() === "despacho") {
        const direccionSeleccionada =
            obtenerDireccionSeleccionada();

        if (!direccionSeleccionada) {
            mostrarMensajeCheckout(
                "Seleccione o agregue una dirección de despacho.",
                false
            );

            if (elementos.errorDireccion) {
                elementos.errorDireccion.textContent =
                    "Debe seleccionar una dirección.";
            }

            elementos.btnPrimeraDireccion?.focus();
            return false;
        }
    }

    if (
        aceptaCondiciones &&
        !aceptaCondiciones.checked
    ) {

        mostrarMensajeCheckout(
            "Debe aceptar los términos y condiciones.",
            false
        );

        return false;
    }

    if (carrito.obtenerCarrito().length === 0) {

        mostrarMensajeCheckout(
            "Su carrito está vacío.",
            false
        );

        return false;
    }

    return true;
}

function validarCorreo(correo) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
}

/* =====================================================
   RUT CHILENO
===================================================== */

function formatearRut(valor) {
    let rut = valor
        .replace(/[^0-9kK]/g, "")
        .toUpperCase()
        .slice(0, 9);

    if (rut.length <= 1) {
        return rut;
    }

    const cuerpo = rut.slice(0, -1);
    const verificador = rut.slice(-1);

    const cuerpoFormateado = cuerpo.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        "."
    );

    return `${cuerpoFormateado}-${verificador}`;
}

function validarRutChileno(rutCompleto) {
    const rutLimpio = rutCompleto
        .replace(/\./g, "")
        .replace("-", "")
        .toUpperCase();

    if (!/^[0-9]+[0-9K]$/.test(rutLimpio)) {
        return false;
    }

    const cuerpo = rutLimpio.slice(0, -1);
    const digitoRecibido = rutLimpio.slice(-1);

    let suma = 0;
    let multiplicador = 2;

    for (let i = cuerpo.length - 1; i >= 0; i--) {
        suma += Number(cuerpo[i]) * multiplicador;

        multiplicador =
            multiplicador === 7
                ? 2
                : multiplicador + 1;
    }

    const resultado = 11 - (suma % 11);

    let digitoCalculado;

    if (resultado === 11) {
        digitoCalculado = "0";
    } else if (resultado === 10) {
        digitoCalculado = "K";
    } else {
        digitoCalculado = String(resultado);
    }

    return digitoCalculado === digitoRecibido;
}

/* =====================================================
   AGREGAR REGISTRO DESDE EL CHECKOUT
===================================================== */

async function registrarClienteDesdeCheckout() {

    const datosRegistro = new FormData();

    datosRegistro.append(
        "rut",
        elementos.rut.value.trim()
    );

    datosRegistro.append(
        "nombre",
        elementos.nombre.value.trim()
    );

    datosRegistro.append(
        "apellido",
        elementos.apellido.value.trim()
    );

    datosRegistro.append(
        "correo",
        elementos.correo.value.trim()
    );

    datosRegistro.append(
        "telefono",
        elementos.telefono.value.trim()
    );

    datosRegistro.append(
        "password",
        elementos.passwordRegistro.value
    );

    datosRegistro.append(
        "confirmarPassword",
        elementos.confirmarPasswordRegistro.value
    );

    /*
     * La dirección solo se registra cuando
     * el cliente selecciona despacho.
     */

    if (obtenerTipoEntrega() === "despacho") {

        datosRegistro.append(
            "region",
            elementos.region.value
        );

        datosRegistro.append(
            "comuna",
            elementos.comuna.value.trim()
        );

        datosRegistro.append(
            "calle",
            elementos.calle.value.trim()
        );

        datosRegistro.append(
            "numero",
            elementos.numeroDireccion.value.trim()
        );

        datosRegistro.append(
            "departamento",
            elementos.departamento.value.trim()
        );

        datosRegistro.append(
            "referencia",
            elementos.referencia.value.trim()
        );
    }

    const response = await fetch(
        "./php/registrarCliente.php",
        {
            method: "POST",
            credentials: "same-origin",
            body: datosRegistro
        }
    );

    const resultado =
        await response.json();

    if (!response.ok || !resultado.ok) {

        throw new Error(
            resultado.mensaje ||
            "No fue posible crear la cuenta."
        );
    }

    /*
     * registrarCliente.php ya creó la sesión.
     * Volvemos a consultarla para obtener también
     * la dirección principal.
     */

    await comprobarSesionCliente();

    if (!clienteAutenticado) {

        throw new Error(
            "La cuenta fue creada, pero no fue posible iniciar la sesión."
        );
    }

    return resultado;
}

/* =====================================================
   ENVIAR PEDIDO
===================================================== */

async function enviarPedido(event) {

    event.preventDefault();

    limpiarMensajeCheckout();

    if (!validarCheckout()) {
        return;
    }

    bloquearBotonPedido(true);

    try {

        /*
         * Si todavía no está conectado, se encuentra
         * en modo registro porque validarCheckout()
         * ya bloqueó el modo login.
         */

        if (
            obtenerTipoEntrega() === "despacho"
        ) {

            mostrarMensajeCheckout(
                "Verificando cobertura y tarifa de despacho...",
                true
            );

            const tarifaValida =
                await calcularTarifaDespacho(true);

            if (
                !tarifaValida ||
                !tarifaDespachoActual
            ) {
                throw new Error(
                    "No fue posible confirmar la tarifa de despacho."
                );
            }
        }

        if (!clienteAutenticado) {

            mostrarMensajeCheckout(
                "Creando su cuenta...",
                true
            );

            await registrarClienteDesdeCheckout();
        }

        const productos =
            carrito.obtenerCarrito().map(
                producto => ({
                    id_producto:
                        Number(
                            producto.id_producto
                        ),

                    cantidad:
                        Number(producto.cant)
                })
            );

        const tipoEntrega =
            obtenerTipoEntrega();

        const datosPedido = {

            /*
             * Se mantienen temporalmente estos datos.
             * crearPedido.php será actualizado para
             * obtener la identidad desde la sesión.
             */

            cliente: {
                nombre:
                    elementos.nombre.value.trim(),

                apellido:
                    elementos.apellido.value.trim(),

                rut:
                    elementos.rut.value.trim(),

                telefono:
                    elementos.telefono.value.trim(),

                correo:
                    elementos.correo.value.trim()
            },

            entrega: {
                tipo: tipoEntrega,

                id_direccion:
                    tipoEntrega === "despacho" &&
                    idDireccionSeleccionada > 0
                        ? idDireccionSeleccionada
                        : 0,

                region:
                    tipoEntrega === "despacho"
                        ? elementos.region.value
                        : "",

                comuna:
                    tipoEntrega === "despacho"
                        ? elementos.comuna.value.trim()
                        : "",

                calle:
                    tipoEntrega === "despacho"
                        ? elementos.calle.value.trim()
                        : "",

                numero:
                    tipoEntrega === "despacho"
                        ? elementos.numeroDireccion
                            .value.trim()
                        : "",

                departamento:
                    tipoEntrega === "despacho"
                        ? elementos.departamento
                            .value.trim()
                        : "",

                referencia:
                    tipoEntrega === "despacho"
                        ? elementos.referencia
                            .value.trim()
                        : ""
            },

            observaciones:
                elementos.observaciones
                    ? elementos.observaciones
                        .value.trim()
                    : "",

            productos
        };

        mostrarMensajeCheckout(
            "Verificando el pedido...",
            true
        );

        const response = await fetch(
            "./php/crearPedido.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify(
                    datosPedido
                )
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {

            throw new Error(
                resultado.mensaje ||
                "No fue posible registrar el pedido."
            );
        }

        mostrarMensajeCheckout(
            "Pedido registrado. Conectando con Webpay...",
            true
        );

        await iniciarPagoWebpay(
            resultado.pedido.id_pedido
        );

    } catch (error) {

        console.error(
            "Error procesando checkout:",
            error
        );

        mostrarMensajeCheckout(
            error.message ||
            "No fue posible procesar la compra.",
            false
        );

    } finally {

        bloquearBotonPedido(false);
    }
}

/* =====================================================
   REDIRECCIÓN A WEBPAY
===================================================== */

async function iniciarPagoWebpay(idPedido) {

    const response = await fetch(
        "./php/iniciarPagoWebpay.php",
        {
            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                id_pedido: idPedido
            })
        }
    );

    const resultado = await response.json();

    if (!response.ok || !resultado.ok) {
        throw new Error(
            resultado.mensaje ||
            "No fue posible iniciar el pago con Webpay."
        );
    }

    /*
     * Guardamos temporalmente el pedido para poder
     * limpiar el carrito después del pago aprobado.
     */

    sessionStorage.setItem(
        "pedidoWebpay",
        String(idPedido)
    );

    enviarFormularioWebpay(
        resultado.url_pago,
        resultado.token
    );
}



function enviarFormularioWebpay(urlPago, token) {

    const formularioWebpay =
        document.createElement("form");

    formularioWebpay.method = "POST";
    formularioWebpay.action = urlPago;

    const inputToken =
        document.createElement("input");

    inputToken.type = "hidden";
    inputToken.name = "token_ws";
    inputToken.value = token;

    formularioWebpay.appendChild(inputToken);

    document.body.appendChild(
        formularioWebpay
    );

    formularioWebpay.submit();
}

/* =====================================================
   MENSAJES Y BOTÓN
===================================================== */

function bloquearBotonPedido(bloquear) {
    if (!elementos.botonConfirmar) {
        return;
    }

    elementos.botonConfirmar.disabled = bloquear;

    elementos.botonConfirmar.innerHTML = bloquear
        ? `
            <i class="fa-solid fa-spinner fa-spin"></i>
            Procesando pedido...
        `
        : `
            Continuar el pago
            <span aria-hidden="true">→</span>
        `;
}

function mostrarMensajeCheckout(mensaje, correcto) {
    if (!elementos.mensaje) {
        return;
    }

    elementos.mensaje.className = correcto
        ? "mensajeCheckout correcto"
        : "mensajeCheckout error";

    elementos.mensaje.textContent = mensaje;
}

function limpiarMensajeCheckout() {
    if (!elementos.mensaje) {
        return;
    }

    elementos.mensaje.className = "mensajeCheckout";
    elementos.mensaje.textContent = "";
}




async function comprobarSesionCliente() {

    try {

        const response = await fetch(
            "./php/verificarSesionCliente.php",
            {
                cache: "no-store",
                credentials: "same-origin"
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {

            clienteAutenticado = null;

            mostrarMensajeCheckout(
                resultado.mensaje ||
                    "No fue posible comprobar la sesión.",
                false
            );

            actualizarInterfazCliente();

            return;
        }

        clienteAutenticado =
            resultado.autenticado
                ? resultado.cliente
                : null;

        actualizarInterfazCliente();

        if (clienteAutenticado) {
            completarDatosCliente(
                clienteAutenticado
            );

            direccionTemporalRegistro = null;
            await cargarDireccionesCliente();
        } else {
            await cargarDireccionesCliente();
        }

    } catch (error) {

        console.error(
            "Error comprobando sesión:",
            error
        );

        clienteAutenticado = null;
        actualizarInterfazCliente();
    }
}

function actualizarInterfazCliente() {

    const autenticado =
        Boolean(clienteAutenticado);

    if (elementos.clienteNoConectado) {
        elementos.clienteNoConectado.hidden =
            autenticado;
    }

    if (elementos.clienteConectado) {
        elementos.clienteConectado.hidden =
            !autenticado;
    }

    if (autenticado) {

        elementos.nombreSesionCliente.textContent =
            `${clienteAutenticado.nombre} ${clienteAutenticado.apellido}`;

        elementos.correoSesionCliente.textContent =
            clienteAutenticado.correo;
    }

    actualizarCamposSegunSesion();
}

function actualizarCamposSegunSesion() {

    const necesitaPassword =
        !clienteAutenticado &&
        modoAccesoCliente === "registro";

    if (elementos.camposPasswordRegistro) {

        elementos.camposPasswordRegistro.hidden =
            !necesitaPassword;

        elementos.camposPasswordRegistro.style.display =
            necesitaPassword
                ? "block"
                : "none";
    }

    if (elementos.passwordRegistro) {

        elementos.passwordRegistro.required =
            necesitaPassword;

        elementos.passwordRegistro.disabled =
            !necesitaPassword;
    }

    if (elementos.confirmarPasswordRegistro) {

        elementos.confirmarPasswordRegistro.required =
            necesitaPassword;

        elementos.confirmarPasswordRegistro.disabled =
            !necesitaPassword;
    }

    /*
     * Un cliente conectado no debe cambiar
     * su identidad accidentalmente durante el pago.
     */

    [
        elementos.nombre,
        elementos.apellido,
        elementos.rut,
        elementos.correo,
        elementos.telefono
    ].forEach(campo => {

        if (campo) {
            campo.readOnly =
                Boolean(clienteAutenticado);
        }
    });
}


function completarDatosCliente(cliente) {

    /*
     * Solamente completamos los datos personales.
     * Las direcciones se cargan separadamente desde
     * obtenerDireccionesCliente.php.
     */

    if (elementos.nombre) {
        elementos.nombre.value =
            cliente.nombre || "";
    }

    if (elementos.apellido) {
        elementos.apellido.value =
            cliente.apellido || "";
    }

    if (elementos.rut) {
        elementos.rut.value =
            cliente.rut || "";
    }

    if (elementos.correo) {
        elementos.correo.value =
            cliente.correo || "";
    }

    if (elementos.telefono) {
        elementos.telefono.value =
            cliente.telefono || "";
    }
}


async function iniciarSesionDesdeCheckout() {

    const correo =
        elementos.correoLogin.value.trim();

    const password =
        elementos.passwordLogin.value;

    if (
        !validarCorreo(correo) ||
        password === ""
    ) {
        mostrarMensajeLogin(
            "Ingrese su correo y contraseña.",
            false
        );

        return;
    }

    elementos.btnIngresarCliente.disabled =
        true;

    elementos.btnIngresarCliente.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Ingresando...
    `;

    try {

        const response = await fetch(
            "./php/iniciarSesionCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify({
                    correo,
                    password
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {

            mostrarMensajeLogin(
                resultado.mensaje ||
                    "No fue posible iniciar sesión.",
                false
            );

            return;
        }

        elementos.passwordLogin.value = "";

        await comprobarSesionCliente();

        mostrarMensajeCheckout(
            `Bienvenido, ${resultado.cliente.nombre}. Sus datos fueron cargados.`,
            true
        );

    } catch (error) {

        console.error(
            "Error iniciando sesión:",
            error
        );

        mostrarMensajeLogin(
            "No fue posible conectar con el servidor.",
            false
        );

    } finally {

        elementos.btnIngresarCliente.disabled =
            false;

        elementos.btnIngresarCliente.innerHTML = `
            <i class="fa-solid fa-right-to-bracket"></i>
            Iniciar sesión
        `;
    }
}

async function cerrarSesionDesdeCheckout() {

    try {

        const response = await fetch(
            "./php/cerrarSesionCliente.php",
            {
                method: "POST",
                credentials: "same-origin"
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                    "No fue posible cerrar sesión."
            );
        }

        clienteAutenticado = null;

        direccionesCliente = [];
        idDireccionSeleccionada = 0;
        direccionTemporalRegistro = null;

        limpiarDatosCliente();
        renderizarDireccionesCliente();
        actualizarInterfazCliente();
        mostrarRegistroCliente();

        mostrarMensajeCheckout(
            "Sesión cerrada correctamente.",
            true
        );

    } catch (error) {

        mostrarMensajeCheckout(
            error.message,
            false
        );
    }
}

function limpiarDatosCliente() {

    [
        elementos.nombre,
        elementos.apellido,
        elementos.rut,
        elementos.correo,
        elementos.telefono,
        elementos.region,
        elementos.comuna,
        elementos.calle,
        elementos.numeroDireccion,
        elementos.departamento,
        elementos.referencia
    ].forEach(campo => {

        if (campo) {
            campo.value = "";
        }
    });
}

function mostrarMensajeLogin(
    mensaje,
    correcto
) {

    if (!elementos.mensajeLogin) {
        return;
    }

    elementos.mensajeLogin.className =
        correcto
            ? "mensajeCheckout correcto"
            : "mensajeCheckout error";

    elementos.mensajeLogin.textContent =
        mensaje;
}
