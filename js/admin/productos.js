
function registrarProducto() {

    const contenido = document.getElementById("contenido");

    contenido.innerHTML = `
        <div class="smart-forms smart-container wrap-2 formularioGestionProducto">

            <form
                method="POST"
                action="./php/crearProducto.php"
                id="form-producto"
                enctype="multipart/form-data">

                <div class="encabezadoFormularioProducto">

                    <button
                        type="button"
                        id="volverProductos"
                        class="btnVolverProducto"
                        title="Volver">

                        <i class="fa-solid fa-arrow-left"></i>

                    </button>

                    <div>
                        <h2>Crear producto</h2>
                        <p>Registre la información comercial y de inventario.</p>
                    </div>

                </div>

                <div class="contenidoFormularioProducto">

                    <!-- COLUMNA DE IMAGEN -->

                    <div class="columnaImagenProducto">

                        <div class="contenedorPreviewProducto">

                            <img
                                id="previewImagen"
                                src="./images/page-bg.webp"
                                alt="Vista previa del producto">

                        </div>

                        <label
                            for="imagen"
                            class="btnSeleccionarImagen">

                            <i class="fa-solid fa-image"></i>
                            Seleccionar imagen

                        </label>

                        <input
                            type="file"
                            name="imagen"
                            id="imagen"
                            accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.tif,.tiff,.avif,.heic,.heif"
                            required>

                        <small>
                            Formatos permitidos: JPG, PNG, WEBP, GIF y BMP.
                        </small>

                        <div class="opcionesPublicacionProducto">

                            <label class="opcionCheckProducto">

                                <input
                                    type="checkbox"
                                    name="visible_tienda"
                                    id="visibleTiendaProducto"
                                    value="1"
                                    checked>

                                <span>
                                    <strong>Visible en la tienda</strong>
                                    <small>El producto podrá ser comprado en línea.</small>
                                </span>

                            </label>

                            <label class="opcionCheckProducto">

                                <input
                                    type="checkbox"
                                    name="destacado"
                                    id="destacadoProducto"
                                    value="1">

                                <span>
                                    <strong>Producto destacado</strong>
                                    <small>Aparecerá en áreas promocionales.</small>
                                </span>

                            </label>

                        </div>

                    </div>

                    <!-- COLUMNA DEL FORMULARIO -->

                    <div class="camposFormularioProducto">

                        <section class="grupoFormularioProducto">

                            <h3>
                                <i class="fa-solid fa-circle-info"></i>
                                Información básica
                            </h3>

                            <div class="gridCamposProducto">

                                <div class="campoProducto">

                                    <label for="nombreProducto">
                                        Nombre *
                                    </label>

                                    <input
                                        type="text"
                                        name="nombre"
                                        id="nombreProducto"
                                        minlength="3"
                                        maxlength="100"
                                        placeholder="Ej.: Pastillas de freno delanteras"
                                        required>

                                </div>

                                <div class="campoProducto">

                                    <label for="skuProducto">
                                        SKU
                                    </label>

                                    <input
                                        type="text"
                                        name="sku"
                                        id="skuProducto"
                                        maxlength="40"
                                        placeholder="Automático si se deja vacío">

                                </div>

                                <div class="campoProducto">

                                    <label for="categoriaProducto">
                                        Categoría *
                                    </label>

                                    <input
                                        type="text"
                                        name="categoria"
                                        id="categoriaProducto"
                                        minlength="3"
                                        maxlength="50"
                                        placeholder="Ej.: Frenos"
                                        required>

                                </div>

                                <div class="campoProducto">

                                    <label for="marcaProducto">
                                        Marca *
                                    </label>

                                    <input
                                        type="text"
                                        name="marca"
                                        id="marcaProducto"
                                        minlength="2"
                                        maxlength="50"
                                        placeholder="Ej.: Bosch"
                                        required>

                                </div>

                                <div class="campoProducto campoCompleto">

                                    <label for="descripcionProducto">
                                        Descripción *
                                    </label>

                                    <textarea
                                        name="descripcion"
                                        id="descripcionProducto"
                                        minlength="10"
                                        maxlength="1000"
                                        placeholder="Descripción detallada del producto"
                                        required></textarea>

                                </div>

                            </div>

                        </section>

                        <section class="grupoFormularioProducto">

                            <h3>
                                <i class="fa-solid fa-boxes-stacked"></i>
                                Inventario
                            </h3>

                            <div class="gridCamposProducto tresColumnas">

                                <div class="campoProducto">

                                    <label for="cantidadProducto">
                                        Stock actual *
                                    </label>

                                    <input
                                        type="number"
                                        name="cantidad"
                                        id="cantidadProducto"
                                        min="0"
                                        step="1"
                                        value="0"
                                        required>

                                </div>

                                <div class="campoProducto">

                                    <label for="stockMinimoProducto">
                                        Stock mínimo *
                                    </label>

                                    <input
                                        type="number"
                                        name="stock_minimo"
                                        id="stockMinimoProducto"
                                        min="0"
                                        step="1"
                                        value="5"
                                        required>

                                </div>

                                <div class="campoProducto">

                                    <label for="ubicacionProducto">
                                        Ubicación
                                    </label>

                                    <input
                                        type="text"
                                        name="ubicacion"
                                        id="ubicacionProducto"
                                        maxlength="100"
                                        placeholder="Ej.: Estante A-03">

                                </div>

                            </div>

                        </section>

                        <section class="grupoFormularioProducto">

                            <h3>
                                <i class="fa-solid fa-tags"></i>
                                Precios y oferta
                            </h3>

                            <div class="gridCamposProducto tresColumnas">

                                <div class="campoProducto">

                                    <label for="compraProducto">
                                        Precio de compra *
                                    </label>

                                    <input
                                        type="number"
                                        name="compra"
                                        id="compraProducto"
                                        min="1"
                                        step="1"
                                        placeholder="$0"
                                        required>

                                </div>

                                <div class="campoProducto">

                                    <label for="precioProducto">
                                        Precio de venta *
                                    </label>

                                    <input
                                        type="number"
                                        name="precio"
                                        id="precioProducto"
                                        min="1"
                                        step="1"
                                        placeholder="$0"
                                        required>

                                </div>

                                <div class="campoProducto">

                                    <label for="precioOfertaProducto">
                                        Precio de oferta
                                    </label>

                                    <input
                                        type="number"
                                        name="precio_oferta"
                                        id="precioOfertaProducto"
                                        min="1"
                                        step="1"
                                        placeholder="Opcional">

                                </div>

                                <div class="campoProducto">

                                    <label for="inicioOfertaProducto">
                                        Inicio de oferta
                                    </label>

                                    <input
                                        type="datetime-local"
                                        name="inicio_oferta"
                                        id="inicioOfertaProducto">

                                </div>

                                <div class="campoProducto">

                                    <label for="finOfertaProducto">
                                        Fin de oferta
                                    </label>

                                    <input
                                        type="datetime-local"
                                        name="fin_oferta"
                                        id="finOfertaProducto">

                                </div>

                                <div class="campoProducto">

                                    <label>Margen estimado</label>

                                    <div
                                        id="margenEstimadoProducto"
                                        class="margenEstimadoProducto">

                                        0%

                                    </div>

                                </div>

                            </div>

                        </section>

                        <section class="grupoFormularioProducto">

                            <h3>
                                <i class="fa-solid fa-car"></i>
                                Información comercial
                            </h3>

                            <div class="gridCamposProducto">

                                <div class="campoProducto">

                                    <label for="garantiaProducto">
                                        Garantía
                                    </label>

                                    <input
                                        type="text"
                                        name="garantia"
                                        id="garantiaProducto"
                                        maxlength="100"
                                        placeholder="Ej.: 6 meses">

                                </div>

                                <div class="campoProducto">

                                    <label for="compatibilidadProducto">
                                        Compatibilidad
                                    </label>

                                    <textarea
                                        name="compatibilidad"
                                        id="compatibilidadProducto"
                                        maxlength="1000"
                                        placeholder="Ej.: Chevrolet Spark GT 2016–2022"></textarea>

                                </div>

                            </div>

                        </section>

                        <div id="respuestaProducto"></div>

                        <div class="botonesFormularioProducto">

                            <button
                                type="button"
                                class="btnCancelarProducto"
                                onclick="cargar('productos')">

                                Cancelar

                            </button>

                            <button
                                type="reset"
                                class="btnReiniciarProducto">

                                <i class="fa-solid fa-rotate-left"></i>
                                Reiniciar

                            </button>

                            <button
                                type="submit"
                                id="btnGuardarNuevoProducto"
                                class="btnGuardarProducto">

                                <i class="fa-solid fa-floppy-disk"></i>
                                Registrar producto

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>
    `;

    const formulario =
        document.getElementById("form-producto");

    const inputImagen =
        document.getElementById("imagen");

    const preview =
        document.getElementById("previewImagen");

    const precioCompra =
        document.getElementById("compraProducto");

    const precioVenta =
        document.getElementById("precioProducto");

    inputImagen.addEventListener("change", function () {

        const archivo = this.files[0];

        if (!archivo) {
            return;
        }

        const tiposPermitidos = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif",
            "image/bmp"
        ];

        if (!tiposPermitidos.includes(archivo.type)) {

            alert("El formato de la imagen no está permitido.");

            this.value = "";

            preview.src = "./images/page-bg.webp";

            return;
        }

        if (archivo.size > 5 * 1024 * 1024) {

            alert("La imagen no puede superar los 5 MB.");

            this.value = "";

            preview.src = "./images/page-bg.webp";

            return;
        }

        const lector = new FileReader();

        lector.onload = evento => {
            preview.src = evento.target.result;
        };

        lector.readAsDataURL(archivo);
    });

    function actualizarMargenFormulario() {

        const compra = Number(precioCompra.value) || 0;
        const venta = Number(precioVenta.value) || 0;

        const margen =
            venta > 0
                ? ((venta - compra) / venta) * 100
                : 0;

        const elemento =
            document.getElementById("margenEstimadoProducto");

        elemento.textContent = margen.toFixed(1) + "%";

        elemento.classList.toggle("negativo", margen < 0);
    }

    precioCompra.addEventListener(
        "input",
        actualizarMargenFormulario
    );

    precioVenta.addEventListener(
        "input",
        actualizarMargenFormulario
    );

    formulario.addEventListener(
        "submit",
        enviarProducto
    );

    formulario.addEventListener("reset", () => {

        setTimeout(() => {

            preview.src = "./images/page-bg.webp";

            document.getElementById(
                "margenEstimadoProducto"
            ).textContent = "0%";

            document.getElementById(
                "respuestaProducto"
            ).innerHTML = "";

        }, 0);
    });

    document
        .getElementById("volverProductos")
        .addEventListener("click", () => {

            cargar("productos");
        });
}

