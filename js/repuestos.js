import Carrito from "./carrito.js";

/* =====================================================
   ESTADO DE LA TIENDA
===================================================== */

const carrito = new Carrito();

let productosTienda = [];
let productosFiltrados = [];
let categoriaSeleccionada =
    localStorage.getItem("guardarProductos") || "";

let paginaActual = 1;

const productosPorPagina = 9;


/* =====================================================
   ELEMENTOS
===================================================== */

const productosContainer =
    document.getElementById("productos-container");

const cargandoProductos =
    document.getElementById("cargandoProductosTienda");

const sinProductos =
    document.getElementById("sinProductosTienda");

const paginacion =
    document.getElementById("paginacionProductosTienda");

const buscar =
    document.getElementById("buscar");

const lista =
    document.getElementById("lista");

const filtroMarca =
    document.getElementById("filtroMarcaTienda");

const filtroDisponibilidad =
    document.getElementById(
        "filtroDisponibilidadTienda"
    );

const ordenar =
    document.getElementById("ordenar");

const dialog =
    document.getElementById("mi-elemento");

const footerCarrito =
    document.getElementById("footerCarrito");


/* =====================================================
   INICIALIZACIÓN
===================================================== */

inicializarTienda();


async function inicializarTienda() {

    configurarMenuRepuestos();
    configurarEventosTienda();
    
    await actualizarAccesoCuentaTienda();
    await cargarProductosTienda();
}


/* =====================================================
   MENÚ PÚBLICO DE REPUESTOS
===================================================== */

function configurarMenuRepuestos() {

    const menu =
        document.getElementById("menuInicio");

    const botonMenu =
        document.getElementById("botonMenuInicio");

    if (!menu || !botonMenu) {
        return;
    }

    botonMenu.addEventListener("click", () => {

        const abierto =
            menu.classList.toggle("abierto");

        botonMenu.setAttribute(
            "aria-expanded",
            String(abierto)
        );
    });

    menu.querySelectorAll("a").forEach(enlace => {

        enlace.addEventListener("click", () => {

            menu.classList.remove("abierto");

            botonMenu.setAttribute(
                "aria-expanded",
                "false"
            );
        });
    });
}


/* =====================================================
   CARGAR PRODUCTOS
===================================================== */

