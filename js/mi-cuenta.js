let clienteCuenta = null;
let pedidosCuenta = [];
let pedidosFiltrados = [];
let direccionesCuenta = [];

const elementos = {

    formDatos: document.getElementById(
    "formDatosClienteCuenta"
   ),

    nombreCliente: document.getElementById(
        "nombreClienteCuenta"
    ),

    correoCliente: document.getElementById(
        "correoClienteCuenta"
    ),

    mensaje: document.getElementById(
        "mensajeCuenta"
    ),

    contadorPedidos: document.getElementById(
        "contadorPedidosCuenta"
    ),

    cargandoPedidos: document.getElementById(
        "cargandoPedidosCuenta"
    ),

    listaPedidos: document.getElementById(
        "listaPedidosCuenta"
    ),

    sinPedidos: document.getElementById(
        "sinPedidosCuenta"
    ),

    buscarPedido: document.getElementById(
        "buscarPedidoCuenta"
    ),

    filtroEstado: document.getElementById(
        "filtroEstadoPedidoCuenta"
    ),

    filtroEntrega: document.getElementById(
        "filtroEntregaPedidoCuenta"
    ),

    dialogPedido: document.getElementById(
        "dialogPedidoCuenta"
    ),

    numeroDialogPedido: document.getElementById(
        "numeroDialogPedidoCuenta"
    ),

    contenidoDialogPedido: document.getElementById(
        "contenidoDialogPedido"
    ),

    btnCerrarDialogPedido: document.getElementById(
        "btnCerrarDialogPedido"
    ),

    btnCerrarSesion: document.getElementById(
        "btnCerrarSesionCuenta"
    ),

    rutDatos: document.getElementById(
        "rutClienteCuenta"
    ),

    nombreDatos: document.getElementById(
        "nombreDatosCuenta"
    ),

    apellidoDatos: document.getElementById(
        "apellidoDatosCuenta"
    ),

    telefonoDatos: document.getElementById(
        "telefonoDatosCuenta"
    ),

    correoDatos: document.getElementById(
        "correoDatosCuenta"
    ),

    listaDirecciones: document.getElementById(
        "listaDireccionesCuenta"
    ),

    sinDirecciones: document.getElementById(
        "sinDireccionesCuenta"
    ),

    btnGuardarDatos: document.getElementById(
    "btnGuardarDatosCuenta"
    ),
    
    btnNuevaDireccion: document.getElementById(
    "btnNuevaDireccionCuenta"
    ),

    dialogDireccion: document.getElementById(
        "dialogDireccionCuenta"
    ),

    btnCerrarDialogDireccion: document.getElementById(
        "btnCerrarDialogDireccion"
    ),

    tituloDialogDireccion: document.getElementById(
        "tituloDialogDireccion"
    ),

    formDireccion: document.getElementById(
        "formDireccionCuenta"
    ),

    idDireccion: document.getElementById(
        "idDireccionCuenta"
    ),

    nombreDireccion: document.getElementById(
        "nombreDireccionCuenta"
    ),

    regionDireccion: document.getElementById(
        "regionDireccionCuenta"
    ),

    comunaDireccion: document.getElementById(
        "comunaDireccionCuenta"
    ),

    calleDireccion: document.getElementById(
        "calleDireccionCuenta"
    ),

    numeroDireccion: document.getElementById(
        "numeroDireccionCuenta"
    ),

    departamentoDireccion: document.getElementById(
        "departamentoDireccionCuenta"
    ),

    referenciaDireccion: document.getElementById(
        "referenciaDireccionCuenta"
    ),

    principalDireccion: document.getElementById(
        "principalDireccionCuenta"
    ),

    mensajeDireccion: document.getElementById(
        "mensajeDireccionCuenta"
    ),

    btnGuardarDireccion: document.getElementById(
    "btnGuardarDireccionCuenta"
    )

};

const formatoCLP = new Intl.NumberFormat(
    "es-CL",
    {
        style: "currency",
        currency: "CLP",
        minimumFractionDigits: 0
    }
);

/* =====================================================
   INICIALIZACIÓN
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    inicializarCuenta
);

async function inicializarCuenta() {

    configurarEventosCuenta();

    const sesionValida =
        await cargarSesionCliente();

    if (!sesionValida) {
        return;
    }

    await Promise.all([cargarPedidosCliente(), cargarDireccionesCliente()]);
}

/* =====================================================
   EVENTOS
===================================================== */

