let datosRentabilidadAdmin = null;

function inicializarGestionRentabilidad() {
    configurarFiltrosRentabilidad();
    establecerMesActualRentabilidad();
    cargarRentabilidadAdmin();
}

function configurarFiltrosRentabilidad() {
    const consultar = document.getElementById("btnAplicarRentabilidad");
    const limpiar = document.getElementById("btnLimpiarRentabilidad");

    if (consultar) {
        consultar.addEventListener("click", cargarRentabilidadAdmin);
    }

    if (limpiar) {
        limpiar.addEventListener("click", () => {
            establecerMesActualRentabilidad();
            const canal = document.getElementById("canalRentabilidad");
            if (canal) canal.value = "";
            cargarRentabilidadAdmin();
        });
    }
}

function establecerMesActualRentabilidad() {
    const hoy = new Date();
    const inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

    asignarValorRentabilidad("desdeRentabilidad", formatearFechaInputRentabilidad(inicio));
    asignarValorRentabilidad("hastaRentabilidad", formatearFechaInputRentabilidad(hoy));
}

async function cargarRentabilidadAdmin() {
    const desde = document.getElementById("desdeRentabilidad")?.value || "";
    const hasta = document.getElementById("hastaRentabilidad")?.value || "";
    const canal = document.getElementById("canalRentabilidad")?.value || "";
    const boton = document.getElementById("btnAplicarRentabilidad");

    if (!desde || !hasta) {
        mostrarErrorRentabilidad("Debe seleccionar el período de consulta.");
        return;
    }

    if (desde > hasta) {
        mostrarErrorRentabilidad("La fecha inicial no puede ser posterior a la fecha final.");
        return;
    }

    if (boton) {
        boton.disabled = true;
        boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando...';
    }

    try {
        const parametros = new URLSearchParams({desde, hasta});
        if (canal) parametros.set("canal", canal);

        const response = await fetch(
            `./php/obtenerRentabilidad.php?${parametros.toString()}&v=${Date.now()}`,
            {cache: "no-store"}
        );

        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            localStorage.clear();
            window.location.href = "page-login.html";
            return;
        }

        if (!resultado.ok) throw new Error(resultado.mensaje);

        datosRentabilidadAdmin = resultado;
        renderizarRentabilidadAdmin(resultado);
    } catch (error) {
        mostrarErrorRentabilidad(
            error.message || "No fue posible cargar la rentabilidad."
        );
    } finally {
        if (boton) {
            boton.disabled = false;
            boton.innerHTML = '<i class="fa-solid fa-magnifying-glass-chart"></i> Consultar';
        }
    }
}

function renderizarRentabilidadAdmin(datos) {
    const resumen = datos.resumen || {};

    asignarTextoRentabilidad("ventasRentabilidad", formatearDineroRentabilidad(resumen.ingresos_totales));
    asignarTextoRentabilidad("costosRentabilidad", formatearDineroRentabilidad(resumen.costos_totales));
    asignarTextoRentabilidad("gananciaRentabilidad", formatearDineroRentabilidad(resumen.ganancia_bruta));
    asignarTextoRentabilidad("margenRentabilidad", formatearPorcentajeRentabilidad(resumen.margen_bruto));
    asignarTextoRentabilidad("ticketRentabilidad", formatearDineroRentabilidad(resumen.ticket_promedio));
    asignarTextoRentabilidad("pedidosRentabilidad", Number(resumen.cantidad_operaciones) || 0);

    const cantidadReembolsos = Number(resumen.cantidad_reembolsos) || 0;
    asignarTextoRentabilidad(
        "reembolsosRentabilidad",
        `${cantidadReembolsos} ${cantidadReembolsos === 1 ? "reembolso" : "reembolsos"} · ${formatearDineroRentabilidad(resumen.monto_reembolsado)}`
    );

    asignarTextoRentabilidad(
        "periodoRentabilidad",
        `${formatearFechaRentabilidad(datos.filtros?.desde)} — ${formatearFechaRentabilidad(datos.filtros?.hasta)}`
    );

    mostrarAlertaCostosRentabilidad(Number(resumen.lineas_sin_costo) || 0);
    renderizarCanalesRentabilidad(datos.canales || []);
    renderizarProductosRentabilidad(datos.productos || []);
    renderizarEvolucionRentabilidad(datos.evolucion || []);
}

