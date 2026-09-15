
async function cargarServiciosModificarCita(
    servicioActual
) {

    const select = document.getElementById(
        "servicioCitaModificar"
    );

    if (!select) {
        return;
    }

    select.innerHTML = `
        <option value="">
            Cargando servicios...
        </option>
    `;

    try {

        const response = await fetch(
            `./php/obtenerServicios.php?v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }       

        select.innerHTML = `
            <option value="">
                Seleccione un servicio
            </option>
        `;

        if (!resultado.ok) {

            select.innerHTML = `
                <option value="">
                    No fue posible cargar los servicios
                </option>
            `;

            return;
        }

        resultado.datos.forEach(servicio => {

            const option =
                document.createElement("option");

            option.value = servicio.nombre;
            option.textContent = servicio.nombre;

            option.selected =
                servicio.nombre === servicioActual;

            select.appendChild(option);
        });

    } catch (error) {

        console.error(
            "Error cargando servicios para la cita:",
            error
        );

        select.innerHTML = `
            <option value="">
                Error al cargar servicios
            </option>
        `;
    }
}


/* =====================================================
   GESTIÓN ADMINISTRATIVA DE SERVICIOS
===================================================== */

let serviciosAdmin = [];
let filtroEstadoServicio = "Todos";

/* =====================================================
   INICIALIZACIÓN
===================================================== */

function inicializarGestionServicios() {

    cargarResumenServicios();
    cargarServiciosAdmin();

    document
        .querySelectorAll(".btnPestanaServicio")
        .forEach(boton => {

            boton.addEventListener("click", function () {

                document
                    .querySelectorAll(".btnPestanaServicio")
                    .forEach(item => {
                        item.classList.remove("activa");
                    });

                this.classList.add("activa");

                filtroEstadoServicio =
                    this.dataset.estado;

                relacionarTarjetasServicios();

                actualizarEncabezadoServicios();

                aplicarFiltrosServicios();
            });
        });

    document
        .querySelectorAll(".tarjetaResumenServicio[data-filtro]")
        .forEach(tarjeta => {

            tarjeta.addEventListener("click", function () {

                filtroEstadoServicio =
                    this.dataset.filtro;

                document
                    .querySelectorAll(".tarjetaResumenServicio")
                    .forEach(item => {
                        item.classList.remove("seleccionada");
                    });

                this.classList.add("seleccionada");

                document
                    .querySelectorAll(".btnPestanaServicio")
                    .forEach(boton => {

                        boton.classList.toggle(
                            "activa",
                            boton.dataset.estado ===
                            filtroEstadoServicio
                        );
                    });

                actualizarEncabezadoServicios();

                aplicarFiltrosServicios();
            });
        });

    const buscador =
        document.getElementById("buscarServicioAdmin");

    if (buscador) {
        buscador.addEventListener(
            "input",
            aplicarFiltrosServicios
        );
    }

    const filtroDuracion =
        document.getElementById("filtroDuracionServicio");

    if (filtroDuracion) {
        filtroDuracion.addEventListener(
            "change",
            aplicarFiltrosServicios
        );
    }

    const orden =
        document.getElementById("ordenServicioAdmin");

    if (orden) {
        orden.addEventListener(
            "change",
            aplicarFiltrosServicios
        );
    }

    const limpiar =
        document.getElementById("btnLimpiarFiltrosServicio");

    if (limpiar) {
        limpiar.addEventListener(
            "click",
            limpiarFiltrosServicios
        );
    }
}

/* =====================================================
   RESUMEN
===================================================== */

async function cargarResumenServicios() {

    try {

        const response = await fetch(
            "./php/obtenerResumenServicios.php"
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

        asignarResumenServicio(
            "totalServiciosAdmin",
            datos.totalServicios
        );

        asignarResumenServicio(
            "serviciosActivosAdmin",
            datos.serviciosActivos
        );

        asignarResumenServicio(
            "serviciosOcultosAdmin",
            datos.serviciosOcultos
        );

        asignarResumenServicio(
            "serviciosDestacadosAdmin",
            datos.serviciosDestacados
        );

        asignarResumenServicio(
            "precioPromedioServiciosAdmin",
            formatearPrecioServicio(datos.precioPromedio)
        );

    } catch (error) {

        console.error(
            "Error cargando resumen de servicios:",
            error
        );
    }
}

function asignarResumenServicio(id, valor) {

    const elemento = document.getElementById(id);

    if (elemento) {
        elemento.textContent = valor;
    }
}

/* =====================================================
   CARGAR SERVICIOS
===================================================== */

async function cargarServiciosAdmin() {

    try {

        const response = await fetch(
            "./php/obtenerServiciosAdmin.php"
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        serviciosAdmin = resultado.datos;

        aplicarFiltrosServicios();

    } catch (error) {

        console.error(error);

        alert("No fue posible cargar los servicios.");
    }
}

/* =====================================================
   FILTRAR Y ORDENAR
===================================================== */

function aplicarFiltrosServicios() {

    const buscador =
        document.getElementById("buscarServicioAdmin");

    const filtroDuracion =
        document.getElementById("filtroDuracionServicio");

    const orden =
        document.getElementById("ordenServicioAdmin");

    const texto = buscador
        ? buscador.value.trim().toLowerCase()
        : "";

    const duracionSeleccionada =
        filtroDuracion ? filtroDuracion.value : "";

    const ordenSeleccionado =
        orden ? orden.value : "nombre_asc";

    let serviciosFiltrados =
        serviciosAdmin.filter(servicio => {

            const coincideTexto =
                !texto ||
                String(servicio.nombre || "")
                    .toLowerCase()
                    .includes(texto) ||
                String(servicio.descripcion || "")
                    .toLowerCase()
                    .includes(texto);

            let coincideEstado = true;

            switch (filtroEstadoServicio) {

                case "Activo":

                    coincideEstado =
                        servicio.estado === "Activo";

                break;

                case "Inactivo":

                    coincideEstado =
                        servicio.estado === "Inactivo";

                break;

                case "Oculto":

                    coincideEstado =
                        Number(servicio.visible_citas) === 0;

                break;

                case "Destacado":

                    coincideEstado =
                        Number(servicio.destacado) === 1;

                break;
            }

            const duracion =
                Number(servicio.duracion_minutos);

            let coincideDuracion = true;

            switch (duracionSeleccionada) {

                case "corta":
                    coincideDuracion = duracion <= 30;
                break;

                case "media":
                    coincideDuracion =
                        duracion >= 31 && duracion <= 60;
                break;

                case "larga":
                    coincideDuracion = duracion > 60;
                break;
            }

            return (
                coincideTexto &&
                coincideEstado &&
                coincideDuracion
            );
        });

    serviciosFiltrados.sort((a, b) => {

        switch (ordenSeleccionado) {

            case "nombre_desc":

                return String(b.nombre).localeCompare(
                    String(a.nombre)
                );

            case "precio_menor":

                return Number(a.precio_min) -
                       Number(b.precio_min);

            case "precio_mayor":

                return Number(b.precio_min) -
                       Number(a.precio_min);

            case "duracion_menor":

                return Number(a.duracion_minutos) -
                       Number(b.duracion_minutos);

            case "duracion_mayor":

                return Number(b.duracion_minutos) -
                       Number(a.duracion_minutos);

            case "actualizado":

                return new Date(b.fecha_actualizacion) -
                       new Date(a.fecha_actualizacion);

            default:

                return String(a.nombre).localeCompare(
                    String(b.nombre)
                );
        }
    });

    cargarTablaServiciosAdmin(serviciosFiltrados);
}

/* =====================================================
   CARGAR TABLA
===================================================== */

function cargarTablaServiciosAdmin(servicios) {

    const tbody =
        document.getElementById("tbody_servicio");

    const tabla =
        document.getElementById("table_servicio");

    const sinResultados =
        document.getElementById("sinServiciosAdmin");

    const contador =
        document.getElementById("cantidadResultadosServicios");

    const textoResultados =
        document.getElementById("textoResultadosServicios");

    if (!tbody) {
        return;
    }

    contador.textContent = servicios.length;

    textoResultados.textContent =
        servicios.length === 1
            ? "resultado"
            : "resultados";

    tbody.innerHTML = "";

    if (servicios.length === 0) {

        tabla.style.display = "none";
        sinResultados.style.display = "block";

        return;
    }

    tabla.style.display = "table";
    sinResultados.style.display = "none";

    servicios.forEach(servicio => {

        const esVisible =
            Number(servicio.visible_citas) === 1;

        const esDestacado =
            Number(servicio.destacado) === 1;

        const claseEstado =
            obtenerClaseEstadoServicio(
                servicio.estado_visual
            );

        const imagen = `./images/servicios/${servicio.id_servicio}.webp`;

        tbody.innerHTML += `

            <tr>

                <td>

                    <img
                        src="${imagen}?v=${Date.now()}"
                        alt="${escaparHTMLServicio(servicio.nombre)}"
                        class="imagenServicioAdmin"
                        onerror="
                            this.onerror=null;
                            this.src='./images/logoAlianzaPro.webp';
                        ">

                </td>

                <td>

                    <div class="infoServicioAdmin">

                        <strong>
                            ${escaparHTMLServicio(servicio.nombre)}
                        </strong>

                        <small>
                            Servicio N.º ${servicio.id_servicio}
                        </small>

                    </div>

                </td>

                <td class="descripcionServicioAdmin">

                    ${escaparHTMLServicio(
                        resumirTextoServicio(
                            servicio.descripcion,
                            100
                        )
                    )}

                </td>

                <td>

                    <span class="duracionServicioAdmin">

                        <i class="fa-regular fa-clock"></i>

                        ${formatearDuracionServicio(
                            servicio.duracion_minutos
                        )}

                    </span>

                </td>

                <td class="precioServicioAdmin">

                    ${formatearPrecioServicio(
                        servicio.precio_min
                    )}

                </td>

                <td>

                    <span class="
                        indicadorServicio
                        ${esVisible ? "visible" : "oculto"}
                    ">

                        <i class="fa-solid
                            ${esVisible
                                ? "fa-eye"
                                : "fa-eye-slash"}">
                        </i>

                        ${esVisible ? "Visible" : "Oculto"}

                    </span>

                </td>

                <td>

                    <span class="
                        indicadorDestacadoServicio
                        ${esDestacado ? "destacado" : ""}
                    ">

                        <i class="fa-solid fa-star"></i>

                        ${esDestacado ? "Sí" : "No"}

                    </span>

                </td>

                <td>

                    <span class="
                        estadoServicioAdmin
                        ${claseEstado}
                    ">

                        ${escaparHTMLServicio(
                            servicio.estado_visual
                        )}

                    </span>

                </td>

                <td>

                    <div class="accionesServicioAdmin">

                        <button
                            type="button"
                            class="
                                btnAccionServicio
                                btnVisibilidadServicioAdmin
                                ${esVisible
                                    ? "btnOcultarServicioAdmin"
                                    : "btnMostrarServicioAdmin"}
                            "
                            title="${esVisible
                                ? "Ocultar de las reservas"
                                : "Mostrar en las reservas"}"
                            onclick="cambiarVisibilidadServicio(
                                ${servicio.id_servicio},
                                ${esVisible ? 0 : 1}
                            )">

                            <i class="fa-solid
                                ${esVisible
                                    ? "fa-eye-slash"
                                    : "fa-eye"}">
                            </i>

                        </button>

                        <button
                        type="button"
                        class="btnAccionServicio btnModificarServicioAdmin"
                        title="Modificar servicio"
                        onclick="modificarServicio(${servicio.id_servicio})">

                        <i class="fa-solid fa-pen-to-square"></i>

                        </button>

                        <button
                            type="button"
                            class="
                                btnAccionServicio
                                btnEliminarServicioAdmin
                            "
                            title="Eliminar servicio"
                            onclick="eliminarServicio(${servicio.id_servicio})"
                            ">

                            <i class="fa-solid fa-trash"></i>

                        </button>

                    </div>

                </td>

            </tr>
        `;
    });

    document.querySelectorAll(".btnModificarServicioAdmin").forEach(boton => {

            acceso(localStorage.getItem("rol"), boton);
        });

    document.querySelectorAll(".btnEliminarServicioAdmin").forEach(boton => {

            acceso(localStorage.getItem("rol"), boton);
        });

    document.querySelectorAll(".btnVisibilidadServicioAdmin").forEach(boton => {

          acceso(localStorage.getItem("rol"), boton);
    });
}

/* =====================================================
   ENCABEZADO SEGÚN FILTRO
===================================================== */

function actualizarEncabezadoServicios() {

    const titulo =
        document.getElementById("tituloResultadosServicios");

    const descripcion =
        document.getElementById(
            "descripcionResultadosServicios"
        );

    const textos = {

        "Todos": {
            titulo: "Todos los servicios",
            descripcion:
                "Catálogo completo de servicios registrados."
        },

        "Activo": {
            titulo: "Servicios activos",
            descripcion:
                "Servicios actualmente disponibles."
        },

        "Inactivo": {
            titulo: "Servicios inactivos",
            descripcion:
                "Servicios que ya no están operativos."
        },

        "Oculto": {
            titulo: "Servicios ocultos",
            descripcion:
                "Servicios que no aparecen al reservar una cita."
        },

        "Destacado": {
            titulo: "Servicios destacados",
            descripcion:
                "Servicios promocionados en la página pública."
        }
    };

    const informacion =
        textos[filtroEstadoServicio] || textos.Todos;

    titulo.textContent = informacion.titulo;
    descripcion.textContent = informacion.descripcion;
}

function relacionarTarjetasServicios() {

    document
        .querySelectorAll(".tarjetaResumenServicio")
        .forEach(tarjeta => {

            tarjeta.classList.toggle(
                "seleccionada",
                tarjeta.dataset.filtro ===
                filtroEstadoServicio
            );
        });
}

/* =====================================================
   LIMPIAR FILTROS
===================================================== */

function limpiarFiltrosServicios() {

    document.getElementById(
        "buscarServicioAdmin"
    ).value = "";

    document.getElementById(
        "filtroDuracionServicio"
    ).value = "";

    document.getElementById(
        "ordenServicioAdmin"
    ).value = "nombre_asc";

    filtroEstadoServicio = "Todos";

    document
        .querySelectorAll(".btnPestanaServicio")
        .forEach(boton => {

            boton.classList.toggle(
                "activa",
                boton.dataset.estado === "Todos"
            );
        });

    relacionarTarjetasServicios();
    actualizarEncabezadoServicios();
    aplicarFiltrosServicios();
}

/* =====================================================
   AUXILIARES
===================================================== */

function formatearPrecioServicio(valor) {

    return new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        minimumFractionDigits: 0
    }).format(Number(valor) || 0);
}

function formatearDuracionServicio(minutos) {

    minutos = Number(minutos) || 0;

    if (minutos < 60) {
        return minutos + " min";
    }

    const horas = Math.floor(minutos / 60);
    const restante = minutos % 60;

    if (restante === 0) {
        return horas + " h";
    }

    return horas + " h " + restante + " min";
}

function resumirTextoServicio(texto, limite) {

    texto = String(texto || "");

    if (texto.length <= limite) {
        return texto;
    }

    return texto.substring(0, limite) + "...";
}

function obtenerClaseEstadoServicio(estado) {

    switch (estado) {

        case "Activo":
            return "activo";

        case "Inactivo":
            return "inactivo";

        case "Oculto":
            return "oculto";

        default:
            return "";
    }
}

function escaparHTMLServicio(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}



function registrarServicio() {

    const contenido = document.getElementById("contenido");

    contenido.innerHTML = `

        <div class="smart-forms smart-container wrap-2 formularioGestionProducto">

            <form
                id="form-servicio"
                method="POST"
                action="./php/crearServicio.php"
                enctype="multipart/form-data">

                <div class="encabezadoFormularioProducto">

                    <button
                        type="button"
                        id="volverServicios"
                        class="btnVolverProducto"
                        title="Volver">

                        <i class="fa-solid fa-arrow-left"></i>

                    </button>

                    <div>

                        <h2>Crear servicio</h2>

                        <p>
                            Registre un nuevo servicio ofrecido por el taller.
                        </p>

                    </div>

                </div>

                <div class="contenidoFormularioProducto">

                    <!-- IMAGEN Y PUBLICACIÓN -->

                    <aside class="columnaImagenProducto">

                        <div class="contenedorPreviewProducto">

                            <img
                                id="previewImagenServicio"
                                src="./images/page-bg.webp"
                                alt="Vista previa del servicio">

                        </div>

                        <label
                            for="imagenServicioCrear"
                            class="btnSeleccionarImagen">

                            <i class="fa-solid fa-image"></i>
                            Seleccionar imagen

                        </label>

                        <input
                            type="file"
                            name="imagen"
                            id="imagenServicioCrear"
                            accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.tif,.tiff,.avif,.heic,.heif"
                            required>

                        <small>
                            Formatos permitidos: JPG, PNG, WEBP, GIF y BMP.
                            Tamaño máximo: 5 MB.
                        </small>

                        <div class="opcionesPublicacionProducto">

                            <label class="opcionCheckProducto">

                                <input
                                    type="checkbox"
                                    name="visible_citas"
                                    id="visibleCitasServicioCrear"
                                    value="1"
                                    checked>

                                <span>
                                    <strong>Visible en reservas</strong>
                                    <small>
                                        Aparecerá en el formulario de citas.
                                    </small>
                                </span>

                            </label>

                            <label class="opcionCheckProducto">

                                <input
                                    type="checkbox"
                                    name="destacado"
                                    id="destacadoServicioCrear"
                                    value="1">

                                <span>
                                    <strong>Servicio destacado</strong>
                                    <small>
                                        Se mostrará en áreas promocionales.
                                    </small>
                                </span>

                            </label>

                        </div>

                    </aside>

                    <!-- CAMPOS -->

                    <div class="camposFormularioProducto">

                        <section class="grupoFormularioProducto">

                            <h3>
                                <i class="fa-solid fa-circle-info"></i>
                                Información del servicio
                            </h3>

                            <div class="gridCamposProducto">

                                <div class="campoProducto campoCompleto">

                                    <label for="nombreServicioCrear">
                                        Nombre del servicio *
                                    </label>

                                    <input
                                        type="text"
                                        name="nombre"
                                        id="nombreServicioCrear"
                                        minlength="3"
                                        maxlength="100"
                                        placeholder="Ej.: Cambio de aceite"
                                        required>

                                </div>

                                <div class="campoProducto campoCompleto">

                                    <label for="descripcionServicioCrear">
                                        Descripción *
                                    </label>

                                    <textarea
                                        name="descripcion"
                                        id="descripcionServicioCrear"
                                        minlength="10"
                                        maxlength="1500"
                                        placeholder="Describa el servicio ofrecido"
                                        required></textarea>

                                </div>

                            </div>

                        </section>

                        <section class="grupoFormularioProducto">

                            <h3>
                                <i class="fa-solid fa-sliders"></i>
                                Configuración
                            </h3>

                            <div class="gridCamposProducto tresColumnas">

                                <div class="campoProducto">

                                    <label for="precioServicioCrear">
                                        Precio mínimo *
                                    </label>

                                    <input
                                        type="number"
                                        name="precio_min"
                                        id="precioServicioCrear"
                                        min="1"
                                        step="1"
                                        placeholder="$0"
                                        required>

                                </div>

                                <div class="campoProducto">

                                    <label for="duracionServicioCrear">
                                        Duración estimada *
                                    </label>

                                    <select
                                        name="duracion_minutos"
                                        id="duracionServicioCrear"
                                        required>

                                        <option value="">
                                            Seleccione
                                        </option>

                                        <option value="30">
                                            30 minutos
                                        </option>

                                        <option value="45">
                                            45 minutos
                                        </option>

                                        <option value="60">
                                            1 hora
                                        </option>

                                        <option value="90">
                                            1 hora 30 minutos
                                        </option>

                                        <option value="120">
                                            2 horas
                                        </option>

                                        <option value="180">
                                            3 horas
                                        </option>

                                        <option value="240">
                                            4 horas
                                        </option>

                                    </select>

                                </div>

                                <div class="campoProducto">

                                    <label for="estadoServicioCrear">
                                        Estado *
                                    </label>

                                    <select
                                        name="estado"
                                        id="estadoServicioCrear"
                                        required>

                                        <option value="Activo">
                                            Activo
                                        </option>

                                        <option value="Inactivo">
                                            Inactivo
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </section>

                        <div id="respuestaServicio"></div>

                        <div class="botonesFormularioProducto">

                            <button
                                type="button"
                                class="btnCancelarProducto"
                                onclick="cargar('servicios')">

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
                                id="btnGuardarNuevoServicio"
                                class="btnGuardarProducto">

                                <i class="fa-solid fa-floppy-disk"></i>
                                Registrar servicio

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>
    `;

    const formulario =
        document.getElementById("form-servicio");

    const inputImagen =
        document.getElementById("imagenServicioCrear");

    const preview =
        document.getElementById("previewImagenServicio");

    inputImagen.addEventListener("change", function () {

        const archivo = this.files[0];

        if (!archivo) {
            return;
        }

        const formatosPermitidos = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif",
            "image/bmp"
        ];

        if (!formatosPermitidos.includes(archivo.type)) {

            alert("El formato de imagen no está permitido.");

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

    formulario.addEventListener(
        "submit",
        enviarServicio
    );

    formulario.addEventListener("reset", () => {

        setTimeout(() => {

            preview.src = "./images/page-bg.webp";

            document.getElementById(
                "respuestaServicio"
            ).innerHTML = "";

        }, 0);
    });

    document
        .getElementById("volverServicios")
        .addEventListener("click", () => {

            cargar("servicios");
        });
}


async function enviarServicio(event) {

    event.preventDefault();

    const formulario =
        document.getElementById("form-servicio");

    const respuesta =
        document.getElementById("respuestaServicio");

    const boton =
        document.getElementById("btnGuardarNuevoServicio");

    const datos = new FormData(formulario);

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Registrando...
    `;

    respuesta.innerHTML = "";

    try {

        const response = await fetch(
            "./php/crearServicio.php",
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

            mostrarRespuestaServicio(
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaServicio(
            resultado.mensaje,
            true
        );

        setTimeout(() => {

            cargar("servicios");

        }, 900);

    } catch (error) {

        console.error(error);

        mostrarRespuestaServicio(
            "No fue posible registrar el servicio.",
            false
        );

    } finally {

        boton.disabled = false;

        boton.innerHTML = `
            <i class="fa-solid fa-floppy-disk"></i>
            Registrar servicio
        `;
    }
}

function mostrarRespuestaServicio(mensaje, correcto) {

    const respuesta =
        document.getElementById("respuestaServicio");

    if (!respuesta) {
        return;
    }

    respuesta.className = correcto
        ? "respuestaProducto correcta"
        : "respuestaProducto error";

    respuesta.textContent = mensaje;
}


async function modificarServicio(idServicio) {

    const dialogo = document.getElementById(
        "dialogModificarServicio"
    );

    if (!dialogo) {

        alert(
            "No se encontró el diálogo para modificar el servicio."
        );

        return;
    }

    try {

        const response = await fetch(
            `./php/obtenerServicio.php?id=${encodeURIComponent(idServicio)}&v=${Date.now()}`,
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

        const servicio = resultado.datos;

        if (!servicio) {

            alert(
                "No se recibieron los datos del servicio."
            );

            return;
        }

        document.getElementById("idServicio").value =
            servicio.id_servicio;

        document.getElementById(
            "nombreServicioModificar"
        ).value = servicio.nombre ?? "";

        document.getElementById(
            "descripcionServicioModificar"
        ).value = servicio.descripcion ?? "";

        document.getElementById(
            "precioMinServicioModificar"
        ).value = servicio.precio_min ?? "";

        document.getElementById(
            "duracionServicioModificar"
        ).value = String(
            servicio.duracion_minutos ?? 60
        );

        document.getElementById(
            "estadoServicioModificar"
        ).value = servicio.estado ?? "Activo";

        document.getElementById(
            "visibleCitasServicioModificar"
        ).checked =
            Number(servicio.visible_citas) === 1;

        document.getElementById(
            "destacadoServicioModificar"
        ).checked =
            Number(servicio.destacado) === 1;

        const inputImagen = document.getElementById(
            "imagenModificarServicio"
        );

        inputImagen.value = "";
        inputImagen.onchange =
            mostrarPreviewModificarServicio;

        const preview = document.getElementById(
            "previewModificarServicio"
        );

        preview.onerror = null;

        preview.removeAttribute(
            "data-ruta-anterior"
        );

        preview.src =
            `./images/servicios/${servicio.id_servicio}.webp?v=${Date.now()}`;

        preview.onerror = function () {

            if (!this.dataset.rutaAnterior) {

                this.dataset.rutaAnterior = "1";

                this.src =
                    `./images/${servicio.id_servicio}.webp?v=${Date.now()}`;

                return;
            }

            this.onerror = null;
            this.src = "./images/page-bg.webp";
        };

        const respuesta = document.getElementById(
            "respuestaModificarServicio"
        );

        respuesta.className = "";
        respuesta.textContent = "";

        document.getElementById(
            "formModificarServicio"
        ).onsubmit = function (event) {

            event.preventDefault();

            guardarServicio();
        };

        if (!dialogo.open) {
            dialogo.showModal();
        }

    } catch (error) {

        console.error(
            "Error al cargar servicio:",
            error
        );

        alert(
            "No fue posible cargar la información del servicio."
        );
    }
}


function mostrarPreviewModificarServicio() {

    const archivo = this.files[0];

    if (!archivo) {
        return;
    }

    if (!validarImagenServicio(archivo)) {

        this.value = "";

        return;
    }

    const lector = new FileReader();

    lector.onload = function (evento) {

        const preview = document.getElementById(
            "previewModificarServicio"
        );

        preview.onerror = null;
        preview.src = evento.target.result;
    };

    lector.readAsDataURL(archivo);
}

function validarImagenServicio(archivo) {

    const permitidos = [
        "image/jpeg",
        "image/png",
        "image/webp",
        "image/gif",
        "image/bmp"
    ];

    if (!permitidos.includes(archivo.type)) {

        alert(
            "El formato de imagen no está permitido."
        );

        return false;
    }

    if (archivo.size > 5 * 1024 * 1024) {

        alert(
            "La imagen no puede superar los 5 MB."
        );

        return false;
    }

    return true;
}

function cerrarDialogModificarServicio() {

    const dialogo = document.getElementById(
        "dialogModificarServicio"
    );

    if (!dialogo) {
        return;
    }

    if (dialogo.open) {
        dialogo.close();
    }

    const formulario = document.getElementById(
        "formModificarServicio"
    );

    formulario.reset();

    const inputImagen = document.getElementById(
        "imagenModificarServicio"
    );

    inputImagen.value = "";

    const preview = document.getElementById(
        "previewModificarServicio"
    );

    preview.onerror = null;

    preview.removeAttribute(
        "data-ruta-anterior"
    );

    preview.src = "./images/page-bg.webp";

    const respuesta = document.getElementById(
        "respuestaModificarServicio"
    );

    respuesta.className = "";
    respuesta.textContent = "";
}


async function guardarServicio() {

    const formulario = document.getElementById(
        "formModificarServicio"
    );

    const boton = document.getElementById(
        "btnGuardarModificarServicio"
    );

    const respuesta = document.getElementById(
        "respuestaModificarServicio"
    );

    const imagen = document.getElementById(
        "imagenModificarServicio"
    ).files[0];

    if (imagen && imagen.size > 5 * 1024 * 1024) {

        mostrarRespuestaModificarServicio(
            "La imagen no puede superar los 5 MB.",
            false
        );

        return;
    }

    const datos = new FormData(formulario);

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Guardando...
    `;

    respuesta.innerHTML = "";

    try {

        const response = await fetch(
            "./php/modificarServicio.php",
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

            mostrarRespuestaModificarServicio(
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaModificarServicio(
            resultado.mensaje,
            true
        );

        await Promise.all([
            cargarServiciosAdmin(),
            cargarResumenServicios()
        ]);

        setTimeout(() => {

            cerrarDialogModificarServicio();

        }, 900);

    } catch (error) {

        console.error(error);

        mostrarRespuestaModificarServicio(
            "No fue posible actualizar el servicio.",
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

function mostrarRespuestaModificarServicio(
    mensaje,
    correcto
) {

    const respuesta = document.getElementById(
        "respuestaModificarServicio"
    );

    if (!respuesta) {
        return;
    }

    respuesta.className = correcto
        ? "respuestaProducto correcta"
        : "respuestaProducto error";

    respuesta.textContent = mensaje;
}


async function eliminarServicio(idServicio) {

    const servicio = serviciosAdmin.find(
        item =>
            Number(item.id_servicio) ===
            Number(idServicio)
    );

    const nombreServicio =
        servicio?.nombre || "este servicio";

    const confirmado = await confirmar(
        "Eliminar servicio",
        `¿Desea eliminar "${nombreServicio}"?`
    );

    if (!confirmado) {
        return;
    }

    try {

        const response = await fetch(
            `./php/eliminarServicio.php?id=${encodeURIComponent(idServicio)}`,
            {
                cache: "no-store"
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
            "Servicio eliminado",
            resultado.mensaje
        );

        await Promise.all([
            cargarServiciosAdmin(),
            cargarResumenServicios()
        ]);

    } catch (error) {

        console.error(
            "Error eliminando servicio:",
            error
        );

        mensajeModal(
            "Error",
            "No fue posible eliminar el servicio."
        );
    }
}


async function cambiarVisibilidadServicio(idServicio, nuevaVisibilidad) {

    const servicio = serviciosAdmin.find(
        item =>
            Number(item.id_servicio) ===
            Number(idServicio)
    );

    if (!servicio) {

        mensajeModal(
            "Error",
            "No se encontró el servicio seleccionado."
        );

        return;
    }

    const mostrar =
        Number(nuevaVisibilidad) === 1;

    const accion =
        mostrar ? "mostrar" : "ocultar";

    const confirmado = await confirmar(
        "Visibilidad del servicio",
        `¿Desea ${accion} "${servicio.nombre}" en el formulario de reservas?`
    );

    if (!confirmado) {
        return;
    }

    const datos = new FormData();

    datos.append(
        "id_servicio",
        idServicio
    );

    datos.append(
        "visible_citas",
        nuevaVisibilidad
    );

    try {

        const response = await fetch(
            "./php/cambiarVisibilidadServicio.php",
            {
                method: "POST",
                body: datos,
                cache: "no-store"
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

        await Promise.all([
            cargarServiciosAdmin(),
            cargarResumenServicios()
        ]);

    } catch (error) {

        console.error(
            "Error cambiando visibilidad:",
            error
        );

        mensajeModal(
            "Error",
            "No fue posible cambiar la visibilidad del servicio."
        );
    }
}