function configurarEventosCuenta() {

    document
        .querySelectorAll(
            ".btnPestanaCuenta"
        )
        .forEach(boton => {

            boton.addEventListener(
                "click",
                function () {

                    cambiarSeccionCuenta(
                        this.dataset.seccion
                    );
                }
            );
        });

    elementos.buscarPedido
        ?.addEventListener(
            "input",
            aplicarFiltrosPedidos
        );

    elementos.filtroEstado
        ?.addEventListener(
            "change",
            aplicarFiltrosPedidos
        );

    elementos.filtroEntrega
        ?.addEventListener(
            "change",
            aplicarFiltrosPedidos
        );

    elementos.btnCerrarDialogPedido
        ?.addEventListener(
            "click",
            function () {

                elementos.dialogPedido.close();
            }
        );

    elementos.dialogPedido
        ?.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    elementos.dialogPedido
                ) {
                    elementos.dialogPedido.close();
                }
            }
        );

    elementos.btnCerrarSesion
        ?.addEventListener(
            "click",
            cerrarSesionCuenta
        );

    elementos.telefonoDatos
        ?.addEventListener(
            "input",
            function () {

                this.value = this.value
                    .replace(/\D/g, "")
                    .slice(0, 9);
            }
        );

    elementos.formDatos
    ?.addEventListener(
        "submit",
        actualizarDatosCliente
    );

    elementos.btnNuevaDireccion
    ?.addEventListener(
        "click",
        function () {
            abrirDialogDireccion();
        }
    );

    elementos.btnCerrarDialogDireccion
        ?.addEventListener(
            "click",
            function () {
                elementos.dialogDireccion.close();
            }
        );

    elementos.dialogDireccion
        ?.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    elementos.dialogDireccion
                ) {
                    elementos.dialogDireccion.close();
                }
            }
        );

    elementos.formDireccion
        ?.addEventListener(
            "submit",
            guardarDireccionCliente
        );
}

/* =====================================================
   SESIÓN
===================================================== */

async function cargarSesionCliente() {

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

        if (
            !response.ok ||
            !resultado.ok ||
            !resultado.autenticado
        ) {

            window.location.replace(
                "./checkOut.html?acceso=login"
            );

            return false;
        }

        clienteCuenta =
            resultado.cliente;

        mostrarClienteCuenta();

        return true;

    } catch (error) {

        console.error(
            "Error verificando sesión:",
            error
        );

        mostrarMensajeCuenta(
            "No fue posible comprobar su sesión.",
            "error"
        );

        return false;
    }
}

function mostrarClienteCuenta() {

    elementos.nombreCliente.textContent =
        `${clienteCuenta.nombre} ${clienteCuenta.apellido}`;

    elementos.correoCliente.textContent =
        clienteCuenta.correo;

    elementos.rutDatos.value =
        clienteCuenta.rut || "";

    elementos.nombreDatos.value =
        clienteCuenta.nombre || "";

    elementos.apellidoDatos.value =
        clienteCuenta.apellido || "";

    elementos.telefonoDatos.value =
        clienteCuenta.telefono || "";

    elementos.correoDatos.value =
        clienteCuenta.correo || "";

}


/* =====================================================
   ACTUALIZAR DATOS PERSONALES
===================================================== */

async function actualizarDatosCliente(
    event
) {

    event.preventDefault();

    const nombre =
        elementos.nombreDatos.value.trim();

    const apellido =
        elementos.apellidoDatos.value.trim();

    const telefono =
        elementos.telefonoDatos.value
            .replace(/\D/g, "");

    if (
        nombre.length < 2 ||
        nombre.length > 80
    ) {

        mostrarMensajeCuenta(
            "Ingrese un nombre válido.",
            "error"
        );

        elementos.nombreDatos.focus();
        return;
    }

    if (
        apellido.length < 2 ||
        apellido.length > 80
    ) {

        mostrarMensajeCuenta(
            "Ingrese un apellido válido.",
            "error"
        );

        elementos.apellidoDatos.focus();
        return;
    }

    if (
        !/^9[0-9]{8}$/.test(
            telefono
        )
    ) {

        mostrarMensajeCuenta(
            "El teléfono debe contener 9 números y comenzar con 9.",
            "error"
        );

        elementos.telefonoDatos.focus();
        return;
    }

    bloquearBotonDatos(true);

    try {

        const response = await fetch(
            "./php/actualizarDatosCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify({
                    nombre,
                    apellido,
                    telefono
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible actualizar sus datos."
            );
        }

        clienteCuenta = {
            ...clienteCuenta,
            ...resultado.cliente
        };

        elementos.nombreCliente.textContent =
            `${clienteCuenta.nombre} ${clienteCuenta.apellido}`;

        elementos.nombreDatos.value =
            clienteCuenta.nombre;

        elementos.apellidoDatos.value =
            clienteCuenta.apellido;

        elementos.telefonoDatos.value =
            clienteCuenta.telefono;

        mostrarMensajeCuenta(
            resultado.mensaje,
            "correcto"
        );

    } catch (error) {

        mostrarMensajeCuenta(
            error.message,
            "error"
        );

    } finally {

        bloquearBotonDatos(false);
    }
}