function mostrarAlertaCostosRentabilidad(cantidad) {
    const alerta = document.getElementById("alertaCostosRentabilidad");
    if (!alerta) return;

    alerta.hidden = cantidad === 0;
    alerta.innerHTML = cantidad > 0
        ? `<i class="fa-solid fa-triangle-exclamation"></i> ${cantidad} líneas de productos o servicios no tienen costo histórico. La ganancia puede estar sobreestimada.`
        : "";
}

function renderizarCanalesRentabilidad(canales) {
    const contenedor = document.getElementById("contenidoCanalesRentabilidad");
    if (!contenedor) return;

    if (canales.length === 0) {
        contenedor.innerHTML = '<div class="sinDatosRentabilidad">No hay ventas en el período.</div>';
        return;
    }

    contenedor.innerHTML = canales.map(canal => {
        const esOnline = canal.canal === "online";
        const esTaller = canal.canal === "taller";
        const nombreCanal = esTaller
            ? "Taller y servicios"
            : esOnline
                ? "En línea"
                : "Presencial";
        const iconoCanal = esTaller
            ? "fa-screwdriver-wrench"
            : esOnline
                ? "fa-globe"
                : "fa-cash-register";
        const tipoOperacion = esTaller ? "órdenes" : "pedidos";

        return `
        <article class="filaCanalRentabilidad">
            <div class="nombreCanalRentabilidad">
                <i class="fa-solid ${iconoCanal}"></i>
                <div>
                    <strong>${nombreCanal}</strong>
                    <small>${Number(canal.cantidad_pedidos)} ${tipoOperacion}</small>
                </div>
            </div>
            <div><span>Ventas</span><strong>${formatearDineroRentabilidad(canal.ventas_productos)}</strong></div>
            <div><span>Ganancia</span><strong class="textoGananciaRentabilidad">${formatearDineroRentabilidad(canal.ganancia_bruta)}</strong></div>
            <div><span>Margen</span><strong>${formatearPorcentajeRentabilidad(canal.margen)}</strong></div>
        </article>
        `;
    }).join("");
}

function renderizarProductosRentabilidad(productos) {
    const tbody = document.getElementById("tbodyProductosRentabilidad");
    if (!tbody) return;

    if (productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="sinDatosRentabilidad">No hay productos vendidos en el período.</td></tr>';
        return;
    }

    tbody.innerHTML = productos.map((producto, indice) => `
        <tr>
            <td>
                <div class="productoRentabilidadAdmin">
                    <span class="posicionProductoRentabilidad">${indice + 1}</span>
                    <div>
                        <strong>${escaparHTMLRentabilidad(producto.nombre_producto)}</strong>
                        <small>SKU: ${escaparHTMLRentabilidad(producto.sku || "Sin SKU")} · ${escaparHTMLRentabilidad(producto.marca_producto || "Sin marca")}</small>
                    </div>
                </div>
            </td>
            <td>${Number(producto.unidades)}</td>
            <td>${formatearDineroRentabilidad(producto.ventas)}</td>
            <td>${formatearDineroRentabilidad(producto.costo)}</td>
            <td><strong class="textoGananciaRentabilidad">${formatearDineroRentabilidad(producto.ganancia)}</strong></td>
            <td><span class="badgeMargenRentabilidad">${formatearPorcentajeRentabilidad(producto.margen)}</span></td>
        </tr>
    `).join("");
}

