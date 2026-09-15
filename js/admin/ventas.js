/* =====================================================
   VENTA PRESENCIAL
===================================================== */

let productosVentaPresencial = [];
let productosFiltradosVenta = [];
let carritoVentaPresencial = [];
let documentoOrigenVenta = null;

/* =====================================================
   ABRIR PANTALLA
===================================================== */

function iniciarVentaPresencial() {
    if (typeof procesandoCobroVenta !== "undefined" && procesandoCobroVenta) return;

    const contenido = document.getElementById(
        "contenido"
    );

    if (!contenido) {
        return;
    }

    productosVentaPresencial = [];
    productosFiltradosVenta = [];
    carritoVentaPresencial = [];
    documentoOrigenVenta = null;

    contenido.innerHTML = `

        <section class="gestionVentaPresencial">

            <!-- ==========================================
                 ENCABEZADO
            =========================================== -->

            <header class="encabezadoVentaPresencial">

                <div class="tituloVentaPresencial">


                    <div class="iconoTituloVenta">

                        <i class="fa-solid fa-cash-register"></i>

                    </div>

                    <div>

                        <span>Venta interna</span>

                        <h1>Nueva venta presencial</h1>

                        <p>
                            Seleccione productos, registre al cliente
                            y confirme el pago.
                        </p>

                    </div>

                </div>

                <div class="usuarioVentaActual">

                    <div class="iconoUsuarioVenta">

                        <i class="fa-solid fa-user"></i>

                    </div>

                    <div>

                        <span>Responsable</span>

                        <strong id="usuarioResponsableVenta">
                            Usuario conectado
                        </strong>

                    </div>

                </div>

            </header>
            

            <section id="buscadorDocumentoVenta" hidden>

                <h2>Recuperar cotización o venta</h2>

                <form id="formBuscarDocumentoVenta">

                    <label for="numeroDocumentoVenta">
                        Número de cotización o pedido
                    </label>

                    <div class="entradaDocumentoVenta">

                        <input
                            type="search"
                            id="numeroDocumentoVenta"
                            maxlength="30"
                            autocomplete="off"
                            placeholder="COT-… / VP-… / P-…"
                            required>

                        <button type="submit">
                            Buscar
                        </button>

                    </div>

                </form>

                <p
                    id="mensajeBusquedaDocumentoVenta"
                    role="status"
                    aria-live="polite">
                </p>

                <div id="resultadoDocumentoVenta" hidden></div>

            </section>

            <!-- ==========================================
                 FILTROS
            =========================================== -->

            <div class="filtrosVentaPresencial">

                <div class="campoFiltroVenta buscadorVenta">

                    <label for="buscarProductoVenta">
                        Buscar producto
                    </label>

                    <div class="inputBusquedaVenta">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="buscarProductoVenta"
                            placeholder="Buscar por SKU, producto o marca">

                    </div>

                </div>


                <div class="campoFiltroVenta">

                    <label for="categoriaProductoVenta">
                        Categoría
                    </label>

                    <select id="categoriaProductoVenta">

                        <option value="">
                            Todas las categorías
                        </option>

                    </select>

                </div>


                <div class="campoFiltroVenta">

                    <label for="marcaProductoVenta">
                        Marca
                    </label>

                    <select id="marcaProductoVenta">

                        <option value="">
                            Todas las marcas
                        </option>

                    </select>

                </div>


                <div class="campoFiltroVenta">

                    <label for="disponibilidadProductoVenta">
                        Disponibilidad
                    </label>

                    <select id="disponibilidadProductoVenta">

                        <option value="disponibles">
                            Disponibles
                        </option>

                        <option value="">
                            Todos
                        </option>

                        <option value="bajo_stock">
                            Bajo stock
                        </option>

                        <option value="agotados">
                            Agotados
                        </option>

                    </select>

                </div>


                <div class="campoFiltroVenta">

                    <label for="ordenProductoVenta">
                        Ordenar
                    </label>

                    <select id="ordenProductoVenta">

                        <option value="nombre_asc">
                            Nombre A–Z
                        </option>

                        <option value="precio_menor">
                            Menor precio
                        </option>

                        <option value="precio_mayor">
                            Mayor precio
                        </option>

                        <option value="stock_menor">
                            Menor stock
                        </option>

                        <option value="stock_mayor">
                            Mayor stock
                        </option>

                    </select>

                </div>


                <button
                    type="button"
                    id="btnLimpiarFiltrosVenta"
                    class="btnLimpiarFiltrosVenta">

                    <i class="fa-solid fa-filter-circle-xmark"></i>

                    Limpiar

                </button>

            </div>


            <!-- ==========================================
                 CONTENIDO PRINCIPAL
            =========================================== -->

            <div class="contenidoPrincipalVenta">

                <!-- ======================================
                     CATÁLOGO
                ======================================= -->

                <section class="catalogoVentaPresencial">

                    <div class="encabezadoCatalogoVenta">

                        <div>

                            <h2>Productos disponibles</h2>

                            <p>
                                Agregue los productos que formarán
                                parte de la venta.
                            </p>

                        </div>

                        <div class="contadorProductosVenta">

                            <strong id="cantidadProductosVenta">
                                0
                            </strong>

                            <span>resultados</span>

                        </div>

                    </div>


                    <div class="contenedorTablaVenta">

                        <table class="tablaProductosVenta">

                            <thead>

                                <tr>

                                    <th>Imagen</th>

                                    <th>Producto</th>

                                    <th>Categoría</th>

                                    <th>Marca</th>

                                    <th>Stock</th>

                                    <th>Precio</th>

                                    <th>Acción</th>

                                </tr>

                            </thead>

                            <tbody id="tbodyProductosVenta">

                                <tr>

                                    <td
                                        colspan="7"
                                        class="cargandoProductosVenta">

                                        <i class="fa-solid fa-spinner fa-spin"></i>

                                        Cargando productos...

                                    </td>

                                </tr>

                            </tbody>

                        </table>


                        <div
                            id="sinProductosVenta"
                            class="sinProductosVenta"
                            hidden>

                            <i class="fa-solid fa-box-open"></i>

                            <strong>
                                No se encontraron productos
                            </strong>

                            <span>
                                Pruebe utilizando otros filtros.
                            </span>

                        </div>

                    </div>

                </section>


                <!-- ======================================
                     CARRITO DE VENTA
                ======================================= -->

                <aside class="panelCarritoVenta">

                    <header class="encabezadoCarritoVenta">

                        <div>

                            <div class="iconoCarritoVenta">

                                <i class="fa-solid fa-cart-shopping"></i>

                            </div>

                            <div>

                                <h2>Resumen de venta</h2>

                                <span id="cantidadCarritoVenta">
                                    0 productos
                                </span>

                            </div>

                        </div>

                        <button
                            type="button"
                            id="btnVaciarCarritoVenta"
                            class="btnVaciarCarritoVenta"
                            title="Vaciar venta">

                            <i class="fa-solid fa-trash-can"></i>

                        </button>

                    </header>


                    <div
                        id="productosCarritoVenta"
                        class="productosCarritoVenta">

                        <div class="carritoVentaVacio">

                            <i class="fa-solid fa-basket-shopping"></i>

                            <strong>
                                Venta vacía
                            </strong>

                            <p>
                                Agregue productos desde el catálogo.
                            </p>

                        </div>

                    </div>


                    <div class="totalesCarritoVenta">

                        <div>

                            <span>Neto</span>

                            <strong id="netoCarritoVenta">
                                $0
                            </strong>

                        </div>

                        <div>

                            <span>IVA incluido (19%)</span>

                            <strong id="ivaCarritoVenta">
                                $0
                            </strong>

                        </div>

                        <div class="totalFinalCarritoVenta">

                            <span>Total</span>

                            <strong id="totalCarritoVenta">
                                $0
                            </strong>

                        </div>

                    </div>

                </aside>

            </div>


            <!-- ==========================================
                 CLIENTE Y PAGO
            =========================================== -->

            <form
                id="formVentaPresencial"
                class="formularioVentaPresencial">

                <div class="columnasFormularioVenta">

                    <!-- CLIENTE -->

                    <section class="seccionFormularioVenta">

                        <div class="encabezadoDatosCliente">

                            <div class="tituloDatosCliente">

                                <h3>
                                    <i class="fa-solid fa-user"></i>
                                    Datos del cliente
                                </h3>

                                <p>
                                    Ingrese la información de la persona que realiza la compra.
                                </p>

                            </div>

                            <div class="buscadorClienteVenta">

                                <label for="buscarClienteVenta">
                                    Buscar cliente
                                </label>

                                <div class="campoBuscarCliente">

                                    <i class="fa-solid fa-magnifying-glass"></i>

                                    <input
                                        type="search"
                                        id="buscarClienteVenta"
                                        placeholder="RUT, correo, nombre o teléfono"
                                        autocomplete="off">

                                    <span
                                        id="cargandoBuscarCliente"
                                        class="cargandoBuscarCliente"
                                        hidden>

                                        <i class="fa-solid fa-spinner fa-spin"></i>

                                    </span>

                                </div>

                                <div
                                    id="resultadosBuscarCliente"
                                    class="resultadosBuscarCliente">
                                </div>

                            </div>

                        </div>

                        <div class="gridCamposVenta">
                            
                                <input
                                type="hidden"
                                name="id_cliente"
                                id="idClienteVenta"
                                value="">

                            <div class="campoVenta">

                                <label for="rutClienteVenta">
                                    RUT
                                </label>

                                <input
                                    type="text"
                                    id="rutClienteVenta"
                                    maxlength="12"
                                    placeholder="12.345.678-5">

                            </div>


                            <div class="campoVenta">

                                <label for="telefonoClienteVenta">
                                    Teléfono *
                                </label>

                                <input
                                    type="tel"
                                    id="telefonoClienteVenta"
                                    maxlength="9"
                                    placeholder="912345678"
                                    required>

                            </div>


                            <div class="campoVenta">

                                <label for="nombreClienteVenta">
                                    Nombre *
                                </label>

                                <input
                                    type="text"
                                    id="nombreClienteVenta"
                                    minlength="2"
                                    maxlength="60"
                                    required>

                            </div>


                            <div class="campoVenta">

                                <label for="apellidoClienteVenta">
                                    Apellido *
                                </label>

                                <input
                                    type="text"
                                    id="apellidoClienteVenta"
                                    minlength="2"
                                    maxlength="60"
                                    required>

                            </div>


                            <div class="campoVenta campoCompletoVenta">

                                <label for="correoClienteVenta">
                                    Correo electrónico
                                    <span class="campoOpcionalVenta">
                                        Opcional
                                    </span>
                                </label>

                                <input
                                    type="email"
                                    id="correoClienteVenta"
                                    maxlength="150"
                                    placeholder="cliente@correo.cl">

                            </div>

                            <div class="accionesClienteEncontrado">

                            <div id="estadoClienteVenta"></div>

                            <button
                                type="button"
                                id="btnActualizarClienteVenta"
                                class="btnActualizarClienteVenta"
                                hidden>

                                <i class="fa-solid fa-user-pen"></i>
                                Actualizar datos del cliente

                            </button>

                        </div>

                        </div>

                    </section>


                    <!-- PAGO -->

                    <section class="seccionFormularioVenta">

                        <h2>

                            <i class="fa-solid fa-credit-card"></i>

                            Información del pago

                        </h2>

                        <p>
                            Seleccione el medio utilizado por el cliente.
                        </p>

                        <div class="metodosPagoVenta">

                            <label class="opcionPagoVenta">

                                <input
                                    type="radio"
                                    name="metodo_pago_venta"
                                    value="efectivo"
                                    checked>

                                <span>

                                    <i class="fa-solid fa-money-bill-wave"></i>

                                    <strong>Efectivo</strong>

                                </span>

                            </label>


                            <label class="opcionPagoVenta">

                                <input
                                    type="radio"
                                    name="metodo_pago_venta"
                                    value="debito">

                                <span>

                                    <i class="fa-solid fa-credit-card"></i>

                                    <strong>Débito</strong>

                                </span>

                            </label>


                            <label class="opcionPagoVenta">

                                <input
                                    type="radio"
                                    name="metodo_pago_venta"
                                    value="credito">

                                <span>

                                    <i class="fa-regular fa-credit-card"></i>

                                    <strong>Crédito</strong>

                                </span>

                            </label>


                            <label class="opcionPagoVenta">

                                <input
                                    type="radio"
                                    name="metodo_pago_venta"
                                    value="transferencia">

                                <span>

                                    <i class="fa-solid fa-building-columns"></i>

                                    <strong>Transferencia</strong>

                                </span>

                            </label>

                        </div>


                        <div class="campoVenta campoObservacionVenta">

                            <label for="observacionesVenta">
                                Observaciones
                            </label>

                            <textarea
                                id="observacionesVenta"
                                maxlength="500"
                                placeholder="Información adicional de la venta"></textarea>

                        </div>

                    </section>

                </div>


                <div
                    id="respuestaVentaPresencial"
                    class="respuestaVentaPresencial">
                </div>


                <footer class="accionesFormularioVenta">

                    <button
                        type="button"
                        id="btnCancelarVentaPresencial"
                        class="btnCancelarVenta">

                        Cancelar

                    </button>

                    <button
                        type="reset"
                        id="btnRestablecerVenta"
                        class="btnRestablecerVenta">

                        <i class="fa-solid fa-rotate-left"></i>

                        Restablecer

                    </button>

                    <button
                        type="button"
                        id="btnGuardarCotizacion"
                        class="btnRestablecerVenta">

                        <i class="fa-solid fa-file-lines"></i>

                        Guardar cotización

                    </button>

                    <button
                        type="submit"
                        id="btnConfirmarVentaPresencial"
                        class="btnConfirmarVenta"
                        disabled>

                        <i class="fa-solid fa-check"></i>

                        Confirmar venta

                    </button>

                </footer>

            </form>

        </section>
    `;

    configurarEventosVentaPresencial();
    mostrarUsuarioResponsableVenta();
    cargarProductosVentaPresencial();
    configurarBuscadorClienteVenta();
    configurarBusquedaDocumentoVenta();
    mostrarPendienteCobroVenta();
}

