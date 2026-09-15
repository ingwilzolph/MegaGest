/* =====================================================
   GESTIÓN ADMINISTRATIVA DE INVENTARIO
===================================================== */

let productosInventarioAdmin = [];
let proveedoresInventarioAdmin = [];
let permisosInventarioAdmin = {};
let productosEntradaInventario = [];
let entradasInventarioAdmin = [];
let movimientosInventarioAdmin = [];

function inicializarGestionInventario() {
    configurarPestanasInventario();
    configurarFiltrosInventario();
    configurarDialogProveedorInventario();
    configurarDialogEntradaInventario();
    configurarDialogAjusteInventario();
    configurarDialogDetalleEntradaInventario();

    Promise.all([
        cargarInventarioAdmin(),
        cargarProveedoresInventario(),
        cargarEntradasInventario(),
        cargarMovimientosInventario()
    ]).catch(error => {
        console.error("Error inicializando inventario:", error);
    });
}

/* =====================================================
   PESTAÑAS
===================================================== */

function configurarPestanasInventario() {
    document
        .querySelectorAll(".btnPestanaInventario")
        .forEach(boton => {
            boton.addEventListener("click", function () {
                const seccion = this.dataset.seccion;

                document
                    .querySelectorAll(".btnPestanaInventario")
                    .forEach(elemento => {
                        elemento.classList.toggle(
                            "activa",
                            elemento === this
                        );
                    });

                document
                    .querySelectorAll(".seccionInventario")
                    .forEach(elemento => {
                        elemento.classList.remove("activa");
                        elemento.hidden = true;
                    });

                const nombres = {
                    existencias: "seccionExistenciasInventario",
                    movimientos: "seccionMovimientosInventario",
                    entradas: "seccionEntradasInventario",
                    proveedores: "seccionProveedoresInventario"
                };

                const destino = document.getElementById(
                    nombres[seccion] || nombres.existencias
                );

                if (destino) {
                    destino.hidden = false;
                    destino.classList.add("activa");
                }
            });
        });
}

/* =====================================================
   INVENTARIO
===================================================== */

async function cargarInventarioAdmin() {
    try {
        const response = await fetch(
            "./php/obtenerInventario.php",
            {cache: "no-store"}
        );

        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) {
            throw new Error(resultado.mensaje);
        }

        productosInventarioAdmin = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        permisosInventarioAdmin = resultado.permisos || {};

        mostrarResumenInventario(resultado.resumen || {});
        aplicarPermisosInventario();
        aplicarFiltrosInventario();
    } catch (error) {
        console.error("Error cargando inventario:", error);
        mostrarErrorInventario(
            error.message || "No fue posible cargar el inventario."
        );
    }
}

function mostrarResumenInventario(resumen) {

    asignarTextoInventario(
        "totalProductosInventario",
        Number(
            resumen.total_productos
        ) || 0
    );

    asignarTextoInventario(
        "totalUnidadesInventario",
        Number(
            resumen.total_unidades
        ) || 0
    );

    asignarTextoInventario(
        "productosBajoStockInventario",
        Number(
            resumen.productos_bajo_stock
        ) || 0
    );

    asignarTextoInventario(
        "productosAgotadosInventario",
        Number(
            resumen.productos_agotados
        ) || 0
    );

    /*
     * Calcular el valor del inventario usando
     * el costo de compra y el stock actual.
     */

    const valorCalculado =
        productosInventarioAdmin.reduce(
            (acumulado, producto) => {

                const cantidad =
                    Number(
                        producto.cantidad
                    ) || 0;

                const costo =
                    Number(
                        producto.compra
                    ) || 0;

                const valorStock =
                    producto.valor_stock !== null &&
                    producto.valor_stock !== undefined
                        ? Number(
                            producto.valor_stock
                        ) || 0
                        : cantidad * costo;

                return acumulado + valorStock;
            },
            0
        );

    const puedeVerCostos =
        Boolean(
            permisosInventarioAdmin.ver_costos
        );

    asignarTextoInventario(
        "valorTotalInventarioAdmin",
        puedeVerCostos
            ? formatearPrecioInventario(
                resumen.valor_inventario ??
                valorCalculado
            )
            : "Información restringida"
    );
}

function configurarFiltrosInventario() {
    const buscador = document.getElementById("buscarProductoInventario");
    const estado = document.getElementById("filtroEstadoInventario");
    const orden = document.getElementById("ordenInventario");
    const limpiar = document.getElementById("btnLimpiarFiltrosInventario");
    const buscarProveedor = document.getElementById("buscarProveedorInventario");

    if (buscador) {
        buscador.addEventListener("input", aplicarFiltrosInventario);
    }

    if (estado) {
        estado.addEventListener("change", aplicarFiltrosInventario);
    }

    if (orden) {
        orden.addEventListener("change", aplicarFiltrosInventario);
    }

    if (limpiar) {
        limpiar.addEventListener("click", () => {
            if (buscador) buscador.value = "";
            if (estado) estado.value = "";
            if (orden) orden.value = "nombre";
            aplicarFiltrosInventario();
        });
    }

    if (buscarProveedor) {
        buscarProveedor.addEventListener(
            "input",
            aplicarFiltroProveedoresInventario
        );
    }
}