async function cargarProductosTienda() {

    mostrarCargandoTienda(true);

    try {

        const response = await fetch(
            `./php/obtenerTableProductos.php?v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        productosTienda = Array.isArray(resultado.datos)
            ? resultado.datos.map(normalizarProducto)
            : [];

        actualizarResumenStockTienda();

        cargarMarcasTienda();

        aplicarFiltrosTienda();

    } catch (error) {

        console.error(
            "Error cargando productos:",
            error
        );

        alert(
            "No fue posible cargar los productos."
        );

    } finally {

        mostrarCargandoTienda(false);
    }
}


function normalizarProducto(producto) {

    return {

        ...producto,

        id_producto:
            Number(producto.id_producto),

        cantidad:
            Math.max(
                0,
                Number(producto.cantidad) || 0
            ),

        stock_minimo:
            Math.max(
                0,
                Number(producto.stock_minimo) || 0
            ),

        precio:
            Math.max(
                0,
                Number(producto.precio) || 0
            ),

        precio_normal:
            Math.max(
                0,
                Number(producto.precio_normal) || 0
            ),

        en_oferta:
            Number(producto.en_oferta) === 1,

        destacado:
            Number(producto.destacado) === 1
    };
}


/* =====================================================
   EVENTOS DE FILTROS
===================================================== */

function configurarEventosTienda() {

    if (buscar) {

        buscar.addEventListener(
            "input",
            function () {

                mostrarSugerenciasTienda(
                    this.value
                );

                paginaActual = 1;
                aplicarFiltrosTienda();
            }
        );
    }

    if (filtroMarca) {

        filtroMarca.addEventListener(
            "change",
            function () {

                paginaActual = 1;
                aplicarFiltrosTienda();
            }
        );
    }

    if (filtroDisponibilidad) {

        filtroDisponibilidad.addEventListener(
            "change",
            function () {

                paginaActual = 1;
                aplicarFiltrosTienda();
            }
        );
    }

    if (ordenar) {

        ordenar.addEventListener(
            "change",
            function () {

                paginaActual = 1;
                aplicarFiltrosTienda();
            }
        );
    }

    const limpiarBusqueda =
        document.getElementById(
            "btnLimpiarBusquedaTienda"
        );

    if (limpiarBusqueda) {

        limpiarBusqueda.addEventListener(
            "click",
            function () {

                buscar.value = "";
                lista.innerHTML = "";

                paginaActual = 1;
                aplicarFiltrosTienda();

                buscar.focus();
            }
        );
    }

    const limpiarFiltros =
        document.getElementById(
            "btnLimpiarFiltrosTienda"
        );

    if (limpiarFiltros) {

        limpiarFiltros.addEventListener(
            "click",
            restablecerFiltrosTienda
        );
    }

    const restablecer =
        document.getElementById(
            "btnRestablecerTienda"
        );

    if (restablecer) {

        restablecer.addEventListener(
            "click",
            restablecerFiltrosTienda
        );
    }

    const todasCategorias =
        document.getElementById(
            "btnTodasCategoriasTienda"
        );

    if (todasCategorias) {

        todasCategorias.addEventListener(
            "click",
            function () {

                categoriaSeleccionada = "";

                localStorage.removeItem(
                    "guardarProductos"
                );

                document
                    .querySelectorAll(
                        ".btnCategoriaTienda"
                    )
                    .forEach(boton => {

                        boton.classList.remove(
                            "activa"
                        );
                    });

                paginaActual = 1;
                aplicarFiltrosTienda();
            }
        );
    }

    document.addEventListener(
        "categoriaTiendaSeleccionada",
        function (evento) {

            categoriaSeleccionada =
                evento.detail.categoria || "";

            paginaActual = 1;

            aplicarFiltrosTienda();

            desplazarAProductos();
        }
    );

    document.addEventListener(
        "click",
        function (evento) {

            if (
                lista &&
                !evento.target.closest(
                    ".buscadorTienda"
                )
            ) {
                lista.innerHTML = "";
            }
        }
    );
}


/* =====================================================
   MARCAS
===================================================== */

function cargarMarcasTienda() {

    if (!filtroMarca) {
        return;
    }

    const marcas = [
        ...new Set(
            productosTienda
                .map(producto =>
                    String(producto.marca || "").trim()
                )
                .filter(Boolean)
        )
    ].sort((a, b) =>
        a.localeCompare(b, "es", {
            sensitivity: "base"
        })
    );

    filtroMarca.innerHTML = `
        <option value="">
            Todas las marcas
        </option>
    `;

    marcas.forEach(marca => {

        const option =
            document.createElement("option");

        option.value =
            marca.toLowerCase();

        option.textContent = marca;

        filtroMarca.appendChild(option);
    });
}


/* =====================================================
   FILTRAR
===================================================== */

function aplicarFiltrosTienda() {

    const texto =
        normalizarTextoTienda(
            buscar?.value || ""
        );

    const marca =
        normalizarTextoTienda(
            filtroMarca?.value || ""
        );

    const disponibilidad =
        filtroDisponibilidad?.value || "";

    productosFiltrados =
        productosTienda.filter(producto => {

            const contenidoBusqueda =
                normalizarTextoTienda(`
                    ${producto.sku || ""}
                    ${producto.nombre || ""}
                    ${producto.marca || ""}
                    ${producto.descripcion || ""}
                    ${producto.compatibilidad || ""}
                `);

            const coincideTexto =
                !texto ||
                contenidoBusqueda.includes(texto);

            const coincideCategoria =
                !categoriaSeleccionada ||
                normalizarTextoTienda(
                    producto.categoria
                ) ===
                normalizarTextoTienda(
                    categoriaSeleccionada
                );

            const coincideMarca =
                !marca ||
                normalizarTextoTienda(
                    producto.marca
                ) === marca;

            let coincideDisponibilidad = true;

            switch (disponibilidad) {

                case "disponible":

                    coincideDisponibilidad =
                        producto.cantidad > 0;

                break;

                case "agotado":

                    coincideDisponibilidad =
                        producto.cantidad === 0;

                break;

                case "oferta":

                    coincideDisponibilidad =
                        producto.en_oferta;

                break;

                case "destacado":

                    coincideDisponibilidad =
                        producto.destacado;

                break;
            }

            return (
                coincideTexto &&
                coincideCategoria &&
                coincideMarca &&
                coincideDisponibilidad
            );
        });

    ordenarProductosTienda();

    actualizarEncabezadoResultados();

    mostrarPaginaProductos();
}


/* =====================================================
   ORDENAR
===================================================== */

function ordenarProductosTienda() {

    const criterio =
        ordenar?.value || "orden_alfabetico";

    productosFiltrados.sort((a, b) => {

        switch (criterio) {

            case "nombre_desc":

                return String(b.nombre).localeCompare(
                    String(a.nombre),
                    "es"
                );

            case "precio_bajo":

                return a.precio - b.precio;

            case "precio_alto":

                return b.precio - a.precio;

            case "mayor_stock":

                return b.cantidad - a.cantidad;

            case "destacados":

                return (
                    Number(b.destacado) -
                    Number(a.destacado)
                );

            default:

                return String(a.nombre).localeCompare(
                    String(b.nombre),
                    "es"
                );
        }
    });
}


/* =====================================================
   PAGINACIÓN
===================================================== */

function mostrarPaginaProductos() {

    if (!productosContainer) {
        return;
    }

    const totalPaginas =
        Math.max(
            1,
            Math.ceil(
                productosFiltrados.length /
                productosPorPagina
            )
        );

    if (paginaActual > totalPaginas) {
        paginaActual = totalPaginas;
    }

    const inicio =
        (paginaActual - 1) *
        productosPorPagina;

    const productosPagina =
        productosFiltrados.slice(
            inicio,
            inicio + productosPorPagina
        );

    productosContainer.innerHTML = "";

    if (productosPagina.length === 0) {

        productosContainer.style.display =
            "none";

        sinProductos.style.display =
            "flex";

        paginacion.innerHTML = "";

        return;
    }

    productosContainer.style.display =
        "grid";

    sinProductos.style.display =
        "none";

    productosPagina.forEach(producto => {

        productosContainer.appendChild(
            crearTarjetaProducto(producto)
        );
    });

    crearPaginacionTienda(totalPaginas);
}


function crearPaginacionTienda(totalPaginas) {

    if (!paginacion) {
        return;
    }

    paginacion.innerHTML = "";

    if (totalPaginas <= 1) {
        return;
    }

    const anterior =
        crearBotonPaginacion(
            '<i class="fa-solid fa-chevron-left"></i>',
            paginaActual - 1,
            paginaActual === 1,
            "Página anterior"
        );

    paginacion.appendChild(anterior);

    for (
        let pagina = 1;
        pagina <= totalPaginas;
        pagina++
    ) {

        if (
            totalPaginas > 7 &&
            pagina !== 1 &&
            pagina !== totalPaginas &&
            Math.abs(pagina - paginaActual) > 1
        ) {

            if (
                pagina === 2 ||
                pagina === totalPaginas - 1
            ) {

                const puntos =
                    document.createElement("span");

                puntos.className =
                    "puntosPaginacionTienda";

                puntos.textContent = "…";

                paginacion.appendChild(puntos);
            }

            continue;
        }

        const boton =
            crearBotonPaginacion(
                pagina,
                pagina,
                false,
                `Página ${pagina}`
            );

        boton.classList.toggle(
            "activa",
            pagina === paginaActual
        );

        paginacion.appendChild(boton);
    }

    const siguiente =
        crearBotonPaginacion(
            '<i class="fa-solid fa-chevron-right"></i>',
            paginaActual + 1,
            paginaActual === totalPaginas,
            "Página siguiente"
        );

    paginacion.appendChild(siguiente);
}


function crearBotonPaginacion(
    contenido,
    pagina,
    deshabilitado,
    titulo
) {

    const boton =
        document.createElement("button");

    boton.type = "button";
    boton.className = "btnPaginaTienda";
    boton.disabled = deshabilitado;
    boton.title = titulo;

    if (
        typeof contenido === "string" &&
        contenido.includes("<i")
    ) {
        boton.innerHTML = contenido;
    } else {
        boton.textContent = contenido;
    }

    boton.addEventListener("click", () => {

        paginaActual = pagina;

        mostrarPaginaProductos();
        desplazarAProductos();
    });

    return boton;
}


/* =====================================================
   TARJETA DE PRODUCTO
===================================================== */

function crearTarjetaProducto(producto) {

    const tarjeta =
        document.createElement("article");

    tarjeta.className = "tarjetaProductoTienda";

    tarjeta.classList.add("tarjetaProductoTiendaClickeable");

    const descuento =
        calcularDescuentoProducto(producto);

    const agotado =
        producto.cantidad <= 0;

    const bajoStock =
        !agotado &&
        producto.cantidad <= producto.stock_minimo;

    tarjeta.innerHTML = `

        <div class="imagenProductoTienda">

            <img
                src="./images/productos/${
                    producto.id_producto
                }.webp?v=${Date.now()}"
                alt="${escaparHTMLTienda(producto.nombre)}"
                loading="lazy">

            <div class="etiquetasProductoTienda">

                ${
                    producto.destacado
                        ? `
                            <span class="etiquetaDestacadoTienda">
                                <i class="fa-solid fa-star"></i>
                                Destacado
                            </span>
                          `
                        : ""
                }

                ${
                    producto.en_oferta
                        ? `
                            <span class="etiquetaOfertaTienda">
                                -${descuento}%
                            </span>
                          `
                        : ""
                }

                ${
                    agotado
                        ? `
                            <span class="etiquetaAgotadoTienda">
                                Agotado
                            </span>
                          `
                        : ""
                }

            </div>

        </div>

        <div class="contenidoProductoTienda">

            <div class="categoriaProductoTienda">

                <span></span>

                ${escaparHTMLTienda(
                    producto.categoria ||
                    "Sin categoría"
                )}

            </div>

            <small class="skuProductoTienda">
                SKU:
                ${escaparHTMLTienda(
                    producto.sku ||
                    `PRO-${producto.id_producto}`
                )}
            </small>

            <h3>
                ${escaparHTMLTienda(
                    producto.nombre ||
                    "Producto sin nombre"
                )}
            </h3>

            <p class="descripcionProductoTienda">
                ${escaparHTMLTienda(
                    resumirTextoTienda(
                        producto.descripcion,
                        110
                    )
                )}
            </p>

            <div class="informacionProductoTienda">

                <span>
                    <i class="fa-solid fa-copyright"></i>

                    ${escaparHTMLTienda(
                        producto.marca ||
                        "Sin marca"
                    )}
                </span>

                <span class="${
                    agotado
                        ? "stockAgotadoTienda"
                        : bajoStock
                            ? "stockBajoTienda"
                            : "stockDisponibleTienda"
                }">

                    <i class="fa-solid ${
                        agotado
                            ? "fa-circle-xmark"
                            : bajoStock
                                ? "fa-triangle-exclamation"
                                : "fa-circle-check"
                    }"></i>

                    ${
                        agotado
                            ? "Agotado"
                            : bajoStock
                                ? `Últimas ${producto.cantidad}`
                                : `Disponible (${producto.cantidad})`
                    }

                </span>

            </div>

            <div class="pieProductoTienda">

                <div class="precioProductoTienda">

                    ${
                        producto.en_oferta
                            ? `
                                <small>
                                    ${formatearPrecioTienda(
                                        producto.precio_normal
                                    )}
                                </small>
                              `
                            : ""
                    }

                    <strong>
                        ${formatearPrecioTienda(
                            producto.precio
                        )}
                    </strong>

                </div>

                <button
                    type="button"
                    class="btnAgregarCarritoTienda"
                    ${agotado ? "disabled" : ""}>

                    <i class="fa-solid ${
                        agotado
                            ? "fa-ban"
                            : "fa-cart-plus"
                    }"></i>

                    ${
                        agotado
                            ? "Agotado"
                            : "Agregar"
                    }

                </button>

            </div>

        </div>
    `;

    const imagen =
        tarjeta.querySelector("img");

    imagen.onerror = function () {

        this.onerror = null;

        this.src =
            "./images/productos/no-image.webp";
    };

    const boton =
        tarjeta.querySelector(
            ".btnAgregarCarritoTienda"
        );

    if (!agotado) {

        boton.addEventListener(
            "click",
            function () {

                agregarProductoDesdeTienda(
                    producto,
                    this
                );
            }
        );
    }

    tarjeta.addEventListener("click", function (evento) {

        if (evento.target.closest(".btnAgregarCarritoTienda")) {
            return;
        }

        abrirDetalleProductoTienda(producto);
    });

    return tarjeta;
}

function abrirDetalleProductoTienda(producto) {

    cerrarDetalleProductoTienda();

    const agotado =
        Number(producto.cantidad) <= 0;

    const bajoStock =
        !agotado &&
        Number(producto.cantidad) <=
        Number(producto.stock_minimo);

    const descuento =
        calcularDescuentoProducto(producto);

    const modal =
        document.createElement("div");

    modal.id = "modalDetalleProductoTienda";
    modal.className = "modalDetalleProductoTienda";

    modal.innerHTML = `

        <div class="detalleProductoTienda">

            <button
                type="button"
                class="cerrarDetalleProductoTienda"
                aria-label="Cerrar">

                <i class="fa-solid fa-xmark"></i>

            </button>


            <div class="detalleProductoTiendaImagen">

                ${
                    producto.en_oferta
                        ? `
                            <span class="ofertaDetalleProductoTienda">
                                -${descuento}%
                            </span>
                        `
                        : ""
                }

                ${
                    producto.destacado
                        ? `
                            <span class="destacadoDetalleProductoTienda">
                                <i class="fa-solid fa-star"></i>
                                Destacado
                            </span>
                        `
                        : ""
                }

                <img
                    src="./images/productos/${producto.id_producto}.webp?v=${Date.now()}"
                    alt="${escaparHTMLTienda(producto.nombre)}">

            </div>


            <div class="detalleProductoTiendaContenido">

                <div class="categoriaDetalleProductoTienda">

                    <span></span>

                    ${escaparHTMLTienda(
                        producto.categoria ||
                        "Sin categoría"
                    )}

                </div>


                <small class="skuDetalleProductoTienda">

                    SKU:
                    ${escaparHTMLTienda(
                        producto.sku ||
                        `PRO-${producto.id_producto}`
                    )}

                </small>


                <h2>
                    ${escaparHTMLTienda(
                        producto.nombre ||
                        "Producto sin nombre"
                    )}
                </h2>


                <p class="descripcionDetalleProductoTienda">

                    ${escaparHTMLTienda(
                        producto.descripcion ||
                        "Sin descripción disponible."
                    )}

                </p>


                <div class="precioDetalleProductoTienda">

                    ${
                        producto.en_oferta
                            ? `
                                <small>
                                    ${formatearPrecioTienda(
                                        producto.precio_normal
                                    )}
                                </small>
                            `
                            : ""
                    }

                    <strong>
                        ${formatearPrecioTienda(
                            producto.precio
                        )}
                    </strong>

                </div>


                <div class="
                    stockDetalleProductoTienda
                    ${
                        agotado
                            ? "agotado"
                            : bajoStock
                                ? "bajo"
                                : ""
                    }
                ">

                    <i class="fa-solid ${
                        agotado
                            ? "fa-circle-xmark"
                            : bajoStock
                                ? "fa-triangle-exclamation"
                                : "fa-circle-check"
                    }"></i>

                    ${
                        agotado
                            ? "Agotado"
                            : bajoStock
                                ? `Últimas ${producto.cantidad} unidades`
                                : `Disponible (${producto.cantidad})`
                    }

                </div>


                <div class="datosDetalleProductoTienda">

                    <div>

                        <span>Marca</span>

                        <strong>
                            ${escaparHTMLTienda(
                                producto.marca ||
                                "No especificada"
                            )}
                        </strong>

                    </div>


                    <div>

                        <span>Garantía</span>

                        <strong>
                            ${escaparHTMLTienda(
                                producto.garantia ||
                                "No especificada"
                            )}
                        </strong>

                    </div>

                </div>


                <div class="compatibilidadDetalleProductoTienda">

                    <h3>
                        <i class="fa-solid fa-car"></i>
                        Autos compatibles
                    </h3>

                    <p>
                        ${escaparHTMLTienda(
                            producto.compatibilidad ||
                            "Compatibilidad no especificada."
                        )}
                    </p>

                </div>


                <button
                    type="button"
                    class="btnAgregarDetalleProductoTienda"
                    ${agotado ? "disabled" : ""}>

                    <i class="fa-solid ${
                        agotado
                            ? "fa-ban"
                            : "fa-cart-plus"
                    }"></i>

                    ${
                        agotado
                            ? "Producto agotado"
                            : "Agregar al carrito"
                    }

                </button>

            </div>

        </div>
    `;


    document.body.appendChild(modal);


    const imagen =
        modal.querySelector(
            ".detalleProductoTiendaImagen img"
        );

    imagen.onerror = function () {

        this.onerror = null;

        this.src =
            "./images/productos/no-image.webp";
    };


    modal
        .querySelector(".cerrarDetalleProductoTienda")
        .addEventListener(
            "click",
            cerrarDetalleProductoTienda
        );


    modal.addEventListener("click", evento => {

        if (evento.target === modal) {

            cerrarDetalleProductoTienda();
        }
    });


    const boton =
        modal.querySelector(
            ".btnAgregarDetalleProductoTienda"
        );


    if (!agotado) {

        boton.addEventListener(
            "click",
            function () {

                agregarProductoDesdeTienda(
                    producto,
                    this
                );
            }
        );
    }
}


    function cerrarDetalleProductoTienda() {

        const modal =
            document.getElementById(
                "modalDetalleProductoTienda"
            );

        if (modal) {
            modal.remove();
        }
    }


/* =====================================================
   AGREGAR AL CARRITO
===================================================== */

function agregarProductoDesdeTienda(
    producto,
    boton
) {

    const resultado =
        carrito.agregarProducto(producto);

    if (!resultado.ok) {

        alert(resultado.mensaje);

        return;
    }

    window.dispatchEvent(
        new CustomEvent("alianzapro:carrito")
    );

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-check"></i>
        Agregado
    `;

    boton.classList.add("agregado");

    setTimeout(() => {

        boton.disabled = false;

        boton.innerHTML = `
            <i class="fa-solid fa-cart-plus"></i>
            Agregar
        `;

        boton.classList.remove("agregado");

    }, 1000);
}