function bloquearBotonDatos(
    bloquear
) {

    elementos.btnGuardarDatos.disabled =
        bloquear;

    elementos.btnGuardarDatos.innerHTML =
        bloquear
            ? `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Guardando...
              `
            : `
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar cambios
              `;
}

/* =====================================================
   PESTAÑAS
===================================================== */

function cambiarSeccionCuenta(seccion) {

    const seccionesValidas = [
        "pedidos",
        "datos",
        "direcciones"
    ];

    if (
        !seccionesValidas.includes(seccion)
    ) {
        return;
    }

    document
        .querySelectorAll(
            ".btnPestanaCuenta"
        )
        .forEach(boton => {

            boton.classList.toggle(
                "activa",
                boton.dataset.seccion ===
                    seccion
            );
        });

    document
        .querySelectorAll(
            ".seccionCuenta"
        )
        .forEach(elemento => {

            elemento.classList.remove(
                "activa"
            );

            elemento.hidden = true;
        });

    const seccionActiva =
        document.getElementById(
            `seccion${
                capitalizar(seccion)
            }Cuenta`
        );

    if (seccionActiva) {

        seccionActiva.hidden = false;

        seccionActiva.classList.add(
            "activa"
        );
    }
}

/* =====================================================
   PEDIDOS
===================================================== */