function renderizarEvolucionRentabilidad(evolucion) {

    const contenedor = document.getElementById(
        "graficoEvolucionRentabilidad"
    );

    if (!contenedor) return;

    if (evolucion.length === 0) {

        contenedor.innerHTML = `
            <div class="sinDatosRentabilidad">
                No hay información para graficar.
            </div>
        `;

        return;
    }

    const maximo = Math.max(
        ...evolucion.map(
            dia => Number(dia.ventas) || 0
        ),
        1
    );

    contenedor.innerHTML = `

        <div class="leyendaGraficoRentabilidad">

            <span>
                <i class="leyendaVentas"></i>
                Ventas
            </span>

            <span>
                <i class="leyendaCostos"></i>
                Costos
            </span>

            <span>
                <i class="leyendaGanancia"></i>
                Ganancia
            </span>

        </div>

        <div class="barrasRentabilidad">

            ${evolucion.map(dia => `

                <div class="filaGraficoRentabilidad">

                    <span class="fechaGraficoRentabilidad">
                        ${formatearFechaRentabilidad(dia.fecha)}
                    </span>

                    <div class="grupoBarrasRentabilidad">

                        <div
                            class="barraRentabilidad barraVentasRentabilidad"
                            style="--ancho-barra: ${
                                calcularAnchoRentabilidad(
                                    dia.ventas,
                                    maximo
                                )
                            }%;"
                        >
                            <span>
                                ${formatearDineroRentabilidad(
                                    dia.ventas
                                )}
                            </span>
                        </div>

                        <div
                            class="barraRentabilidad barraCostosRentabilidad"
                            style="--ancho-barra: ${
                                calcularAnchoRentabilidad(
                                    dia.costo,
                                    maximo
                                )
                            }%;"
                        >
                            <span>
                                ${formatearDineroRentabilidad(
                                    dia.costo
                                )}
                            </span>
                        </div>

                        <div
                            class="barraRentabilidad barraGananciaRentabilidad"
                            style="--ancho-barra: ${
                                calcularAnchoRentabilidad(
                                    dia.ganancia,
                                    maximo
                                )
                            }%;"
                        >
                            <span>
                                ${formatearDineroRentabilidad(
                                    dia.ganancia
                                )}
                            </span>
                        </div>

                    </div>

                </div>

            `).join("")}

        </div>
    `;
}

function calcularAnchoRentabilidad(valor, maximo) {
    if (Number(valor) <= 0) return 0;
    return Math.max(8, Math.round((Number(valor) / maximo) * 100));
}

function mostrarErrorRentabilidad(mensaje) {
    const tbody = document.getElementById("tbodyProductosRentabilidad");
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="6" class="errorRentabilidad"><i class="fa-solid fa-triangle-exclamation"></i> ${escaparHTMLRentabilidad(mensaje)}</td></tr>`;
    }
}

function formatearDineroRentabilidad(valor) {
    return new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        minimumFractionDigits: 0
    }).format(Number(valor) || 0);
}

function formatearPorcentajeRentabilidad(valor) {
    return `${new Intl.NumberFormat("es-CL", {minimumFractionDigits: 0, maximumFractionDigits: 2}).format(Number(valor) || 0)}%`;
}

function formatearFechaRentabilidad(fecha) {
    if (!fecha) return "Sin fecha";
    const partes = String(fecha).split("-").map(Number);
    if (partes.length !== 3) return fecha;
    return new Intl.DateTimeFormat("es-CL").format(new Date(partes[0], partes[1] - 1, partes[2]));
}

function formatearFechaInputRentabilidad(fecha) {
    const desplazamiento = fecha.getTimezoneOffset() * 60000;
    return new Date(fecha.getTime() - desplazamiento).toISOString().slice(0, 10);
}

function asignarTextoRentabilidad(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = valor;
}

function asignarValorRentabilidad(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.value = valor;
}

function escaparHTMLRentabilidad(valor) {
    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}
