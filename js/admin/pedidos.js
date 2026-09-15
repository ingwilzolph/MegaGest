/* =====================================================
   GESTIÓN ADMINISTRATIVA DE PEDIDOS
===================================================== */

let pedidosAdmin = [];

let filtroEstadoPedido = "Todos";

let idPedidoAbierto = 0;

/* =====================================================
   INICIALIZACIÓN
===================================================== */

function inicializarGestionPedidos() {

    cargarResumenPedidos();
    cargarPedidosAdmin();

    configurarPestanasPedidos();
    configurarTarjetasPedidos();
    configurarFiltrosPedidos();
    configurarDialogPedido();
}

/* =====================================================
   PESTAÑAS
===================================================== */

function configurarPestanasPedidos() {

    document
        .querySelectorAll(".btnPestanaPedido")
        .forEach(boton => {

            boton.addEventListener(
                "click",
                function () {

                    filtroEstadoPedido =
                        this.dataset.estado || "Todos";

                    actualizarSeleccionPedido();
                    actualizarEncabezadoPedidos();
                    aplicarFiltrosPedidos();
                }
            );
        });
}

/* =====================================================
   TARJETAS
===================================================== */

function configurarTarjetasPedidos() {

    document
        .querySelectorAll(
            ".tarjetaResumenPedido[data-filtro]"
        )
        .forEach(tarjeta => {

            tarjeta.addEventListener(
                "click",
                function () {

                    filtroEstadoPedido =
                        this.dataset.filtro || "Todos";

                    actualizarSeleccionPedido();
                    actualizarEncabezadoPedidos();
                    aplicarFiltrosPedidos();
                }
            );
        });
}

/* =====================================================
   ACTUALIZAR SELECCIÓN
===================================================== */

function actualizarSeleccionPedido() {

    document
        .querySelectorAll(".btnPestanaPedido")
        .forEach(boton => {

            boton.classList.toggle(
                "activa",
                boton.dataset.estado ===
                    filtroEstadoPedido
            );
        });

    document
        .querySelectorAll(
            ".tarjetaResumenPedido[data-filtro]"
        )
        .forEach(tarjeta => {

            tarjeta.classList.toggle(
                "seleccionada",
                tarjeta.dataset.filtro ===
                    filtroEstadoPedido
            );
        });
}

/* =====================================================
   FILTROS
===================================================== */

function configurarFiltrosPedidos() {

    const buscador = document.getElementById(
        "buscarPedidoAdmin"
    );

    const canal = document.getElementById(
        "filtroCanalPedido"
    );

    const pago = document.getElementById(
        "filtroPagoPedido"
    );

    const orden = document.getElementById(
        "ordenPedidoAdmin"
    );

    const limpiar = document.getElementById(
        "btnLimpiarFiltrosPedido"
    );

    const limpiarVacio = document.getElementById(
        "btnLimpiarSinPedidos"
    );

    const estadoPedido = document.getElementById(
        "filtroEstadoPedidoSelect"
    );

    if (buscador) {

        buscador.addEventListener(
            "input",
            aplicarFiltrosPedidos
        );
    }

    if (canal) {

        canal.addEventListener(
            "change",
            aplicarFiltrosPedidos
        );
    }

    if (pago) {

        pago.addEventListener(
            "change",
            aplicarFiltrosPedidos
        );
    }

    if (estadoPedido) {
        estadoPedido.addEventListener(
            "change",
            aplicarFiltrosPedidos
        );
    }

    if (orden) {

        orden.addEventListener(
            "change",
            aplicarFiltrosPedidos
        );
    }

    if (limpiar) {

        limpiar.addEventListener(
            "click",
            limpiarFiltrosPedidos
        );
    }

    if (limpiarVacio) {

        limpiarVacio.addEventListener(
            "click",
            limpiarFiltrosPedidos
        );
    }
}

/* =====================================================
   CARGAR RESUMEN
===================================================== */

