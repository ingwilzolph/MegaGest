/* =====================================================
   ESTADO
===================================================== */

let categoriasAdmin = [];
let filtroEstadoCategoria = "Todas";

/* =====================================================
   INICIALIZACIÓN
===================================================== */

function inicializarGestionCategorias() {

    cargarResumenCategorias();
    cargarCategoriasAdmin();

    document
        .querySelectorAll(".btnPestanaCategoria")
        .forEach(boton => {

            boton.addEventListener("click", function () {

                filtroEstadoCategoria =
                    this.dataset.estado;

                document
                    .querySelectorAll(".btnPestanaCategoria")
                    .forEach(item => {

                        item.classList.toggle(
                            "activa",
                            item === this
                        );
                    });

                relacionarTarjetasCategorias();
                actualizarEncabezadoCategorias();
                aplicarFiltrosCategorias();
            });
        });

    document
        .querySelectorAll(
            ".tarjetaResumenCategoria[data-filtro]"
        )
        .forEach(tarjeta => {

            tarjeta.addEventListener("click", function () {

                filtroEstadoCategoria =
                    this.dataset.filtro;

                document
                    .querySelectorAll(".btnPestanaCategoria")
                    .forEach(boton => {

                        boton.classList.toggle(
                            "activa",
                            boton.dataset.estado ===
                            filtroEstadoCategoria
                        );
                    });

                relacionarTarjetasCategorias();
                actualizarEncabezadoCategorias();
                aplicarFiltrosCategorias();
            });
        });

    document
        .getElementById("buscarCategoriaAdmin")
        ?.addEventListener(
            "input",
            aplicarFiltrosCategorias
        );

    document
        .getElementById("ordenCategoriaAdmin")
        ?.addEventListener(
            "change",
            aplicarFiltrosCategorias
        );

    document
        .getElementById("btnLimpiarFiltrosCategoria")
        ?.addEventListener(
            "click",
            limpiarFiltrosCategorias
        );
}

/* =====================================================
   RESUMEN
===================================================== */