/* =====================================================
   CONFIGURAR EVENTOS
===================================================== */

function configurarEventosVentaPresencial() {

    const botonCotizacion = document.getElementById("btnGuardarCotizacion");

    if (botonCotizacion) {
        botonCotizacion.addEventListener("click", guardarCotizacionPresencial);
    }

    const buscador = document.getElementById(
        "buscarProductoVenta"
    );

    const categoria = document.getElementById(
        "categoriaProductoVenta"
    );

    const marca = document.getElementById(
        "marcaProductoVenta"
    );

    const disponibilidad = document.getElementById(
        "disponibilidadProductoVenta"
    );

    const orden = document.getElementById(
        "ordenProductoVenta"
    );

    const limpiar = document.getElementById(
        "btnLimpiarFiltrosVenta"
    );

    const vaciar = document.getElementById(
        "btnVaciarCarritoVenta"
    );

    const formulario = document.getElementById(
        "formVentaPresencial"
    );

    const rut = document.getElementById(
        "rutClienteVenta"
    );

    const telefono = document.getElementById(
        "telefonoClienteVenta"
    );

    [
        buscador,
        categoria,
        marca,
        disponibilidad,
        orden
    ].forEach(elemento => {

        if (!elemento) {
            return;
        }

        const evento =
            elemento.tagName === "INPUT"
                ? "input"
                : "change";

        elemento.addEventListener(
            evento,
            aplicarFiltrosVentaPresencial
        );
    });

    limpiar.addEventListener(
        "click",
        limpiarFiltrosVentaPresencial
    );

    vaciar.addEventListener(
        "click",
        vaciarCarritoVentaPresencial
    );

    formulario.addEventListener(
        "submit",
        enviarVentaPresencial
    );

    formulario.addEventListener(
        "reset",
        function () {

            setTimeout(() => {

                carritoVentaPresencial = [];
                documentoOrigenVenta = null;
                renderizarCarritoVenta();
                limpiarRespuestaVenta();

            }, 0);
        }
    );

    rut.addEventListener("input", function () {

        this.value = formatearRutVenta(
            this.value
        );
    });

    telefono.addEventListener(
        "input",
        function () {

            this.value = this.value
                .replace(/\D/g, "")
                .slice(0, 9);
        }
    );

}

/* =====================================================
   USUARIO RESPONSABLE
===================================================== */

function mostrarUsuarioResponsableVenta() {

    const elemento = document.getElementById(
        "usuarioResponsableVenta"
    );

    if (!elemento) {
        return;
    }

    elemento.textContent =
        localStorage.getItem("usuario") ||
        "Usuario conectado";
}

/* =====================================================
   CARGAR PRODUCTOS
===================================================== */

async function cargarProductosVentaPresencial() {

    try {

        const response = await fetch(
            "./php/obtenerProductosAdmin.php",
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarErrorProductosVenta(
                resultado.mensaje
            );

            return;
        }

        productosVentaPresencial =
            Array.isArray(resultado.datos)
                ? resultado.datos
                : [];

        cargarOpcionesFiltrosVenta();
        aplicarFiltrosVentaPresencial();

    } catch (error) {

        console.error(
            "Error cargando productos para venta:",
            error
        );

        mostrarErrorProductosVenta(
            "No fue posible cargar los productos."
        );
    }
}

/* =====================================================
   OPCIONES DE FILTROS
===================================================== */

function cargarOpcionesFiltrosVenta() {

    const selectCategoria =
        document.getElementById(
            "categoriaProductoVenta"
        );

    const selectMarca =
        document.getElementById(
            "marcaProductoVenta"
        );

    const categorias = [
        ...new Set(
            productosVentaPresencial
                .map(producto =>
                    String(
                        producto.categoria || ""
                    ).trim()
                )
                .filter(Boolean)
        )
    ].sort((a, b) =>
        a.localeCompare(b, "es", {
            sensitivity: "base"
        })
    );

    const marcas = [
        ...new Set(
            productosVentaPresencial
                .map(producto =>
                    String(
                        producto.marca || ""
                    ).trim()
                )
                .filter(Boolean)
        )
    ].sort((a, b) =>
        a.localeCompare(b, "es", {
            sensitivity: "base"
        })
    );

    categorias.forEach(categoria => {

        const option =
            document.createElement("option");

        option.value = categoria;
        option.textContent = categoria;

        selectCategoria.appendChild(option);
    });

    marcas.forEach(marca => {

        const option =
            document.createElement("option");

        option.value = marca;
        option.textContent = marca;

        selectMarca.appendChild(option);
    });
}

/* =====================================================
   FILTRAR PRODUCTOS
===================================================== */