async function cargarPedidosCliente() {

    elementos.cargandoPedidos.hidden =
        false;

    elementos.listaPedidos.innerHTML = "";

    elementos.sinPedidos.hidden = true;

    try {

        const response = await fetch(
            "./php/listarPedidosCliente.php",
            {
                cache: "no-store",
                credentials: "same-origin"
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {

            if (
                resultado
                    .requiereAutenticacion
            ) {
                window.location.replace(
                    "./checkOut.html?acceso=login"
                );

                return;
            }

            throw new Error(
                resultado.mensaje ||
                "No fue posible cargar los pedidos."
            );
        }

        pedidosCuenta =
            Array.isArray(resultado.pedidos)
                ? resultado.pedidos
                : [];

        elementos.contadorPedidos.textContent =
            pedidosCuenta.length;

        aplicarFiltrosPedidos();

    } catch (error) {

        console.error(
            "Error cargando pedidos:",
            error
        );

        mostrarMensajeCuenta(
            error.message,
            "error"
        );

    } finally {

        elementos.cargandoPedidos.hidden =
            true;
    }
}

function aplicarFiltrosPedidos() {

    const texto =
        normalizarTexto(
            elementos.buscarPedido
                ?.value || ""
        );

    const estado =
        elementos.filtroEstado
            ?.value || "";

    const entrega =
        elementos.filtroEntrega
            ?.value || "";

    pedidosFiltrados =
        pedidosCuenta.filter(pedido => {

            const contenido = normalizarTexto(`
                ${pedido.numero_pedido}
                ${pedido.numero_cotizacion || ""}
                ${pedido.productos
                    .map(producto =>
                        `${producto.nombre}
                         ${producto.sku || ""}
                         ${producto.marca || ""}`
                    )
                    .join(" ")}
            `);

            return (
                (!texto ||
                    contenido.includes(texto)) &&
                (!estado ||
                    pedido.estado === estado) &&
                (!entrega ||
                    pedido.tipo_entrega ===
                        entrega)
            );
        });

    renderizarPedidos();
}

function renderizarPedidos() {

    elementos.listaPedidos.innerHTML = "";

    if (pedidosFiltrados.length === 0) {

        elementos.sinPedidos.hidden =
            false;

        return;
    }

    elementos.sinPedidos.hidden = true;

    pedidosFiltrados.forEach(pedido => {

        elementos.listaPedidos.appendChild(
            crearTarjetaPedido(pedido)
        );
    });
}

function crearTarjetaPedido(pedido) {

    const articulo =
        document.createElement("article");

    articulo.className =
        "pedidoCuenta";

    articulo.innerHTML = `
        <div class="encabezadoPedidoCuenta">

            <div class="numeroPedidoCuenta">
                <strong>
                    ${escaparHTML(
                        pedido.numero_pedido
                    )}
                </strong>

                <small>
                    ${formatearFecha(
                        pedido.fecha_pedido
                    )}
                </small>
            </div>

            <div class="estadosPedidoCuenta">

                <span class="estadoPedidoCuenta ${
                    claseEstado(
                        pedido.estado
                    )
                }">
                    ${textoEstado(
                        pedido.estado
                    )}
                </span>

                <span class="estadoPedidoCuenta ${
                    claseEstadoPago(
                        pedido.estado_pago
                    )
                }">
                    ${textoEstadoPago(
                        pedido.estado_pago
                    )}
                </span>

            </div>

        </div>

        <div class="cuerpoPedidoCuenta">

            <div class="resumenPedidoCuenta">

                <span class="datoPedidoCuenta">
                    <i class="fa-solid fa-box"></i>

                    ${pedido.cantidad_productos}
                    ${
                        pedido.cantidad_productos === 1
                            ? "producto"
                            : "productos"
                    }
                </span>

                <span class="datoPedidoCuenta">
                    <i class="fa-solid ${
                        pedido.tipo_entrega ===
                        "despacho"
                            ? "fa-truck-fast"
                            : "fa-store"
                    }"></i>

                    ${
                        pedido.tipo_entrega ===
                        "despacho"
                            ? "Despacho"
                            : "Retiro en tienda"
                    }
                </span>

            </div>

            <div class="totalPedidoCuenta">
                <span>Total</span>

                <strong>
                    ${formatoCLP.format(
                        pedido.total
                    )}
                </strong>
            </div>

        </div>

        <div class="accionesPedidoCuenta">

            <button
                type="button"
                class="btnVerPedidoCuenta">

                <i class="fa-solid fa-eye"></i>

                Ver pedido

            </button>

            ${
                pedido.acciones
                    .puede_pagar
                    ? `
                        <button
                            type="button"
                            class="btnPagarPedidoCuenta">

                            <i class="fa-solid fa-credit-card"></i>

                            Pagar
                        </button>
                      `
                    : ""
            }

            ${
                pedido.acciones
                    .puede_imprimir
                    ? `
                        <button
                            type="button"
                            class="btnImprimirPedidoCuenta">

                            <i class="fa-solid fa-print"></i>

                            Imprimir
                        </button>
                      `
                    : ""
            }

            ${
                pedido.acciones
                    .puede_cancelar
                    ? `
                        <button
                            type="button"
                            class="btnCancelarPedidoCuenta">

                            <i class="fa-solid fa-ban"></i>

                            Cancelar
                        </button>
                      `
                    : ""
            }

        </div>
    `;

    articulo
        .querySelector(
            ".btnVerPedidoCuenta"
        )
        .addEventListener(
            "click",
            () => abrirDetallePedido(pedido)
        );

    articulo
        .querySelector(
            ".btnPagarPedidoCuenta"
        )
        ?.addEventListener(
            "click",
            () => pagarPedido(pedido)
        );

    articulo
        .querySelector(
            ".btnImprimirPedidoCuenta"
        )
        ?.addEventListener(
            "click",
            () => imprimirPedido(pedido)
        );

    articulo
        .querySelector(
            ".btnCancelarPedidoCuenta"
        )
        ?.addEventListener(
            "click",
            () => prepararCancelacionPedido(
                pedido
            )
        );

    return articulo;
}

/* =====================================================
   DETALLE
===================================================== */

function abrirDetallePedido(pedido) {

    elementos.numeroDialogPedido.textContent =
        pedido.numero_pedido;

    const productos = pedido.productos
        .map(producto => `
            <div class="productoDetalleCuenta">

                <div>
                    <strong>
                        ${escaparHTML(
                            producto.nombre
                        )}
                    </strong>

                    <small>
                        ${escaparHTML(
                            producto.sku ||
                            "Sin SKU"
                        )}
                        ·
                        ${escaparHTML(
                            producto.marca ||
                            "Sin marca"
                        )}
                    </small>
                </div>

                <span>
                    ${producto.cantidad}
                    ×
                    ${formatoCLP.format(
                        producto.precio_unitario
                    )}
                </span>

                <strong>
                    ${formatoCLP.format(
                        producto.total_linea
                    )}
                </strong>

            </div>
        `)
        .join("");

    const direccion =
        pedido.tipo_entrega === "despacho"
            ? `
                <div class="bloqueDetalleCuenta">

                    <h3>Dirección de despacho</h3>

                    <p>
                        ${escaparHTML(
                            [
                                pedido.entrega.direccion,
                                pedido.entrega.comuna,
                                pedido.entrega.region
                            ]
                                .filter(Boolean)
                                .join(", ")
                        )}
                    </p>

                </div>
              `
            : `
                <div class="bloqueDetalleCuenta">

                    <h3>Retiro en tienda</h3>

                    <p>
                        Baquedano 687, Lampa.
                    </p>

                </div>
              `;

    elementos.contenidoDialogPedido.innerHTML = `
        <div class="bloqueDetalleCuenta">

            <h3>Productos</h3>

            <div class="productosDetalleCuenta">
                ${productos}
            </div>

        </div>

        ${direccion}

        <div class="totalesDetalleCuenta">

            <div>
                <span>Neto</span>
                <strong>
                    ${formatoCLP.format(
                        pedido.neto
                    )}
                </strong>
            </div>

            <div>
                <span>IVA</span>
                <strong>
                    ${formatoCLP.format(
                        pedido.iva
                    )}
                </strong>
            </div>

            <div>
                <span>Despacho</span>
                <strong>
                    ${
                        pedido.entrega.costo > 0
                            ? formatoCLP.format(
                                pedido.entrega.costo
                            )
                            : "Gratis"
                    }
                </strong>
            </div>

            <div class="totalDetalleCuenta">
                <span>Total</span>
                <strong>
                    ${formatoCLP.format(
                        pedido.total
                    )}
                </strong>
            </div>

        </div>
    `;

    elementos.dialogPedido.showModal();
}

/* =====================================================
   PAGAR NUEVAMENTE
===================================================== */

async function pagarPedido(pedido) {

    mostrarMensajeCuenta(
        "Conectando con Webpay...",
        "informacion"
    );

    try {

        const response = await fetch(
            "./php/iniciarPagoWebpay.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify({
                    id_pedido:
                        pedido.id_pedido
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible iniciar el pago."
            );
        }

        sessionStorage.setItem(
            "pedidoWebpay",
            String(pedido.id_pedido)
        );

        enviarFormularioWebpay(
            resultado.url_pago,
            resultado.token
        );

    } catch (error) {

        mostrarMensajeCuenta(
            error.message,
            "error"
        );
    }
}

function enviarFormularioWebpay(
    url,
    token
) {

    const formulario =
        document.createElement("form");

    formulario.method = "POST";
    formulario.action = url;

    const campoToken =
        document.createElement("input");

    campoToken.type = "hidden";
    campoToken.name = "token_ws";
    campoToken.value = token;

    formulario.appendChild(
        campoToken
    );

    document.body.appendChild(
        formulario
    );

    formulario.submit();
}

/* =====================================================
   IMPRESIÓN
===================================================== */

function imprimirPedido(pedido) {

    const ventana =
        window.open(
            "",
            "_blank",
            "width=850,height=700"
        );

    if (!ventana) {

        mostrarMensajeCuenta(
            "Permita las ventanas emergentes para imprimir.",
            "error"
        );

        return;
    }

    const productos = pedido.productos
        .map(producto => `
            <tr>
                <td>
                    ${escaparHTML(
                        producto.nombre
                    )}
                </td>
                <td>${producto.cantidad}</td>
                <td>
                    ${formatoCLP.format(
                        producto.precio_unitario
                    )}
                </td>
                <td>
                    ${formatoCLP.format(
                        producto.total_linea
                    )}
                </td>
            </tr>
        `)
        .join("");

    ventana.document.write(`
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">

            <title>
                Pedido ${escaparHTML(
                    pedido.numero_pedido
                )}
            </title>

            <style>
                body {
                    padding: 30px;
                    color: #263548;
                    font-family: Arial, sans-serif;
                }

                header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 2px solid #1e5aa0;
                    padding-bottom: 15px;
                }

                img {
                    width: 150px;
                }

                table {
                    width: 100%;
                    margin-top: 25px;
                    border-collapse: collapse;
                }

                th,
                td {
                    padding: 10px;
                    border-bottom: 1px solid #dddddd;
                    text-align: left;
                }

                th {
                    background: #edf5ff;
                }

                .total {
                    margin-top: 24px;
                    text-align: right;
                    font-size: 20px;
                }

                footer {
                    margin-top: 35px;
                    color: #69778a;
                    font-size: 12px;
                }
            </style>
        </head>

        <body>

            <header>
                <img
                    src="./images/logoAlianzaPro.webp"
                    alt="AlianzaPro">

                <div>
                    <strong>
                        ${escaparHTML(
                            pedido.numero_pedido
                        )}
                    </strong>

                    <p>
                        ${formatearFecha(
                            pedido.fecha_pedido
                        )}
                    </p>
                </div>
            </header>

            <h2>Comprobante de pedido</h2>

            <p>
                Estado:
                ${escaparHTML(
                    textoEstado(
                        pedido.estado
                    )
                )}
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    ${productos}
                </tbody>
            </table>

            <div class="total">
                Total:
                <strong>
                    ${formatoCLP.format(
                        pedido.total
                    )}
                </strong>
            </div>

            <footer>
                AlianzaPro SPA · Baquedano 687,
                Lampa · soporte@alianzapro.cl
            </footer>

        </body>
        </html>
    `);

    ventana.document.close();

    ventana.onload = function () {
        ventana.print();
    };
}

/* =====================================================
   CANCELACIÓN
===================================================== */

async function prepararCancelacionPedido(pedido) {

    const motivo = window.prompt(
        `Indique el motivo para cancelar el pedido ${pedido.numero_pedido}:`
    );

    if (motivo === null) {
        return;
    }

    if (
        motivo.trim().length < 5
    ) {

        mostrarMensajeCuenta(
            "Ingrese un motivo de al menos 5 caracteres.",
            "error"
        );

        return;
    }

    const confirmar = window.confirm(
        "El pedido será cancelado y se solicitará la devolución completa a Webpay. ¿Desea continuar?"
    );

    if (!confirmar) {
        return;
    }

    mostrarMensajeCuenta(
        "Procesando cancelación y reembolso...",
        "informacion"
    );

    try {

        const response = await fetch(
            "./php/solicitarCancelacionPedido.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify({
                    id_pedido:
                        pedido.id_pedido,

                    motivo:
                        motivo.trim()
                })
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {

            if (
                resultado
                    .reembolso_pendiente
            ) {

                mostrarMensajeCuenta(
                    resultado.mensaje,
                    "informacion"
                );

                await cargarPedidosCliente();
                return;
            }

            throw new Error(
                resultado.mensaje ||
                "No fue posible cancelar el pedido."
            );
        }

        mostrarMensajeCuenta(
            resultado.mensaje,
            "correcto"
        );

        await cargarPedidosCliente();

    } catch (error) {

        mostrarMensajeCuenta(
            error.message,
            "error"
        );
    }
}

