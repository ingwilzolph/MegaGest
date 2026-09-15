/* =====================================================
   LISTADO DE COTIZACIONES
===================================================== */

function inicializarGestionCotizaciones() {

    const contenido = document.getElementById("contenido");

    if (!contenido) {
        return;
    }

    contenido.innerHTML = `
        <section id="gestionCotizacionesAdmin">

            <h1>📋 Gestión de cotizaciones</h1>

            <p>
                Consulte las cotizaciones guardadas.
                Estas no representan pagos ni reservas de stock.
            </p>

            <div class="filtrosCotizacionesAdmin">

                <div>
                    <label for="buscarCotizacionAdmin">
                        Buscar
                    </label>

                    <input
                        type="search"
                        id="buscarCotizacionAdmin"
                        placeholder="Número, cliente o RUT">
                </div>

                <div>
                    <label for="vigenciaCotizacionAdmin">
                        Vigencia
                    </label>

                    <select id="vigenciaCotizacionAdmin">
                        <option value="">Todas</option>
                        <option value="vigente">Vigentes</option>
                        <option value="vencida">Vencidas</option>
                    </select>
                </div>

                <button
                    type="button"
                    id="actualizarCotizacionesAdmin">
                    Actualizar
                </button>

                <button
                    type="button"
                    id="nuevaCotizacionAdmin">
                    Nueva cotización
                </button>

            </div>

            <p
                id="mensajeCotizacionesAdmin"
                role="status"
                aria-live="polite">
            </p>

            <div class="tablaCotizacionesContenedor">

                <table
                    id="tablaCotizacionesAdmin"
                    class="tablaCotizacionesAdmin"
                    hidden>

                    <caption>Cotizaciones pendientes de conversión</caption>

                    <thead>
                        <tr>
                            <th scope="col">Número</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Creación</th>
                            <th scope="col">Vencimiento</th>
                            <th scope="col">Unidades</th>
                            <th scope="col">Total</th>
                            <th scope="col">Vigencia</th>
                            <th scope="col">Creada por</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="tbodyCotizacionesAdmin"></tbody>

                </table>

            </div>

        </section>
    `;

    /*
     * Cada apertura tiene sus propios datos y elementos.
     * Una respuesta tardía no modifica otra pestaña.
     */

    const seccion = document.getElementById(
        "gestionCotizacionesAdmin"
    );

    const buscador = seccion.querySelector(
        "#buscarCotizacionAdmin"
    );

    const filtro = seccion.querySelector(
        "#vigenciaCotizacionAdmin"
    );

    const actualizar = seccion.querySelector(
        "#actualizarCotizacionesAdmin"
    );

    const mensaje = seccion.querySelector(
        "#mensajeCotizacionesAdmin"
    );

    const tabla = seccion.querySelector(
        "#tablaCotizacionesAdmin"
    );

    const tbody = seccion.querySelector(
        "#tbodyCotizacionesAdmin"
    );

    let cotizaciones = [];
    let cargando = false;
    let cargaCorrecta = false;

    const moneda = new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        maximumFractionDigits: 0
    });

    function normalizar(valor) {

        return String(valor ?? "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim();
    }

    function mostrarFecha(valor) {

        if (!valor) {
            return "No informada";
        }

        const partes = String(valor).split(" ");
        const fecha = partes[0].split("-");

        if (fecha.length !== 3) {
            return String(valor);
        }

        const hora = partes[1]
            ? " " + partes[1].slice(0, 5)
            : "";

        return `${fecha[2]}/${fecha[1]}/${fecha[0]}${hora}`;
    }

    function renderizar() {

        if (!seccion.isConnected || !cargaCorrecta) {
            return;
        }

        const texto = normalizar(buscador.value);
        const vigencia = filtro.value;

        const visibles = cotizaciones.filter(cotizacion => {

            const contenidoBusqueda = normalizar([
                cotizacion.numero_cotizacion,
                cotizacion.cliente,
                cotizacion.rut
            ].join(" "));

            return (
                (!texto || contenidoBusqueda.includes(texto)) &&
                (!vigencia || cotizacion.vigencia === vigencia)
            );
        });

        tbody.replaceChildren();
        tabla.hidden = visibles.length === 0;

        if (visibles.length === 0) {

            mensaje.textContent = cotizaciones.length === 0
                ? "No hay cotizaciones pendientes de conversión."
                : "No hay cotizaciones que coincidan con los filtros.";

            return;
        }

        mensaje.textContent =
            `${visibles.length} ` +
            (visibles.length === 1
                ? "cotización encontrada."
                : "cotizaciones encontradas.");

        const fragmento = document.createDocumentFragment();

        visibles.forEach(cotizacion => {

            const fila = document.createElement("tr");

            const valores = [
                cotizacion.numero_cotizacion,
                cotizacion.cliente,
                mostrarFecha(cotizacion.fecha_cotizacion),
                mostrarFecha(
                    cotizacion.fecha_expiracion_cotizacion
                ),
                Number(cotizacion.cantidad_productos),
                moneda.format(Number(cotizacion.total) || 0),
                cotizacion.vigencia === "vigente"
                    ? "Vigente"
                    : "Vencida",
                cotizacion.usuario_creacion || "No informado"
            ];

            valores.forEach(valor => {

                const celda = document.createElement("td");

                // Los datos se insertan como texto, no como HTML.
                celda.textContent = String(valor ?? "—");

                fila.appendChild(celda);
            });

            const celdaAcciones = document.createElement("td");

            const botonVer = document.createElement("button");

            botonVer.type = "button";
            botonVer.textContent = "Ver detalle";

            botonVer.addEventListener("click", () => {
                abrirDetalleCotizacion(cotizacion);
            });

            const enlaceImprimir = document.createElement("a");

            enlaceImprimir.textContent = "Reimprimir";

            enlaceImprimir.href =
                "./php/imprimirDocumento.php?id=" +
                encodeURIComponent(cotizacion.id_pedido) +
                "&tipo=cotizacion&auto=1";

            enlaceImprimir.target = "_blank";
            enlaceImprimir.rel = "noopener noreferrer";
            enlaceImprimir.style.marginLeft = "10px";

            celdaAcciones.append(botonVer, enlaceImprimir);
            fila.appendChild(celdaAcciones);

            fragmento.appendChild(fila);
        });

        tbody.appendChild(fragmento);
    }


    async function abrirDetalleCotizacion(cotizacion) {

        const dialogo = document.createElement("dialog");

        dialogo.className = "dialogDetalleCotizacion";

        dialogo.innerHTML = `
            <header class="cabeceraDetalleCotizacion">

                <div class="iconoCabeceraCotizacion" aria-hidden="true">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>

                <div class="textoCabeceraCotizacion">
                    <span class="etiquetaCabeceraCotizacion">
                        GESTIÓN DE COTIZACIONES
                    </span>

                    <h2>Detalle de cotización</h2>

                    <p class="numeroDetalleCotizacion"></p>
                </div>

                <button
                    type="button"
                    class="cerrarDetalleCotizacion"
                    aria-label="Cerrar detalle">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

            </header>

            <div class="cuerpoDetalleCotizacion">

                <p
                    class="mensajeDetalleCotizacion"
                    role="status"
                    aria-live="polite">
                    Cargando información...
                </p>

                <div class="contenidoDetalleCotizacion" hidden>

                    <dl class="datosDetalleCotizacion"></dl>

                    <div class="tablaCotizacionesContenedor">

                        <table class="tablaCotizacionesAdmin">

                            <caption>Productos cotizados</caption>

                            <thead>
                                <tr>
                                    <th scope="col">Producto</th>
                                    <th scope="col">Precio unitario</th>
                                    <th scope="col">Cantidad</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>

                            <tbody></tbody>

                        </table>

                    </div>

                    <dl class="totalesDetalleCotizacion"></dl>

                    <p class="notaDetalleCotizacion">
                        Esta cotización no representa un pago
                        ni una reserva de existencias.
                    </p>

                </div>
            </div>
        `;

        dialogo.querySelector(
            ".numeroDetalleCotizacion"
        ).textContent = cotizacion.numero_cotizacion;

        const controlador = new AbortController();

        dialogo.querySelector(
            ".cerrarDetalleCotizacion"
        ).addEventListener("click", () => {
            dialogo.close();
        });

        dialogo.addEventListener("close", () => {
            controlador.abort();
            dialogo.remove();
        }, { once: true });

        seccion.appendChild(dialogo);
        dialogo.showModal();

        const mensajeDetalle = dialogo.querySelector(
            ".mensajeDetalleCotizacion"
        );

        function agregarDato(contenedor, etiqueta, valor) {

            const termino = document.createElement("dt");
            const descripcion = document.createElement("dd");

            termino.textContent = etiqueta;
            descripcion.textContent = String(valor ?? "—");

            contenedor.append(termino, descripcion);
        }

        try {

            const response = await fetch(
                "./php/obtenerPedidoDetalle.php?id=" +
                    encodeURIComponent(cotizacion.id_pedido),
                {
                    cache: "no-store",
                    signal: controlador.signal
                }
            );

            const resultado = await response.json();

            if (!dialogo.isConnected || !dialogo.open) {
                return;
            }

            if (manejarSesionExpirada(resultado)) {
                dialogo.close();
                return;
            }

            if (!response.ok || !resultado.ok) {
                throw new Error(
                    resultado.mensaje ||
                    "No fue posible cargar el detalle."
                );
            }

            const pedido = resultado.datos?.pedido;
            const productos = resultado.datos?.productos;

            if (!pedido || !Array.isArray(productos)) {
                throw new Error(
                    "El detalle recibido no tiene el formato esperado."
                );
            }

            if (pedido.estado !== "cotizacion") {
                throw new Error(
                    "Este registro ya no está en estado cotización. " +
                    "Cierre el detalle y actualice el listado."
                );
            }

            const informacion = dialogo.querySelector(
                ".datosDetalleCotizacion"
            );

            agregarDato(informacion, "Cliente", pedido.cliente);
            agregarDato(informacion, "RUT", pedido.rut || "No informado");
            agregarDato(
                informacion,
                "Correo",
                pedido.correo_cliente || "No informado"
            );
            agregarDato(
                informacion,
                "Teléfono",
                pedido.telefono_cliente || "No informado"
            );
            agregarDato(
                informacion,
                "Observaciones",
                pedido.observaciones || "Sin observaciones."
            );

            const cuerpoTabla = dialogo.querySelector("tbody");

            productos.forEach(producto => {

                const fila = document.createElement("tr");

                const valores = [
                    producto.nombre_producto,
                    moneda.format(Number(producto.precio_unitario) || 0),
                    Number(producto.cantidad),
                    moneda.format(Number(producto.total_linea) || 0)
                ];

                valores.forEach((valor, indice) => {

                    const celda = document.createElement("td");

                    if (indice === 0) {

                        const nombre = document.createElement("strong");
                        nombre.textContent = producto.nombre_producto || "Producto";

                        const sku = document.createElement("small");
                        sku.className = "skuDebajoProducto";
                        sku.textContent = "SKU: " + (producto.sku || "Sin SKU");

                        celda.append(nombre, sku);

                    } else {
                        celda.textContent = String(valor ?? "—");
                    }

                    fila.appendChild(celda);
                });

                cuerpoTabla.appendChild(fila);
            });

            const totales = dialogo.querySelector(
                ".totalesDetalleCotizacion"
            );

            agregarDato(
                totales,
                "Neto",
                moneda.format(Number(pedido.neto) || 0)
            );

            agregarDato(
                totales,
                "IVA",
                moneda.format(Number(pedido.iva) || 0)
            );

            agregarDato(
                totales,
                "Total",
                moneda.format(Number(pedido.total) || 0)
            );

            mensajeDetalle.textContent = productos.length
                ? ""
                : "La cotización no contiene productos.";

            dialogo.querySelector(
                ".contenidoDetalleCotizacion"
            ).hidden = false;

        } catch (error) {

            if (error.name === "AbortError") {
                return;
            }

            console.error("Error consultando cotización:", error);

            if (dialogo.isConnected && dialogo.open) {
                mensajeDetalle.textContent = error.message ||
                    "No fue posible cargar el detalle.";
            }
        }
    }

    async function cargarListado() {

        if (cargando) {
            return;
        }

        cargando = true;
        cargaCorrecta = false;
        actualizar.disabled = true;

        tabla.hidden = true;
        tbody.replaceChildren();
        mensaje.textContent = "Cargando cotizaciones...";

        try {

            const response = await fetch(
                "./php/obtenerCotizaciones.php",
                {
                    cache: "no-store"
                }
            );

            const resultado = await response.json();

            if (!seccion.isConnected) {
                return;
            }

            if (manejarSesionExpirada(resultado)) {
                return;
            }

            if (!response.ok || !resultado.ok) {
                throw new Error(
                    resultado.mensaje ||
                    "No fue posible cargar las cotizaciones."
                );
            }

            if (!Array.isArray(resultado.datos)) {
                throw new Error(
                    "La respuesta de cotizaciones no es válida."
                );
            }

            cotizaciones = resultado.datos;
            cargaCorrecta = true;

            renderizar();

        } catch (error) {

            console.error(
                "Error cargando cotizaciones:",
                error
            );

            if (seccion.isConnected) {
                mensaje.textContent = error.message ||
                    "No fue posible cargar las cotizaciones.";
            }

        } finally {

            cargando = false;

            if (seccion.isConnected) {
                actualizar.disabled = false;
            }
        }
    }

    buscador.addEventListener("input", renderizar);
    filtro.addEventListener("change", renderizar);
    actualizar.addEventListener("click", cargarListado);

    seccion.querySelector(
        "#nuevaCotizacionAdmin"
    ).addEventListener("click", () => {
        cargar("ventas");
    });

    cargarListado();
}