function aplicarFiltrosVentaPresencial() {

    const texto = normalizarTextoVenta(
        document.getElementById(
            "buscarProductoVenta"
        ).value
    );

    const categoria = document.getElementById(
        "categoriaProductoVenta"
    ).value;

    const marca = document.getElementById(
        "marcaProductoVenta"
    ).value;

    const disponibilidad =
        document.getElementById(
            "disponibilidadProductoVenta"
        ).value;

    const orden = document.getElementById(
        "ordenProductoVenta"
    ).value;

    productosFiltradosVenta =
        productosVentaPresencial.filter(
            producto => {

                const contenido =
                    normalizarTextoVenta(
                        [
                            producto.sku,
                            producto.nombre,
                            producto.marca,
                            producto.categoria,
                            producto.descripcion
                        ].join(" ")
                    );

                const stock =
                    Number(producto.cantidad) || 0;

                const stockMinimo =
                    Number(
                        producto.stock_minimo
                    ) || 5;

                const coincideTexto =
                    !texto ||
                    contenido.includes(texto);

                const coincideCategoria =
                    !categoria ||
                    producto.categoria ===
                        categoria;

                const coincideMarca =
                    !marca ||
                    producto.marca === marca;

                let coincideDisponibilidad = true;

                if (
                    disponibilidad ===
                    "disponibles"
                ) {

                    coincideDisponibilidad =
                        stock > 0;
                }

                if (
                    disponibilidad ===
                    "bajo_stock"
                ) {

                    coincideDisponibilidad =
                        stock > 0 &&
                        stock <= stockMinimo;
                }

                if (
                    disponibilidad ===
                    "agotados"
                ) {

                    coincideDisponibilidad =
                        stock <= 0;
                }

                return (
                    coincideTexto &&
                    coincideCategoria &&
                    coincideMarca &&
                    coincideDisponibilidad
                );
            }
        );

    productosFiltradosVenta.sort(
        (a, b) => {

            switch (orden) {

                case "precio_menor":

                    return obtenerPrecioVenta(a) -
                        obtenerPrecioVenta(b);

                case "precio_mayor":

                    return obtenerPrecioVenta(b) -
                        obtenerPrecioVenta(a);

                case "stock_menor":

                    return Number(a.cantidad) -
                        Number(b.cantidad);

                case "stock_mayor":

                    return Number(b.cantidad) -
                        Number(a.cantidad);

                default:

                    return String(a.nombre)
                        .localeCompare(
                            String(b.nombre),
                            "es",
                            {
                                sensitivity: "base"
                            }
                        );
            }
        }
    );

    renderizarProductosVenta();
}

/* =====================================================
   TABLA PRODUCTOS
===================================================== */

function renderizarProductosVenta() {

    const tbody = document.getElementById(
        "tbodyProductosVenta"
    );

    const sinProductos = document.getElementById(
        "sinProductosVenta"
    );

    const contador = document.getElementById(
        "cantidadProductosVenta"
    );

    tbody.innerHTML = "";

    contador.textContent =
        productosFiltradosVenta.length;

    if (
        productosFiltradosVenta.length === 0
    ) {

        sinProductos.hidden = false;
        sinProductos.style.display = "flex";

        return;
    }

    sinProductos.hidden = true;
    sinProductos.style.display = "none";

    productosFiltradosVenta.forEach(
        producto => {

            tbody.appendChild(
                crearFilaProductoVenta(producto)
            );
        }
    );
}

function crearFilaProductoVenta(producto) {

    const fila = document.createElement("tr");

    const stock =
        Number(producto.cantidad) || 0;

    const agotado = stock <= 0;

    fila.innerHTML = `

        <td>

            <img
                src="./images/productos/${Number(
                    producto.id_producto
                )}.webp?v=${Date.now()}"
                alt="${escaparHTMLVenta(
                    producto.nombre
                )}"
                class="imagenProductoVenta">

        </td>

        <td>

            <div class="infoProductoVenta">

                <strong>
                    ${escaparHTMLVenta(
                        producto.nombre
                    )}
                </strong>

                <small>
                    SKU:
                    ${escaparHTMLVenta(
                        producto.sku ||
                        "Sin SKU"
                    )}
                </small>

            </div>

        </td>

        <td>
            ${escaparHTMLVenta(
                producto.categoria
            )}
        </td>

        <td>
            ${escaparHTMLVenta(
                producto.marca
            )}
        </td>

        <td>

            <span class="
                stockProductoVenta
                ${agotado ? "agotado" : ""}
            ">

                ${stock}

            </span>

        </td>

        <td>

            ${mostrarPrecioProductoVenta(producto)}

        </td>

        <td>

            <button
                type="button"
                class="btnAgregarProductoVenta"
                data-id-producto="${Number(
                    producto.id_producto
                )}"
                ${agotado ? "disabled" : ""}>

                <i class="fa-solid fa-plus"></i>

                ${agotado ? "Agotado" : "Agregar"}

            </button>

        </td>
    `;

    const imagen = fila.querySelector(
        ".imagenProductoVenta"
    );

    imagen.onerror = function () {

        this.onerror = null;

        this.src =
            "./images/productos/no-image.webp";
    };

    const boton = fila.querySelector(
        ".btnAgregarProductoVenta"
    );

    boton.addEventListener(
        "click",
        function () {

            agregarProductoVenta(
                Number(this.dataset.idProducto)
            );
        }
    );

    return fila;
}

/* =====================================================
   AGREGAR PRODUCTO
===================================================== */

function agregarProductoVenta(idProducto) {
    if (procesandoCobroVenta) return;

    const producto =
        productosVentaPresencial.find(
            item =>
                Number(item.id_producto) ===
                Number(idProducto)
        );

    if (!producto) {
        return;
    }

    const stock =
        Number(producto.cantidad) || 0;

    const itemExistente =
        carritoVentaPresencial.find(
            item =>
                Number(item.id_producto) ===
                Number(idProducto)
        );

    if (itemExistente) {

        if (itemExistente.cant >= stock) {

            mostrarRespuestaVenta(
                `No hay más unidades disponibles de "${producto.nombre}".`,
                false
            );

            return;
        }

        itemExistente.cant++;

    } else {

        carritoVentaPresencial.push({
            ...producto,
            cant: 1,
            precio_venta:
                obtenerPrecioVenta(producto)
        });
    }

    limpiarRespuestaVenta();
    renderizarCarritoVenta();
}

/* =====================================================
   RENDERIZAR CARRITO
===================================================== */

function renderizarCarritoVenta() {

    const contenedor = document.getElementById(
        "productosCarritoVenta"
    );

    const cantidad = document.getElementById(
        "cantidadCarritoVenta"
    );

    const botonConfirmar = document.getElementById(
        "btnConfirmarVentaPresencial"
    );

    contenedor.innerHTML = "";

    const totalUnidades =
        carritoVentaPresencial.reduce(
            (total, producto) =>
                total +
                Number(producto.cant),
            0
        );

    cantidad.textContent =
        `${totalUnidades} ${
            totalUnidades === 1
                ? "producto"
                : "productos"
        }`;

    if (
        carritoVentaPresencial.length === 0
    ) {

        contenedor.innerHTML = `

            <div class="carritoVentaVacio">

                <i class="fa-solid fa-basket-shopping"></i>

                <strong>Venta vacía</strong>

                <p>
                    Agregue productos desde el catálogo.
                </p>

            </div>
        `;

        botonConfirmar.disabled = true;

        actualizarTotalesVenta();

        return;
    }

    carritoVentaPresencial.forEach(
        producto => {

            contenedor.appendChild(
                crearItemCarritoVenta(producto)
            );
        }
    );

    botonConfirmar.disabled = false;

    actualizarTotalesVenta();
}

function crearItemCarritoVenta(producto) {

    const item = document.createElement("article");

    item.className = "itemCarritoVenta";

    item.innerHTML = `

        <img
            src="./images/productos/${Number(
                producto.id_producto
            )}.webp"
            alt="${escaparHTMLVenta(
                producto.nombre
            )}">

        <div class="infoItemCarritoVenta">

            <strong>
                ${escaparHTMLVenta(
                    producto.nombre
                )}
            </strong>

            <small>
                ${escaparHTMLVenta(
                    producto.marca || ""
                )}
            </small>

            <div class="controlesItemVenta">

                <button
                    type="button"
                    class="btnMenosVenta"
                    aria-label="Disminuir cantidad">

                    −

                </button>

                <span>
                    ${Number(producto.cant)}
                </span>

                <button
                    type="button"
                    class="btnMasVenta"
                    aria-label="Aumentar cantidad">

                    +

                </button>

                <strong>
                    ${formatearPrecioVenta(
                        producto.precio_venta *
                        producto.cant
                    )}
                </strong>

            </div>

        </div>

        <button
            type="button"
            class="btnEliminarItemVenta"
            title="Eliminar producto">

            <i class="fa-solid fa-trash-can"></i>

        </button>
    `;

    const imagen = item.querySelector("img");

    imagen.onerror = function () {

        this.onerror = null;

        this.src =
            "./images/productos/no-image.webp";
    };

    item.querySelector(
        ".btnMenosVenta"
    ).addEventListener(
        "click",
        () => cambiarCantidadVenta(
            producto.id_producto,
            -1
        )
    );

    item.querySelector(
        ".btnMasVenta"
    ).addEventListener(
        "click",
        () => cambiarCantidadVenta(
            producto.id_producto,
            1
        )
    );

    item.querySelector(
        ".btnEliminarItemVenta"
    ).addEventListener(
        "click",
        () => eliminarProductoVenta(
            producto.id_producto
        )
    );

    return item;
}