async function enviarProducto(event) {

    event.preventDefault();

    const formulario =
        document.getElementById("form-producto");

    const boton =
        document.getElementById("btnGuardarNuevoProducto");

    const respuesta =
        document.getElementById("respuestaProducto");

    const precioNormal = Number(
        document.getElementById("precioProducto").value
    );

    const precioOferta = Number(
        document.getElementById("precioOfertaProducto").value
    );

    const inicioOferta =
        document.getElementById("inicioOfertaProducto").value;

    const finOferta =
        document.getElementById("finOfertaProducto").value;

    if (
        precioOferta > 0 &&
        precioOferta >= precioNormal
    ) {

        mostrarRespuestaProducto(
            "El precio de oferta debe ser menor que el precio normal.",
            false
        );

        return;
    }

    if (
        precioOferta > 0 &&
        (!inicioOferta || !finOferta)
    ) {

        mostrarRespuestaProducto(
            "Debe ingresar el inicio y el fin de la oferta.",
            false
        );

        return;
    }

    if (
        inicioOferta &&
        finOferta &&
        new Date(finOferta) <= new Date(inicioOferta)
    ) {

        mostrarRespuestaProducto(
            "La fecha final debe ser posterior al inicio de la oferta.",
            false
        );

        return;
    }

    const datos = new FormData(formulario);

    boton.disabled = true;
    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Registrando...
    `;

    respuesta.innerHTML = "";

    try {

        const response = await fetch(
            "./php/crearProducto.php",
            {
                method: "POST",
                body: datos
            }
        );

        const texto = await response.text();

        console.log("Respuesta modificarProducto.php:", texto);

        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (error) {
            throw new Error(
                "El servidor no devolvió JSON válido. Respuesta: " + texto
            );
        }

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaProducto(
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaProducto(
            resultado.mensaje,
            true
        );

        setTimeout(() => {
            cargar("productos");
        }, 900);

    } catch (error) {

        console.error(error);

        mostrarRespuestaProducto(
            "No fue posible registrar el producto.",
            false
        );

    } finally {

        boton.disabled = false;

        boton.innerHTML = `
            <i class="fa-solid fa-floppy-disk"></i>
            Registrar producto
        `;
    }
}

function mostrarRespuestaProducto(mensaje, correcto) {

    const respuesta =
        document.getElementById("respuestaProducto");

    if (!respuesta) {
        return;
    }

    respuesta.className = correcto
        ? "respuestaProducto correcta"
        : "respuestaProducto error";

    respuesta.textContent = mensaje;
}



async function mostrarInput(){

    const input = document.getElementById("input-porProducto");

    input.value = "";

    const lista = document.getElementById("lista1");
    lista.style.display = "block";

    if(input.style.display === "block"){
        input.value = "";
        lista.innerHTML = "";
        input.style.display="none";
        lista.style.display="none";
        return;
    }

    const response = await fetch("./php/obtenerTableProductos.php");
    const resultado = await response.json();

    const resultadoTable = resultado.datos;

    input.style.display="block";
    lista.style.display="block";

    input.onkeyup = function(){

        const texto = input.value.toLowerCase();

        lista.innerHTML="";

        if(texto.length < 2) return;

        resultadoTable
            .filter(p=>p.nombre.toLowerCase().includes(texto))
            .forEach(producto=>{

                const item=document.createElement("div");

                item.textContent=`${producto.nombre} | ${producto.marca} | ${producto.cantidad}`;

                item.onclick=()=>{

                    cargarTabla([producto]);

                    input.value=producto.nombre;

                    lista.innerHTML="";
                };

                lista.appendChild(item);

            });

    };

}




async function cargarProductosCategoriaAdmin(categoria){

    const response = await fetch("./php/obtenerProductos.php",{

        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:"id="+encodeURIComponent(categoria)

    });

    const resultado = await response.json();

    if(!resultado.ok){
        alert(resultado.mensaje);
        return;
    }

    cargarTabla(resultado.datos);
}



async function modificarProducto(idProducto) {

    try {

        const response = await fetch(
            `./php/obtenerProducto.php?id=${idProducto}`
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        // Ahora datos es un objeto, no un arreglo
        const producto = resultado.datos;

        // Cargar información básica
        document.getElementById("idProducto").value =
            producto.id_producto;

        document.getElementById("nombreProductoModificar").value =
            producto.nombre ?? "";

        document.getElementById("skuProductoModificar").value =
            producto.sku ?? "";

        document.getElementById("categoriaProductoModificar").value =
            producto.categoria ?? "";

        document.getElementById("marcaModificar").value =
            producto.marca ?? "";

        document.getElementById("descripcionProductoModificar").value =
            producto.descripcion ?? "";

        // Inventario
        document.getElementById("cantidadModificar").value =
            producto.cantidad ?? 0;

        document.getElementById("stockMinimoModificar").value =
            producto.stock_minimo ?? 0;

        document.getElementById("ubicacionModificar").value =
            producto.ubicacion ?? "";

        // Precios
        document.getElementById("compraProductoModificar").value =
            producto.compra ?? "";

        document.getElementById("precioProductoModificar").value =
            producto.precio ?? "";

        document.getElementById("precioOfertaModificar").value =
            producto.precio_oferta ?? "";

        // Fechas de oferta
        document.getElementById("inicioOfertaModificar").value =
            convertirFechaParaInput(producto.inicio_oferta);

        document.getElementById("finOfertaModificar").value =
            convertirFechaParaInput(producto.fin_oferta);

        // Información comercial
        document.getElementById("garantiaModificar").value =
            producto.garantia ?? "";

        document.getElementById("compatibilidadModificar").value =
            producto.compatibilidad ?? "";

        // Visibilidad y destacado
        document.getElementById("visibleTiendaModificar").checked =
            Number(producto.visible_tienda) === 1;

        document.getElementById("destacadoModificar").checked =
            Number(producto.destacado) === 1;

        // Imagen
        const preview =
            document.getElementById("previewModificarProducto");

        preview.src =
            `./images/productos/${producto.id_producto}.webp?v=${Date.now()}`;

        preview.onerror = function () {

            this.onerror = null;
            this.src = "./images/page-bg.webp";
        };

        // Limpiar selección anterior de imagen
        document.getElementById(
            "imagenProductoModificar"
        ).value = "";

        document.getElementById(
            "respuestaModificarProducto"
        ).innerHTML = "";

        // Calcular margen
        actualizarMargenModificarProducto();

        // Eventos de precios
        document.getElementById(
            "compraProductoModificar"
        ).oninput = actualizarMargenModificarProducto;

        document.getElementById(
            "precioProductoModificar"
        ).oninput = actualizarMargenModificarProducto;

        // Vista previa al cambiar imagen
        document.getElementById(
            "imagenProductoModificar"
        ).onchange = mostrarPreviewModificarProducto;

        // Enviar formulario
        document.getElementById(
            "formModificarProducto"
        ).onsubmit = function (event) {

            event.preventDefault();

            guardarProducto();
        };

        // Abrir diálogo
        document.getElementById(
            "dialogModificarProducto"
        ).showModal();

    } catch (error) {

        console.error(error);

        alert("Error al cargar el producto: " + error.message);
    }
}

async function guardarProducto() {

    const formulario =
        document.getElementById("formModificarProducto");

    const boton =
        document.getElementById("btnGuardarModificarProducto");

    const precioNormal = Number(
        document.getElementById("precioProductoModificar").value
    );

    const precioOfertaTexto =
        document.getElementById("precioOfertaModificar").value;

    const precioOferta =
        precioOfertaTexto === ""
            ? null
            : Number(precioOfertaTexto);

    const inicioOferta =
        document.getElementById("inicioOfertaModificar").value;

    const finOferta =
        document.getElementById("finOfertaModificar").value;

    if (
        precioOferta !== null &&
        precioOferta >= precioNormal
    ) {

        mostrarRespuestaModificarProducto(
            "El precio de oferta debe ser menor que el precio normal.",
            false
        );

        return;
    }

    if (
        precioOferta !== null &&
        (!inicioOferta || !finOferta)
    ) {

        mostrarRespuestaModificarProducto(
            "Debe completar el inicio y el fin de la oferta.",
            false
        );

        return;
    }

    if (
        inicioOferta &&
        finOferta &&
        new Date(finOferta) <= new Date(inicioOferta)
    ) {

        mostrarRespuestaModificarProducto(
            "El fin de la oferta debe ser posterior al inicio.",
            false
        );

        return;
    }

    const imagen =
        document.getElementById("imagenProductoModificar").files[0];

    if (imagen && imagen.size > 5 * 1024 * 1024) {

        mostrarRespuestaModificarProducto(
            "La imagen no puede superar los 5 MB.",
            false
        );

        return;
    }

    const formData = new FormData(formulario);

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Guardando...
    `;

    try {

        const response = await fetch(
            "./php/modificarProducto.php",
            {
                method: "POST",
                body: formData
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaModificarProducto(
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaModificarProducto(
            resultado.mensaje,
            true
        );

        await cargarProductos();
        await cargarResumenProductos();

        setTimeout(() => {

            cerrarDialogModificarProducto();

        }, 900);

    } catch (error) {

        console.error(error);

        mostrarRespuestaModificarProducto(
            "No fue posible actualizar el producto.",
            false
        );

    } finally {

        boton.disabled = false;

        boton.innerHTML = `
            <i class="fa-solid fa-floppy-disk"></i>
            Guardar cambios
        `;
    }
}

function mostrarRespuestaModificarProducto(
    mensaje,
    correcto
) {

    const respuesta =
        document.getElementById("respuestaModificarProducto");

    respuesta.className = correcto
        ? "respuestaProducto correcta"
        : "respuestaProducto error";

    respuesta.textContent = mensaje;
}

async function eliminarProducto(idProducto, nombreProducto) {

    const respuesta = await confirmar("Eliminar producto","¿Desea eliminar "+nombreProducto+" ?");

    if (!respuesta) {
        return;
    }


    try {

        const response = await fetch(`./php/eliminarProducto.php?id=${idProducto}`);
        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {
            alert(resultado.mensaje);
            return;
        }
        else{
            alert(resultado.mensaje);
            
            const tbody = document.getElementById("tbody_producto");
            tbody.innerHTML = "";

            cargarProductos();
        }

    } catch (error) {

        alert("Error: " + error);

    }

}

document.getElementById("imagenProductoModificar").addEventListener("change", function () {

    if (this.files.length === 0) return;

    const lector = new FileReader();

    lector.onload = function (e) {

        document.getElementById("previewModificarProducto").src = e.target.result;

    };

    lector.readAsDataURL(this.files[0]);

});


/*=============================================
CARGAR RESUMEN DE PRODUCTOS
=============================================*/

async function cargarResumenProductos() {

    try {

        const response = await fetch(
            "./php/obtenerResumenProductos.php"
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            console.error(resultado.mensaje);

            return;
        }

        const datos = resultado.datos;

        asignarResumenProducto(
            "totalProductosAdmin",
            datos.totalProductos
        );

        asignarResumenProducto(
            "unidadesProductosAdmin",
            datos.unidadesDisponibles
        );

        asignarResumenProducto(
            "bajoStockProductosAdmin",
            datos.productosBajoStock
        );

        asignarResumenProducto(
            "agotadosProductosAdmin",
            datos.productosAgotados
        );

        asignarResumenProducto(
            "productosOfertaAdmin",
            Number(datos.productosEnOferta) || 0
        );

    } catch (error) {

        console.error(
            "Error cargando resumen de productos:",
            error
        );
    }
}


function asignarResumenProducto(id, valor) {

    const elemento =
        document.getElementById(id);

    if (elemento) {
        elemento.textContent = valor;
    }
}


function formatearPrecioProducto(valor) {

    return new Intl.NumberFormat(
        "es-CL",
        {
            style: "currency",
            currency: "CLP",
            minimumFractionDigits: 0
        }
    ).format(Number(valor) || 0);
}

/* =====================================================
   GESTIÓN ADMINISTRATIVA DE PRODUCTOS
===================================================== */

let productosAdmin = [];
let estadoProductoSeleccionado = "Todos";

/* =====================================================
   INICIALIZACIÓN
===================================================== */

function inicializarGestionProductos() {

    cargarResumenProductos();
    cargarProductos();

    document.querySelectorAll(".btnPestanaProducto").forEach(boton => {

            boton.addEventListener("click", function () {

                document
                    .querySelectorAll(".btnPestanaProducto")
                    .forEach(item => item.classList.remove("activa"));

                this.classList.add("activa");

                estadoProductoSeleccionado = this.dataset.estado;

                document.querySelectorAll(".tarjetaResumenProducto").forEach(tarjeta => {

        tarjeta.classList.toggle("seleccionada", tarjeta.dataset.filtro === estadoProductoSeleccionado);
    });

                aplicarFiltrosProductos();
            });
        });

    const buscador = document.getElementById("buscarProductoAdmin");

    if (buscador) {
        buscador.addEventListener("input", aplicarFiltrosProductos);
    }

    const filtroCategoria =
        document.getElementById("filtroCategoriaProducto");

    if (filtroCategoria) {
        filtroCategoria.addEventListener(
            "change",
            aplicarFiltrosProductos
        );
    }

    const filtroMarca =
        document.getElementById("filtroMarcaProducto");

    if (filtroMarca) {
        filtroMarca.addEventListener(
            "change",
            aplicarFiltrosProductos
        );
    }

    const orden =
        document.getElementById("ordenProductoAdmin");

    if (orden) {
        orden.addEventListener(
            "change",
            aplicarFiltrosProductos
        );
    }

    const btnLimpiar =
        document.getElementById("btnLimpiarFiltrosProducto");

    if (btnLimpiar) {
        btnLimpiar.addEventListener(
            "click",
            limpiarFiltrosProductos
        );
    }

    document.querySelectorAll(".tarjetaResumenProducto[data-filtro]").forEach(tarjeta => {

        tarjeta.addEventListener("click", function () {

            const filtro = this.dataset.filtro;

            estadoProductoSeleccionado = filtro;

            // Marcar tarjeta seleccionada
            document
                .querySelectorAll(".tarjetaResumenProducto")
                .forEach(item => {
                    item.classList.remove("seleccionada");
                });

            this.classList.add("seleccionada");

            // Relacionar con las pestañas
            document
                .querySelectorAll(".btnPestanaProducto")
                .forEach(boton => {

                    boton.classList.toggle(
                        "activa",
                        boton.dataset.estado === filtro
                    );
                });

            aplicarFiltrosProductos();
        });
    });
}

/* =====================================================
   CARGAR PRODUCTOS
===================================================== */

async function cargarProductos() {

    try {

        const response =
            await fetch(
                `./php/obtenerProductosAdmin.php?v=${Date.now()}`,
                {
                    cache: "no-store"
                }
            );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        productosAdmin = resultado.datos;

        cargarOpcionesFiltros(productosAdmin);

        aplicarFiltrosProductos();

    } catch (error) {

        console.error(error);

        alert("No fue posible cargar los productos.");
    }
}

/* =====================================================
   CARGAR CATEGORÍAS Y MARCAS EN LOS FILTROS
===================================================== */

function cargarOpcionesFiltros(productos) {

    const selectCategoria =
        document.getElementById("filtroCategoriaProducto");

    const selectMarca =
        document.getElementById("filtroMarcaProducto");

    if (!selectCategoria || !selectMarca) {
        return;
    }

    const categoriaSeleccionada = selectCategoria.value;
    const marcaSeleccionada = selectMarca.value;

    const categorias = [
        ...new Set(
            productos
                .map(producto => producto.categoria)
                .filter(valor => valor)
        )
    ].sort((a, b) => a.localeCompare(b));

    const marcas = [
        ...new Set(
            productos
                .map(producto => producto.marca)
                .filter(valor => valor)
        )
    ].sort((a, b) => a.localeCompare(b));

    selectCategoria.innerHTML =
        `<option value="">Todas las categorías</option>`;

    categorias.forEach(categoria => {

        selectCategoria.innerHTML += `
            <option value="${escaparHTML(categoria)}">
                ${escaparHTML(categoria)}
            </option>
        `;
    });

    selectMarca.innerHTML =
        `<option value="">Todas las marcas</option>`;

    marcas.forEach(marca => {

        selectMarca.innerHTML += `
            <option value="${escaparHTML(marca)}">
                ${escaparHTML(marca)}
            </option>
        `;
    });

    selectCategoria.value = categoriaSeleccionada;
    selectMarca.value = marcaSeleccionada;
}

/* =====================================================
   APLICAR BUSCADOR, FILTROS Y ORDENAMIENTO
===================================================== */

function aplicarFiltrosProductos() {

    const buscador =
        document.getElementById("buscarProductoAdmin");

    const selectCategoria =
        document.getElementById("filtroCategoriaProducto");

    const selectMarca =
        document.getElementById("filtroMarcaProducto");

    const selectOrden =
        document.getElementById("ordenProductoAdmin");

    const texto = buscador
        ? buscador.value.trim().toLowerCase()
        : "";

    const categoria = selectCategoria
        ? selectCategoria.value
        : "";

    const marca = selectMarca
        ? selectMarca.value
        : "";

    const orden = selectOrden
        ? selectOrden.value
        : "nombre_asc";

    let productosFiltrados = productosAdmin.filter(producto => {

        const coincideTexto =
            !texto ||
            String(producto.nombre || "")
                .toLowerCase()
                .includes(texto) ||
            String(producto.sku || "")
                .toLowerCase()
                .includes(texto) ||
            String(producto.marca || "")
                .toLowerCase()
                .includes(texto) ||
            String(producto.categoria || "")
                .toLowerCase()
                .includes(texto);

        const coincideCategoria =
            !categoria ||
            producto.categoria === categoria;

        const coincideMarca =
            !marca ||
            producto.marca === marca;

        const coincideEstado =
            estadoProductoSeleccionado === "Todos" ||
            producto.estado === estadoProductoSeleccionado;

        return (
            coincideTexto &&
            coincideCategoria &&
            coincideMarca &&
            coincideEstado
        );
    });

    productosFiltrados.sort((a, b) => {

        switch (orden) {

            case "nombre_desc":
                return String(b.nombre).localeCompare(
                    String(a.nombre)
                );

            case "precio_mayor":
                return Number(b.precio) - Number(a.precio);

            case "precio_menor":
                return Number(a.precio) - Number(b.precio);

            case "stock_mayor":
                return Number(b.cantidad) - Number(a.cantidad);

            case "stock_menor":
                return Number(a.cantidad) - Number(b.cantidad);

            case "actualizado":
                return new Date(b.fecha_actualizacion) -
                       new Date(a.fecha_actualizacion);

            default:
                return String(a.nombre).localeCompare(
                    String(b.nombre)
                );
        }
    });

    cargarTabla(productosFiltrados);
}

/* =====================================================
   MOSTRAR PRODUCTOS EN LA TABLA
===================================================== */

function cargarTabla(productos) {

    const tbody = document.getElementById("tbody_producto");
    const tabla = document.getElementById("table_producto");
    const sinProductos = document.getElementById("sinProductosAdmin");

    const cantidadResultados = document.getElementById("cantidadResultadosProductos");

    const textoResultados = document.getElementById("textoResultadosProductos");


    if (cantidadResultados) {
        cantidadResultados.textContent = productos.length;
    }

    if (textoResultados) {
        textoResultados.textContent =
            productos.length === 1
                ? "resultado"
                : "resultados";
    }

    if (!tbody) {
        return;
    }

    tbody.innerHTML = "";

    if (productos.length === 0) {

        if (tabla) {
            tabla.style.display = "none";
        }

        if (sinProductos) {
            sinProductos.style.display = "block";
        }

        return;
    }

    if (tabla) {
        tabla.style.display = "table";
    }

    if (sinProductos) {
        sinProductos.style.display = "none";
    }

    productos.forEach(producto => {

        const compra = Number(producto.compra) || 0;

        const venta = ofertaProductoVigente(producto)
            ? Number(producto.precio_oferta)
            : Number(producto.precio) || 0;

        const cantidad = Number(producto.cantidad) || 0;

        const margen = calcularMargenProducto(compra, venta);

        const claseMargen =
            margen < 0
                ? "margenProductoAdmin negativo"
                : "margenProductoAdmin";

        const claseStock =
            cantidad === 0
                ? "stockProductoAdmin agotado"
                : producto.estado === "Bajo stock"
                    ? "stockProductoAdmin bajo"
                    : "stockProductoAdmin";

        const productoVisible = Number(producto.visible_tienda) === 1;

        const claseVisibilidad = productoVisible ? "btnOcultarProducto" : "btnMostrarProducto";

        const iconoVisibilidad = productoVisible ? "fa-eye-slash" : "fa-eye";

        const tituloVisibilidad =  productoVisible ? "Ocultar de la tienda" : "Mostrar en la tienda";

        const nuevaVisibilidad = productoVisible ? 0 : 1;

        const claseEstado = obtenerClaseEstadoProducto(producto.estado);

        const imagen =
            `images/productos/${producto.id_producto}.webp?v=${Date.now()}`;


        tbody.innerHTML += `
            <tr>

                <td>
                    <img
                        src="${imagen}"
                        alt="${escaparHTML(producto.nombre)}"
                        class="imagenProductoAdmin"
                       onerror="this.src='images/logoAlianzaPro.webp'">
                </td>

                <td>
                    <div class="infoProductoAdmin">

                        <span class="nombreProductoAdmin">
                            ${escaparHTML(producto.nombre)}
                        </span>

                        <span class="skuProductoAdmin">
                            SKU:
                            ${escaparHTML(producto.sku || "Sin SKU")}
                        </span>

                    </div>
                </td>

                <td>
                    ${escaparHTML(producto.categoria || "Sin categoría")}
                </td>

                <td>
                    ${escaparHTML(producto.marca || "Sin marca")}
                </td>

                <td>
                    <span class="${claseStock}">
                        ${cantidad}
                    </span>
                </td>

                <td>
                    ${formatearPrecioProducto(compra)}
                </td>

                <td>
                    ${mostrarPrecioVentaProducto(producto)}
                </td>

                <td>
                    <span class="${claseMargen}">
                        ${margen.toFixed(1)}%
                    </span>
                </td>

                <td>
                    <span class="estadoProductoAdmin ${claseEstado}">
                        ${escaparHTML(producto.estado)}
                    </span>
                </td>

                <td>
                    <div class="accionesProductoAdmin">

                        <button
                            type="button"
                            class="btnAccionProducto btnVisibilidadProducto ${claseVisibilidad}"
                            title="${tituloVisibilidad}"
                            onclick="cambiarVisibilidadProducto(
                                ${producto.id_producto},
                                ${nuevaVisibilidad},
                                '${escaparAtributoJS(producto.nombre)}'
                            )">

                            <i class="fa-solid ${iconoVisibilidad}"></i>

                        </button>

                        <button
                            type="button"
                            class="btnAccionProducto btnModificarProducto"
                            title="Modificar producto"
                            onclick="modificarProducto(${producto.id_producto})">

                            <i class="fa-solid fa-pen-to-square"></i>

                        </button>

                        <button
                            type="button"
                            class="btnAccionProducto btnEliminarProducto"
                            title="Eliminar producto"
                            onclick="eliminarProducto(
                                ${producto.id_producto},
                                '${escaparAtributoJS(producto.nombre)}'
                            )">

                            <i class="fa-solid fa-trash"></i>

                        </button>

                    </div>
                </td>

            </tr>
        `;
    });

    document.querySelectorAll(".btnEliminarProducto").forEach(boton => {

            acceso(localStorage.getItem("rol"), boton);
        });

    document.querySelectorAll(".btnModificarProducto").forEach(boton => {

            acceso(localStorage.getItem("rol"), boton);
        });
    
    document.querySelectorAll(".btnVisibilidadProducto").forEach(boton => {

        acceso(localStorage.getItem("rol"), boton);
    });
}

/* =====================================================
   PRECIO NORMAL Y PRECIO EN OFERTA
===================================================== */

function mostrarPrecioVentaProducto(producto) {

    const precioNormal = Number(producto.precio) || 0;
    const precioOferta = Number(producto.precio_oferta) || 0;

    if (
        precioOferta > 0 &&
        ofertaProductoVigente(producto)
    ) {

        return `
            <div class="precioProductoOfertaAdmin">

                <small>
                    ${formatearPrecioProducto(precioNormal)}
                </small>

                <strong>
                    ${formatearPrecioProducto(precioOferta)}
                </strong>

            </div>
        `;
    }

    return formatearPrecioProducto(precioNormal);
}

function ofertaProductoVigente(producto) {

    const normal = Number(producto.precio);
    const oferta = Number(producto.precio_oferta);

    return (
        Number(producto.en_oferta) === 1 &&
        Number.isFinite(normal) &&
        Number.isFinite(oferta) &&
        oferta > 0 &&
        oferta < normal
    );
}

function convertirFechaMySQL(fecha) {

    return new Date(
        String(fecha).replace(" ", "T")
    );
}

/* =====================================================
   FUNCIONES AUXILIARES
===================================================== */

function calcularMargenProducto(compra, venta) {

    if (venta <= 0) {
        return 0;
    }

    return ((venta - compra) / venta) * 100;
}

function obtenerClaseEstadoProducto(estado) {

    switch (estado) {

        case "Disponible":
            return "disponible";

        case "Bajo stock":
            return "bajo-stock";

        case "Agotado":
            return "agotado";

        case "Oculto":
            return "oculto";

        default:
            return "";
    }
}

function formatearPrecioProducto(valor) {

    return new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        minimumFractionDigits: 0
    }).format(Number(valor) || 0);
}

function escaparHTML(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function escaparAtributoJS(valor) {

    return String(valor ?? "")
        .replaceAll("\\", "\\\\")
        .replaceAll("'", "\\'")
        .replaceAll("\n", " ")
        .replaceAll("\r", " ");
}

/* =====================================================
   LIMPIAR FILTROS
===================================================== */

function limpiarFiltrosProductos() {

    const buscador =
        document.getElementById("buscarProductoAdmin");

    const categoria =
        document.getElementById("filtroCategoriaProducto");

    const marca =
        document.getElementById("filtroMarcaProducto");

    const orden =
        document.getElementById("ordenProductoAdmin");

    if (buscador) {
        buscador.value = "";
    }

    if (categoria) {
        categoria.value = "";
    }

    if (marca) {
        marca.value = "";
    }

    if (orden) {
        orden.value = "nombre_asc";
    }

    estadoProductoSeleccionado = "Todos";

    document
        .querySelectorAll(".btnPestanaProducto")
        .forEach(boton => {

            boton.classList.toggle(
                "activa",
                boton.dataset.estado === "Todos"
            );
        });

    aplicarFiltrosProductos();
}



/* =====================================================
   CONVERTIR DATETIME MYSQL PARA DATETIME-LOCAL
===================================================== */

function convertirFechaParaInput(fecha) {

    if (!fecha) {
        return "";
    }

    // De: 2026-08-21 15:30:00
    // A:  2026-08-21T15:30

    return String(fecha)
        .replace(" ", "T")
        .substring(0, 16);
}

/* =====================================================
   ACTUALIZAR MARGEN
===================================================== */

function actualizarMargenModificarProducto() {

    const compra = Number(
        document.getElementById(
            "compraProductoModificar"
        ).value
    ) || 0;

    const venta = Number(
        document.getElementById(
            "precioProductoModificar"
        ).value
    ) || 0;

    const margen =
        venta > 0
            ? ((venta - compra) / venta) * 100
            : 0;

    const elemento =
        document.getElementById(
            "margenEstimadoModificar"
        );

    elemento.textContent =
        margen.toFixed(1) + "%";

    elemento.classList.toggle(
        "negativo",
        margen < 0
    );
}

/* =====================================================
   VISTA PREVIA DE LA NUEVA IMAGEN
===================================================== */

function mostrarPreviewModificarProducto() {

    const archivo = this.files[0];

    if (!archivo) {
        return;
    }

    const permitidos = [
        "image/jpeg",
        "image/png",
        "image/webp",
        "image/gif",
        "image/bmp"
    ];

    if (!permitidos.includes(archivo.type)) {

        alert("El formato de imagen no está permitido.");

        this.value = "";

        return;
    }

    if (archivo.size > 5 * 1024 * 1024) {

        alert("La imagen no puede superar los 5 MB.");

        this.value = "";

        return;
    }

    const lector = new FileReader();

    lector.onload = function (evento) {

        document.getElementById(
            "previewModificarProducto"
        ).src = evento.target.result;
    };

    lector.readAsDataURL(archivo);
}

/* =====================================================
   CERRAR DIÁLOGO
===================================================== */

function cerrarDialogModificarProducto() {

    const dialogo =
        document.getElementById("dialogModificarProducto");

    dialogo.close();

    document.getElementById(
        "formModificarProducto"
    ).reset();

    document.getElementById(
        "imagenProductoModificar"
    ).value = "";

    document.getElementById(
        "previewModificarProducto"
    ).src = "./images/page-bg.webp";

    document.getElementById(
        "respuestaModificarProducto"
    ).innerHTML = "";
}


async function cambiarVisibilidadProducto(idProducto, nuevaVisibilidad, nombreProducto) {

    const accion = Number(nuevaVisibilidad) === 1 ? "mostrar" : "ocultar";

    const respuesta = await confirmar(
        "Visibilidad del producto",
        `¿Desea ${accion} "${nombreProducto}" en la tienda?`
    );

    if (!respuesta) {
        return;
    }

    const datos = new FormData();

    datos.append("idProducto", idProducto);
    datos.append("visible_tienda", nuevaVisibilidad);

    try {

        const response = await fetch(
            "./php/cambiarVisibilidadProducto.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mensajeModal(
                "Error",
                resultado.mensaje
            );

            return;
        }

        mensajeModal(
            "Visibilidad actualizada",
            resultado.mensaje
        );

        await cargarProductos();
        await cargarResumenProductos();

    } catch (error) {

        console.error(error);

        mensajeModal(
            "Error",
            "No fue posible cambiar la visibilidad del producto."
        );
    }
}