/* =====================================================
   DIRECCIONES
===================================================== */

async function cargarDireccionesCliente() {

    try {

        const response = await fetch(
            "./php/listarDireccionesCliente.php",
            {
                cache: "no-store",
                credentials: "same-origin"
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible cargar las direcciones."
            );
        }

        direccionesCuenta =
            Array.isArray(
                resultado.direcciones
            )
                ? resultado.direcciones
                : [];

        renderizarDirecciones();

    } catch (error) {

        console.error(
            "Error cargando direcciones:",
            error
        );

        mostrarMensajeCuenta(
            error.message,
            "error"
        );
    }
}

function renderizarDirecciones() {

    elementos.listaDirecciones.innerHTML =
        "";

    if (direccionesCuenta.length === 0) {

        elementos.sinDirecciones.hidden =
            false;

        return;
    }

    elementos.sinDirecciones.hidden =
        true;

    direccionesCuenta.forEach(
        direccion => {

            elementos.listaDirecciones
                .appendChild(
                    crearTarjetaDireccion(
                        direccion
                    )
                );
        }
    );
}

function crearTarjetaDireccion(direccion) {

    /*
     * MySQL puede devolver es_principal
     * como número, booleano o texto.
     */

    const esPrincipal =
        direccion.es_principal === true ||
        Number(
            direccion.es_principal
        ) === 1;

    const tarjeta =
        document.createElement(
            "article"
        );

    tarjeta.className =
        esPrincipal
            ? "direccionCuenta direccionCuentaPrincipal"
            : "direccionCuenta";

    tarjeta.innerHTML = `

        ${
            esPrincipal
                ? `
                    <span class="etiquetaPrincipalCuenta">

                        <i class="fa-solid fa-star"></i>

                        Principal

                    </span>
                  `
                : ""
        }

        <h3>
            ${escaparHTML(
                direccion.nombre ||
                direccion.nombre_direccion ||
                "Dirección"
            )}
        </h3>

        <p>

            <i class="fa-solid fa-location-dot"></i>

            ${escaparHTML(
                `${direccion.calle || ""} ${
                    direccion.numero || ""
                }`
            )}

            ${
                direccion.departamento
                    ? `,
                        ${escaparHTML(
                            direccion.departamento
                        )}`
                    : ""
            }

        </p>

        <p>
            ${escaparHTML(
                `${direccion.comuna || ""}, ${
                    direccion.region || ""
                }`
            )}
        </p>

        ${
            direccion.referencia
                ? `
                    <p>

                        <strong>
                            Referencia:
                        </strong>

                        ${escaparHTML(
                            direccion.referencia
                        )}

                    </p>
                  `
                : ""
        }

        <div class="accionesDireccionCuenta">

            <button
                type="button"
                class="btnEditarDireccionCuenta">

                <i class="fa-solid fa-pen"></i>

                Editar

            </button>

            ${
                !esPrincipal
                    ? `
                        <button
                            type="button"
                            class="btnEliminarDireccionCuenta"
                            title="Eliminar dirección">

                            <i class="fa-solid fa-trash-can"></i>

                            Eliminar

                        </button>
                      `
                    : ""
            }

        </div>
    `;

    /* =================================================
       EDITAR
    ================================================= */

    const botonEditar =
        tarjeta.querySelector(
            ".btnEditarDireccionCuenta"
        );

    botonEditar.addEventListener(
        "click",
        function () {

            abrirDialogDireccion(
                direccion
            );
        }
    );

    /* =================================================
       ELIMINAR
    ================================================= */

    const botonEliminar =
        tarjeta.querySelector(
            ".btnEliminarDireccionCuenta"
        );

    if (botonEliminar) {

        botonEliminar.addEventListener(
            "click",
            function () {

                eliminarDireccionCuenta(
                    {
                        ...direccion,
                        es_principal:
                            esPrincipal
                    },
                    botonEliminar
                );
            }
        );
    }

    return tarjeta;
}