/* =====================================================
   CANTIDADES
===================================================== */

function cambiarCantidadVenta(
    idProducto,
    cambio
) {
    if (procesandoCobroVenta) return;

    const item =
        carritoVentaPresencial.find(
            producto =>
                Number(producto.id_producto) ===
                Number(idProducto)
        );

    if (!item) {
        return;
    }

    const nuevaCantidad =
        Number(item.cant) + cambio;

    if (nuevaCantidad <= 0) {

        eliminarProductoVenta(idProducto);

        return;
    }

    if (
        nuevaCantidad >
        Number(item.cantidad)
    ) {

        mostrarRespuestaVenta(
            `El stock máximo disponible es ${item.cantidad}.`,
            false
        );

        return;
    }

    item.cant = nuevaCantidad;

    limpiarRespuestaVenta();
    renderizarCarritoVenta();
}

function eliminarProductoVenta(idProducto) {
    if (procesandoCobroVenta) return;

    carritoVentaPresencial =
        carritoVentaPresencial.filter(
            producto =>
                Number(producto.id_producto) !==
                Number(idProducto)
        );

    renderizarCarritoVenta();
}

function vaciarCarritoVentaPresencial() {
    if (procesandoCobroVenta) return;

    carritoVentaPresencial = [];

    renderizarCarritoVenta();
    limpiarRespuestaVenta();
}

/* =====================================================
   TOTALES
===================================================== */

function actualizarTotalesVenta() {

    const total =
        carritoVentaPresencial.reduce(
            (acumulado, producto) =>
                acumulado +
                Number(producto.precio_venta) *
                Number(producto.cant),
            0
        );

    const neto = Math.round(
        total / 1.19
    );

    const iva = total - neto;

    document.getElementById(
        "netoCarritoVenta"
    ).textContent =
        formatearPrecioVenta(neto);

    document.getElementById(
        "ivaCarritoVenta"
    ).textContent =
        formatearPrecioVenta(iva);

    document.getElementById(
        "totalCarritoVenta"
    ).textContent =
        formatearPrecioVenta(total);
}

/* =====================================================
   ENVIAR VENTA
===================================================== */

let procesandoCobroVenta = false;

function claveCobroPendienteVenta() {
    return 'megagest:cobro-pendiente:' + (localStorage.getItem('usuario') || 'sesion');
}

function mostrarPendienteCobroVenta() {
    const form = document.getElementById('formVentaPresencial');
    if (!form) return;
    let aviso = document.getElementById('avisoCobroPendienteVenta');
    if (aviso) aviso.remove();
    let pendiente;
    try { pendiente = sessionStorage.getItem(claveCobroPendienteVenta()); }
    catch (_) { return; }
    if (!pendiente) return;
    aviso = document.createElement('div');
    aviso.id = 'avisoCobroPendienteVenta';
    const texto = document.createElement('p');
    texto.textContent = 'Hay un registro cuyo resultado debe comprobarse. No vuelva a cobrar ni inicie otra venta. Este botón reenvía la misma operación, no crea una nueva.';
    const boton = document.createElement('button');
    boton.type = 'button';
    boton.textContent = 'Comprobar registro pendiente';
    boton.addEventListener('click', enviarVentaPresencial);
    aviso.append(texto, boton);
    form.prepend(aviso);
}

async function enviarVentaPresencial(event) {
    event.preventDefault();
    if (guardandoCotizacionPresencial || procesandoCobroVenta) return;
    const formulario = document.getElementById('formVentaPresencial');
    if (!formulario) return;
    const seccion = formulario.closest('.gestionVentaPresencial');
    const clave = claveCobroPendienteVenta();
    let pendiente = null;
    try {
        const almacenado = sessionStorage.getItem(clave);
        if (almacenado) pendiente = JSON.parse(almacenado);
    } catch (_) {
        mostrarRespuestaVenta('No se puede consultar el registro pendiente. No confirme otra venta hasta revisar el almacenamiento del navegador.', false);
        return;
    }
    let datos, url;
    if (pendiente) {
        if (!['./php/crearVentaPresencial.php','./php/confirmarVentaCotizacion.php'].includes(pendiente.url) || !pendiente.datos?.operacion) {
            mostrarRespuestaVenta('El registro pendiente no es válido. Revise Pedidos antes de continuar.', false); return;
        }
        ({datos, url} = pendiente);
    } else {
        if (!formulario.reportValidity()) return;
        if (!carritoVentaPresencial.length) {
            mostrarRespuestaVenta('Agregue productos antes de confirmar.', false); return;
        }
        const metodo = formulario.querySelector('input[name="metodo_pago_venta"]:checked');
        if (!metodo) { mostrarRespuestaVenta('Seleccione el método de pago.', false); return; }
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        const campo = id => document.getElementById(id).value.trim();
        const origen = documentoOrigenVenta ? {
            tipo: documentoOrigenVenta.tipo,
            id_pedido: Number(documentoOrigenVenta.id_pedido)
        } : null;
        datos = {
            accion: 'revisar',
            operacion: Array.from(bytes, b => b.toString(16).padStart(2,'0')).join(''),
            origen,
            cliente: {
                id_cliente: Number(document.getElementById('idClienteVenta')?.value || 0),
                rut: campo('rutClienteVenta'), nombre: campo('nombreClienteVenta'),
                apellido: campo('apellidoClienteVenta'), correo: campo('correoClienteVenta'),
                telefono: campo('telefonoClienteVenta')
            },
            metodo_pago: metodo.value,
            observaciones: campo('observacionesVenta'),
            productos: carritoVentaPresencial.map(p => ({id_producto: Number(p.id_producto), cantidad: Number(p.cant)}))
        };
        url = origen?.tipo === 'cotizacion' ? './php/confirmarVentaCotizacion.php' : './php/crearVentaPresencial.php';
    }
    procesandoCobroVenta = true;
    const controles = Array.from(seccion.querySelectorAll('input,select,textarea,button')).map(el => [el, el.disabled]);
    controles.forEach(([el]) => { el.disabled = true; });
    let guardada = false;
    try {
        const solicitar = async payload => {
            const response = await fetch(url, {
                method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
            });
            const resultado = await response.json();
            if (manejarSesionExpirada(resultado)) return null;
            return {response, resultado};
        };
        let revision;
        if (!pendiente) {
            mostrarRespuestaVenta('Comprobando vigencia, precios y existencias...', true);
            const consulta = await solicitar(datos);
            if (!consulta) return;
            if (!consulta.response.ok || !consulta.resultado.ok) {
                mostrarRespuestaVenta(consulta.resultado.mensaje || 'No se pudo revisar la venta.', false); return;
            }
            revision = consulta.resultado.revision;
        }
        while (true) {
            if (!pendiente) {
                if (!formulario.isConnected) return;
                if (!revision || !Array.isArray(revision.productos) || !revision.token_revision) throw Error('Revisión inválida');
                const diferencias = [];
                revision.productos.forEach(p => {
                    const item = carritoVentaPresencial.find(x => Number(x.id_producto) === Number(p.id_producto));
                    if (!item) throw Error('El carrito cambió durante la revisión.');
                    if (Number(item.precio_venta) !== Number(p.precio_unitario)) {
                        diferencias.push((p.sku || ('Producto '+p.id_producto)) + ': ' + formatearPrecioVenta(item.precio_venta) + ' → ' + formatearPrecioVenta(p.precio_unitario));
                    }
                    item.precio_venta = Number(p.precio_unitario);
                    item.cantidad = Number(p.stock_actual);
                });
                renderizarCarritoVenta();
                // renderizarCarritoVenta habilita el botón: mantenemos el bloqueo mientras se confirma.
                seccion.querySelectorAll('input,select,textarea,button').forEach(el => { el.disabled = true; });
                const texto = (diferencias.length ? 'Precios actualizados:\n'+diferencias.join('\n')+'\n\n' : '') +
                    'Total revisado: '+formatearPrecioVenta(revision.total)+'\nMétodo: '+datos.metodo_pago+
                    '\n\n¿Acepta estos importes y confirma que recibió y verificó el pago?\nSe registrará la venta y se descontará stock. No se realiza un cargo bancario desde esta pantalla.';
                // Diálogo nativo: trata los nombres/SKU exclusivamente como texto.
                if (!window.confirm(texto)) {
                    mostrarRespuestaVenta('Operación cancelada. No se registró ningún pago. El resumen conserva los precios revisados.', true); return;
                }
                datos = {...datos, accion:'confirmar', token_revision:revision.token_revision, cobro_confirmado:true};
                // Se guarda ANTES de enviar. Si falla la conexión, se reenvía el mismo ID y contenido.
                sessionStorage.setItem(clave, JSON.stringify({url,datos}));
            }
            mostrarRespuestaVenta('Registrando venta. No repita el cobro...', true);
            const confirmacion = await solicitar(datos);
            if (!confirmacion) return;
            const {response, resultado} = confirmacion;
            if (!response.ok || !resultado.ok) {
                if (resultado.codigo === 'revision_requerida' && resultado.revision) {
                    sessionStorage.removeItem(clave);
                    if (pendiente) {
                        mostrarRespuestaVenta('El intento anterior no se registró y los precios o el documento cambiaron. Recupere el documento y revise nuevamente.', false); return;
                    }
                    revision = resultado.revision;
                    continue;
                }
                // Solo liberamos la operación ante un rechazo explícito de negocio.
                if (response.status >= 400 && response.status < 500 && !resultado.sesionExpirada) sessionStorage.removeItem(clave);
                mostrarRespuestaVenta(resultado.mensaje || 'No se pudo confirmar el registro. Revise antes de repetir el cobro.', false);
                return;
            }
            sessionStorage.removeItem(clave);
            guardada = true;
            carritoVentaPresencial = [];
            documentoOrigenVenta = null;
            if (formulario.isConnected) {
                renderizarCarritoVenta();
                mostrarRespuestaVenta(resultado.mensaje, true);
                try { ofrecerImpresionVenta(resultado.datos.id_pedido, resultado.datos.numero_pedido); }
                catch (e) { console.error('Venta guardada; revise su impresión.',e); }
            } else {
                window.alert('Venta registrada: '+resultado.datos.numero_pedido+'. Puede reimprimirla desde Ventas.');
            }
            break;
        }
    } catch (error) {
        console.error('Error en revisión/registro de venta:', error);
        let incierto = true;
        try { incierto = !!sessionStorage.getItem(clave); } catch (_) {}
        mostrarRespuestaVenta(incierto ?
            'No se pudo comprobar el resultado. No repita el cobro. Use Comprobar registro pendiente para reenviar la misma operación.' :
            'No se pudo completar la revisión. No se envió la confirmación de venta.', false);
    } finally {
        procesandoCobroVenta = false;
        controles.forEach(([el, disabled]) => { if (el.isConnected) el.disabled=disabled; });
        if (formulario.isConnected) {
            renderizarCarritoVenta();
            const boton = document.getElementById('btnConfirmarVentaPresencial');
            if (boton) boton.disabled = guardada || carritoVentaPresencial.length===0;
            mostrarPendienteCobroVenta();
        }
    }
}