function aplicarFiltrosInventario() {
    const buscador = document.getElementById("buscarProductoInventario");
    const estado = document.getElementById("filtroEstadoInventario");
    const orden = document.getElementById("ordenInventario");

    const texto = normalizarTextoInventario(
        buscador ? buscador.value : ""
    );

    const estadoSeleccionado = estado ? estado.value : "";
    const ordenSeleccionado = orden ? orden.value : "nombre";

    const productos = productosInventarioAdmin.filter(producto => {
        const contenido = normalizarTextoInventario([
            producto.nombre,
            producto.sku,
            producto.marca,
            producto.categoria
        ].join(" "));

        return (
            (!texto || contenido.includes(texto)) &&
            (!estadoSeleccionado || producto.estado === estadoSeleccionado)
        );
    });

    productos.sort((a, b) => {
        switch (ordenSeleccionado) {
            case "stock_menor":
                return Number(a.cantidad) - Number(b.cantidad);
            case "stock_mayor":
                return Number(b.cantidad) - Number(a.cantidad);
            case "valor_mayor":
                return Number(b.valor_stock || 0) - Number(a.valor_stock || 0);
            default:
                return String(a.nombre || "").localeCompare(
                    String(b.nombre || ""),
                    "es",
                    {sensitivity: "base"}
                );
        }
    });

    renderizarInventario(productos);
}

function renderizarInventario(productos) {
    const tbody = document.getElementById("tbodyInventario");
    const tabla = document.getElementById("tablaInventarioAdmin");
    const vacio = document.getElementById("sinProductosInventario");

    if (!tbody) return;

    asignarTextoInventario("cantidadResultadosInventario", productos.length);
    tbody.innerHTML = "";

    if (productos.length === 0) {
        if (tabla) tabla.style.display = "none";
        if (vacio) {
            vacio.hidden = false;
            vacio.style.display = "flex";
        }
        return;
    }

    if (tabla) tabla.style.display = "table";
    if (vacio) {
        vacio.hidden = true;
        vacio.style.display = "none";
    }

    const fragmento = document.createDocumentFragment();

    productos.forEach(producto => {
        const fila = document.createElement("tr");
        const puedeGestionar = Boolean(
            permisosInventarioAdmin.ajustar_stock
        );

        fila.innerHTML = `
            <td>
                <div class="productoInventarioAdmin">
                    <strong>${escaparHTMLInventario(producto.nombre)}</strong>
                    <small>${escaparHTMLInventario(producto.categoria)}</small>
                </div>
            </td>
            <td>${escaparHTMLInventario(producto.sku)}</td>
            <td>${escaparHTMLInventario(producto.marca)}</td>
            <td><strong>${Number(producto.cantidad)}</strong></td>
            <td>${Number(producto.stock_minimo)}</td>
            <td class="columnaCostoInventario">
                ${producto.compra === null
                    ? "—"
                    : formatearPrecioInventario(producto.compra)}
            </td>
            <td class="columnaCostoInventario">
                ${producto.valor_stock === null
                    ? "—"
                    : formatearPrecioInventario(producto.valor_stock)}
            </td>
            <td>
                <span class="estadoInventario ${escaparHTMLInventario(producto.estado)}">
                    ${escaparHTMLInventario(producto.estado_visual)}
                </span>
            </td>
            <td>
                ${puedeGestionar
                    ? `<button type="button"
                            class="btnAjustarInventario"
                            data-id-producto="${Number(producto.id_producto)}"
                            title="Ajustar stock">
                            <i class="fa-solid fa-sliders"></i>
                       </button>`
                    : "—"}
            </td>
        `;

        const boton = fila.querySelector(".btnAjustarInventario");

        if (boton) {
            boton.addEventListener("click", function () {
                const idProducto = Number(this.dataset.idProducto);
                abrirAjusteInventario(idProducto);
            });
        }

        fragmento.appendChild(fila);
    });

    tbody.appendChild(fragmento);
    actualizarVisibilidadCostosInventario();
}

function aplicarPermisosInventario() {
    const btnProveedor = document.getElementById("btnNuevoProveedor");
    const btnEntrada = document.getElementById("btnNuevaEntradaInventario");

    if (btnProveedor) {
        btnProveedor.hidden = !permisosInventarioAdmin.gestionar_proveedores;
    }

    if (btnEntrada) {
        btnEntrada.hidden = !permisosInventarioAdmin.registrar_entrada;
    }

    actualizarVisibilidadCostosInventario();
}

function actualizarVisibilidadCostosInventario() {
    const mostrar = Boolean(permisosInventarioAdmin.ver_costos);
    const tabla = document.getElementById("tablaInventarioAdmin");
    const valor = document.getElementById("valorTotalInventarioAdmin");

    if (valor && valor.closest("article")) {
        valor.closest("article").hidden = !mostrar;
    }

    if (!tabla) return;

    tabla
        .querySelectorAll("th:nth-child(6), th:nth-child(7), .columnaCostoInventario")
        .forEach(elemento => {
            elemento.style.display = mostrar ? "" : "none";
        });
}