async function cargarResumenCategorias() {

    try {

        const response = await fetch(
            `./php/obtenerResumenCategorias.php?v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (comprobarSesionCategoria(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {
            throw new Error(resultado.mensaje);
        }

        const datos = resultado.datos;

        asignarResumenCategoria(
            "totalCategoriasAdmin",
            datos.totalCategorias
        );

        asignarResumenCategoria(
            "categoriasVisiblesAdmin",
            datos.categoriasVisibles
        );

        asignarResumenCategoria(
            "categoriasOcultasAdmin",
            datos.categoriasOcultas
        );

        asignarResumenCategoria(
            "categoriasVaciasAdmin",
            datos.categoriasVacias
        );

        asignarResumenCategoria(
            "productosClasificadosAdmin",
            datos.productosClasificados
        );

    } catch (error) {

        console.error(
            "Error cargando resumen de categorías:",
            error
        );
    }
}

function asignarResumenCategoria(id, valor) {

    const elemento = document.getElementById(id);

    if (elemento) {
        elemento.textContent = valor;
    }
}

/* =====================================================
   CARGAR CATEGORÍAS
===================================================== */

async function cargarCategoriasAdmin() {

    try {

        const response = await fetch(
            `./php/obtenerCategoriasAdmin.php?v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (comprobarSesionCategoria(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {
            throw new Error(resultado.mensaje);
        }

        categoriasAdmin = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        aplicarFiltrosCategorias();

    } catch (error) {

        console.error(
            "Error cargando categorías:",
            error
        );

        alert(
            "No fue posible cargar las categorías."
        );
    }
}

/* =====================================================
   FILTROS
===================================================== */

function aplicarFiltrosCategorias() {

    const buscador =
        document.getElementById("buscarCategoriaAdmin");

    const orden =
        document.getElementById("ordenCategoriaAdmin");

    const texto = buscador
        ? buscador.value.trim().toLowerCase()
        : "";

    const ordenSeleccionado =
        orden ? orden.value : "orden";

    let categoriasFiltradas =
        categoriasAdmin.filter(categoria => {

            const coincideTexto =
                !texto ||
                String(categoria.categoria || "")
                    .toLowerCase()
                    .includes(texto) ||
                String(categoria.descripcion || "")
                    .toLowerCase()
                    .includes(texto) ||
                String(categoria.slug || "")
                    .toLowerCase()
                    .includes(texto);

            let coincideEstado = true;

            switch (filtroEstadoCategoria) {

                case "Visible":

                    coincideEstado =
                        Number(categoria.visible_tienda) === 1;

                break;

                case "Oculta":

                    coincideEstado =
                        Number(categoria.visible_tienda) === 0;

                break;

                case "Vacía":

                    coincideEstado =
                        Number(categoria.total_productos) === 0;

                break;
            }

            return coincideTexto && coincideEstado;
        });

    categoriasFiltradas.sort((a, b) => {

        switch (ordenSeleccionado) {

            case "nombre_asc":

                return String(a.categoria).localeCompare(
                    String(b.categoria)
                );

            case "nombre_desc":

                return String(b.categoria).localeCompare(
                    String(a.categoria)
                );

            case "productos_mayor":

                return Number(b.total_productos) -
                       Number(a.total_productos);

            case "productos_menor":

                return Number(a.total_productos) -
                       Number(b.total_productos);

            case "actualizado":

                return new Date(b.fecha_actualizacion) -
                       new Date(a.fecha_actualizacion);

            default:

                return (
                    Number(a.orden) - Number(b.orden) ||
                    String(a.categoria).localeCompare(
                        String(b.categoria)
                    )
                );
        }
    });

    cargarTablaCategoriasAdmin(
        categoriasFiltradas
    );
}

/* =====================================================
   TABLA
===================================================== */

function cargarTablaCategoriasAdmin(categorias) {

    const tbody =
        document.getElementById("tbody_categoria");

    if (!tbody) {
        return;
    }

    const tabla =
        document.getElementById("table_categoria");

    const sinResultados =
        document.getElementById("sinCategoriasAdmin");

    const contador =
        document.getElementById(
            "cantidadResultadosCategorias"
        );

    const textoContador =
        document.getElementById(
            "textoResultadosCategorias"
        );

    if (contador) {
        contador.textContent = categorias.length;
    }

    if (textoContador) {

        textoContador.textContent =
            categorias.length === 1
                ? "resultado"
                : "resultados";
    }

    tbody.innerHTML = "";

    if (categorias.length === 0) {

        tabla.style.display = "none";
        sinResultados.style.display = "block";

        return;
    }

    tabla.style.display = "table";
    sinResultados.style.display = "none";

    categorias.forEach(categoria => {

        const visible =
            Number(categoria.visible_tienda) === 1;

        const tieneProductos =
            Number(categoria.total_productos) > 0;

        tbody.innerHTML += `

            <tr>

                <td>
                    ${categoria.id_categoria}
                </td>

                <td>

                    <div class="infoCategoriaAdmin">

                        <strong>
                            ${escaparHTMLCategoria(
                                categoria.categoria
                            )}
                        </strong>

                        <small>
                            ${Number(categoria.unidades_productos)}
                            unidades en inventario
                        </small>

                    </div>

                </td>

                <td class="descripcionCategoriaAdmin">

                    ${escaparHTMLCategoria(
                        categoria.descripcion ||
                        "Sin descripción"
                    )}

                </td>

                <td>

                    <code class="slugCategoriaAdmin">

                        ${escaparHTMLCategoria(
                            categoria.slug || ""
                        )}

                    </code>

                </td>

                <td>

                    <span class="
                        cantidadProductosCategoria
                        ${tieneProductos ? "conProductos" : "vacia"}
                    ">

                        ${categoria.total_productos}

                    </span>

                </td>

                <td>

                    <span class="ordenCategoriaAdmin">
                        ${categoria.orden}
                    </span>

                </td>

                <td>

                    <span class="
                        visibilidadCategoriaAdmin
                        ${visible ? "visible" : "oculta"}
                    ">

                        <i class="fa-solid
                            ${visible
                                ? "fa-eye"
                                : "fa-eye-slash"}">
                        </i>

                        ${visible ? "Visible" : "Oculta"}

                    </span>

                </td>

                <td>

                    ${formatearFechaCategoria(
                        categoria.fecha_actualizacion
                    )}

                </td>

                <td>

                    <div class="accionesCategoriaAdmin">

                        <button
                            type="button"
                            class="
                                btnAccionCategoria
                                btnVisibilidadCategoria
                                ${visible
                                    ? "btnOcultarCategoria"
                                    : "btnMostrarCategoria"}
                            "
                            title="${visible
                                ? "Ocultar categoría"
                                : "Mostrar categoría"}"
                            onclick="cambiarVisibilidadCategoria(
                                ${categoria.id_categoria},
                                ${visible ? 0 : 1}
                            )">

                            <i class="fa-solid
                                ${visible
                                    ? "fa-eye-slash"
                                    : "fa-eye"}">
                            </i>

                        </button>

                        <button
                            type="button"
                            class="
                                btnAccionCategoria
                                btnModificarCategoriaAdmin
                            "
                            title="Modificar categoría"
                            onclick="modificarCategoria(
                                ${categoria.id_categoria}
                            )">

                            <i class="fa-solid fa-pen-to-square"></i>

                        </button>

                        <button
                            type="button"
                            class="
                                btnAccionCategoria
                                btnEliminarCategoriaAdmin
                            "
                            title="Eliminar categoría"
                            onclick="eliminarCategoria(
                                ${categoria.id_categoria}
                            )">

                            <i class="fa-solid fa-trash"></i>

                        </button>

                    </div>

                </td>

            </tr>
        `;
    });

    document
        .querySelectorAll(`
            .btnVisibilidadCategoria,
            .btnModificarCategoriaAdmin,
            .btnEliminarCategoriaAdmin
        `)
        .forEach(boton => {

            acceso(
                localStorage.getItem("rol"),
                boton
            );
        });
}

/* =====================================================
   ENCABEZADO
===================================================== */

function actualizarEncabezadoCategorias() {

    const titulo = document.getElementById(
        "tituloResultadosCategorias"
    );

    const descripcion = document.getElementById(
        "descripcionResultadosCategorias"
    );

    if (!titulo || !descripcion) {
        return;
    }

    const textos = {

        "Todas": [
            "Todas las categorías",
            "Catálogo completo de categorías registradas."
        ],

        "Visible": [
            "Categorías visibles",
            "Categorías disponibles en la tienda."
        ],

        "Oculta": [
            "Categorías ocultas",
            "Categorías que no aparecen en la tienda."
        ],

        "Vacía": [
            "Categorías sin productos",
            "Categorías que todavía no tienen productos."
        ]
    };

    const informacion =
        textos[filtroEstadoCategoria] ||
        textos.Todas;

    titulo.textContent = informacion[0];
    descripcion.textContent = informacion[1];
}

function relacionarTarjetasCategorias() {

    document
        .querySelectorAll(".tarjetaResumenCategoria")
        .forEach(tarjeta => {

            tarjeta.classList.toggle(
                "seleccionada",
                tarjeta.dataset.filtro ===
                filtroEstadoCategoria
            );
        });
}

function limpiarFiltrosCategorias() {

    const buscador =
        document.getElementById("buscarCategoriaAdmin");

    const orden =
        document.getElementById("ordenCategoriaAdmin");

    if (buscador) {
        buscador.value = "";
    }

    if (orden) {
        orden.value = "orden";
    }

    filtroEstadoCategoria = "Todas";

    document
        .querySelectorAll(".btnPestanaCategoria")
        .forEach(boton => {

            boton.classList.toggle(
                "activa",
                boton.dataset.estado === "Todas"
            );
        });

    relacionarTarjetasCategorias();
    actualizarEncabezadoCategorias();
    aplicarFiltrosCategorias();
}

/* =====================================================
   CREAR CATEGORÍA
===================================================== */

function registrarCategoria() {

    const contenido =
        document.getElementById("contenido");

    contenido.innerHTML = `
        <div class="formularioGestionCategoria">

            <div class="encabezadoFormularioCategoria">

                <button
                    type="button"
                    class="btnVolverCategoria"
                    onclick="cargar('categorias')">

                    <i class="fa-solid fa-arrow-left"></i>

                </button>

                <div>
                    <h2>Crear categoría</h2>
                    <p>
                        Registre una nueva clasificación de productos.
                    </p>
                </div>

            </div>

            <form id="formCrearCategoria">

                <div class="gridFormularioCategoria">

                    <div class="campoCategoria">

                        <label for="nombreCategoriaCrear">
                            Nombre *
                        </label>

                        <input
                            type="text"
                            name="categoria"
                            id="nombreCategoriaCrear"
                            minlength="3"
                            maxlength="100"
                            required>

                    </div>

                    <div class="campoCategoria">

                        <label for="slugCategoriaCrear">
                            Slug
                        </label>

                        <input
                            type="text"
                            name="slug"
                            id="slugCategoriaCrear"
                            maxlength="120"
                            placeholder="Se genera automáticamente">

                    </div>

                    <div class="campoCategoria campoCompleto">

                        <label for="descripcionCategoriaCrear">
                            Descripción
                        </label>

                        <textarea
                            name="descripcion"
                            id="descripcionCategoriaCrear"
                            maxlength="300"></textarea>

                    </div>

                    <div class="campoCategoria">

                        <label for="ordenCategoriaCrear">
                            Orden
                        </label>

                        <input
                            type="number"
                            name="orden"
                            id="ordenCategoriaCrear"
                            min="0"
                            value="0"
                            required>

                    </div>

                    <label class="checkCategoria">

                        <input
                            type="checkbox"
                            name="visible_tienda"
                            value="1"
                            checked>

                        <span>
                            Visible en la tienda
                        </span>

                    </label>

                </div>

                <div id="respuestaCrearCategoria"></div>

                <div class="botonesFormularioCategoria">

                    <button
                        type="button"
                        class="btnCancelarCategoria"
                        onclick="cargar('categorias')">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        id="btnGuardarCategoria"
                        class="btnGuardarCategoria">

                        <i class="fa-solid fa-floppy-disk"></i>
                        Registrar categoría

                    </button>

                </div>

            </form>

        </div>
    `;

    const nombre =
        document.getElementById("nombreCategoriaCrear");

    const slug =
        document.getElementById("slugCategoriaCrear");

    let slugEditado = false;

    slug.addEventListener("input", () => {
        slugEditado = slug.value.trim() !== "";
    });

    nombre.addEventListener("input", () => {

        if (!slugEditado) {
            slug.value = generarSlugCategoriaJS(
                nombre.value
            );
        }
    });

    document
        .getElementById("formCrearCategoria")
        .addEventListener(
            "submit",
            enviarCategoria
        );
}

async function enviarCategoria(event) {

    event.preventDefault();

    const formulario = event.currentTarget;

    const boton = document.getElementById(
        "btnGuardarCategoria"
    );

    boton.disabled = true;

    try {

        const response = await fetch(
            "./php/crearCategoria.php",
            {
                method: "POST",
                body: new FormData(formulario)
            }
        );

        const resultado = await response.json();

        if (comprobarSesionCategoria(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaCategoria(
                "respuestaCrearCategoria",
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaCategoria(
            "respuestaCrearCategoria",
            resultado.mensaje,
            true
        );

        setTimeout(() => {
            cargar("categorias");
        }, 800);

    } catch (error) {

        mostrarRespuestaCategoria(
            "respuestaCrearCategoria",
            "No fue posible registrar la categoría.",
            false
        );

    } finally {

        boton.disabled = false;
    }
}

/* =====================================================
   MODIFICAR CATEGORÍA
===================================================== */

async function modificarCategoria(idCategoria) {

    try {

        const response = await fetch(
            `./php/obtenerCategoria.php?id=${idCategoria}&v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (comprobarSesionCategoria(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {
            throw new Error(resultado.mensaje);
        }

        abrirDialogCategoria(resultado.datos);

    } catch (error) {

        alert(
            error.message ||
            "No fue posible cargar la categoría."
        );
    }
}

function abrirDialogCategoria(categoria) {

    let dialogo = document.getElementById(
        "dialogModificarCategoria"
    );

    if (!dialogo) {

        dialogo = document.createElement("dialog");

        dialogo.id = "dialogModificarCategoria";

        document.body.appendChild(dialogo);
    }

    dialogo.className = "dialogCategoriaCompleto";

    dialogo.innerHTML = `
        <div class="cabeceraDialogCategoria">

            <div>
                <h2>Modificar categoría</h2>
                <p>Actualice la información de la categoría.</p>
            </div>

            <button
                type="button"
                onclick="cerrarDialogCategoria()">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <form id="formModificarCategoria">

            <input
                type="hidden"
                name="id_categoria"
                value="${categoria.id_categoria}">

            <div class="gridFormularioCategoria">

                <div class="campoCategoria">

                    <label>Nombre *</label>

                    <input
                        type="text"
                        name="categoria"
                        id="nombreCategoriaModificar"
                        value="${escaparAtributoCategoria(
                            categoria.categoria
                        )}"
                        required>

                </div>

                <div class="campoCategoria">

                    <label>Slug *</label>

                    <input
                        type="text"
                        name="slug"
                        id="slugCategoriaModificar"
                        value="${escaparAtributoCategoria(
                            categoria.slug || ""
                        )}"
                        required>

                </div>

                <div class="campoCategoria campoCompleto">

                    <label>Descripción</label>

                    <textarea
                        name="descripcion"
                        maxlength="300">${escaparHTMLCategoria(
                            categoria.descripcion || ""
                        )}</textarea>

                </div>

                <div class="campoCategoria">

                    <label>Orden</label>

                    <input
                        type="number"
                        name="orden"
                        min="0"
                        value="${categoria.orden}"
                        required>

                </div>

                <label class="checkCategoria">

                    <input
                        type="checkbox"
                        name="visible_tienda"
                        value="1"
                        ${Number(categoria.visible_tienda) === 1
                            ? "checked"
                            : ""}>

                    <span>Visible en la tienda</span>

                </label>

            </div>

            <div id="respuestaModificarCategoria"></div>

            <div class="botonesFormularioCategoria">

                <button
                    type="button"
                    class="btnCancelarCategoria"
                    onclick="cerrarDialogCategoria()">

                    Cancelar

                </button>

                <button
                    type="submit"
                    id="btnActualizarCategoria"
                    class="btnGuardarCategoria">

                    Guardar cambios

                </button>

            </div>

        </form>
    `;

    document
        .getElementById("formModificarCategoria")
        .onsubmit = guardarCategoria;

    if (!dialogo.open) {
        dialogo.showModal();
    }
}

async function guardarCategoria(event) {

    event.preventDefault();

    const formulario = event.currentTarget;

    const boton = document.getElementById(
        "btnActualizarCategoria"
    );

    boton.disabled = true;

    try {

        const response = await fetch(
            "./php/modificarCategoria.php",
            {
                method: "POST",
                body: new FormData(formulario)
            }
        );

        const resultado = await response.json();

        if (comprobarSesionCategoria(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaCategoria(
                "respuestaModificarCategoria",
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaCategoria(
            "respuestaModificarCategoria",
            resultado.mensaje,
            true
        );

        await Promise.all([
            cargarCategoriasAdmin(),
            cargarResumenCategorias()
        ]);

        setTimeout(() => {
            cerrarDialogCategoria();
        }, 700);

    } catch (error) {

        mostrarRespuestaCategoria(
            "respuestaModificarCategoria",
            "No fue posible actualizar la categoría.",
            false
        );

    } finally {

        boton.disabled = false;
    }
}

function cerrarDialogCategoria() {

    const dialogo = document.getElementById(
        "dialogModificarCategoria"
    );

    if (dialogo?.open) {
        dialogo.close();
    }
}

/* =====================================================
   VISIBILIDAD
===================================================== */

async function cambiarVisibilidadCategoria(
    idCategoria,
    visibleTienda
) {

    const categoria = categoriasAdmin.find(
        item =>
            Number(item.id_categoria) ===
            Number(idCategoria)
    );

    const accion =
        Number(visibleTienda) === 1
            ? "mostrar"
            : "ocultar";

    const confirmado = await confirmar(
        "Visibilidad de categoría",
        `¿Desea ${accion} "${categoria?.categoria || "esta categoría"}"?`
    );

    if (!confirmado) {
        return;
    }

    const datos = new FormData();

    datos.append("id_categoria", idCategoria);
    datos.append("visible_tienda", visibleTienda);

    try {

        const response = await fetch(
            "./php/cambiarVisibilidadCategoria.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }


        if (!resultado.ok) {

            mensajeModal(
                "Error",
                resultado.mensaje
            );

            return;
        }

        await Promise.all([
            cargarCategoriasAdmin(),
            cargarResumenCategorias()
        ]);

    } catch (error) {

        mensajeModal(
            "Error",
            "No fue posible cambiar la visibilidad."
        );
    }
}

/* =====================================================
   ELIMINAR
===================================================== */

async function eliminarCategoria(idCategoria) {

    const categoria = categoriasAdmin.find(
        item =>
            Number(item.id_categoria) ===
            Number(idCategoria)
    );

    const confirmado = await confirmar(
        "Eliminar categoría",
        `¿Desea eliminar "${categoria?.categoria || "esta categoría"}"?`
    );

    if (!confirmado) {
        return;
    }

    const datos = new FormData();

    datos.append("id_categoria", idCategoria);

    try {

        const response = await fetch(
            "./php/eliminarCategoria.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mensajeModal(
                "No se puede eliminar",
                resultado.mensaje
            );

            return;
        }

        mensajeModal(
            "Categoría eliminada",
            resultado.mensaje
        );

        await Promise.all([
            cargarCategoriasAdmin(),
            cargarResumenCategorias()
        ]);

    } catch (error) {

        mensajeModal(
            "Error",
            "No fue posible eliminar la categoría."
        );
    }
}

/* =====================================================
   AUXILIARES
===================================================== */

function comprobarSesionCategoria(resultado) {

    if (manejarSesionExpirada(resultado)) {
      return;
    }
}

function generarSlugCategoriaJS(texto) {

    return String(texto || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

function formatearFechaCategoria(fecha) {

    if (!fecha) {
        return "Sin información";
    }

    const objeto = new Date(
        String(fecha).replace(" ", "T")
    );

    return objeto.toLocaleString("es-CL", {
        dateStyle: "short",
        timeStyle: "short"
    });
}

function mostrarRespuestaCategoria(
    id,
    mensaje,
    correcto
) {

    const elemento = document.getElementById(id);

    if (!elemento) {
        return;
    }

    elemento.className = correcto
        ? "respuestaCategoria correcta"
        : "respuestaCategoria error";

    elemento.textContent = mensaje;
}

function escaparHTMLCategoria(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function escaparAtributoCategoria(valor) {

    return escaparHTMLCategoria(valor);
}