/* =====================================================
   ELIMINAR DIRECCIÓN
===================================================== */

async function eliminarDireccionCuenta(
    direccion,
    boton
) {

    const idDireccion =
        Number(
            direccion.id_direccion
        );

    if (idDireccion <= 0) {

        mostrarMensajeCuenta(
            "La dirección seleccionada no es válida.",
            "error"
        );

        return;
    }

    const esPrincipal =
        direccion.es_principal === true ||
        Number(
            direccion.es_principal
        ) === 1;

    if (esPrincipal) {

        mostrarMensajeCuenta(
            "La dirección principal no puede eliminarse. Seleccione primero otra dirección como principal.",
            "error"
        );

        return;
    }

    const nombreDireccion =
        direccion.nombre ||
        direccion.nombre_direccion ||
        `${direccion.calle} ${direccion.numero}`;

    const confirmado =
        window.confirm(
            `¿Desea eliminar la dirección "${nombreDireccion}"?`
        );

    if (!confirmado) {
        return;
    }

    const contenidoOriginal =
        boton.innerHTML;

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Eliminando...
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

                credentials:
                    "same-origin",

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

        mostrarMensajeCuenta(
            resultado.mensaje ||
            "Dirección eliminada correctamente.",
            "correcto"
        );

        await cargarDireccionesCliente();

    } catch (error) {

        console.error(
            "Error eliminando dirección:",
            error
        );

        mostrarMensajeCuenta(
            error.message ||
            "No fue posible eliminar la dirección.",
            "error"
        );

        boton.disabled = false;

        boton.innerHTML =
            contenidoOriginal;
    }
}