function mostrarErrorInventario(mensaje) {
    const tbody = document.getElementById("tbodyInventario");

    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="errorInventarioAdmin">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    ${escaparHTMLInventario(mensaje)}
                </td>
            </tr>
        `;
    }
}

/* =====================================================
   HISTORIAL DE ENTRADAS
===================================================== */

async function cargarEntradasInventario() {
    const contenedor = document.getElementById("contenidoEntradasInventario");

    try {
        const response = await fetch(
            "./php/obtenerEntradasInventario.php",
            {cache: "no-store"}
        );
        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) throw new Error(resultado.mensaje);

        entradasInventarioAdmin = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        asignarTextoInventario(
            "entradasMesInventario",
            Number(resultado.resumen?.entradas_mes) || 0
        );

        renderizarEntradasInventario();
    } catch (error) {
        if (contenedor) {
            contenedor.innerHTML = `<div class="errorInventarioAdmin">${escaparHTMLInventario(error.message)}</div>`;
        }
    }
}

function renderizarEntradasInventario() {
    const contenedor = document.getElementById("contenidoEntradasInventario");
    if (!contenedor) return;

    if (entradasInventarioAdmin.length === 0) {
        contenedor.innerHTML = '<div class="sinResultadosInventario"><i class="fa-solid fa-file-circle-xmark"></i><h3>No hay entradas registradas</h3></div>';
        return;
    }

    const filas = entradasInventarioAdmin.map(entrada => `
        <tr>
            <td><strong>${escaparHTMLInventario(entrada.numero_entrada)}</strong><small>${formatearFechaHoraInventario(entrada.fecha_confirmacion || entrada.fecha_registro)}</small></td>
            <td>${escaparHTMLInventario(entrada.proveedor)}</td>
            <td><strong>${formatearTipoDocumentoInventario(entrada.tipo_documento)}</strong><small>${escaparHTMLInventario(entrada.numero_documento || "Sin número")}</small></td>
            <td>${Number(entrada.cantidad_unidades)} <small>${Number(entrada.cantidad_lineas)} líneas</small></td>
            <td><strong>${formatearPrecioInventario(entrada.total)}</strong></td>
            <td>${escaparHTMLInventario(entrada.usuario_responsable)}</td>
            <td><span class="estadoProveedorInventario activo">Confirmada</span></td>
            <td>
                <button
                    type="button"
                    class="btnVerEntradaInventario"
                    data-id-entrada="${Number(entrada.id_entrada)}"
                    title="Ver detalle">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join("");

    contenedor.innerHTML = `
        <div class="contenedorTablaInventario">
            <table class="tablaHistorialInventario">
                <thead><tr><th>Entrada</th><th>Proveedor</th><th>Documento</th><th>Unidades</th><th>Total</th><th>Responsable</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
    `;

    contenedor.querySelectorAll(".btnVerEntradaInventario").forEach(boton => {
        boton.addEventListener("click", function () {
            abrirDetalleEntradaInventario(Number(this.dataset.idEntrada));
        });
    });
}

function configurarDialogDetalleEntradaInventario() {
    const cerrar = document.getElementById("btnCerrarDetalleEntradaInventario");
    const aceptar = document.getElementById("btnAceptarDetalleEntradaInventario");
    const dialogo = document.getElementById("dialogDetalleEntradaInventario");

    if (cerrar) cerrar.addEventListener("click", cerrarDetalleEntradaInventario);
    if (aceptar) aceptar.addEventListener("click", cerrarDetalleEntradaInventario);

    if (dialogo) {
        dialogo.addEventListener("click", event => {
            if (event.target === dialogo) cerrarDetalleEntradaInventario();
        });
    }
}

async function abrirDetalleEntradaInventario(idEntrada) {
    if (!idEntrada) return;

    const dialogo = document.getElementById("dialogDetalleEntradaInventario");
    const tbody = document.getElementById("tbodyDetalleEntradaInventario");

    if (!dialogo) return;

    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="5"><i class="fa-solid fa-spinner fa-spin"></i> Cargando productos...</td></tr>';
    }

    if (!dialogo.open) dialogo.showModal();

    try {
        const response = await fetch(
            `./php/obtenerDetalleEntradaInventario.php?id=${encodeURIComponent(idEntrada)}&v=${Date.now()}`,
            {cache: "no-store"}
        );
        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) throw new Error(resultado.mensaje);

        cargarDetalleEntradaInventario(resultado.datos);
    } catch (error) {
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" class="errorInventarioAdmin">${escaparHTMLInventario(error.message)}</td></tr>`;
        }
    }
}

function cargarDetalleEntradaInventario(datos) {
    const entrada = datos.entrada || {};
    const productos = Array.isArray(datos.productos) ? datos.productos : [];

    asignarTextoInventario("numeroDetalleEntradaInventario", entrada.numero_entrada);
    asignarTextoInventario("fechaDetalleEntradaInventario", formatearFechaHoraInventario(entrada.fecha_confirmacion || entrada.fecha_registro));
    asignarTextoInventario("proveedorDetalleEntradaInventario", entrada.proveedor);
    asignarTextoInventario("rutDetalleEntradaInventario", entrada.rut_proveedor || "No informado");
    asignarTextoInventario("documentoDetalleEntradaInventario", `${formatearTipoDocumentoInventario(entrada.tipo_documento)} · ${entrada.numero_documento || "Sin número"}`);
    asignarTextoInventario("responsableDetalleEntradaInventario", entrada.usuario_responsable);
    asignarTextoInventario("observacionesDetalleEntradaInventario", entrada.observaciones || "Sin observaciones.");
    asignarTextoInventario("netoDetalleEntradaInventario", formatearPrecioInventario(entrada.neto));
    asignarTextoInventario("ivaDetalleEntradaInventario", formatearPrecioInventario(entrada.iva));
    asignarTextoInventario("totalDetalleEntradaInventario", formatearPrecioInventario(entrada.total));

    const tbody = document.getElementById("tbodyDetalleEntradaInventario");
    if (!tbody) return;

    if (productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">Esta entrada no contiene productos.</td></tr>';
        return;
    }

    tbody.innerHTML = productos.map(producto => `
        <tr>
            <td><strong>${escaparHTMLInventario(producto.producto)}</strong><small>SKU: ${escaparHTMLInventario(producto.sku || "Sin SKU")} · ${escaparHTMLInventario(producto.marca || "Sin marca")}</small></td>
            <td>${Number(producto.cantidad)}</td>
            <td>${formatearPrecioInventario(producto.costo_unitario)}</td>
            <td><strong>${formatearPrecioInventario(producto.total_linea)}</strong></td>
            <td>${Number(producto.stock_actual)}</td>
        </tr>
    `).join("");
}