/* =====================================================
   FILTROS Y VOLVER
===================================================== */

function limpiarFiltrosVentaPresencial() {

    document.getElementById(
        "buscarProductoVenta"
    ).value = "";

    document.getElementById(
        "categoriaProductoVenta"
    ).value = "";

    document.getElementById(
        "marcaProductoVenta"
    ).value = "";

    document.getElementById(
        "disponibilidadProductoVenta"
    ).value = "disponibles";

    document.getElementById(
        "ordenProductoVenta"
    ).value = "nombre_asc";

    aplicarFiltrosVentaPresencial();
}

/* =====================================================
   RESPUESTAS
===================================================== */

function mostrarRespuestaVenta(
    mensaje,
    correcto
) {

    const respuesta = document.getElementById(
        "respuestaVentaPresencial"
    );

    respuesta.className = correcto
        ? "respuestaVentaPresencial correcta"
        : "respuestaVentaPresencial error";

    respuesta.textContent = mensaje;
}

function limpiarRespuestaVenta() {

    const respuesta = document.getElementById(
        "respuestaVentaPresencial"
    );

    if (!respuesta) {
        return;
    }

    respuesta.className =
        "respuestaVentaPresencial";

    respuesta.textContent = "";
}

function mostrarErrorProductosVenta(mensaje) {

    const tbody = document.getElementById(
        "tbodyProductosVenta"
    );

    tbody.innerHTML = `

        <tr>

            <td
                colspan="7"
                class="errorProductosVenta">

                <i class="fa-solid fa-triangle-exclamation"></i>

                ${escaparHTMLVenta(mensaje)}

            </td>

        </tr>
    `;
}

/* =====================================================
   AUXILIARES
===================================================== */

function obtenerPrecioVenta(producto) {

    const precioOferta =
        Number(producto.precio_oferta) || 0;

    const precioNormal =
        Number(producto.precio) || 0;

    const enOferta =
        Number(producto.en_oferta) === 1;

    if (
        enOferta &&
        precioOferta > 0 &&
        precioOferta < precioNormal
    ) {
        return precioOferta;
    }

    return precioNormal;
}

function formatearPrecioVenta(valor) {

    return new Intl.NumberFormat(
        "es-CL",
        {
            style: "currency",
            currency: "CLP",
            minimumFractionDigits: 0
        }
    ).format(Number(valor) || 0);
}