/* =====================================================
   CARRITO LATERAL
===================================================== */

function configurarEventosCarrito() {

    const panel =
        document.getElementById(
            "carritoLateralInicio"
        );

    const fondo =
        document.getElementById(
            "fondoCarritoInicio"
        );

    const abrir =
        document.getElementById(
            "abrirCarritoInicio"
        );

    const cerrar =
        document.getElementById(
            "cerrarCarritoInicio"
        );

    function cambiarEstadoCarrito(abierto) {

        if (!panel) {
            return;
        }

        panel.classList.toggle(
            "abierto",
            abierto
        );

        panel.setAttribute(
            "aria-hidden",
            String(!abierto)
        );

        if (fondo) {
            fondo.hidden = !abierto;
        }

        document.body.classList.toggle(
            "carritoAbierto",
            abierto
        );

        document.body.classList.toggle(
            "pushy-open-right",
            abierto
        );
    }

    abrir?.addEventListener("click", () => {
        cambiarEstadoCarrito(true);
    });

    cerrar?.addEventListener("click", () => {
        cambiarEstadoCarrito(false);
    });

    fondo?.addEventListener("click", () => {
        cambiarEstadoCarrito(false);
    });

    document.addEventListener("keydown", evento => {

        if (evento.key === "Escape") {
            cambiarEstadoCarrito(false);
        }
    });

    const vaciar =
        document.getElementById(
            "btnVaciarCarrito"
        );

    if (vaciar) {

        vaciar.addEventListener(
            "click",
            function () {

                if (carrito.estaVacio()) {
                    return;
                }

                const respuesta = confirm(
                    "¿Desea vaciar completamente el carrito?"
                );

                if (!respuesta) {
                    return;
                }

                carrito.vaciarCarrito();
                actualizarCarrito();
            }
        );
    }
}