function cerrarDetalleEntradaInventario() {
    const dialogo = document.getElementById("dialogDetalleEntradaInventario");
    if (dialogo && dialogo.open) dialogo.close();
}

/* =====================================================
   HISTORIAL DE MOVIMIENTOS
===================================================== */

async function cargarMovimientosInventario() {
    const contenedor = document.getElementById("contenidoMovimientosInventario");

    try {
        const response = await fetch(
            "./php/obtenerMovimientosInventario.php",
            {cache: "no-store"}
        );
        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) throw new Error(resultado.mensaje);

        movimientosInventarioAdmin = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        asignarTextoInventario("movimientosHoyInventario", Number(resultado.resumen?.movimientos_hoy) || 0);
        asignarTextoInventario("salidasMesInventario", Number(resultado.resumen?.salidas_mes) || 0);
        asignarTextoInventario("ajustesMesInventario", Number(resultado.resumen?.ajustes_mes) || 0);

        renderizarMovimientosInventario(Boolean(resultado.permisos?.ver_costos));
    } catch (error) {
        if (contenedor) {
            contenedor.innerHTML = `<div class="errorInventarioAdmin">${escaparHTMLInventario(error.message)}</div>`;
        }
    }
}

function renderizarMovimientosInventario(verCostos) {
    const contenedor = document.getElementById("contenidoMovimientosInventario");
    if (!contenedor) return;

    if (movimientosInventarioAdmin.length === 0) {
        contenedor.innerHTML = '<div class="sinResultadosInventario"><i class="fa-solid fa-clock-rotate-left"></i><h3>No hay movimientos registrados</h3></div>';
        return;
    }

    const filas = movimientosInventarioAdmin.map(movimiento => {
        const entrada = movimiento.sentido === "entrada";
        const signo = entrada ? "+" : "−";
        const clase = entrada ? "movimientoEntrada" : "movimientoSalida";

        return `
            <tr>
                <td><strong>${escaparHTMLInventario(movimiento.producto)}</strong><small>SKU: ${escaparHTMLInventario(movimiento.sku || "Sin SKU")}</small></td>
                <td><span class="tipoMovimientoInventario ${clase}">${formatearTipoMovimientoInventario(movimiento.tipo_movimiento)}</span></td>
                <td><strong class="${clase}">${signo}${Number(movimiento.cantidad)}</strong></td>
                <td>${Number(movimiento.stock_anterior)} → <strong>${Number(movimiento.stock_resultante)}</strong></td>
                ${verCostos ? `<td>${formatearPrecioInventario(movimiento.costo_unitario)}</td>` : ""}
                <td>${escaparHTMLInventario(movimiento.referencia || "Sin referencia")}</td>
                <td>${escaparHTMLInventario(movimiento.usuario_responsable || "Sistema")}</td>
                <td>${formatearFechaHoraInventario(movimiento.fecha_movimiento)}</td>
            </tr>
        `;
    }).join("");

    contenedor.innerHTML = `
        <div class="contenedorTablaInventario">
            <table class="tablaHistorialInventario">
                <thead><tr><th>Producto</th><th>Movimiento</th><th>Cantidad</th><th>Stock</th>${verCostos ? "<th>Costo</th>" : ""}<th>Referencia</th><th>Responsable</th><th>Fecha</th></tr></thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
    `;
}

function formatearTipoDocumentoInventario(tipo) {
    const nombres = {
        factura: "Factura",
        boleta: "Boleta",
        guia_despacho: "Guía de despacho",
        sin_documento: "Sin documento"
    };
    return nombres[tipo] || tipo || "Sin documento";
}

function formatearTipoMovimientoInventario(tipo) {
    const nombres = {
        saldo_inicial: "Saldo inicial",
        entrada_compra: "Entrada por compra",
        salida_venta: "Salida por venta",
        devolucion_cliente: "Devolución",
        ajuste_entrada: "Ajuste de entrada",
        ajuste_salida: "Ajuste de salida",
        merma: "Merma"
    };
    return nombres[tipo] || String(tipo || "Movimiento").replaceAll("_", " ");
}

function formatearFechaHoraInventario(fecha) {
    if (!fecha) return "Sin fecha";
    const valor = new Date(String(fecha).replace(" ", "T"));
    if (Number.isNaN(valor.getTime())) return "Sin fecha";
    return new Intl.DateTimeFormat("es-CL", {
        dateStyle: "short",
        timeStyle: "short"
    }).format(valor);
}

/* =====================================================
   PROVEEDORES
===================================================== */