function normalizarTextoVenta(valor) {

    return String(valor ?? "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();
}

function escaparHTMLVenta(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function formatearRutVenta(valor) {

    let rut = String(valor)
        .replace(/[^0-9kK]/g, "")
        .toUpperCase()
        .slice(0, 9);

    if (rut.length <= 1) {
        return rut;
    }

    const cuerpo = rut.slice(0, -1);
    const digito = rut.slice(-1);

    return (
        cuerpo.replace(
            /\B(?=(\d{3})+(?!\d))/g,
            "."
        ) +
        "-" +
        digito
    );
}


let temporizadorBuscarCliente = null;
let controladorBusquedaCliente = null;

function configurarBuscadorClienteVenta() {

    const buscador = document.getElementById(
        "buscarClienteVenta"
    );

    const botonActualizar = document.getElementById(
        "btnActualizarClienteVenta"
    );

    if (!buscador) {

        console.error(
            "No existe el input #buscarClienteVenta"
        );

        return;
    }

    buscador.oninput = function () {

        clearTimeout(temporizadorBuscarCliente);

        /*
        Al cambiar el buscador se elimina la selección
        del cliente anterior.
        */

        limpiarClienteSeleccionadoVenta();

        const texto = this.value.trim();

        if (texto.length < 2) {

            if (controladorBusquedaCliente) {
                controladorBusquedaCliente.abort();
            }

            cerrarResultadosClientes();

            return;
        }

        temporizadorBuscarCliente = setTimeout(() => {

            buscarClientesVenta(texto);

        }, 350);
    };

    buscador.onkeydown = function (event) {

        if (event.key === "Escape") {
            cerrarResultadosClientes();
        }
    };

    if (botonActualizar) {

        /*
         onClick evita registrar el evento varias veces
         cuando se vuelve a abrir la pestaña Ventas.
        */

        botonActualizar.onclick = function (event) {

            event.preventDefault();

            actualizarClienteVenta();
        };

    } else {

        console.error(
            "No existe el botón #btnActualizarClienteVenta"
        );
    }
}


async function buscarClientesVenta(texto) {

    const resultados = document.getElementById(
        "resultadosBuscarCliente"
    );

    const cargando = document.getElementById(
        "cargandoBuscarCliente"
    );

    if (!texto || texto.length < 2) {
        cerrarResultadosClientes();
        return;
    }

    if (controladorBusquedaCliente) {
        controladorBusquedaCliente.abort();
    }

    controladorBusquedaCliente =
        new AbortController();

    resultados.innerHTML = `
        <div class="mensajeResultadoCliente">
            Buscando clientes...
        </div>
    `;

    resultados.classList.add("visible");

    if (cargando) {
        cargando.hidden = false;
    }

    try {

        const response = await fetch(
            `./php/buscarClientesVenta.php?buscar=${encodeURIComponent(texto)}`,
            {
                cache: "no-store",
                signal: controladorBusquedaCliente.signal
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            resultados.innerHTML = `
                <div class="mensajeResultadoCliente error">
                    ${escaparHTMLVenta(resultado.mensaje)}
                </div>
            `;

            return;
        }

        /*
         Evita mostrar resultados antiguos cuando
         el usuario continuó escribiendo.
        */

        const textoActual = document
            .getElementById("buscarClienteVenta")
            .value
            .trim();

        if (textoActual !== texto) {
            return;
        }

        mostrarResultadosClientes(resultado.datos);

    } catch (error) {

        if (error.name !== "AbortError") {

            console.error(
                "Error buscando clientes:",
                error
            );

            resultados.innerHTML = `
                <div class="mensajeResultadoCliente error">
                    No fue posible buscar clientes.
                </div>
            `;
        }

    } finally {

        if (cargando) {
            cargando.hidden = true;
        }
    }
}


function mostrarResultadosClientes(clientes) {

    const contenedor = document.getElementById(
        "resultadosBuscarCliente"
    );

    contenedor.innerHTML = "";

    if (!clientes.length) {

        contenedor.innerHTML = `
            <div class="itemResultadoCliente">
                No se encontraron clientes.
            </div>
        `;

        contenedor.classList.add("visible");

        return;
    }

    clientes.forEach(cliente => {

        const boton = document.createElement("button");

        boton.type = "button";
        boton.className = "itemResultadoCliente";

        const nombreCompleto = [
            cliente.nombre,
            cliente.apellido
        ]
            .filter(Boolean)
            .join(" ");

        boton.innerHTML = `
            <span class="informacionResultadoCliente">

                <strong>
                    ${escaparHTMLVenta(nombreCompleto)}
                </strong>

                <small>
                    ${escaparHTMLVenta(
                        cliente.correo ||
                        cliente.telefono ||
                        "Sin datos de contacto"
                    )}
                </small>

            </span>

            <span class="rutResultadoCliente">
                ${escaparHTMLVenta(
                    cliente.rut || "Sin RUT"
                )}
            </span>
        `;

        boton.addEventListener("click", function () {

            cargarClienteEnVenta(cliente);
        });

        contenedor.appendChild(boton);
    });

    contenedor.classList.add("visible");
}


function cargarClienteEnVenta(cliente) {
    if (procesandoCobroVenta) return;

    document.getElementById("idClienteVenta").value =
        cliente.id_cliente ?? "";

    document.getElementById("rutClienteVenta").value =
        cliente.rut ?? "";

    document.getElementById("nombreClienteVenta").value =
        cliente.nombre ?? "";

    document.getElementById("apellidoClienteVenta").value =
        cliente.apellido ?? "";

    document.getElementById("telefonoClienteVenta").value =
        cliente.telefono ?? "";

    document.getElementById("correoClienteVenta").value =
        cliente.correo ?? "";

    document.getElementById(
        "btnActualizarClienteVenta"
    ).hidden = false;

    document.getElementById(
        "buscarClienteVenta"
    ).value = [
        cliente.nombre,
        cliente.apellido
    ]
        .filter(Boolean)
        .join(" ");

    cerrarResultadosClientes();

    mostrarEstadoClienteVenta(
        "Cliente encontrado. Puede modificar sus datos si es necesario.",
        true
    );
}


async function actualizarClienteVenta() {
    if (procesandoCobroVenta) return;

    const idCliente = Number(
        document.getElementById("idClienteVenta").value
    );

    const boton = document.getElementById(
        "btnActualizarClienteVenta"
    );

    if (!idCliente) {

        mostrarEstadoClienteVenta(
            "Primero debe seleccionar un cliente registrado.",
            false
        );

        return;
    }

    const datos = new FormData();

    datos.append("id_cliente", idCliente);

    datos.append(
        "rut",
        document.getElementById("rutClienteVenta").value.trim()
    );

    datos.append(
        "nombre",
        document.getElementById("nombreClienteVenta").value.trim()
    );

    datos.append(
        "apellido",
        document.getElementById("apellidoClienteVenta").value.trim()
    );

    datos.append(
        "telefono",
        document.getElementById("telefonoClienteVenta").value.trim()
    );

    datos.append(
        "correo",
        document.getElementById("correoClienteVenta").value.trim()
    );

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Actualizando...
    `;

    try {

        const response = await fetch(
            "./php/actualizarClienteVenta.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        mostrarEstadoClienteVenta(
            resultado.mensaje,
            resultado.ok
        );

    } catch (error) {

        console.error(error);

        mostrarEstadoClienteVenta(
            "No fue posible actualizar el cliente.",
            false
        );

    } finally {

        boton.disabled = false;

        boton.innerHTML = `
            <i class="fa-solid fa-user-pen"></i>
            Actualizar datos del cliente
        `;
    }
}


function cerrarResultadosClientes() {

    const resultados = document.getElementById(
        "resultadosBuscarCliente"
    );

    if (!resultados) {
        return;
    }

    resultados.classList.remove("visible");
    resultados.innerHTML = "";
}

function mostrarEstadoClienteVenta(mensaje, correcto) {

    const estado = document.getElementById(
        "estadoClienteVenta"
    );

    if (!estado) {
        return;
    }

    estado.className = correcto
        ? "correcto"
        : "error";

    estado.textContent = mensaje;
}

function escaparHTMLVenta(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


function limpiarClienteSeleccionadoVenta() {

    const idCliente = document.getElementById(
        "idClienteVenta"
    );

    const botonActualizar = document.getElementById(
        "btnActualizarClienteVenta"
    );

    const estado = document.getElementById(
        "estadoClienteVenta"
    );

    if (idCliente) {
        idCliente.value = "";
    }

    if (botonActualizar) {
        botonActualizar.hidden = true;
        botonActualizar.disabled = false;
    }

    if (estado) {
        estado.className = "";
        estado.textContent = "";
    }
}



let guardandoCotizacionPresencial = false;

async function guardarCotizacionPresencial() {
    if (procesandoCobroVenta) return;

    if (guardandoCotizacionPresencial) {
        return;
    }

    if (documentoOrigenVenta !== null) {

        mostrarRespuestaVenta(
            "Hay un documento recuperado en revisión. " +
            "Pulse Restablecer para iniciar una cotización nueva.",
            false
        );

        return;
    }

    const formulario = document.getElementById(
        "formVentaPresencial"
    );

    const boton = document.getElementById(
        "btnGuardarCotizacion"
    );

    const botonVenta = document.getElementById(
        "btnConfirmarVentaPresencial"
    );

    if (!formulario || !boton) {
        return;
    }

    if (carritoVentaPresencial.length === 0) {

        mostrarRespuestaVenta(
            "Debe agregar al menos un producto para cotizar.",
            false
        );

        return;
    }

    /*
     * Validamos los datos del cliente por separado.
     * Cotizar no exige seleccionar un método de pago.
     */

    const camposCliente = [
        "rutClienteVenta",
        "nombreClienteVenta",
        "apellidoClienteVenta",
        "correoClienteVenta",
        "telefonoClienteVenta",
        "observacionesVenta"
    ];

    for (const id of camposCliente) {

        const campo = document.getElementById(id);

        if (!campo) {

            mostrarRespuestaVenta(
                "No se encontró un campo del formulario.",
                false
            );

            return;
        }

        if (!campo.reportValidity()) {
            return;
        }
    }

    /*
    * Evita abrir varias confirmaciones mientras
    * el usuario decide si desea guardar.
    */

    guardandoCotizacionPresencial = true;

    try {

        const confirmado = await confirmar(
            "Guardar cotización",
            "¿Desea guardar esta cotización y abrir su impresión? " +
            "No se registrará ningún cobro ni se descontará stock."
        );

        if (!confirmado) {
            return;
        }

    } catch (error) {

        console.error(
            "Error mostrando la confirmación:",
            error
        );

        mostrarRespuestaVenta(
            "No fue posible mostrar la confirmación.",
            false
        );

        return;

    } finally {

        guardandoCotizacionPresencial = false;
    }

    // No continuar si el usuario salió de la pantalla.
    if (!formulario.isConnected) {
        return;
    }

    const valorCampo = id =>
        document.getElementById(id).value.trim();

    const datos = {

        cliente: {
            rut: valorCampo("rutClienteVenta"),
            nombre: valorCampo("nombreClienteVenta"),
            apellido: valorCampo("apellidoClienteVenta"),
            correo: valorCampo("correoClienteVenta"),
            telefono: valorCampo("telefonoClienteVenta")
        },

        observaciones: valorCampo("observacionesVenta"),

        productos: carritoVentaPresencial.map(
            producto => ({
                id_producto: Number(producto.id_producto),
                cantidad: Number(producto.cant)
            })
        )
    };

    guardandoCotizacionPresencial = true;

    /*
     * Conservamos el estado de los controles para restaurarlo.
     * Durante el envío no se permite cambiar el formulario.
     */

    const controles = Array.from(
        formulario.querySelectorAll(
            "input, select, textarea, button"
        )
    ).map(elemento => ({
        elemento,
        deshabilitado: elemento.disabled
    }));

    controles.forEach(({ elemento }) => {
        elemento.disabled = true;
    });

    const contenidoBoton = boton.innerHTML;

    boton.disabled = true;

    if (botonVenta) {
        botonVenta.disabled = true;
    }

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Guardando cotización...
    `;

    limpiarRespuestaVenta();

    let guardada = false;

    /*
    * Se abre durante el clic para reducir el riesgo
    * de que el navegador bloquee la ventana emergente.
    */

    let ventanaImpresion = null;

    try {
        ventanaImpresion = window.open("about:blank", "_blank");

        if (ventanaImpresion) {
            ventanaImpresion.opener = null;
            ventanaImpresion.document.title = "Preparando cotización";
            ventanaImpresion.document.body.textContent = "Guardando cotización. Espere un momento...";
        }
    } catch (error) {
        console.warn("No se pudo preparar la ventana de impresión:", error);
    }

    try {

        const response = await fetch(
            "./php/crearCotizacion.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(datos)
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!response.ok || !resultado.ok) {

            mostrarRespuestaVenta(
                resultado.mensaje ||
                    "No fue posible guardar la cotización.",
                false
            );

            return;
        }

        guardada = true;

        mostrarRespuestaVenta(
            "Cotización " +
            resultado.datos.numero_cotizacion +
            " guardada. No se registró ningún cobro " +
            "ni se descontó stock.",
            true
        );

        /*
        * La impresión se gestiona por separado:
        * si falla, la cotización sigue guardada.
        */

        try {

            const urlImpresion =
                "./php/imprimirDocumento.php?id=" +
                encodeURIComponent(resultado.datos.id_pedido) +
                "&tipo=cotizacion&auto=1";

            if (ventanaImpresion && !ventanaImpresion.closed) {
                ventanaImpresion.location.replace(urlImpresion);
            }

            let accionesImpresion = document.getElementById(
                "accionesImpresionVenta"
            );

            if (!accionesImpresion) {
                accionesImpresion = document.createElement("div");
                accionesImpresion.id = "accionesImpresionVenta";
                formulario.appendChild(accionesImpresion);
            }

            accionesImpresion.replaceChildren();

            const enlace = document.createElement("a");

            enlace.href = urlImpresion;
            enlace.target = "_blank";
            enlace.rel = "noopener noreferrer";

            enlace.textContent =
                "Imprimir / Reimprimir " +
                resultado.datos.numero_cotizacion;

            accionesImpresion.appendChild(enlace);

        } catch (errorImpresion) {

            console.error(
                "Cotización guardada, pero no se pudo abrir su impresión:",
                errorImpresion
            );

            mostrarRespuestaVenta(
                "Cotización guardada correctamente. " +
                "No se pudo abrir la impresión; puede reimprimirla desde Cotizaciones.",
                true
            );
        }

    } catch (error) {

        console.error(
            "Error guardando cotización:",
            error
        );

        mostrarRespuestaVenta(
            "No se pudo confirmar el guardado. " +
            "Revise si la cotización se registró antes de reintentar.",
            false
        );

    } finally {

        controles.forEach(
            ({ elemento, deshabilitado }) => {
                elemento.disabled = deshabilitado;
            }
        );

        boton.innerHTML = contenidoBoton;

        guardandoCotizacionPresencial = false;

        if (guardada) {

            /*
             * Evita cobrar estos mismos productos como una venta
             * independiente después de guardar la cotización.
             * La conversión de cotización a venta tendrá su propio flujo.
             */

            carritoVentaPresencial = [];
            renderizarCarritoVenta();
        }

        if (!guardada && ventanaImpresion && !ventanaImpresion.closed) {
            ventanaImpresion.close();
        }
    }
}



function configurarBusquedaDocumentoVenta() {

    const seccion = document.getElementById(
        "buscadorDocumentoVenta"
    );

    if (!seccion) {
        return;
    }

    /*
     * Esta comprobación solo controla la presentación.
     * buscarDocumentoVenta.php valida el usuario y su rol
     * directamente en la base de datos.
     */

    const rol = String(
        localStorage.getItem("rol") || ""
    ).trim().toLowerCase();

    if (!["administrador", "cajero"].includes(rol)) {
        seccion.hidden = true;
        return;
    }

    seccion.hidden = false;

    const formulario = seccion.querySelector(
        "#formBuscarDocumentoVenta"
    );

    const entrada = seccion.querySelector(
        "#numeroDocumentoVenta"
    );

    const boton = formulario.querySelector(
        'button[type="submit"]'
    );

    const mensaje = seccion.querySelector(
        "#mensajeBusquedaDocumentoVenta"
    );

    const resultadoVisual = seccion.querySelector(
        "#resultadoDocumentoVenta"
    );

    let buscando = false;

    function agregarTexto(contenedor, etiqueta, valor) {

        const parrafo = document.createElement("p");
        const titulo = document.createElement("strong");

        titulo.textContent = etiqueta + ": ";

        parrafo.append(
            titulo,
            document.createTextNode(String(valor ?? "—"))
        );

        contenedor.appendChild(parrafo);
    }

    function mostrarDocumento(datos) {

        const documento = datos.documento;
        const cliente = datos.cliente;

        resultadoVisual.replaceChildren();

        const titulo = document.createElement("h3");

        titulo.textContent = datos.tipo === "cotizacion"
            ? "Cotización encontrada"
            : (
                datos.tipo === "venta"
                    ? "Venta registrada"
                    : "Pedido encontrado"
            );

        resultadoVisual.appendChild(titulo);

        agregarTexto(
            resultadoVisual,
            "Número de pedido",
            documento.numero_pedido
        );

        if (documento.numero_cotizacion) {
            agregarTexto(
                resultadoVisual,
                "Número de cotización",
                documento.numero_cotizacion
            );
        }

        agregarTexto(
            resultadoVisual,
            "Cliente",
            [cliente.nombre, cliente.apellido]
                .filter(Boolean)
                .join(" ")
        );

        agregarTexto(
            resultadoVisual,
            "Estado del registro",
            documento.estado
        );

        agregarTexto(
            resultadoVisual,
            "Estado del pago",
            documento.estado_pago
        );

        if (datos.tipo === "cotizacion") {

            const nombresVigencia = {
                vigente: "Vigente",
                vencida: "Vencida",
                sin_vencimiento: "Sin vencimiento válido"
            };

            agregarTexto(
                resultadoVisual,
                "Vigencia",
                nombresVigencia[documento.vigencia] ||
                    "No determinada"
            );

            agregarTexto(
                resultadoVisual,
                "Vencimiento",
                documento.fecha_expiracion_cotizacion ||
                    "No informado"
            );

            if (datos.cotizacion_vigente !== true) {

                const aviso = document.createElement("p");

                aviso.className = "advertenciaDocumentoVenta";

                aviso.textContent =
                    datos.mensaje_vigencia ||
                    "Esta cotización no está habilitada para cobrar.";

                resultadoVisual.appendChild(aviso);
            }
        }

        agregarTexto(
            resultadoVisual,
            "Total guardado del documento",
            formatearPrecioVenta(datos.totales_guardados.total)
        );

        const explicacion = document.createElement("p");

        explicacion.textContent = datos.tipo === "venta"
            ? "Los datos actuales siguientes solo sirven para " +
              "preparar una venta similar. No modifican la venta original."
            : "La revisión siguiente muestra precios y existencias " +
              "actuales. No modifica el documento guardado.";

        resultadoVisual.appendChild(explicacion);

        const contenedorTabla = document.createElement("div");

        contenedorTabla.className = "tablaRevisionDocumentoVenta";

        const tabla = document.createElement("table");

        tabla.innerHTML = `
            <caption>Revisión de productos</caption>

            <thead>
                <tr>
                    <th scope="col">Producto</th>
                    <th scope="col">Cantidad</th>
                    <th scope="col">Precio guardado</th>
                    <th scope="col">Precio actual</th>
                    <th scope="col">Stock actual</th>
                    <th scope="col">Revisión</th>
                </tr>
            </thead>

            <tbody></tbody>
        `;

        const cuerpo = tabla.querySelector("tbody");

        datos.productos.forEach(producto => {

            const fila = document.createElement("tr");

            const avisos = Array.isArray(producto.problemas)
                ? [...producto.problemas]
                : [];

            if (producto.cambio_precio) {
                avisos.push("El precio cambió.");
            }

            const valores = [
                producto.nombre_original,
                producto.cantidad,

                formatearPrecioVenta(
                    producto.precio_original
                ),

                producto.precio_actual !== null
                    ? formatearPrecioVenta(
                        producto.precio_actual
                    )
                    : "No disponible",

                producto.stock_actual,

                avisos.length
                    ? avisos.join(" ")
                    : "Sin diferencias detectadas."
            ];

            valores.forEach((valor, indice) => {

                const celda = document.createElement("td");

                if (indice === 0) {

                    const nombre = document.createElement("strong");

                    nombre.textContent =
                        producto.nombre_original || "Producto";

                    const sku = document.createElement("small");
                    sku.className = "skuDebajoProducto";

                    sku.textContent =
                        "SKU: " + (producto.sku_original || "Sin SKU");

                    celda.append(nombre, sku);

                } else {
                    celda.textContent = String(valor ?? "—");
                }

                fila.appendChild(celda);
            });

            cuerpo.appendChild(fila);
        });

        contenedorTabla.appendChild(tabla);
        resultadoVisual.appendChild(contenedorTabla);

        if (datos.revision_actual.completa === true) {

            agregarTexto(
                resultadoVisual,
                "Subtotal de productos a precios actuales, sin despacho",
                formatearPrecioVenta(
                    datos.revision_actual.subtotal_productos
                )
            );

        } else {

            const aviso = document.createElement("p");

            aviso.className = "advertenciaDocumentoVenta";

            aviso.textContent =
                "La revisión tiene incidencias. " +
                "No considere el cálculo parcial como un total a cobrar.";

            resultadoVisual.appendChild(aviso);
        }

        const aclaracion = document.createElement("p");

        aclaracion.textContent =
            "Consulta únicamente: no se cargó el documento al carrito, " +
            "no se registraron pagos y no se modificó el stock.";

        aclaracion.textContent =
            "La búsqueda no modifica el documento original. " +
            "Puede cargarlo al resumen mediante la acción disponible.";

        resultadoVisual.appendChild(aclaracion);

        const esCotizacion = datos.tipo === "cotizacion";
        const esVenta = datos.tipo === "venta";

        const acciones = document.createElement("div");
        acciones.className = "accionesRecuperarDocumento";

        if (
            esVenta ||
            (esCotizacion && datos.cotizacion_vigente === true)
        ) {

            const botonCargar = document.createElement("button");

            botonCargar.type = "button";

            botonCargar.textContent = esCotizacion
                ? "Cargar cotización al resumen"
                : "Crear venta similar";

            botonCargar.addEventListener("click", () => {
                cargarDocumentoEnResumenVenta(datos);
            });

            acciones.appendChild(botonCargar);
        }

        const botonLimpiar = document.createElement("button");

        botonLimpiar.type = "button";
        botonLimpiar.textContent = "Limpiar búsqueda";

        botonLimpiar.addEventListener("click", () => {

            entrada.value = "";
            mensaje.textContent = "";

            resultadoVisual.replaceChildren();
            resultadoVisual.hidden = true;

            entrada.focus();
        });

        
if (datos.documento.numero_cotizacion) {
    const imprimirCot = document.createElement('a');
    imprimirCot.href = './php/imprimirDocumento.php?id=' + encodeURIComponent(datos.documento.id_pedido) + '&tipo=cotizacion&auto=1';
    imprimirCot.target='_blank'; imprimirCot.rel='noopener noreferrer';
    imprimirCot.textContent='Reimprimir cotización'; acciones.appendChild(imprimirCot);
}
if (datos.tipo === 'venta') {
    const imprimirVenta = document.createElement('a');
    imprimirVenta.href = './php/imprimirDocumento.php?id=' + encodeURIComponent(datos.documento.id_pedido) + '&tipo=venta&auto=1';
    imprimirVenta.target='_blank'; imprimirVenta.rel='noopener noreferrer';
    imprimirVenta.textContent='Reimprimir venta'; acciones.appendChild(imprimirVenta);
}
acciones.appendChild(botonLimpiar);
        resultadoVisual.appendChild(acciones);

        resultadoVisual.hidden = false;
    }

    formulario.addEventListener("submit", async event => {

        event.preventDefault();
        event.stopPropagation();

        if (buscando || !formulario.reportValidity()) {
            return;
        }

        const numero = entrada.value.trim();

        if (!numero) {
            mensaje.textContent = "Ingrese el número del documento.";
            return;
        }

        buscando = true;
        boton.disabled = true;
        entrada.disabled = true;

        resultadoVisual.hidden = true;
        resultadoVisual.replaceChildren();

        mensaje.textContent = "Buscando documento...";

        try {

            const response = await fetch(
                "./php/buscarDocumentoVenta.php?numero=" +
                    encodeURIComponent(numero),
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
                    "No fue posible buscar el documento."
                );
            }

            const datos = resultado.datos;

            if (
                !datos?.documento ||
                !datos.cliente ||
                !datos.totales_guardados ||
                !datos.revision_actual ||
                !Array.isArray(datos.productos)
            ) {
                throw new Error(
                    "La respuesta del documento no tiene el formato esperado."
                );
            }

            mostrarDocumento(datos);

            mensaje.textContent = "Documento encontrado.";

        } catch (error) {

            console.error(
                "Error buscando documento de venta:",
                error
            );

            if (seccion.isConnected) {
                resultadoVisual.hidden = true;

                mensaje.textContent =
                    error.message ||
                    "No fue posible buscar el documento.";
            }

        } finally {

            buscando = false;

            if (seccion.isConnected) {
                boton.disabled = false;
                entrada.disabled = false;
            }
        }
    });
}



function cargarDocumentoEnResumenVenta(datos) {
    if (procesandoCobroVenta) return;

    if (guardandoCotizacionPresencial) {
        return;
    }


    const documento = datos.documento;

    if (
        !documento ||
        !datos.cliente ||
        !Array.isArray(datos.productos)
    ) {
        mostrarRespuestaVenta(
            "El documento recibido no es válido.",
            false
        );
        return;
    }

    const esCotizacion = datos.tipo === "cotizacion";
    const esVenta = datos.tipo === "venta";

    if (!esCotizacion && !esVenta) {
        mostrarRespuestaVenta(
            "Este registro no puede utilizarse para esta operación.",
            false
        );
        return;
    }

    if (
        esCotizacion &&
        datos.cotizacion_vigente !== true
    ) {
        mostrarRespuestaVenta(
            "La cotización no está vigente. No puede cargarla para cobrar.",
            false
        );
        return;
    }

    /*
     * No descartamos productos ni reducimos cantidades
     * automáticamente para hacer coincidir el stock.
     */

    if (datos.revision_actual?.completa !== true) {

        mostrarRespuestaVenta(
            "Hay incidencias de precio, productos o existencias. " +
            "Revise la tabla de búsqueda; no se modificó el carrito.",
            false
        );

        return;
    }

    const agrupados = new Map();

    for (const producto of datos.productos) {

        const id = Number(producto.id_producto);
        const cantidad = Number(producto.cantidad);
        const stock = Number(producto.stock_actual);
        const precio = Number(producto.precio_actual);

        if (
            !producto.existe ||
            !Number.isSafeInteger(id) ||
            id <= 0 ||
            !Number.isSafeInteger(cantidad) ||
            cantidad <= 0 ||
            !Number.isSafeInteger(stock) ||
            stock < 0 ||
            !Number.isSafeInteger(precio) ||
            precio <= 0
        ) {
            mostrarRespuestaVenta(
                "Uno de los productos no puede cargarse.",
                false
            );
            return;
        }

        const existente = agrupados.get(id);

        if (existente) {

            if (
                existente.precio_venta !== precio ||
                existente.cantidad !== stock
            ) {
                mostrarRespuestaVenta(
                    "Los datos del producto no son consistentes. " +
                    "Vuelva a buscar el documento.",
                    false
                );
                return;
            }

            existente.cant += cantidad;

        } else {

            agrupados.set(id, {
                id_producto: id,
                sku: producto.sku_actual || "",
                nombre: producto.nombre_actual ||
                    producto.nombre_original,
                marca: producto.marca_actual || "",

                // cantidad es stock; cant es cantidad seleccionada.
                cantidad: stock,
                cant: cantidad,

                precio: precio,
                precio_venta: precio,

                precio_oferta: null,
                inicio_oferta: null,
                fin_oferta: null
            });
        }
    }

    const nuevoCarrito = Array.from(agrupados.values());

    if (
        nuevoCarrito.length === 0 ||
        nuevoCarrito.some(
            producto =>
                producto.cant > producto.cantidad ||
                producto.cant > 1000
        )
    ) {
        mostrarRespuestaVenta(
            "Las cantidades solicitadas no pueden cargarse.",
            false
        );
        return;
    }

    const numero = esCotizacion
        ? documento.numero_cotizacion
        : documento.numero_pedido;

    const advertenciaCarrito =
        carritoVentaPresencial.length > 0
            ? "\n\nSe reemplazarán los productos del carrito actual."
            : "";

    const advertenciaPrecios =
        datos.revision_actual.hay_cambios_precio
            ? "\n\nHay precios distintos de los originales. " +
              "Se cargarán los precios actuales mostrados."
            : "\n\nSe cargarán los precios actuales consultados.";

    const aceptado = window.confirm(
        (esCotizacion
            ? "¿Cargar la cotización "
            : "¿Preparar una venta similar a ") +
        numero +
        "?" +
        advertenciaPrecios +
        advertenciaCarrito
    );

    if (!aceptado) {
        return;
    }

    documentoOrigenVenta = {
        tipo: esCotizacion ? "cotizacion" : "venta_similar",
        id_pedido: Number(documento.id_pedido),
        numero: numero,
        cambios_precio_revisados:
            datos.revision_actual.hay_cambios_precio === true
    };

    /*
     * La aceptación anterior es solo de la vista previa.
     * No confirma un pago ni sustituye la validación del servidor.
     */

    carritoVentaPresencial = nuevoCarrito;

    /*
     * Actualizamos los datos en memoria de estos productos
     * para que agregar otra unidad no use el stock anterior
     * del catálogo.
     */

    nuevoCarrito.forEach(producto => {

        const catalogo = productosVentaPresencial.find(
            item =>
                Number(item.id_producto) === producto.id_producto
        );

        if (catalogo) {
            catalogo.cantidad = producto.cantidad;
            catalogo.precio = producto.precio_venta;
            catalogo.precio_oferta = null;
            catalogo.inicio_oferta = null;
            catalogo.fin_oferta = null;
        }
    });

    cargarClienteEnVenta(datos.cliente);

    const observaciones = document.getElementById(
        "observacionesVenta"
    );

    if (observaciones) {
        observaciones.value = esCotizacion
            ? documento.observaciones || ""
            : "";
    }

    /*
     * Una venta similar no copia el método de pago anterior.
     * Dejamos la selección vacía para que se indique nuevamente.
     */

    document.querySelectorAll(
        'input[name="metodo_pago_venta"]'
    ).forEach(input => {
        input.checked = false;
    });

    renderizarCarritoVenta();

    mostrarRespuestaVenta(
        (esCotizacion
            ? "Cotización "
            : "Nueva venta similar basada en ") +
        numero +
        " cargada al resumen. Puede revisar los productos. " +
        "Al confirmar se volverán a verificar vigencia, precios y stock.",
        true
    );

    document.getElementById(
        "productosCarritoVenta"
    )?.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });
}