function actualizarCarrito() {

    if (!dialog || !footerCarrito) {
        return;
    }

    carrito.actualizarContador();

    const cantidad =
        carrito.obtenerCantidadTotal();

    const titulo =
        document.getElementById("titleCarrito");

    if (titulo) {
        titulo.textContent =
            `Carrito (${cantidad})`;
    }

    dialog.innerHTML = "";

    if (carrito.estaVacio()) {

        dialog.innerHTML = `

            <div class="carritoVacio">

                <div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>

                <h3>Su carrito está vacío</h3>

                <p>
                    Agregue productos para comenzar
                    su compra.
                </p>

            </div>
        `;

        footerCarrito.innerHTML = "";

        return;
    }

    carrito.obtenerCarrito().forEach(item => {

        dialog.appendChild(
            crearItemCarrito(item)
        );
    });

    const total =
        carrito.calcularTotal();

    const neto =
        carrito.calcularNeto();

    const iva =
        carrito.calcularIVA();

    footerCarrito.innerHTML = `

        <div class="filaResumenCarrito">
            <span>Neto</span>
            <strong>
                ${formatearPrecioTienda(neto)}
            </strong>
        </div>

        <div class="filaResumenCarrito">
            <span>IVA incluido (19%)</span>
            <strong>
                ${formatearPrecioTienda(iva)}
            </strong>
        </div>

        <div class="totalResumenCarrito">
            <span>Total</span>
            <strong>
                ${formatearPrecioTienda(total)}
            </strong>
        </div>

        <button
            type="button"
            id="btnContinuarCompra"
            class="btn-comprar">

            Continuar compra

            <i class="fa-solid fa-arrow-right"></i>

        </button>
    `;

    document
        .getElementById("btnContinuarCompra")
        .addEventListener("click", () => {

            window.location.href =
                "./checkOut.html";
        });
}