async function cargarProveedoresInventario() {
    try {
        const response = await fetch(
            "./php/obtenerProveedores.php",
            {cache: "no-store"}
        );

        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) {
            throw new Error(resultado.mensaje);
        }

        proveedoresInventarioAdmin = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        if (resultado.permisos) {
            permisosInventarioAdmin.gestionar_proveedores = Boolean(
                resultado.permisos.crear
            );
        }

        aplicarFiltroProveedoresInventario();
        aplicarPermisosInventario();
    } catch (error) {
        console.error("Error cargando proveedores:", error);

        const tbody = document.getElementById("tbodyProveedoresInventario");

        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="errorInventarioAdmin">
                        ${escaparHTMLInventario(error.message)}
                    </td>
                </tr>
            `;
        }
    }
}

function aplicarFiltroProveedoresInventario() {
    const buscador = document.getElementById("buscarProveedorInventario");
    const texto = normalizarTextoInventario(
        buscador ? buscador.value : ""
    );

    const proveedores = proveedoresInventarioAdmin.filter(proveedor => {
        const contenido = normalizarTextoInventario([
            proveedor.razon_social,
            proveedor.rut,
            proveedor.nombre_contacto,
            proveedor.correo,
            proveedor.telefono
        ].join(" "));

        return !texto || contenido.includes(texto);
    });

    renderizarProveedoresInventario(proveedores);
}

function renderizarProveedoresInventario(proveedores) {
    const tbody = document.getElementById("tbodyProveedoresInventario");

    if (!tbody) return;

    asignarTextoInventario("cantidadProveedoresInventario", proveedores.length);
    tbody.innerHTML = "";

    if (proveedores.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7">No hay proveedores registrados.</td>
            </tr>
        `;
        return;
    }

    const fragmento = document.createDocumentFragment();

    proveedores.forEach(proveedor => {
        const fila = document.createElement("tr");
        const puedeEditar = Boolean(
            permisosInventarioAdmin.gestionar_proveedores
        );

        fila.innerHTML = `
            <td><strong>${escaparHTMLInventario(proveedor.razon_social)}</strong></td>
            <td>${escaparHTMLInventario(proveedor.rut || "No informado")}</td>
            <td>
                <strong>${escaparHTMLInventario(proveedor.nombre_contacto || "No informado")}</strong>
                <small>${escaparHTMLInventario(proveedor.correo || "")}</small>
            </td>
            <td>${escaparHTMLInventario(proveedor.telefono || "No informado")}</td>
            <td>${Number(proveedor.cantidad_entradas)}</td>
            <td>
                <span class="estadoProveedorInventario ${escaparHTMLInventario(proveedor.estado)}">
                    ${proveedor.estado === "activo" ? "Activo" : "Inactivo"}
                </span>
            </td>
            <td>
                ${puedeEditar
                    ? `<button type="button"
                            class="btnEditarProveedorInventario"
                            data-id-proveedor="${Number(proveedor.id_proveedor)}"
                            title="Modificar proveedor">
                            <i class="fa-solid fa-pen"></i>
                       </button>`
                    : "—"}
            </td>
        `;

        const boton = fila.querySelector(".btnEditarProveedorInventario");

        if (boton) {
            boton.addEventListener("click", function () {
                abrirDialogProveedorInventario(
                    Number(this.dataset.idProveedor)
                );
            });
        }

        fragmento.appendChild(fila);
    });

    tbody.appendChild(fragmento);
}

function configurarDialogProveedorInventario() {
    const nuevo = document.getElementById("btnNuevoProveedor");
    const cerrar = document.getElementById("btnCerrarDialogProveedor");
    const cancelar = document.getElementById("btnCancelarProveedor");
    const formulario = document.getElementById("formProveedorInventario");
    const dialogo = document.getElementById("dialogProveedorInventario");

    if (nuevo) {
        nuevo.addEventListener("click", () => abrirDialogProveedorInventario(0));
    }

    if (cerrar) cerrar.addEventListener("click", cerrarDialogProveedorInventario);
    if (cancelar) cancelar.addEventListener("click", cerrarDialogProveedorInventario);

    if (formulario) {
        formulario.addEventListener("submit", guardarProveedorInventario);
    }

    if (dialogo) {
        dialogo.addEventListener("click", event => {
            if (event.target === dialogo) cerrarDialogProveedorInventario();
        });
    }
}

function abrirDialogProveedorInventario(idProveedor) {
    if (!permisosInventarioAdmin.gestionar_proveedores) return;

    const dialogo = document.getElementById("dialogProveedorInventario");
    const formulario = document.getElementById("formProveedorInventario");
    const proveedor = proveedoresInventarioAdmin.find(
        elemento => Number(elemento.id_proveedor) === Number(idProveedor)
    );

    if (!dialogo || !formulario) return;

    formulario.reset();
    asignarValorInventario("idProveedorInventario", proveedor ? proveedor.id_proveedor : 0);
    asignarValorInventario("razonSocialProveedor", proveedor ? proveedor.razon_social : "");
    asignarValorInventario("rutProveedor", proveedor ? proveedor.rut : "");
    asignarValorInventario("contactoProveedor", proveedor ? proveedor.nombre_contacto : "");
    asignarValorInventario("correoProveedor", proveedor ? proveedor.correo : "");
    asignarValorInventario("telefonoProveedor", proveedor ? proveedor.telefono : "");
    asignarValorInventario("direccionProveedor", proveedor ? proveedor.direccion : "");
    asignarValorInventario("estadoProveedor", proveedor ? proveedor.estado : "activo");
    asignarTextoInventario("tituloDialogProveedor", proveedor ? "Modificar proveedor" : "Nuevo proveedor");

    const respuesta = document.getElementById("respuestaProveedorInventario");
    if (respuesta) {
        respuesta.className = "respuestaInventario";
        respuesta.textContent = "";
    }

    if (!dialogo.open) dialogo.showModal();
}