function ofrecerImpresionVenta(idPedido, numeroPedido) {

    const id = Number(idPedido);

    if (!Number.isSafeInteger(id) || id <= 0) {
        console.error(
            "La venta fue guardada, pero su ID de impresión no es válido."
        );
        return;
    }

    const formulario = document.getElementById(
        "formVentaPresencial"
    );

    if (!formulario) {
        return;
    }

    const url =
        "./php/imprimirDocumento.php?id=" +
        encodeURIComponent(id) +
        "&tipo=venta&auto=1";

    let contenedor = document.getElementById(
        "accionesImpresionVenta"
    );

    if (!contenedor) {
        contenedor = document.createElement("div");
        contenedor.id = "accionesImpresionVenta";
        formulario.appendChild(contenedor);
    }

    contenedor.replaceChildren();

    const mensaje = document.createElement("p");

    mensaje.textContent =
        "Venta " + numeroPedido +
        " registrada. Si no se abre la impresión, " +
        "utilice el siguiente enlace. No vuelva a confirmar la venta.";

    const enlace = document.createElement("a");

    enlace.href = url;
    enlace.target = "_blank";
    enlace.rel = "noopener noreferrer";

    enlace.textContent =
        "Imprimir / Reimprimir comprobante " + numeroPedido;

    contenedor.append(mensaje, enlace);

    /*
     * El navegador puede bloquear esta apertura por producirse
     * después de una petición. El enlace manual queda disponible.
     */

    try {
        window.open(url, "_blank", "noopener,noreferrer");
    } catch (error) {
        console.warn(
            "No se pudo abrir automáticamente el comprobante:",
            error
        );
    }
}



function mostrarPrecioProductoVenta(producto) {

    const normal = Number(producto.precio) || 0;
    const efectivo = obtenerPrecioVenta(producto);

    if (
        Number(producto.en_oferta) === 1 &&
        efectivo > 0 &&
        efectivo < normal
    ) {

        return `
            <div class="precioProductoOfertaAdmin">

                <small>
                    <del>${formatearPrecioVenta(normal)}</del>
                </small>

                <strong>
                    ${formatearPrecioVenta(efectivo)}
                </strong>

            </div>
        `;
    }

    return `
        <strong class="precioProductoVenta">
            ${formatearPrecioVenta(efectivo)}
        </strong>
    `;
}