function abrirDialogDireccion(
    direccion = null
) {

    elementos.formDireccion.reset();

    elementos.idDireccion.value = "";

    elementos.mensajeDireccion.className =
        "mensajeCuenta";

    elementos.mensajeDireccion.textContent =
        "";

    if (direccion) {

        elementos.tituloDialogDireccion.textContent =
            "Editar dirección";

        elementos.idDireccion.value =
            direccion.id_direccion;

        elementos.nombreDireccion.value =
            direccion.nombre || "";

        elementos.regionDireccion.value =
            direccion.region || "";

        elementos.comunaDireccion.value =
            direccion.comuna || "";

        elementos.calleDireccion.value =
            direccion.calle || "";

        elementos.numeroDireccion.value =
            direccion.numero || "";

        elementos.departamentoDireccion.value =
            direccion.departamento || "";

        elementos.referenciaDireccion.value =
            direccion.referencia || "";

        elementos.principalDireccion.checked =
            direccion.es_principal === true ||
            Number(
                direccion.es_principal
            ) === 1;

    } else {

        elementos.tituloDialogDireccion.textContent =
            "Nueva dirección";

        elementos.regionDireccion.value =
            "Región Metropolitana";

        elementos.principalDireccion.checked =
            direccionesCuenta.length === 0;
    }

    elementos.dialogDireccion.showModal();
}