async function guardarProveedorInventario(event) {
    event.preventDefault();

    const formulario = event.currentTarget;
    const boton = document.getElementById("btnGuardarProveedor");

    if (!formulario.reportValidity()) return;

    if (boton) {
        boton.disabled = true;
        boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    }

    try {
        const response = await fetch(
            "./php/guardarProveedor.php",
            {method: "POST", body: new FormData(formulario)}
        );

        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) throw new Error(resultado.mensaje);

        mostrarRespuestaProveedorInventario(resultado.mensaje, true);
        await cargarProveedoresInventario();

        setTimeout(cerrarDialogProveedorInventario, 700);
    } catch (error) {
        mostrarRespuestaProveedorInventario(
            error.message || "No fue posible guardar el proveedor.",
            false
        );
    } finally {
        if (boton) {
            boton.disabled = false;
            boton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar proveedor';
        }
    }
}

function mostrarRespuestaProveedorInventario(mensaje, correcto) {
    const respuesta = document.getElementById("respuestaProveedorInventario");
    if (!respuesta) return;
    respuesta.className = correcto
        ? "respuestaInventario correcta"
        : "respuestaInventario error";
    respuesta.textContent = mensaje;
}

function cerrarDialogProveedorInventario() {
    const dialogo = document.getElementById("dialogProveedorInventario");
    if (dialogo && dialogo.open) dialogo.close();
}

/* =====================================================
   ENTRADA DE MERCADERÍA
===================================================== */

function configurarDialogEntradaInventario() {
    const abrir = document.getElementById("btnNuevaEntradaInventario");
    const cerrar = document.getElementById("btnCerrarDialogEntradaInventario");
    const cancelar = document.getElementById("btnCancelarEntradaInventario");
    const agregar = document.getElementById("btnAgregarProductoEntrada");
    const formulario = document.getElementById("formEntradaInventario");
    const dialogo = document.getElementById("dialogEntradaInventario");
    const tipoDocumento = document.getElementById("tipoDocumentoEntrada");

    if (abrir) abrir.addEventListener("click", abrirDialogEntradaInventario);
    if (cerrar) cerrar.addEventListener("click", cerrarDialogEntradaInventario);
    if (cancelar) cancelar.addEventListener("click", cerrarDialogEntradaInventario);
    if (agregar) agregar.addEventListener("click", agregarProductoEntradaInventario);
    if (formulario) formulario.addEventListener("submit", guardarEntradaInventario);

    if (tipoDocumento) {
        tipoDocumento.addEventListener("change", actualizarDocumentoEntrada);
    }

    if (dialogo) {
        dialogo.addEventListener("click", event => {
            if (event.target === dialogo) cerrarDialogEntradaInventario();
        });
    }
}

function abrirDialogEntradaInventario() {
    if (!permisosInventarioAdmin.registrar_entrada) return;

    const dialogo = document.getElementById("dialogEntradaInventario");
    const formulario = document.getElementById("formEntradaInventario");
    const selectorProveedor = document.getElementById("proveedorEntradaInventario");
    const selectorProducto = document.getElementById("productoNuevaEntrada");
    const fecha = document.getElementById("fechaDocumentoEntrada");

    if (!dialogo || !formulario) return;

    formulario.reset();
    productosEntradaInventario = [];

    if (selectorProveedor) {
        selectorProveedor.innerHTML = '<option value="">Sin proveedor</option>';

        proveedoresInventarioAdmin
            .filter(proveedor => proveedor.estado === "activo")
            .forEach(proveedor => {
                const opcion = document.createElement("option");
                opcion.value = proveedor.id_proveedor;
                opcion.textContent = proveedor.razon_social;
                selectorProveedor.appendChild(opcion);
            });
    }

    if (selectorProducto) {
        selectorProducto.innerHTML = '<option value="">Seleccione un producto</option>';

        productosInventarioAdmin.forEach(producto => {
            const opcion = document.createElement("option");
            opcion.value = producto.id_producto;
            opcion.textContent = `${producto.sku || "Sin SKU"} · ${producto.nombre}`;
            selectorProducto.appendChild(opcion);
        });
    }

    if (fecha) fecha.value = obtenerFechaLocalInventario();

    actualizarDocumentoEntrada();
    renderizarProductosEntradaInventario();
    mostrarRespuestaEntradaInventario("", true);

    if (!dialogo.open) dialogo.showModal();
}

function actualizarDocumentoEntrada() {
    const tipo = document.getElementById("tipoDocumentoEntrada");
    const numero = document.getElementById("numeroDocumentoEntrada");

    if (!tipo || !numero) return;

    const sinDocumento = tipo.value === "sin_documento";
    numero.disabled = sinDocumento;
    numero.required = !sinDocumento;

    if (sinDocumento) numero.value = "";
}

function agregarProductoEntradaInventario() {
    const selector = document.getElementById("productoNuevaEntrada");
    const cantidad = document.getElementById("cantidadNuevaEntrada");
    const costo = document.getElementById("costoNuevaEntrada");

    const idProducto = Number(selector ? selector.value : 0);
    const cantidadIngresada = Number(cantidad ? cantidad.value : 0);
    const costoIngresado = Number(costo ? costo.value : 0);

    if (!idProducto || !Number.isInteger(cantidadIngresada) || cantidadIngresada <= 0) {
        mostrarRespuestaEntradaInventario("Seleccione un producto e ingrese una cantidad válida.", false);
        return;
    }

    if (!Number.isInteger(costoIngresado) || costoIngresado <= 0) {
        mostrarRespuestaEntradaInventario("Ingrese un costo unitario válido.", false);
        return;
    }

    if (productosEntradaInventario.some(item => item.id_producto === idProducto)) {
        mostrarRespuestaEntradaInventario("El producto ya se encuentra agregado.", false);
        return;
    }

    const producto = productosInventarioAdmin.find(
        item => Number(item.id_producto) === idProducto
    );

    if (!producto) return;

    productosEntradaInventario.push({
        id_producto: idProducto,
        nombre: producto.nombre,
        sku: producto.sku || "Sin SKU",
        cantidad: cantidadIngresada,
        costo_unitario: costoIngresado
    });

    if (selector) selector.value = "";
    if (cantidad) cantidad.value = 1;
    if (costo) costo.value = "";

    mostrarRespuestaEntradaInventario("", true);
    renderizarProductosEntradaInventario();
}