function crearItemCarrito(item) {

    const elemento =
        document.createElement("article");

    elemento.className = "itemCarrito";

    elemento.innerHTML = `

        <div class="imagenItemCarrito">

            <img
                src="./images/productos/${
                    item.id_producto
                }.webp"
                alt="${escaparHTMLTienda(item.nombre)}">

        </div>

        <div class="informacionItemCarrito">

            <strong>
                ${escaparHTMLTienda(item.nombre)}
            </strong>

            <small>
                ${escaparHTMLTienda(
                    item.marca || "Sin marca"
                )}
            </small>

            <div class="controlesItemCarrito">

                <div class="cantidadItemCarrito">

                    <button
                        type="button"
                        class="btnRestarItem"
                        title="Disminuir cantidad">

                        <i class="fa-solid fa-minus"></i>

                    </button>

                    <span>${item.cant}</span>

                    <button
                        type="button"
                        class="btnSumarItem"
                        title="Aumentar cantidad">

                        <i class="fa-solid fa-plus"></i>

                    </button>

                </div>

                <strong class="precioItemCarrito">
                    ${formatearPrecioTienda(
                        item.precio * item.cant
                    )}
                </strong>

            </div>

        </div>

        <button
            type="button"
            class="btnEliminarItemCarrito"
            title="Eliminar producto">

            <i class="fa-solid fa-trash-can"></i>

        </button>
    `;

    elemento
        .querySelector(".btnRestarItem")
        .addEventListener("click", () => {

            carrito.actualizarCantidad(
                item.id_producto,
                item.cant - 1
            );

            actualizarCarrito();
        });

    elemento
        .querySelector(".btnSumarItem")
        .addEventListener("click", () => {

            const resultado =
                carrito.actualizarCantidad(
                    item.id_producto,
                    item.cant + 1
                );

            if (!resultado.ok) {
                alert(resultado.mensaje);
            }

            actualizarCarrito();
        });

    elemento
        .querySelector(".btnEliminarItemCarrito")
        .addEventListener("click", () => {

            carrito.eliminarProducto(
                item.id_producto
            );

            actualizarCarrito();
        });

    const imagen =
        elemento.querySelector("img");

    imagen.onerror = function () {

        this.onerror = null;

        this.src =
            "./images/productos/no-image.webp";
    };

    return elemento;
}