async function guardarDireccionCliente(event) {

    event.preventDefault();

    const datos = {

        id_direccion:
            Number(
                elementos.idDireccion.value
            ) || 0,

        nombre_direccion:
            elementos.nombreDireccion
                .value.trim(),

        region:
            elementos.regionDireccion
                .value.trim(),

        comuna:
            elementos.comunaDireccion
                .value.trim(),

        calle:
            elementos.calleDireccion
                .value.trim(),

        numero:
            elementos.numeroDireccion
                .value.trim(),

        departamento:
            elementos.departamentoDireccion
                .value.trim(),

        referencia:
            elementos.referenciaDireccion
                .value.trim(),

        es_principal:
            elementos.principalDireccion
                .checked
    };

    if (
        datos.nombre_direccion.length < 2
    ) {
        mostrarMensajeDireccion(
            "Ingrese un nombre para la dirección.",
            "error"
        );

        return;
    }

    if (
        !datos.region ||
        datos.comuna.length < 2 ||
        datos.calle.length < 2 ||
        !datos.numero
    ) {
        mostrarMensajeDireccion(
            "Complete los datos obligatorios de la dirección.",
            "error"
        );

        return;
    }

    bloquearBotonDireccion(true);

    try {

        const response = await fetch(
            "./php/guardarDireccionCliente.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                credentials: "same-origin",

                body: JSON.stringify(datos)
            }
        );

        const resultado =
            await response.json();

        if (!response.ok || !resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible guardar la dirección."
            );
        }

        elementos.dialogDireccion.close();

        mostrarMensajeCuenta(
            resultado.mensaje,
            "correcto"
        );

        await cargarDireccionesCliente();

    } catch (error) {

        mostrarMensajeDireccion(
            error.message,
            "error"
        );

    } finally {

        bloquearBotonDireccion(false);
    }
}

function mostrarMensajeDireccion(
    mensaje,
    tipo
) {

    elementos.mensajeDireccion.className =
        `mensajeCuenta ${tipo}`;

    elementos.mensajeDireccion.textContent =
        mensaje;
}

function bloquearBotonDireccion(
    bloquear
) {

    elementos.btnGuardarDireccion.disabled =
        bloquear;

    elementos.btnGuardarDireccion.innerHTML =
        bloquear
            ? `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Guardando...
              `
            : `
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar dirección
              `;
}

/* =====================================================
   CERRAR SESIÓN
===================================================== */

async function cerrarSesionCuenta() {

    try {

        await fetch(
            "./php/cerrarSesionCliente.php",
            {
                method: "POST",
                credentials: "same-origin"
            }
        );

    } finally {

        window.location.replace(
            "./listing-row.html"
        );
    }
}

/* =====================================================
   MENSAJES Y AUXILIARES
===================================================== */

function mostrarMensajeCuenta(
    mensaje,
    tipo
) {

    elementos.mensaje.className =
        `mensajeCuenta ${tipo}`;

    elementos.mensaje.textContent =
        mensaje;

    elementos.mensaje.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });
}

function formatearFecha(fecha) {

    if (!fecha) {
        return "";
    }

    const fechaNormalizada =
        fecha.replace(" ", "T");

    const objetoFecha =
        new Date(fechaNormalizada);

    if (
        Number.isNaN(
            objetoFecha.getTime()
        )
    ) {
        return fecha;
    }

    return new Intl.DateTimeFormat(
        "es-CL",
        {
            dateStyle: "medium",
            timeStyle: "short"
        }
    ).format(objetoFecha);
}

function textoEstado(estado) {

    const textos = {
        pendiente_pago:
            "Pendiente de pago",
        pagado:
            "Pagado",
        preparando:
            "Preparando",
        listo_retiro:
            "Listo para retirar",
        enviado:
            "Enviado",
        entregado:
            "Entregado",
        cancelacion_solicitada:
            "Cancelación solicitada",
        cancelado:
            "Cancelado"
    };

    return textos[estado] || estado;
}

function textoEstadoPago(estado) {

    const textos = {
        pendiente:
            "Pago pendiente",
        aprobado:
            "Pago aprobado",
        rechazado:
            "Pago rechazado",
        anulado:
            "Pago anulado",
        reembolso_pendiente:
            "Reembolso pendiente",
        reembolsado:
            "Reembolsado"
    };

    return textos[estado] || estado;
}

function claseEstado(estado) {

    if (
        estado === "pagado" ||
        estado === "entregado" ||
        estado === "listo_retiro"
    ) {
        return "aprobado";
    }

    if (
        estado === "cancelado"
    ) {
        return "cancelado";
    }

    if (
        estado ===
        "cancelacion_solicitada"
    ) {
        return "reembolso";
    }

    if (
        estado === "preparando" ||
        estado === "enviado"
    ) {
        return estado;
    }

    return "pendiente";
}

function claseEstadoPago(estado) {

    if (estado === "aprobado") {
        return "aprobado";
    }

    if (
        estado === "rechazado" ||
        estado === "anulado"
    ) {
        return "rechazado";
    }

    if (
        estado === "reembolsado" ||
        estado ===
            "reembolso_pendiente"
    ) {
        return "reembolso";
    }

    return "pendiente";
}

function normalizarTexto(valor) {

    return String(valor || "")
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(
            /[\u0300-\u036f]/g,
            ""
        );
}

function capitalizar(valor) {

    return (
        valor.charAt(0).toUpperCase() +
        valor.slice(1)
    );
}

function escaparHTML(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}