function renderizarProductosEntradaInventario() {
    const tbody = document.getElementById("tbodyProductosEntradaInventario");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (productosEntradaInventario.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">Todavía no agregó productos.</td></tr>';
        actualizarTotalesEntradaInventario();
        return;
    }

    productosEntradaInventario.forEach((producto, indice) => {
        const totalLinea = producto.cantidad * producto.costo_unitario;
        const fila = document.createElement("tr");

        fila.innerHTML = `
            <td><strong>${escaparHTMLInventario(producto.nombre)}</strong><small>SKU: ${escaparHTMLInventario(producto.sku)}</small></td>
            <td>${producto.cantidad}</td>
            <td>${formatearPrecioInventario(producto.costo_unitario)}</td>
            <td><strong>${formatearPrecioInventario(totalLinea)}</strong></td>
            <td><button type="button" class="btnQuitarProductoEntrada" data-indice="${indice}" title="Quitar producto"><i class="fa-solid fa-trash"></i></button></td>
        `;

        fila.querySelector(".btnQuitarProductoEntrada").addEventListener("click", function () {
            productosEntradaInventario.splice(Number(this.dataset.indice), 1);
            renderizarProductosEntradaInventario();
        });

        tbody.appendChild(fila);
    });

    actualizarTotalesEntradaInventario();
}

function actualizarTotalesEntradaInventario() {
    const total = productosEntradaInventario.reduce(
        (acumulado, producto) => acumulado + producto.cantidad * producto.costo_unitario,
        0
    );
    const neto = Math.round(total / 1.19);
    const iva = total - neto;

    asignarTextoInventario("netoEntradaInventario", formatearPrecioInventario(neto));
    asignarTextoInventario("ivaEntradaInventario", formatearPrecioInventario(iva));
    asignarTextoInventario("totalEntradaInventario", formatearPrecioInventario(total));
}

async function guardarEntradaInventario(event) {
    event.preventDefault();

    const formulario = event.currentTarget;
    const boton = document.getElementById("btnGuardarEntradaInventario");

    if (!formulario.reportValidity()) return;

    if (productosEntradaInventario.length === 0) {
        mostrarRespuestaEntradaInventario("Debe agregar al menos un producto.", false);
        return;
    }

    const confirmado = window.confirm(
        "¿Confirma la entrada de mercadería? Esta operación aumentará el stock y actualizará el costo promedio."
    );

    if (!confirmado) return;

    const datos = {
        id_proveedor: Number(document.getElementById("proveedorEntradaInventario")?.value || 0),
        tipo_documento: document.getElementById("tipoDocumentoEntrada")?.value || "",
        numero_documento: document.getElementById("numeroDocumentoEntrada")?.value.trim() || "",
        fecha_documento: document.getElementById("fechaDocumentoEntrada")?.value || "",
        observaciones: document.getElementById("observacionesEntradaInventario")?.value.trim() || "",
        productos: productosEntradaInventario.map(producto => ({
            id_producto: producto.id_producto,
            cantidad: producto.cantidad,
            costo_unitario: producto.costo_unitario
        }))
    };

    if (boton) {
        boton.disabled = true;
        boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';
    }

    try {
        const response = await fetch("./php/registrarEntradaInventario.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(datos)
        });

        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) throw new Error(resultado.mensaje);

        mostrarRespuestaEntradaInventario(resultado.mensaje, true);

        await Promise.all([
            cargarInventarioAdmin(),
            cargarProveedoresInventario(),
            cargarEntradasInventario(),
            cargarMovimientosInventario()
        ]);

        setTimeout(cerrarDialogEntradaInventario, 900);
    } catch (error) {
        mostrarRespuestaEntradaInventario(
            error.message || "No fue posible registrar la entrada.",
            false
        );
    } finally {
        if (boton) {
            boton.disabled = false;
            boton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Registrar entrada';
        }
    }
}

function mostrarRespuestaEntradaInventario(mensaje, correcto) {
    const respuesta = document.getElementById("respuestaEntradaInventario");
    if (!respuesta) return;
    respuesta.className = mensaje
        ? (correcto ? "respuestaInventario correcta" : "respuestaInventario error")
        : "respuestaInventario";
    respuesta.textContent = mensaje;
}

function cerrarDialogEntradaInventario() {
    const dialogo = document.getElementById("dialogEntradaInventario");
    if (dialogo && dialogo.open) dialogo.close();
    productosEntradaInventario = [];
}

function obtenerFechaLocalInventario() {
    const fecha = new Date();
    const desplazamiento = fecha.getTimezoneOffset() * 60000;
    return new Date(fecha.getTime() - desplazamiento)
        .toISOString()
        .slice(0, 10);
}

/* =====================================================
   AJUSTES Y MERMAS
===================================================== */