async function cargarResumenPedidos() {

    try {

        const response = await fetch(
            "./php/obtenerResumenPedidos.php",
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (resultado.sesionExpirada) {

            cerrarSesionPedidos();

            return;
        }

        if (!resultado.ok) {

            console.error(resultado.mensaje);

            return;
        }

        const datos = resultado.datos;

        asignarResumenPedido(
            "totalPedidosAdmin",
            datos.totalPedidos
        );

        asignarResumenPedido(
            "pedidosPendientesAdmin",
            datos.pedidosPendientes
        );

        asignarResumenPedido(
            "pedidosPagadosAdmin",
            datos.pedidosPagados
        );

        asignarResumenPedido(
            "pedidosPreparandoAdmin",
            datos.pedidosPreparando
        );

        asignarResumenPedido(
            "pedidosListosAdmin",
            datos.pedidosListos
        );

        asignarResumenPedido(
            "ventasMesPedidosAdmin",
            formatearPrecioPedido(
                datos.ventasMes
            )
        );

        asignarResumenPedido(
            "ventasHoyPedidosAdmin",
            `Hoy: ${formatearPrecioPedido(
                datos.ventasHoy
            )}`
        );

    } catch (error) {

        console.error(
            "Error cargando resumen de pedidos:",
            error
        );
    }
}

function asignarResumenPedido(id, valor) {

    const elemento = document.getElementById(id);

    if (elemento) {
        elemento.textContent = valor;
    }
}

/* =====================================================
   CARGAR PEDIDOS
===================================================== */

async function cargarPedidosAdmin() {

    mostrarCargandoPedidos();

    try {

        const response = await fetch(
            "./php/obtenerPedidosAdmin.php",
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (resultado.sesionExpirada) {

            cerrarSesionPedidos();

            return;
        }

        if (!resultado.ok) {

            mostrarErrorTablaPedidos(
                resultado.mensaje
            );

            return;
        }

        pedidosAdmin = Array.isArray(
            resultado.datos
        )
            ? resultado.datos
            : [];

        aplicarFiltrosPedidos();

    } catch (error) {

        console.error(
            "Error cargando pedidos:",
            error
        );

        mostrarErrorTablaPedidos(
            "No fue posible cargar los pedidos."
        );
    }
}

/* =====================================================
   APLICAR FILTROS
===================================================== */

function aplicarFiltrosPedidos() {

    const filtroEstadoSelect =
        document.getElementById(
            "filtroEstadoPedidoSelect"
        );

    const estadoSeleccionado =
        filtroEstadoSelect
            ? filtroEstadoSelect.value
            : "";

    const buscador = document.getElementById(
        "buscarPedidoAdmin"
    );

    const filtroCanal = document.getElementById(
        "filtroCanalPedido"
    );

    const filtroPago = document.getElementById(
        "filtroPagoPedido"
    );

    const orden = document.getElementById(
        "ordenPedidoAdmin"
    );

    const texto = buscador
        ? normalizarTextoPedido(buscador.value)
        : "";

    const canalSeleccionado = filtroCanal
        ? filtroCanal.value
        : "";

    const pagoSeleccionado = filtroPago
        ? filtroPago.value
        : "";

    const ordenSeleccionado = orden
        ? orden.value
        : "reciente";

    let pedidosFiltrados =
        pedidosAdmin.filter(pedido => {

            const contenidoBusqueda =
                normalizarTextoPedido(
                    [
                        pedido.numero_pedido,
                        pedido.cliente,
                        pedido.nombre_cliente,
                        pedido.apellido_cliente,
                        pedido.correo_cliente,
                        pedido.telefono_cliente,
                        pedido.rut
                    ].join(" ")
                );

            const coincideTexto =
                !texto ||
                contenidoBusqueda.includes(texto);

            const coincideCanal =
                !canalSeleccionado ||
                pedido.canal === canalSeleccionado;

            const coincidePago =
                !pagoSeleccionado ||
                pedido.estado_pago ===
                    pagoSeleccionado;

            const coincideEstadoSeleccionado =
                !estadoSeleccionado ||
                pedido.estado === estadoSeleccionado;

            let coincideEstado = true;

            if (
                filtroEstadoPedido !== "Todos"
            ) {

                /*
                 * La pestaña Pagados muestra todos los
                 * pedidos con pago aprobado, incluso si ya
                 * están preparando o listos para retirar.
                 */

                if (
                    filtroEstadoPedido === "pagado"
                ) {

                    coincideEstado =
                        pedido.estado_pago ===
                        "aprobado";

                } else {

                    coincideEstado =
                        pedido.estado ===
                        filtroEstadoPedido;
                }
            }

            return (
                coincideTexto &&
                coincideCanal &&
                coincidePago &&
                coincideEstado &&
                coincideEstadoSeleccionado
            );
        });

    pedidosFiltrados.sort((a, b) => {

        switch (ordenSeleccionado) {

            case "antiguo":

                return convertirFechaPedido(
                    a.fecha_pedido
                ) -
                convertirFechaPedido(
                    b.fecha_pedido
                );

            case "total_mayor":

                return (
                    Number(b.total) -
                    Number(a.total)
                );

            case "total_menor":

                return (
                    Number(a.total) -
                    Number(b.total)
                );

            case "cliente_asc":

                return String(a.cliente).localeCompare(
                    String(b.cliente),
                    "es",
                    {
                        sensitivity: "base"
                    }
                );

            default:

                return convertirFechaPedido(
                    b.fecha_pedido
                ) -
                convertirFechaPedido(
                    a.fecha_pedido
                );
        }
    });

    renderizarTablaPedidos(
        pedidosFiltrados
    );
}

/* =====================================================
   TABLA
===================================================== */

function renderizarTablaPedidos(pedidos) {

    const tbody = document.getElementById(
        "tbody_pedido"
    );

    const tabla = document.getElementById(
        "table_pedido"
    );

    const sinResultados = document.getElementById(
        "sinPedidosAdmin"
    );

    const contador = document.getElementById(
        "cantidadResultadosPedidos"
    );

    const textoResultados = document.getElementById(
        "textoResultadosPedidos"
    );

    if (!tbody) {
        return;
    }

    if (contador) {
        contador.textContent = pedidos.length;
    }

    if (textoResultados) {

        textoResultados.textContent =
            pedidos.length === 1
                ? "resultado"
                : "resultados";
    }

    tbody.innerHTML = "";

    if (pedidos.length === 0) {

        if (tabla) {
            tabla.style.display = "none";
        }

        if (sinResultados) {
            sinResultados.hidden = false;
            sinResultados.style.display = "flex";
        }

        return;
    }

    if (tabla) {
        tabla.style.display = "table";
    }

    if (sinResultados) {
        sinResultados.hidden = true;
        sinResultados.style.display = "none";
    }

    const fragmento =
        document.createDocumentFragment();

    pedidos.forEach(pedido => {

        fragmento.appendChild(
            crearFilaPedido(pedido)
        );
    });

    tbody.appendChild(fragmento);
}

/* =====================================================
   CREAR FILA
===================================================== */

function crearFilaPedido(pedido) {

    const fila = document.createElement("tr");

    const claseEstado =
        obtenerClaseEstadoPedido(
            pedido.estado
        );

    const clasePago =
        obtenerClasePagoPedido(
            pedido.estado_pago
        );

    const iconoCanal =
        pedido.canal === "online"
            ? "fa-globe"
            : "fa-cash-register";

    fila.innerHTML = `

        <td>

            <div class="infoNumeroPedido">

                <strong>
                    ${escaparHTMLPedido(
                        pedido.numero_pedido
                    )}
                </strong>

                <small>
                    Pedido N.º ${Number(
                        pedido.id_pedido
                    )}
                </small>

            </div>

        </td>


        <td>

            <div class="infoClientePedido">

                <div class="avatarClientePedido">

                    ${obtenerInicialesPedido(
                        pedido.nombre_cliente,
                        pedido.apellido_cliente
                    )}

                </div>

                <div>

                    <strong>
                        ${escaparHTMLPedido(
                            pedido.cliente
                        )}
                    </strong>

                    <small>
                        ${escaparHTMLPedido(
                            pedido.correo_cliente
                        )}
                    </small>

                </div>

            </div>

        </td>


        <td>

            <span class="canalPedidoAdmin">

                <i class="fa-solid ${iconoCanal}"></i>

                ${escaparHTMLPedido(
                    pedido.canal_visual
                )}

            </span>

        </td>


        <td>

            <div class="cantidadProductosPedido">

                <strong>
                    ${Number(
                        pedido.cantidad_productos
                    )}
                </strong>

                <span>
                    ${
                        Number(
                            pedido.cantidad_productos
                        ) === 1
                            ? "unidad"
                            : "unidades"
                    }
                </span>

            </div>

        </td>


        <td>

            <strong class="totalPedidoAdmin">

                ${formatearPrecioPedido(
                    pedido.total
                )}

            </strong>

        </td>


        <td>

            <span class="
                estadoPagoPedidoAdmin
                ${clasePago}
            ">

                <span></span>

                ${escaparHTMLPedido(
                    pedido.estado_pago_visual
                )}

            </span>

        </td>


        <td>

            <span class="
                estadoPedidoAdmin
                ${claseEstado}
            ">

                ${escaparHTMLPedido(
                    pedido.estado_visual
                )}

            </span>

        </td>


        <td>

            <div class="fechaPedidoAdmin">

                <i class="fa-regular fa-calendar"></i>

                <span>
                    ${formatearFechaPedido(
                        pedido.fecha_pedido
                    )}
                </span>

            </div>

        </td>


        <td>

            <div class="accionesPedidoAdmin">

                <button
                    type="button"
                    class="
                        btnAccionPedido
                        btnVerPedidoAdmin
                    "
                    data-id-pedido="${Number(
                        pedido.id_pedido
                    )}"
                    title="Ver pedido">

                    <i class="fa-solid fa-eye"></i>

                </button>

            </div>

        </td>
    `;

    const botonVer = fila.querySelector(
        ".btnVerPedidoAdmin"
    );

    botonVer.addEventListener(
        "click",
        function () {

            const idPedido = Number(
                this.dataset.idPedido
            );

            abrirDetallePedido(idPedido);
        }
    );

    return fila;
}

/* =====================================================
   CARGANDO Y ERROR
===================================================== */

function mostrarCargandoPedidos() {

    const tbody = document.getElementById(
        "tbody_pedido"
    );

    const tabla = document.getElementById(
        "table_pedido"
    );

    const sinResultados = document.getElementById(
        "sinPedidosAdmin"
    );

    if (tabla) {
        tabla.style.display = "table";
    }

    if (sinResultados) {
        sinResultados.hidden = true;
        sinResultados.style.display = "none";
    }

    if (tbody) {

        tbody.innerHTML = `

            <tr>

                <td
                    colspan="9"
                    class="cargandoPedidosAdmin">

                    <i class="fa-solid fa-spinner fa-spin"></i>

                    Cargando pedidos...

                </td>

            </tr>
        `;
    }
}

function mostrarErrorTablaPedidos(mensaje) {

    const tbody = document.getElementById(
        "tbody_pedido"
    );

    if (!tbody) {
        return;
    }

    tbody.innerHTML = `

        <tr>

            <td
                colspan="9"
                class="errorPedidosAdmin">

                <i class="fa-solid fa-triangle-exclamation"></i>

                ${escaparHTMLPedido(mensaje)}

            </td>

        </tr>
    `;
}

/* =====================================================
   ENCABEZADO
===================================================== */

function actualizarEncabezadoPedidos() {

    const titulo = document.getElementById(
        "tituloResultadosPedidos"
    );

    const descripcion = document.getElementById(
        "descripcionResultadosPedidos"
    );

    if (!titulo || !descripcion) {
        return;
    }

    const textos = {

        Todos: {
            titulo: "Todos los pedidos",
            descripcion:
                "Listado completo de pedidos registrados."
        },

        pendiente_pago: {
            titulo: "Pedidos pendientes de pago",
            descripcion:
                "Pedidos que todavía no tienen un pago aprobado."
        },

        pagado: {
            titulo: "Pedidos pagados",
            descripcion:
                "Pedidos cuyo pago fue aprobado correctamente."
        },

        preparando: {
            titulo: "Pedidos en preparación",
            descripcion:
                "Pedidos que actualmente están siendo preparados."
        },

        listo_retiro: {
            titulo: "Pedidos listos para retirar",
            descripcion:
                "Productos preparados y disponibles para el cliente."
        },

        entregado: {
            titulo: "Pedidos entregados",
            descripcion:
                "Pedidos que ya fueron entregados al cliente."
        },

        cancelado: {
            titulo: "Pedidos cancelados",
            descripcion:
                "Pedidos que fueron cancelados."
        },

        cancelacion_solicitada: {
            titulo: "Cancelaciones solicitadas",
            descripcion:
                "Pedidos cuya cancelación fue solicitada por el cliente."
        }
    };

    const informacion =
        textos[filtroEstadoPedido] ||
        textos.Todos;

    titulo.textContent = informacion.titulo;

    descripcion.textContent =
        informacion.descripcion;
}

/* =====================================================
   LIMPIAR FILTROS
===================================================== */

function limpiarFiltrosPedidos() {

    const buscador = document.getElementById(
        "buscarPedidoAdmin"
    );

    const canal = document.getElementById(
        "filtroCanalPedido"
    );

    const pago = document.getElementById(
        "filtroPagoPedido"
    );

    const orden = document.getElementById(
        "ordenPedidoAdmin"
    );

    const estadoPedido = document.getElementById(
        "filtroEstadoPedidoSelect"
    );

    if (estadoPedido) {
        estadoPedido.value = "";
    }

    if (buscador) {
        buscador.value = "";
    }

    if (canal) {
        canal.value = "";
    }

    if (pago) {
        pago.value = "";
    }

    if (orden) {
        orden.value = "reciente";
    }

    filtroEstadoPedido = "Todos";

    actualizarSeleccionPedido();
    actualizarEncabezadoPedidos();
    aplicarFiltrosPedidos();
}

/* =====================================================
   CONFIGURAR DIÁLOGO
===================================================== */

function configurarDialogPedido() {

    const dialogo = document.getElementById(
        "dialogDetallePedido"
    );

    const formulario = document.getElementById(
        "formEstadoPedido"
    );

    const cerrar = document.getElementById(
        "btnCerrarDialogPedido"
    );

    const cancelar = document.getElementById(
        "btnCancelarDialogPedido"
    );

    if (cerrar) {

        cerrar.addEventListener(
            "click",
            cerrarDialogPedido
        );
    }

    if (cancelar) {

        cancelar.addEventListener(
            "click",
            cerrarDialogPedido
        );
    }

    if (formulario) {

        formulario.addEventListener(
            "submit",
            guardarEstadoPedido
        );
    }

    if (dialogo) {

        dialogo.addEventListener(
            "click",
            function (event) {

                if (event.target === dialogo) {
                    cerrarDialogPedido();
                }
            }
        );
    }
}

/* =====================================================
   ABRIR DETALLE
===================================================== */

async function abrirDetallePedido(idPedido) {

    if (!idPedido) {
        return;
    }

    const dialogo = document.getElementById(
        "dialogDetallePedido"
    );

    const tbody = document.getElementById(
        "tbodyDetallePedido"
    );

    const respuesta = document.getElementById(
        "respuestaEstadoPedido"
    );

    idPedidoAbierto = idPedido;

    configurarEstadosDisponiblesPedido([]);

    const campoId = document.getElementById("idPedidoModificar");

    if (campoId) {
        campoId.value = "";
    }

    if (tbody) {

        tbody.innerHTML = `

            <tr>

                <td colspan="4">

                    <i class="fa-solid fa-spinner fa-spin"></i>

                    Cargando productos...

                </td>

            </tr>
        `;
    }

    if (respuesta) {
        respuesta.className = "respuestaEstadoPedido";

        respuesta.textContent = "";
    }

    if (dialogo && !dialogo.open) {

        /*
        * Reiniciar cualquier desplazamiento anterior.
        */

        dialogo.scrollTop = 0;

        const contenidoDialogo =
            dialogo.querySelector(".contenidoDialogPedido");

        if (contenidoDialogo) {
            contenidoDialogo.scrollTop = 0;
        }

        dialogo.showModal();

        /*
        * Se repite después de abrir porque el navegador
        * puede desplazar el diálogo al enfocar un elemento.
        */

        requestAnimationFrame(() => {

            dialogo.scrollTop = 0;

            if (contenidoDialogo) {
                contenidoDialogo.scrollTop = 0;
            }
        });
    }

    try {

        const response = await fetch(
            `./php/obtenerPedidoDetalle.php?id=${encodeURIComponent(idPedido)}&v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

                /*
         * Ignorar respuestas de un pedido que
         * ya fue cerrado o sustituido por otro.
         */

        if (idPedidoAbierto !== idPedido || !dialogo || !dialogo.open) {
            return;
        }

        if (resultado.sesionExpirada) {

            cerrarSesionPedidos();

            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaEstadoPedido(resultado.mensaje, false);

            return;
        }

        cargarDatosDialogPedido(resultado.datos);

    } catch (error) {

        console.error("Error cargando detalle del pedido:", error);

        mostrarRespuestaEstadoPedido("No fue posible cargar el pedido.", false);
    }
}


/* =====================================================
   CARGAR DIÁLOGO
===================================================== */

function cargarDatosDialogPedido(datos) {

    const pedido = datos.pedido;
    const productos = datos.productos || [];

    asignarTextoPedido("numeroPedidoDialog", pedido.numero_pedido);

    asignarTextoPedido("fechaPedidoDialog", formatearFechaHoraPedido(pedido.fecha_pedido));

    asignarTextoPedido("clientePedidoDialog", pedido.cliente);

    asignarTextoPedido("rutPedidoDialog", pedido.rut || "No informado");

    asignarTextoPedido("correoPedidoDialog", pedido.correo_cliente);

    asignarTextoPedido("telefonoPedidoDialog", pedido.telefono_cliente);

    asignarTextoPedido("canalPedidoDialog",  pedido.canal_visual);

    asignarTextoPedido("entregaPedidoDialog", pedido.entrega_visual);

    asignarTextoPedido("usuarioPedidoDialog", pedido.usuario_venta);

    asignarTextoPedido("pagoPedidoDialog", pedido.estado_pago_visual);

    asignarTextoPedido("netoPedidoDialog", formatearPrecioPedido(pedido.neto));

    asignarTextoPedido( "ivaPedidoDialog", formatearPrecioPedido(pedido.iva));

    const esDespacho = pedido.tipo_entrega === "despacho";

    asignarTextoPedido("etiquetaDespachoPedidoDialog", esDespacho ? "Despacho" : "Retiro en tienda");

    asignarTextoPedido("despachoPedidoDialog", esDespacho ? formatearPrecioPedido(pedido.costo_despacho) : "Gratis");

    asignarTextoPedido("totalPedidoDialog", formatearPrecioPedido(pedido.total));

    asignarTextoPedido("observacionesPedidoDialog", pedido.observaciones || "Sin observaciones.");

    const idPedido = document.getElementById( "idPedidoModificar");

    if (idPedido) {
        idPedido.value = pedido.id_pedido;
    }

    configurarEstadosDisponiblesPedido(datos.estados_disponibles);

    configurarReembolsoPresencialPedido(datos);

    renderizarProductosDialogPedido(productos);
}

/* =====================================================
   PRODUCTOS DEL DIÁLOGO
===================================================== */

function renderizarProductosDialogPedido(productos) {

    const tbody = document.getElementById(
        "tbodyDetallePedido"
    );

    if (!tbody) {
        return;
    }

    tbody.innerHTML = "";

    if (productos.length === 0) {

        tbody.innerHTML = `

            <tr>

                <td colspan="4">
                    El pedido no contiene productos.
                </td>

            </tr>
        `;

        return;
    }

    productos.forEach(producto => {

        const fila = document.createElement("tr");

        fila.innerHTML = `

            <td>

                <div class="productoDetallePedido">

                    <strong>
                        ${escaparHTMLPedido(
                            producto.nombre_producto
                        )}
                    </strong>

                    <small>
                        ${escaparHTMLPedido(
                            producto.sku ||
                            "Sin SKU"
                        )}
                        ·
                        ${escaparHTMLPedido(
                            producto.marca_producto ||
                            "Sin marca"
                        )}
                    </small>

                </div>

            </td>

            <td>
                ${formatearPrecioPedido(
                    producto.precio_unitario
                )}
            </td>

            <td>
                ${Number(producto.cantidad)}
            </td>

            <td>

                <strong>
                    ${formatearPrecioPedido(
                        producto.total_linea
                    )}
                </strong>

            </td>
        `;

        tbody.appendChild(fila);
    });
}

/* =====================================================
   GUARDAR ESTADO
===================================================== */

async function guardarEstadoPedido(event) {

    event.preventDefault();

    const idPedido = Number(
        document.getElementById(
            "idPedidoModificar"
        ).value
    );

    const estado = document.getElementById(
        "estadoPedidoModificar"
    ).value;

    const boton = document.getElementById(
        "btnGuardarEstadoPedido"
    );

    if (!idPedido || !estado) {

        mostrarRespuestaEstadoPedido(
            "Debe seleccionar un estado válido.",
            false
        );

        return;
    }

    let productosRecibidos = false;

    if (estado === "cancelacion_solicitada") {

        productosRecibidos = window.confirm(
            "Esta cancelación reintegrará todos los productos al stock.\n\n" +
            "Confirme que todas las unidades están físicamente en tienda " +
            "y aptas para volver a venderse.\n\n" +
            "Si el pedido ya fue entregado, debe haber recibido " +
            "primero la devolución completa del cliente.\n\n" +
            "¿Desea continuar?"
        );

        if (!productosRecibidos) {
            return;
        }
    }

    boton.disabled = true;

    boton.innerHTML = `

        <i class="fa-solid fa-spinner fa-spin"></i>

        Guardando...
    `;

    try {

        const datos = new FormData();

        datos.append("id_pedido", String(idPedido));

        datos.append("estado", estado);

        datos.append("productos_recibidos", productosRecibidos ? "1" : "0");

        const response = await fetch(
            "./php/modificarEstadoPedido.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (resultado.sesionExpirada) {

            cerrarSesionPedidos();

            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaEstadoPedido(
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaEstadoPedido(
            resultado.mensaje,
            true
        );

        await Promise.all([
            cargarPedidosAdmin(),
            cargarResumenPedidos()
        ]);

        setTimeout(() => {
            cerrarDialogPedido();
        }, 850);

    } catch (error) {

        console.error(
            "Error modificando estado:",
            error
        );

        mostrarRespuestaEstadoPedido(
            "No fue posible actualizar el estado.",
            false
        );

    } finally {

        boton.disabled = false;

        boton.innerHTML = `

            <i class="fa-solid fa-floppy-disk"></i>

            Guardar estado
        `;
    }
}

/* =====================================================
   CERRAR DIÁLOGO
===================================================== */

function cerrarDialogPedido() {

    const dialogo = document.getElementById(
        "dialogDetallePedido"
    );

    if (dialogo && dialogo.open) {
        dialogo.close();
    }

    idPedidoAbierto = 0;

    const formulario = document.getElementById(
        "formEstadoPedido"
    );

    if (formulario) {
        formulario.reset();
    }
}

/* =====================================================
   RESPUESTA DEL DIÁLOGO
===================================================== */

function mostrarRespuestaEstadoPedido(
    mensaje,
    correcto
) {

    const respuesta = document.getElementById(
        "respuestaEstadoPedido"
    );

    if (!respuesta) {
        return;
    }

    respuesta.className = correcto
        ? "respuestaEstadoPedido correcta"
        : "respuestaEstadoPedido error";

    respuesta.textContent = mensaje;
}


/* =====================================================
   CLASES
===================================================== */

function obtenerClaseEstadoPedido(estado) {

    const clases = {
        pendiente_pago: "pendiente",
        pagado: "pagado",
        preparando: "preparando",
        listo_retiro: "listo",
        enviado: "enviado",
        entregado: "entregado",
        cancelado: "cancelado"
    };

    return clases[estado] || "";
}

function obtenerClasePagoPedido(estado) {

    const clases = {
        pendiente: "pendiente",
        aprobado: "aprobado",
        rechazado: "rechazado",
        anulado: "anulado",
        reembolsado: "reembolsado"
    };

    return clases[estado] || "";
}

/* =====================================================
   FECHAS
===================================================== */

function convertirFechaPedido(fecha) {

    if (!fecha) {
        return new Date(0);
    }

    return new Date(
        String(fecha).replace(" ", "T")
    );
}

function formatearFechaPedido(fecha) {

    const fechaPedido =
        convertirFechaPedido(fecha);

    if (
        Number.isNaN(
            fechaPedido.getTime()
        )
    ) {
        return "Sin fecha";
    }

    return new Intl.DateTimeFormat(
        "es-CL",
        {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }
    ).format(fechaPedido);
}

function formatearFechaHoraPedido(fecha) {

    const fechaPedido =
        convertirFechaPedido(fecha);

    if (
        Number.isNaN(
            fechaPedido.getTime()
        )
    ) {
        return "Sin fecha";
    }

    return new Intl.DateTimeFormat(
        "es-CL",
        {
            dateStyle: "long",
            timeStyle: "short"
        }
    ).format(fechaPedido);
}

/* =====================================================
   AUXILIARES
===================================================== */

function formatearPrecioPedido(valor) {

    return new Intl.NumberFormat(
        "es-CL",
        {
            style: "currency",
            currency: "CLP",
            minimumFractionDigits: 0
        }
    ).format(Number(valor) || 0);
}

function normalizarTextoPedido(valor) {

    return String(valor ?? "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();
}

function obtenerInicialesPedido(
    nombre,
    apellido
) {

    const inicialNombre =
        String(nombre || "").trim().charAt(0);

    const inicialApellido =
        String(apellido || "").trim().charAt(0);

    return (
        inicialNombre +
        inicialApellido
    ).toUpperCase() || "CL";
}

function asignarTextoPedido(id, valor) {

    const elemento = document.getElementById(id);

    if (elemento) {
        elemento.textContent = valor ?? "—";
    }
}

function escaparHTMLPedido(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

/* =====================================================
   SESIÓN EXPIRADA
===================================================== */

function cerrarSesionPedidos() {

    localStorage.clear();

    window.location.href =
        "page-login.html";
}


/* =====================================================
   SELECTOR DE ESTADOS AUTORIZADOS
===================================================== */

function configurarEstadosDisponiblesPedido(
    estadosRecibidos
) {

    const selector = document.getElementById(
        "estadoPedidoModificar"
    );

    const boton = document.getElementById(
        "btnGuardarEstadoPedido"
    );

    if (!selector || !boton) {
        return;
    }

    const estados = Array.isArray(estadosRecibidos)
        ? estadosRecibidos
        : [];

    selector.replaceChildren();

    const opcionInicial =
        document.createElement("option");

    opcionInicial.value = "";

    opcionInicial.textContent =
        estados.length > 0
            ? "Seleccione el siguiente estado"
            : "Sin cambios de estado disponibles";

    opcionInicial.disabled = true;
    opcionInicial.selected = true;

    selector.appendChild(opcionInicial);

    estados.forEach(estado => {

        const opcion =
            document.createElement("option");

        opcion.value = estado.valor;
        opcion.textContent = estado.texto;

        selector.appendChild(opcion);
    });

    selector.disabled = estados.length === 0;
    selector.required = true;

    boton.disabled = true;

    /*
     * Asignar onchange evita acumular eventos
     * cada vez que se abre un pedido.
     */

    selector.onchange = () => {

        boton.disabled =
            selector.disabled ||
            selector.value === "";
    };
}


/* =====================================================
   REEMBOLSO PRESENCIAL
===================================================== */

function configurarReembolsoPresencialPedido(datos) {

    document.getElementById("btnReembolsoPresencial")?.remove();
    document.getElementById("btnReembolsoWebpay")?.remove();

    const permisos = datos.permisos_reembolso;
    const pedido = datos.pedido;
    const detalle = document.getElementById("dialogDetallePedido");

    if (
        detalle &&
        permisos?.gestionar &&
        pedido.canal === "online"
    ) {
        configurarBotonReembolsoWebpay(datos, detalle);
        return;
    }

    if (
        !detalle ||
        !permisos?.gestionar ||
        pedido.canal !== "presencial"
    ) {
        return;
    }

    const almacenamiento =
        `megagest:reembolso:${permisos.id_usuario}:${pedido.id_pedido}`;

    let pendiente;

    try {
        pendiente = sessionStorage.getItem(almacenamiento);
    } catch {
        mostrarRespuestaEstadoPedido(
            "El navegador no permite conservar la operación. " +
            "No se habilitará el registro del reembolso.",
            false
        );
        return;
    }

    /*
     * Una solicitud pendiente permite consultar/reintentar
     * exactamente la misma operación, aunque el servidor
     * ya la haya registrado y su respuesta se haya perdido.
     */
    if (!permisos.registrar && !pendiente) {
        return;
    }

    const boton = document.createElement("button");

    boton.id = "btnReembolsoPresencial";
    boton.type = "button";
    boton.textContent = pendiente
        ? "Revisar registro de reembolso pendiente"
        : "Registrar reembolso";

    boton.addEventListener("click", () => {
        abrirFormularioReembolsoPresencial(
            datos,
            almacenamiento
        );
    });

    const selectorEstado = detalle.querySelector(
        "#estadoPedidoModificar"
    );

    if (selectorEstado) {
        selectorEstado.insertAdjacentElement("afterend", boton);
    } else {
        const contenido = detalle.querySelector(
            ".contenidoDialogPedido"
        );

        (contenido || detalle).appendChild(boton);
    }

    boton.style.display = "inline-block";
    boton.style.margin = "12px 0";

}

function ubicarBotonReembolsoPedido(detalle, boton) {

    const selectorEstado = detalle.querySelector(
        "#estadoPedidoModificar"
    );

    if (selectorEstado) {
        selectorEstado.insertAdjacentElement("afterend", boton);
    } else {
        const contenido = detalle.querySelector(".contenidoDialogPedido");
        (contenido || detalle).appendChild(boton);
    }

    boton.style.display = "inline-block";
    boton.style.margin = "12px 0";
}

function configurarBotonReembolsoWebpay(datos, detalle) {

    const permisos = datos.permisos_reembolso;
    const pedido = datos.pedido;
    const almacenamiento =
        `megagest:reembolso-webpay:${permisos.id_usuario}:${pedido.id_pedido}`;

    let pendiente = null;

    try {
        pendiente = sessionStorage.getItem(almacenamiento);
    } catch {
        mostrarRespuestaEstadoPedido(
            "El navegador no permite conservar la operación Webpay.",
            false
        );
        return;
    }

    if (!permisos.registrar && !pendiente) {
        return;
    }

    const boton = document.createElement("button");
    boton.id = "btnReembolsoWebpay";
    boton.type = "button";
    boton.textContent = pendiente
        ? "Revisar reembolso Webpay pendiente"
        : "Reembolsar por Webpay";

    boton.addEventListener("click", () => {
        abrirFormularioReembolsoWebpay(datos, almacenamiento);
    });

    ubicarBotonReembolsoPedido(detalle, boton);
}

function abrirFormularioReembolsoWebpay(datos, almacenamiento) {

    document.getElementById("dialogReembolsoWebpay")?.remove();

    let solicitud = null;

    try {
        const guardado = sessionStorage.getItem(almacenamiento);
        solicitud = guardado ? JSON.parse(guardado) : null;
    } catch {
        mostrarRespuestaEstadoPedido(
            "La operación Webpay guardada no se puede leer. Revísela antes de continuar.",
            false
        );
        return;
    }

    const dialogo = document.createElement("dialog");
    dialogo.id = "dialogReembolsoWebpay";
    dialogo.innerHTML = `
        <form>
            <h2>Reembolso Webpay</h2>
            <p data-resumen></p>
            <p>
                Esta acción solicitará a Webpay la devolución total.
                No debe repetirse si la respuesta es incierta.
            </p>
            <fieldset>
                <label>
                    Motivo del reembolso
                    <br>
                    <textarea name="motivo" minlength="5"
                        maxlength="500" rows="3" required></textarea>
                </label>
                <p>
                    <label>
                        <input name="confirmacion" type="checkbox" required>
                        Confirmo que revisé el pedido, la devolución física
                        y el importe total antes de solicitar el reembolso.
                    </label>
                </p>
            </fieldset>
            <p
                data-mensaje
                role="status"
                aria-live="polite">
            </p>

            <div class="accionesReembolsoWebpay">

                <button type="submit">
                    Solicitar reembolso total
                </button>

                <button
                    type="button"
                    data-cerrar>
                    Cerrar
                </button>

            </div>
        </form>
    `;

    const formulario = dialogo.querySelector("form");
    const campos = dialogo.querySelector("fieldset");
    const mensaje = dialogo.querySelector("[data-mensaje]");
    const guardar = dialogo.querySelector('[type="submit"]');
    const cerrar = dialogo.querySelector("[data-cerrar]");
    let enviando = false;

    dialogo.querySelector("[data-resumen]").textContent =
        `Pedido ${datos.pedido.numero_pedido} — ` +
        `${formatearPrecioPedido(datos.pedido.total)}`;

    if (solicitud) {
        formulario.elements.namedItem("motivo").value = solicitud.motivo;
        formulario.elements.namedItem("confirmacion").checked = true;
        campos.disabled = true;
        guardar.textContent = "Verificar el mismo reembolso";
        mensaje.textContent =
            "Existe una solicitud sin confirmación local. No inicie otra devolución.";
    }

    cerrar.addEventListener("click", () => {
        if (!enviando) dialogo.close();
    });

    dialogo.addEventListener("cancel", event => {
        if (enviando) event.preventDefault();
    });

    dialogo.addEventListener("close", () => dialogo.remove(), { once: true });

    formulario.addEventListener("submit", async event => {
        event.preventDefault();
        if (enviando) return;

        if (!solicitud) {
            if (!formulario.reportValidity()) return;

            const motivo = formulario.elements.namedItem("motivo").value.trim();
            const confirma = window.confirm(
                `¿Solicitar a Webpay el reembolso total de ` +
                `${formatearPrecioPedido(datos.pedido.total)} del pedido ` +
                `${datos.pedido.numero_pedido}?\n\nEsta operación mueve dinero.`
            );

            if (!confirma) return;

            const bytes = crypto.getRandomValues(new Uint8Array(16));
            const clave = Array.from(
                bytes,
                valor => valor.toString(16).padStart(2, "0")
            ).join("");

            solicitud = {
                id_pedido: Number(datos.pedido.id_pedido),
                motivo,
                clave_operacion: clave,
                reembolso_confirmado: true
            };

            try {
                sessionStorage.setItem(
                    almacenamiento,
                    JSON.stringify(solicitud)
                );
            } catch {
                mensaje.textContent =
                    "No se pudo conservar la operación. No se envió a Webpay.";
                solicitud = null;
                return;
            }

            campos.disabled = true;
        }

        enviando = true;
        guardar.disabled = true;
        cerrar.disabled = true;
        mensaje.textContent = "Procesando el reembolso con Webpay…";

        try {
            const response = await fetch(
                "./php/procesarReembolsoWebpayAdmin.php",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "X-MegaGest-Reembolso": "1"
                    },
                    body: JSON.stringify(solicitud)
                }
            );

            const resultado = await response.json();

            if (resultado.sesionExpirada) {
                cerrarSesionPedidos();
                return;
            }

            if (!response.ok || !resultado.ok) {
                if (
                    resultado.reembolso_pendiente === false &&
                    !resultado.reembolso_confirmado_webpay &&
                    response.status < 500
                ) {
                    sessionStorage.removeItem(almacenamiento);
                    solicitud = null;
                    campos.disabled = false;
                }

                mensaje.textContent = resultado.mensaje ||
                    "No fue posible confirmar el reembolso. No lo repita.";
                return;
            }

            sessionStorage.removeItem(almacenamiento);
            guardar.hidden = true;
            document.getElementById("btnReembolsoWebpay")?.remove();
            mensaje.textContent = resultado.mensaje;

            await Promise.allSettled([
                cargarPedidosAdmin(),
                cargarResumenPedidos(),
                abrirDetallePedido(Number(datos.pedido.id_pedido))
            ]);

        } catch {
            mensaje.textContent =
                "No se pudo confirmar la respuesta. No repita la devolución; " +
                "use el mismo botón para verificar la operación guardada.";
        } finally {
            enviando = false;
            guardar.disabled = false;
            cerrar.disabled = false;
        }
    });

    document.body.appendChild(dialogo);
    dialogo.showModal();
}

function abrirFormularioReembolsoPresencial(
    datos,
    almacenamiento
) {

    const anterior = document.getElementById(
        "dialogReembolsoPresencial"
    );

    if (anterior?.open) {
        return;
    }

    anterior?.remove();

    let solicitudPendiente = null;

    try {

        const guardado = sessionStorage.getItem(almacenamiento);

        if (guardado) {

            solicitudPendiente = JSON.parse(guardado);

            if (
                solicitudPendiente.id_pedido !==
                    Number(datos.pedido.id_pedido) ||
                typeof solicitudPendiente.clave_operacion !== "string"
            ) {
                throw new Error("Registro local inválido.");
            }
        }

    } catch {

        mostrarRespuestaEstadoPedido(
            "No fue posible recuperar la operación pendiente. " +
            "Revise el registro antes de intentar otra devolución.",
            false
        );

        return;
    }

    const dialogo = document.createElement("dialog");

    dialogo.id = "dialogReembolsoPresencial";

    /*
     * Plantilla estática. Los datos del pedido se asignan
     * después con textContent, no mediante HTML.
     */
    dialogo.innerHTML = `
        <form>
            <h2>Registrar reembolso presencial</h2>

            <p data-resumen></p>

            <p>
                Este formulario registra una devolución ya realizada.
                No transfiere dinero ni modifica el stock.
            </p>

            <fieldset>
                <p>
                    <label>
                        Motivo de la devolución
                        <br>
                        <textarea
                            name="motivo"
                            minlength="5"
                            maxlength="500"
                            rows="3"
                            required
                        ></textarea>
                    </label>
                </p>

                <p>
                    <label>
                        Medio utilizado
                        <br>
                        <select name="medio" required>
                            <option value="">Seleccione</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">
                                Transferencia realizada
                            </option>
                            <option value="reversa_tarjeta">
                                Reversa de tarjeta confirmada fuera del sistema
                            </option>
                        </select>
                    </label>
                </p>

                <p>
                    <label>
                        Referencia o número de comprobante
                        <br>
                        <input
                            name="referencia"
                            type="text"
                            maxlength="150"
                            required
                        >
                    </label>
                </p>

                <p>
                    <label>
                        <input
                            name="confirmacion"
                            type="checkbox"
                            required
                        >
                        Confirmo que el importe total ya fue devuelto
                        al cliente y que verifiqué el comprobante.
                    </label>
                </p>
            </fieldset>

            <p data-mensaje role="status" aria-live="polite"></p>

            <div class="accionesReembolsoPresencial">

                <button type="submit">
                    Registrar devolución realizada
                </button>

                <button type="button" data-cerrar>
                    Cerrar
                </button>

            </div>
        </form>
    `;

    const formulario = dialogo.querySelector("form");
    const campos = dialogo.querySelector("fieldset");
    const resumen = dialogo.querySelector("[data-resumen]");
    const mensaje = dialogo.querySelector("[data-mensaje]");
    const guardar = dialogo.querySelector('[type="submit"]');
    const cerrar = dialogo.querySelector("[data-cerrar]");

    let enviando = false;

    resumen.textContent =
        `Pedido ${datos.pedido.numero_pedido} — ` +
        `Importe total: ${formatearPrecioPedido(datos.pedido.total)}`;

    function mostrarSolicitudPendiente() {

        formulario.elements.namedItem("motivo").value =
            solicitudPendiente.motivo;

        formulario.elements.namedItem("medio").value =
            solicitudPendiente.medio;

        formulario.elements.namedItem("referencia").value =
            solicitudPendiente.referencia;

        formulario.elements.namedItem("confirmacion").checked = true;

        campos.disabled = true;

        guardar.textContent = "Verificar / reenviar el mismo registro";

        mensaje.textContent =
            "Existe un registro enviado sin confirmación local. " +
            "Se conservarán sus datos y su identificador. " +
            "No vuelva a entregar dinero al cliente.";
    }

    if (solicitudPendiente) {
        mostrarSolicitudPendiente();
    }

    cerrar.addEventListener("click", () => {
        if (!enviando) {
            dialogo.close();
        }
    });

    dialogo.addEventListener("cancel", event => {
        if (enviando) {
            event.preventDefault();
        }
    });

    dialogo.addEventListener("close", () => {
        dialogo.remove();
    }, { once: true });

    formulario.addEventListener("submit", async event => {

        event.preventDefault();

        if (enviando) {
            return;
        }

        if (!solicitudPendiente) {

            if (!formulario.reportValidity()) {
                return;
            }

            const motivo = formulario.elements
                .namedItem("motivo").value.trim();

            const referencia = formulario.elements
                .namedItem("referencia").value.trim();

            if (motivo.length < 5 || !referencia) {
                mensaje.textContent =
                    "Complete el motivo y la referencia del comprobante.";
                return;
            }

            const confirma = window.confirm(
                `¿Registrar la devolución total de ` +
                `${formatearPrecioPedido(datos.pedido.total)} ` +
                `del pedido ${datos.pedido.numero_pedido}?\n\n` +
                "El dinero debe haber sido devuelto previamente. " +
                "El pedido quedará cancelado y el pago reembolsado."
            );

            if (!confirma) {
                return;
            }

            const bytes = crypto.getRandomValues(new Uint8Array(16));

            const clave = Array.from(
                bytes,
                valor => valor.toString(16).padStart(2, "0")
            ).join("");

            const nuevaSolicitud = {
                id_pedido: Number(datos.pedido.id_pedido),
                motivo,
                medio: formulario.elements.namedItem("medio").value,
                referencia,
                clave_operacion: clave,
                devolucion_confirmada: true
            };

            /*
             * Guardar antes de enviar. Si falla el almacenamiento,
             * no se realiza la petición.
             */
            try {

                sessionStorage.setItem(
                    almacenamiento,
                    JSON.stringify(nuevaSolicitud)
                );

            } catch {

                mensaje.textContent =
                    "No se pudo conservar la operación en el navegador. " +
                    "No se envió el registro.";

                return;
            }

            solicitudPendiente = nuevaSolicitud;
            mostrarSolicitudPendiente();
        }

        enviando = true;
        guardar.disabled = true;
        cerrar.disabled = true;
        mensaje.textContent = "Comprobando y registrando la devolución…";

        try {

            const response = await fetch(
                "./php/registrarReembolsoPresencial.php",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "X-MegaGest-Reembolso": "1"
                    },
                    body: JSON.stringify(solicitudPendiente)
                }
            );

            const resultado = await response.json();

            if (resultado.sesionExpirada) {

                mensaje.textContent =
                    "La sesión expiró. Inicie sesión nuevamente " +
                    "con el mismo usuario. No repita la devolución del dinero.";

                return;
            }

            if (!response.ok || !resultado.ok) {

                mensaje.textContent =
                    (resultado.mensaje || "No se confirmó el registro.") +
                    " Se conservará la solicitud para revisión; " +
                    "no vuelva a entregar dinero.";

                return;
            }

            /*
             * La respuesta confirma que esta operación quedó
             * registrada o que ya existía.
             */
            let almacenamientoLimpiado = true;

            try {
                sessionStorage.removeItem(almacenamiento);
            } catch {
                almacenamientoLimpiado = false;
            }

            mensaje.textContent =
                resultado.mensaje +
                (almacenamientoLimpiado
                    ? ""
                    : " No se pudo limpiar el registro local del navegador.");

            guardar.hidden = true;

            document.getElementById(
                "btnReembolsoPresencial"
            )?.remove();

            /*
             * Actualizar la información sin confundir un fallo
             * de recarga con un fallo del reembolso.
             */
            const actualizaciones = [
                cargarPedidosAdmin(),
                cargarResumenPedidos()
            ];

            if (
                Number(idPedidoAbierto) ===
                Number(datos.pedido.id_pedido)
            ) {
                actualizaciones.push(
                    abrirDetallePedido(Number(datos.pedido.id_pedido))
                );
            }

            await Promise.allSettled(actualizaciones);

        } catch {

            mensaje.textContent =
                "No se pudo confirmar la respuesta del servidor. " +
                "No vuelva a devolver dinero. Utilice “Verificar / " +
                "reenviar el mismo registro” para conservar la misma operación.";

        } finally {

            enviando = false;
            guardar.disabled = false;
            cerrar.disabled = false;
        }
    });

    document.body.appendChild(dialogo);
    dialogo.showModal();
}