/* =====================================================
   SUGERENCIAS
===================================================== */

function mostrarSugerenciasTienda(valor) {

    if (!lista) {
        return;
    }

    const texto =
        normalizarTextoTienda(valor);

    lista.innerHTML = "";

    if (texto.length < 2) {
        return;
    }

    const resultados =
        productosTienda
            .filter(producto => {

                const contenido =
                    normalizarTextoTienda(`
                        ${producto.sku || ""}
                        ${producto.nombre || ""}
                        ${producto.marca || ""}
                    `);

                return contenido.includes(texto);
            })
            .slice(0, 6);

    resultados.forEach(producto => {

        const boton =
            document.createElement("button");

        boton.type = "button";

        boton.className =
            "itemSugerenciaTienda";

        boton.innerHTML = `

            <img
                src="./images/productos/${
                    producto.id_producto
                }.webp"
                alt="">

            <span>
                <strong>
                    ${escaparHTMLTienda(producto.nombre)}
                </strong>

                <small>
                    ${escaparHTMLTienda(producto.marca)}
                    ·
                    ${formatearPrecioTienda(producto.precio)}
                </small>
            </span>
        `;

        boton.addEventListener("click", () => {

            buscar.value =
                producto.nombre || "";

            lista.innerHTML = "";

            categoriaSeleccionada = "";

            document
                .querySelectorAll(
                    ".btnCategoriaTienda"
                )
                .forEach(elemento => {

                    elemento.classList.remove(
                        "activa"
                    );
                });

            paginaActual = 1;
            aplicarFiltrosTienda();
            desplazarAProductos();
        });

        lista.appendChild(boton);
    });
}