function configurarDialogAjusteInventario() {
    const cerrar = document.getElementById("btnCerrarDialogAjusteInventario");
    const cancelar = document.getElementById("btnCancelarAjusteInventario");
    const formulario = document.getElementById("formAjusteInventario");
    const dialogo = document.getElementById("dialogAjusteInventario");
    const tipo = document.getElementById("tipoAjusteInventario");
    const cantidad = document.getElementById("cantidadAjusteInventario");

    if (cerrar) cerrar.addEventListener("click", cerrarDialogAjusteInventario);
    if (cancelar) cancelar.addEventListener("click", cerrarDialogAjusteInventario);
    if (formulario) formulario.addEventListener("submit", guardarAjusteInventario);
    if (tipo) tipo.addEventListener("change", actualizarResultadoAjusteInventario);
    if (cantidad) cantidad.addEventListener("input", actualizarResultadoAjusteInventario);

    if (dialogo) {
        dialogo.addEventListener("click", event => {
            if (event.target === dialogo) cerrarDialogAjusteInventario();
        });
    }
}

function abrirAjusteInventario(idProducto) {
    if (!permisosInventarioAdmin.ajustar_stock) return;

    const producto = productosInventarioAdmin.find(
        elemento => Number(elemento.id_producto) === Number(idProducto)
    );

    if (!producto) return;

    const dialogo = document.getElementById("dialogAjusteInventario");
    const formulario = document.getElementById("formAjusteInventario");

    if (!dialogo || !formulario) return;

    formulario.reset();

    asignarValorInventario("idProductoAjusteInventario", producto.id_producto);
    asignarTextoInventario("nombreProductoAjusteInventario", producto.nombre);
    asignarTextoInventario("skuProductoAjusteInventario", producto.sku || "Sin SKU");
    asignarTextoInventario("stockActualAjusteInventario", Number(producto.cantidad));
    asignarTextoInventario("stockResultadoAjusteInventario", Number(producto.cantidad));

    const respuesta = document.getElementById("respuestaAjusteInventario");
    if (respuesta) {
        respuesta.className = "respuestaInventario";
        respuesta.textContent = "";
    }

    if (!dialogo.open) dialogo.showModal();
}

function actualizarResultadoAjusteInventario() {
    const idProducto = Number(
        document.getElementById("idProductoAjusteInventario")?.value || 0
    );
    const tipo = document.getElementById("tipoAjusteInventario")?.value || "entrada";
    const cantidad = Number(
        document.getElementById("cantidadAjusteInventario")?.value || 0
    );
    const producto = productosInventarioAdmin.find(
        elemento => Number(elemento.id_producto) === idProducto
    );

    if (!producto) return;

    const stockActual = Number(producto.cantidad) || 0;
    const resultado = tipo === "entrada"
        ? stockActual + Math.max(0, cantidad)
        : stockActual - Math.max(0, cantidad);

    asignarTextoInventario(
        "stockResultadoAjusteInventario",
        resultado < 0 ? "Stock insuficiente" : resultado
    );

    const resultadoElemento = document.getElementById("stockResultadoAjusteInventario");
    if (resultadoElemento) {
        resultadoElemento.classList.toggle("stockResultadoError", resultado < 0);
    }
}

async function guardarAjusteInventario(event) {
    event.preventDefault();

    const formulario = event.currentTarget;
    const boton = document.getElementById("btnGuardarAjusteInventario");

    if (!formulario.reportValidity()) return;

    const datos = {
        id_producto: Number(document.getElementById("idProductoAjusteInventario")?.value || 0),
        tipo_ajuste: document.getElementById("tipoAjusteInventario")?.value || "",
        cantidad: Number(document.getElementById("cantidadAjusteInventario")?.value || 0),
        motivo: document.getElementById("motivoAjusteInventario")?.value.trim() || ""
    };

    const nombres = {
        entrada: "aumentar el stock",
        salida: "disminuir el stock",
        merma: "registrar la merma"
    };

    if (!window.confirm(`¿Confirma ${nombres[datos.tipo_ajuste] || "el ajuste"} en ${datos.cantidad} unidades?`)) {
        return;
    }

    if (boton) {
        boton.disabled = true;
        boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    }

    try {
        const response = await fetch("./php/ajustarInventario.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(datos)
        });

        const resultado = await response.json();

        if (resultado.sesionExpirada) {
            cerrarSesionInventario();
            return;
        }

        if (!resultado.ok) throw new Error(resultado.mensaje);

        mostrarRespuestaAjusteInventario(resultado.mensaje, true);

        await Promise.all([
            cargarInventarioAdmin(),
            cargarMovimientosInventario()
        ]);

        setTimeout(cerrarDialogAjusteInventario, 850);
    } catch (error) {
        mostrarRespuestaAjusteInventario(
            error.message || "No fue posible registrar el ajuste.",
            false
        );
    } finally {
        if (boton) {
            boton.disabled = false;
            boton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar ajuste';
        }
    }
}

function mostrarRespuestaAjusteInventario(mensaje, correcto) {
    const respuesta = document.getElementById("respuestaAjusteInventario");
    if (!respuesta) return;
    respuesta.className = correcto
        ? "respuestaInventario correcta"
        : "respuestaInventario error";
    respuesta.textContent = mensaje;
}

function cerrarDialogAjusteInventario() {
    const dialogo = document.getElementById("dialogAjusteInventario");
    if (dialogo && dialogo.open) dialogo.close();
}

/* =====================================================
   AUXILIARES
===================================================== */

function asignarTextoInventario(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = valor;
}

function asignarValorInventario(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.value = valor ?? "";
}

function formatearPrecioInventario(valor) {
    return new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        minimumFractionDigits: 0
    }).format(Number(valor) || 0);
}

function normalizarTextoInventario(valor) {
    return String(valor ?? "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();
}

function escaparHTMLInventario(valor) {
    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function cerrarSesionInventario() {
    localStorage.clear();
    window.location.href = "page-login.html";
}