/* =====================================================
   RESTABLECER
===================================================== */

function restablecerFiltrosTienda() {

    if (buscar) {
        buscar.value = "";
    }

    if (lista) {
        lista.innerHTML = "";
    }

    if (filtroMarca) {
        filtroMarca.value = "";
    }

    if (filtroDisponibilidad) {
        filtroDisponibilidad.value = "";
    }

    if (ordenar) {
        ordenar.value = "orden_alfabetico";
    }

    categoriaSeleccionada = "";

    localStorage.removeItem(
        "guardarProductos"
    );

    document
        .querySelectorAll(
            ".btnCategoriaTienda"
        )
        .forEach(boton => {

            boton.classList.remove("activa");
        });

    paginaActual = 1;

    aplicarFiltrosTienda();
}


/* =====================================================
   AUXILIARES
===================================================== */

function mostrarCargandoTienda(mostrar) {

    if (cargandoProductos) {

        cargandoProductos.style.display =
            mostrar ? "flex" : "none";
    }

    if (
        mostrar &&
        productosContainer
    ) {
        productosContainer.innerHTML = "";
    }
}


function actualizarEncabezadoResultados() {

    const cantidad =
        productosFiltrados.length;

    const contador =
        document.getElementById(
            "cantidadProductosTienda"
        );

    const texto =
        document.getElementById(
            "textoCantidadProductosTienda"
        );

    const titulo =
        document.getElementById(
            "tituloProductosTienda"
        );

    if (contador) {
        contador.textContent = cantidad;
    }

    if (texto) {

        texto.textContent =
            cantidad === 1
                ? "producto"
                : "productos";
    }

    if (titulo) {

        titulo.textContent =
            categoriaSeleccionada
                ? capitalizarTienda(
                    categoriaSeleccionada
                )
                : "Todos los productos";
    }
}


function calcularDescuentoProducto(producto) {

    if (
        !producto.en_oferta ||
        producto.precio_normal <= 0 ||
        producto.precio >=
            producto.precio_normal
    ) {
        return 0;
    }

    return Math.round(
        (
            (
                producto.precio_normal -
                producto.precio
            ) /
            producto.precio_normal
        ) * 100
    );
}


function formatearPrecioTienda(valor) {

    return new Intl.NumberFormat(
        "es-CL",
        {
            style: "currency",
            currency: "CLP",
            minimumFractionDigits: 0
        }
    ).format(Number(valor) || 0);
}


function normalizarTextoTienda(valor) {

    return String(valor || "")
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(
            /[\u0300-\u036f]/g,
            ""
        );
}


function resumirTextoTienda(texto, limite) {

    const valor =
        String(texto || "Sin descripción disponible");

    if (valor.length <= limite) {
        return valor;
    }

    return valor.substring(
        0,
        limite
    ).trim() + "…";
}


function capitalizarTienda(valor) {

    const texto =
        String(valor || "");

    return (
        texto.charAt(0).toUpperCase() +
        texto.slice(1)
    );
}


function escaparHTMLTienda(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


function desplazarAProductos() {

    const encabezado =
        document.querySelector(
            ".encabezadoResultadosTienda"
        );

    if (!encabezado) {
        return;
    }

    const posicion =
        encabezado.getBoundingClientRect().top +
        window.scrollY -
        110;

    window.scrollTo({
        top: posicion,
        behavior: "smooth"
    });
}


function actualizarResumenStockTienda() {

    const productosDisponibles =
        productosTienda.filter(
            producto =>
                Number(producto.cantidad) > 0
        ).length;

    const unidadesDisponibles =
        productosTienda.reduce(
            (total, producto) =>
                total +
                Math.max(
                    0,
                    Number(producto.cantidad) || 0
                ),
            0
        );

    const elementoProductos =
        document.getElementById(
            "productosDisponiblesTienda"
        );

    const elementoUnidades =
        document.getElementById(
            "unidadesDisponiblesTienda"
        );

    if (elementoProductos) {

        elementoProductos.textContent =
            productosDisponibles === 1
                ? "1 producto disponible"
                : `${productosDisponibles} productos disponibles`;
    }

    if (elementoUnidades) {

        elementoUnidades.textContent =
            unidadesDisponibles === 1
                ? "1 unidad en stock"
                : `${unidadesDisponibles} unidades en stock`;
    }
}



async function actualizarAccesoCuentaTienda() {

    const acceso =
        document.getElementById(
            "accesoCuentaTienda"
        );

    const titulo =
        document.getElementById(
            "tituloCuentaTienda"
        );

    const descripcion =
        document.getElementById(
            "descripcionCuentaTienda"
        );

    if (
        !acceso ||
        !titulo ||
        !descripcion
    ) {
        return;
    }

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

            acceso.classList.remove(
                "conectado"
            );

            titulo.textContent =
                "Mi cuenta";

            descripcion.textContent =
                "Ingrese para revisar sus pedidos";

            return;
        }

        acceso.classList.add(
            "conectado"
        );

        titulo.textContent =
            `Hola, ${resultado.cliente.nombre}`;

        descripcion.textContent =
            "Ver mis pedidos y despachos";

    } catch (error) {

        console.error(
            "Error comprobando cliente:",
            error
        );
    }
}
