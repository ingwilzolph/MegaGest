/* =====================================================
   GESTIÓN ADMINISTRATIVA DE USUARIOS
===================================================== */

let usuariosAdmin = [];
let filtroUsuarioActual = "Todos";


/* =====================================================
   INICIALIZACIÓN
===================================================== */

function inicializarGestionUsuarios() {

    filtroUsuarioActual = "Todos";

    cargarResumenUsuarios();
    cargarUsuariosAdmin();

    document
        .querySelectorAll(".btnPestanaUsuario")
        .forEach(boton => {

            boton.onclick = function () {

                filtroUsuarioActual =
                    this.dataset.estado || "Todos";

                actualizarSeleccionUsuarios();
                actualizarEncabezadoUsuarios();
                aplicarFiltrosUsuarios();
            };
        });

    document
        .querySelectorAll(
            ".tarjetaResumenUsuario[data-filtro]"
        )
        .forEach(tarjeta => {

            tarjeta.onclick = function () {

                filtroUsuarioActual =
                    this.dataset.filtro || "Todos";

                actualizarSeleccionUsuarios();
                actualizarEncabezadoUsuarios();
                aplicarFiltrosUsuarios();
            };
        });

    const buscador = document.getElementById(
        "buscarUsuarioAdmin"
    );

    if (buscador) {
        buscador.oninput = aplicarFiltrosUsuarios;
    }

    const filtroRol = document.getElementById(
        "filtroRolUsuario"
    );

    if (filtroRol) {
        filtroRol.onchange = aplicarFiltrosUsuarios;
    }

    const filtroEstado = document.getElementById(
        "filtroEstadoUsuario"
    );

    if (filtroEstado) {
        filtroEstado.onchange = aplicarFiltrosUsuarios;
    }

    const orden = document.getElementById(
        "ordenUsuarioAdmin"
    );

    if (orden) {
        orden.onchange = aplicarFiltrosUsuarios;
    }

    const limpiar = document.getElementById(
        "btnLimpiarFiltrosUsuario"
    );

    if (limpiar) {
        limpiar.onclick = limpiarFiltrosUsuarios;
    }

    const nuevoUsuario = document.getElementById(
        "btnNuevoUsuarioAdmin"
    );

    if (nuevoUsuario) {
        nuevoUsuario.onclick = inscripcion;
    }
}


/* =====================================================
   CONTROL DE SESIÓN Y PERMISOS
===================================================== */

function comprobarRespuestaUsuario(resultado) {

    if (manejarSesionExpirada(resultado)) {
        return;
    }

    if (resultado.sinPermiso) {

        alert(resultado.mensaje);

        return false;
    }

    return true;
}


/* =====================================================
   RESUMEN
===================================================== */

async function cargarResumenUsuarios() {

    try {

        const response = await fetch(
            `./php/obtenerResumenUsuarios.php?v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (!comprobarRespuestaUsuario(resultado)) {
            return;
        }

        if (!resultado.ok) {

            console.error(resultado.mensaje);

            return;
        }

        const datos = resultado.datos;

        asignarResumenUsuario(
            "totalUsuariosAdmin",
            datos.totalUsuarios
        );

        asignarResumenUsuario(
            "usuariosActivosAdmin",
            datos.usuariosActivos
        );

        asignarResumenUsuario(
            "usuariosInactivosAdmin",
            datos.usuariosInactivos
        );

        asignarResumenUsuario(
            "administradoresAdmin",
            datos.administradores
        );

        asignarResumenUsuario(
            "empleadosAdmin",
            datos.empleados
        );

    } catch (error) {

        console.error(
            "Error cargando resumen de usuarios:",
            error
        );
    }
}


function asignarResumenUsuario(id, valor) {

    const elemento =
        document.getElementById(id);

    if (elemento) {
        elemento.textContent =
            Number(valor) || 0;
    }
}


/* =====================================================
   CARGAR USUARIOS
===================================================== */

async function cargarUsuariosAdmin() {

    try {

        const response = await fetch(
            `./php/obtenerUsuarios.php?v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (!comprobarRespuestaUsuario(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        usuariosAdmin = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        aplicarFiltrosUsuarios();

    } catch (error) {

        console.error(
            "Error cargando usuarios:",
            error
        );

        alert(
            "No fue posible cargar los usuarios."
        );
    }
}


/* =====================================================
   FILTRAR Y ORDENAR
===================================================== */

function aplicarFiltrosUsuarios() {

    const buscador = document.getElementById(
        "buscarUsuarioAdmin"
    );

    const filtroRol = document.getElementById(
        "filtroRolUsuario"
    );

    const filtroEstado = document.getElementById(
        "filtroEstadoUsuario"
    );

    const orden = document.getElementById(
        "ordenUsuarioAdmin"
    );

    const texto = buscador
        ? buscador.value.trim().toLowerCase()
        : "";

    const rolSeleccionado = filtroRol
        ? filtroRol.value.toLowerCase()
        : "";

    const estadoSeleccionado = filtroEstado
        ? filtroEstado.value.toLowerCase()
        : "";

    const ordenSeleccionado = orden
        ? orden.value
        : "nombre_asc";

    let usuariosFiltrados =
        usuariosAdmin.filter(usuario => {

            const nombreCompleto = `
                ${usuario.nombre || ""}
                ${usuario.apellido || ""}
            `.toLowerCase();

            const correo = String(
                usuario.correo || ""
            ).toLowerCase();

            const rol = String(
                usuario.rol || ""
            ).toLowerCase();

            const estado = String(
                usuario.estado || ""
            ).toLowerCase();

            const coincideTexto =
                !texto ||
                nombreCompleto.includes(texto) ||
                correo.includes(texto);

            const coincideRolSelect =
                !rolSeleccionado ||
                rol === rolSeleccionado;

            const coincideEstadoSelect =
                !estadoSeleccionado ||
                estado === estadoSeleccionado;

            let coincidePestana = true;

            switch (
                String(filtroUsuarioActual).toLowerCase()
            ) {

                case "activo":
                    coincidePestana =
                        estado === "activo";
                break;

                case "inactivo":
                    coincidePestana =
                        estado === "inactivo";
                break;

                case "administrador":
                    coincidePestana =
                        rol === "administrador";
                break;

                case "empleado":
                    coincidePestana =
                        rol === "empleado";
                break;

                case "cajero":
                    coincidePestana =
                        rol === "cajero";
                break;

                case "vendedor":
                    coincidePestana =
                        rol === "vendedor";
                break;

                case "mecanico":
                    coincidePestana =
                        rol === "mecanico";
                break;
            }

            return (
                coincideTexto &&
                coincideRolSelect &&
                coincideEstadoSelect &&
                coincidePestana
            );
        });

    usuariosFiltrados.sort((a, b) => {

        const nombreA = `
            ${a.nombre || ""}
            ${a.apellido || ""}
        `.trim();

        const nombreB = `
            ${b.nombre || ""}
            ${b.apellido || ""}
        `.trim();

        switch (ordenSeleccionado) {

            case "nombre_desc":

                return nombreB.localeCompare(
                    nombreA,
                    "es"
                );

            case "correo_asc":

                return String(a.correo).localeCompare(
                    String(b.correo),
                    "es"
                );

            case "correo_desc":

                return String(b.correo).localeCompare(
                    String(a.correo),
                    "es"
                );

            case "antiguos":

                return convertirFechaUsuario(
                    a.fechaRegistro
                ) -
                convertirFechaUsuario(
                    b.fechaRegistro
                );

            case "recientes":

                return convertirFechaUsuario(
                    b.fechaRegistro
                ) -
                convertirFechaUsuario(
                    a.fechaRegistro
                );

            default:

                return nombreA.localeCompare(
                    nombreB,
                    "es"
                );
        }
    });

    cargarTablaUsuariosAdmin(
        usuariosFiltrados
    );
}


function convertirFechaUsuario(fecha) {

    if (!fecha) {
        return 0;
    }

    return new Date(
        String(fecha).replace(" ", "T")
    ).getTime() || 0;
}


/* =====================================================
   TABLA
===================================================== */

function cargarTablaUsuariosAdmin(usuarios) {

    const tbody = document.getElementById(
        "tbody_usuario"
    );

    const tabla = document.getElementById(
        "table_usuario"
    );

    const sinResultados =
        document.getElementById(
            "sinUsuariosAdmin"
        );

    const contador = document.getElementById(
        "cantidadResultadosUsuarios"
    );

    const textoResultados =
        document.getElementById(
            "textoResultadosUsuarios"
        );

    if (!tbody) {
        return;
    }

    if (contador) {
        contador.textContent = usuarios.length;
    }

    if (textoResultados) {

        textoResultados.textContent =
            usuarios.length === 1
                ? "resultado"
                : "resultados";
    }

    tbody.innerHTML = "";

    if (usuarios.length === 0) {

        if (tabla) {
            tabla.style.display = "none";
        }

        if (sinResultados) {
            sinResultados.style.display = "block";
        }

        return;
    }

    if (tabla) {
        tabla.style.display = "table";
    }

    if (sinResultados) {
        sinResultados.style.display = "none";
    }

    const idUsuarioSesion = Number(
        localStorage.getItem("id_usuario")
    );

    usuarios.forEach(usuario => {

        const idUsuario =
            Number(usuario.id_usuario);

        const nombreCompleto = `
            ${usuario.nombre || ""}
            ${usuario.apellido || ""}
        `.trim();

        const iniciales =
            obtenerInicialesUsuario(
                usuario.nombre,
                usuario.apellido
            );

        const rol = String(
            usuario.rol || ""
        ).toLowerCase();

        const estado = String(
            usuario.estado || ""
        ).toLowerCase();

        const esUsuarioSesion =
            idUsuario === idUsuarioSesion;

        const fila =
            document.createElement("tr");

        fila.innerHTML = `

            <td>

                <div class="infoUsuarioAdmin">

                    <div class="avatarUsuarioAdmin">

                        ${escaparHTMLUsuario(iniciales)}

                    </div>

                    <div>

                        <strong>
                            ${escaparHTMLUsuario(nombreCompleto)}
                        </strong>

                        <small>
                            Usuario N.º ${idUsuario}
                            ${esUsuarioSesion
                                ? " · Sesión actual"
                                : ""}
                        </small>

                    </div>

                </div>

            </td>

            <td class="correoUsuarioAdmin">

                <i class="fa-regular fa-envelope"></i>

                ${escaparHTMLUsuario(
                    usuario.correo
                )}

            </td>

            <td>

                <span class="
                    rolUsuarioAdmin
                    ${rol}
                ">

                    <i class="fa-solid ${
                        rol === "administrador"
                            ? "fa-user-shield"
                            : "fa-user-gear"
                    }"></i>

                    ${capitalizarUsuario(rol)}

                </span>

            </td>

            <td>

                <span class="
                    estadoUsuarioAdmin
                    ${estado}
                ">

                    <span></span>

                    ${capitalizarUsuario(estado)}

                </span>

            </td>

            <td>

                <span class="fechaUsuarioAdmin">

                    <i class="fa-regular fa-calendar"></i>

                    ${formatearFechaUsuario(
                        usuario.fechaRegistro
                    )}

                </span>

            </td>

            <td>

                <div class="accionesUsuarioAdmin">

                    <button
                        type="button"
                        class="
                            btnAccionUsuario
                            btnModificarUsuarioAdmin
                        "
                        title="Modificar usuario"
                        data-id="${idUsuario}">

                        <i class="fa-solid fa-pen-to-square"></i>

                    </button>

                    <button
                        type="button"
                        class="
                            btnAccionUsuario
                            btnEliminarUsuarioAdmin
                        "
                        title="${
                            esUsuarioSesion
                                ? "No puede eliminar su cuenta"
                                : "Eliminar usuario"
                        }"
                        data-id="${idUsuario}"
                        data-correo="${escaparAtributoUsuario(
                            usuario.correo
                        )}"
                        ${esUsuarioSesion
                            ? "disabled"
                            : ""}>

                        <i class="fa-solid fa-trash"></i>

                    </button>

                </div>

            </td>
        `;

        tbody.appendChild(fila);
    });

    tbody
        .querySelectorAll(
            ".btnModificarUsuarioAdmin"
        )
        .forEach(boton => {

            boton.onclick = function () {

                modificarUsuario(
                    Number(this.dataset.id)
                );
            };
        });

    tbody
        .querySelectorAll(
            ".btnEliminarUsuarioAdmin"
        )
        .forEach(boton => {

            boton.onclick = function () {

                if (this.disabled) {
                    return;
                }

                eliminarUsuario(
                    Number(this.dataset.id),
                    this.dataset.correo
                );
            };
        });
}


/* =====================================================
   ENCABEZADO
===================================================== */

function actualizarEncabezadoUsuarios() {

    const titulo = document.getElementById(
        "tituloResultadosUsuarios"
    );

    const descripcion =
        document.getElementById(
            "descripcionResultadosUsuarios"
        );

    if (!titulo || !descripcion) {
        return;
    }

    const textos = {

        Todos: {
            titulo: "Todos los usuarios",
            descripcion:
                "Listado completo de usuarios registrados."
        },

        activo: {
            titulo: "Usuarios activos",
            descripcion:
                "Usuarios que pueden acceder al sistema."
        },

        inactivo: {
            titulo: "Usuarios inactivos",
            descripcion:
                "Usuarios que no pueden iniciar sesión."
        },

        administrador: {
            titulo: "Administradores",
            descripcion:
                "Usuarios con acceso administrativo completo."
        },

        cajero: {
            titulo: "Cajeros",
            descripcion:
                "Usuarios con permisos operativos limitados."
        },
     

        empleado: {
            titulo: "Empleados",
            descripcion:
                "Usuarios con permisos operativos limitados."
        },

        vendedor: {
            titulo: "Vendedores",
            descripcion:
                "Usuarios con permisos operativos limitados."
        },

        mecanico: {
            titulo: "Mecanicos",
            descripcion:
                "Usuarios con permisos operativos limitados."
        }
    };

    const clave =
        String(filtroUsuarioActual);

    const informacion =
        textos[clave] || textos.Todos;

    titulo.textContent =
        informacion.titulo;

    descripcion.textContent =
        informacion.descripcion;
}


function actualizarSeleccionUsuarios() {

    document
        .querySelectorAll(".btnPestanaUsuario")
        .forEach(boton => {

            boton.classList.toggle(
                "activa",
                String(boton.dataset.estado)
                    .toLowerCase() ===
                String(filtroUsuarioActual)
                    .toLowerCase()
            );
        });

    document
        .querySelectorAll(
            ".tarjetaResumenUsuario"
        )
        .forEach(tarjeta => {

            tarjeta.classList.toggle(
                "seleccionada",
                String(tarjeta.dataset.filtro)
                    .toLowerCase() ===
                String(filtroUsuarioActual)
                    .toLowerCase()
            );
        });
}


/* =====================================================
   LIMPIAR FILTROS
===================================================== */

function limpiarFiltrosUsuarios() {

    const buscador = document.getElementById(
        "buscarUsuarioAdmin"
    );

    const filtroRol = document.getElementById(
        "filtroRolUsuario"
    );

    const filtroEstado = document.getElementById(
        "filtroEstadoUsuario"
    );

    const orden = document.getElementById(
        "ordenUsuarioAdmin"
    );

    if (buscador) {
        buscador.value = "";
    }

    if (filtroRol) {
        filtroRol.value = "";
    }

    if (filtroEstado) {
        filtroEstado.value = "";
    }

    if (orden) {
        orden.value = "nombre_asc";
    }

    filtroUsuarioActual = "Todos";

    actualizarSeleccionUsuarios();
    actualizarEncabezadoUsuarios();
    aplicarFiltrosUsuarios();
}


/* =====================================================
   CREAR USUARIO
===================================================== */

function inscripcion() {

    const contenido =
        document.getElementById("contenido");

    if (!contenido) {
        return;
    }

    contenido.innerHTML = `

        <div class="
            smart-forms
            smart-container
            wrap-2
            formularioGestionUsuario
        ">

            <form
                id="form-inscripcion"
                method="POST"
                action="./php/crearCuenta.php">

                <div class="encabezadoFormularioUsuario">

                    <button
                        type="button"
                        id="volverUsuarios"
                        class="btnVolverUsuario"
                        title="Volver">

                        <i class="fa-solid fa-arrow-left"></i>

                    </button>

                    <div>

                        <h2>Crear usuario</h2>

                        <p>
                            Registre una nueva cuenta administrativa.
                        </p>

                    </div>

                </div>

                <div class="contenidoFormularioUsuario">

                    <section class="grupoFormularioUsuario">

                        <h3>
                            <i class="fa-solid fa-user"></i>
                            Información personal
                        </h3>

                        <div class="gridCamposUsuario">

                            <div class="campoUsuario">

                                <label for="nombreUsuarioCrear">
                                    Nombre *
                                </label>

                                <input
                                    type="text"
                                    name="nombre"
                                    id="nombreUsuarioCrear"
                                    minlength="2"
                                    maxlength="60"
                                    autocomplete="given-name"
                                    required>

                            </div>

                            <div class="campoUsuario">

                                <label for="apellidoUsuarioCrear">
                                    Apellido *
                                </label>

                                <input
                                    type="text"
                                    name="apellido"
                                    id="apellidoUsuarioCrear"
                                    minlength="2"
                                    maxlength="60"
                                    autocomplete="family-name"
                                    required>

                            </div>

                            <div class="
                                campoUsuario
                                campoCompletoUsuario
                            ">

                                <label for="correoUsuarioCrear">
                                    Correo electrónico *
                                </label>

                                <input
                                    type="email"
                                    name="correo"
                                    id="correoUsuarioCrear"
                                    maxlength="150"
                                    autocomplete="email"
                                    placeholder="usuario@correo.cl"
                                    required>

                            </div>

                        </div>

                    </section>

                    <section class="grupoFormularioUsuario">

                        <h3>
                            <i class="fa-solid fa-shield-halved"></i>
                            Acceso al sistema
                        </h3>

                        <div class="gridCamposUsuario">

                            <div class="campoUsuario">

                                <label for="passwordUsuarioCrear">
                                    Contraseña *
                                </label>

                                <div class="campoPasswordUsuario">

                                    <input
                                        type="password"
                                        name="password"
                                        id="passwordUsuarioCrear"
                                        minlength="8"
                                        maxlength="72"
                                        autocomplete="new-password"
                                        required>

                                    <button
                                        type="button"
                                        id="mostrarPasswordUsuario"
                                        title="Mostrar contraseña">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>

                                <small>
                                    Debe contener entre 8 y 72 caracteres.
                                </small>

                            </div>

                            <div class="campoUsuario">

                                <label for="rolUsuarioCrear">
                                    Rol *
                                </label>

                                <select
                                    name="rol"
                                    id="rolUsuarioCrear"
                                    required>

                                    <option value="">
                                        Seleccione un rol
                                    </option>

                                    <option value="administrador">
                                            Administrador
                                        </option>

                                        <option value="vendedor">
                                            Vendedor
                                        </option>

                                        <option value="cajero">
                                            Cajero
                                        </option>

                                        <option value="bodeguero">
                                            Bodeguero
                                        </option>

                                        <option value="chofer">
                                            Chofer
                                        </option>

                                        <option value="mecanico">
                                            Mecánico
                                        </option>

                                </select>

                            </div>

                        </div>

                    </section>

                    <div id="respuestaUsuario"></div>

                    <div class="botonesFormularioUsuario">

                        <button
                            type="button"
                            id="cancelarNuevoUsuario"
                            class="btnCancelarUsuario">

                            Cancelar

                        </button>

                        <button
                            type="reset"
                            class="btnReiniciarUsuario">

                            <i class="fa-solid fa-rotate-left"></i>
                            Reiniciar

                        </button>

                        <button
                            type="submit"
                            id="btnGuardarNuevoUsuario"
                            class="btnGuardarUsuario">

                            <i class="fa-solid fa-floppy-disk"></i>
                            Registrar usuario

                        </button>

                    </div>

                </div>

            </form>

        </div>
    `;

    const formulario = document.getElementById(
        "form-inscripcion"
    );

    formulario.onsubmit =
        enviarInscripcion;

    document.getElementById(
        "volverUsuarios"
    ).onclick = () => cargar("usuarios");

    document.getElementById(
        "cancelarNuevoUsuario"
    ).onclick = () => cargar("usuarios");

    document.getElementById(
        "mostrarPasswordUsuario"
    ).onclick = function () {

        const input = document.getElementById(
            "passwordUsuarioCrear"
        );

        const mostrar =
            input.type === "password";

        input.type =
            mostrar ? "text" : "password";

        this.innerHTML = mostrar
            ? '<i class="fa-solid fa-eye-slash"></i>'
            : '<i class="fa-solid fa-eye"></i>';
    };
}


async function enviarInscripcion(event) {

    event.preventDefault();

    const formulario = event.currentTarget;

    const boton = document.getElementById(
        "btnGuardarNuevoUsuario"
    );

    const respuesta = document.getElementById(
        "respuestaUsuario"
    );

    if (!formulario.checkValidity()) {

        formulario.reportValidity();

        return;
    }

    boton.disabled = true;

    boton.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Registrando...
    `;

    respuesta.textContent = "";
    respuesta.className = "";

    try {

        const response = await fetch(
            "./php/crearCuenta.php",
            {
                method: "POST",
                body: new FormData(formulario)
            }
        );

        const resultado = await response.json();

        if (!comprobarRespuestaUsuario(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaUsuario(
                "respuestaUsuario",
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaUsuario(
            "respuestaUsuario",
            resultado.mensaje,
            true
        );

        formulario.reset();

        setTimeout(() => {

            cargar("usuarios");

        }, 900);

    } catch (error) {

        console.error(error);

        mostrarRespuestaUsuario(
            "respuestaUsuario",
            "No fue posible registrar el usuario.",
            false
        );

    } finally {

        boton.disabled = false;

        boton.innerHTML = `
            <i class="fa-solid fa-floppy-disk"></i>
            Registrar usuario
        `;
    }
}


/* =====================================================
   MODIFICAR USUARIO
===================================================== */

async function modificarUsuario(idUsuario) {

    const dialogo = document.getElementById(
        "dialogModificarUsuario"
    );

    if (!dialogo) {

        alert(
            "No se encontró el diálogo para modificar usuarios."
        );

        return;
    }

    try {

        const response = await fetch(
            `./php/obtenerUsuario.php?id=${
                encodeURIComponent(idUsuario)
            }&v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (!comprobarRespuestaUsuario(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        const usuario = resultado.datos;

        if (!usuario) {

            alert(
                "No se recibieron los datos del usuario."
            );

            return;
        }

        document.getElementById(
            "idUsuario"
        ).value = usuario.id_usuario;

        document.getElementById(
            "nombreModificar"
        ).value = usuario.nombre || "";

        document.getElementById(
            "apellidoModificar"
        ).value = usuario.apellido || "";

        document.getElementById(
            "correoModificar"
        ).value = usuario.correo || "";

        document.getElementById(
            "rolModificar"
        ).value = String(
            usuario.rol || ""
        ).toLowerCase();

        document.getElementById(
            "estadoModificar"
        ).value = String(
            usuario.estado || ""
        ).toLowerCase();

        const formulario =
            document.getElementById(
                "formModificarUsuario"
            );

        if (formulario) {

            formulario.onsubmit =
                function (event) {

                    event.preventDefault();
                    guardarUsuario();
                };
        }

        const respuesta =
            document.getElementById(
                "respuestaModificarUsuario"
            );

        if (respuesta) {
            respuesta.className = "";
            respuesta.textContent = "";
        }

        if (!dialogo.open) {
            dialogo.showModal();
        }

    } catch (error) {

        console.error(
            "Error cargando usuario:",
            error
        );

        alert(
            "No fue posible cargar la información del usuario."
        );
    }
}


async function guardarUsuario() {

    const idUsuario =
        document.getElementById("idUsuario");

    const nombre =
        document.getElementById("nombreModificar");

    const apellido =
        document.getElementById("apellidoModificar");

    const correo =
        document.getElementById("correoModificar");

    const rol =
        document.getElementById("rolModificar");

    const estado =
        document.getElementById("estadoModificar");

    const boton =
        document.getElementById(
            "btnGuardarModificarUsuario"
        );

    if (
        !idUsuario ||
        !nombre ||
        !apellido ||
        !correo ||
        !rol ||
        !estado
    ) {

        alert(
            "El formulario para modificar usuarios está incompleto."
        );

        return;
    }

    const datos = new FormData();

    datos.append(
        "id_usuario",
        idUsuario.value
    );

    datos.append(
        "nombre",
        nombre.value.trim()
    );

    datos.append(
        "apellido",
        apellido.value.trim()
    );

    datos.append(
        "correo",
        correo.value.trim()
    );

    datos.append(
        "rol",
        rol.value
    );

    datos.append(
        "estado",
        estado.value
    );

    if (boton) {

        boton.disabled = true;

        boton.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            Guardando...
        `;
    }

    try {

        const response = await fetch(
            "./php/modificarUsuario.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (!comprobarRespuestaUsuario(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarRespuestaUsuario(
                "respuestaModificarUsuario",
                resultado.mensaje,
                false
            );

            return;
        }

        mostrarRespuestaUsuario(
            "respuestaModificarUsuario",
            resultado.mensaje,
            true
        );

        await Promise.all([
            cargarUsuariosAdmin(),
            cargarResumenUsuarios()
        ]);

        setTimeout(() => {

            cerrarDialogModificarUsuario();

        }, 900);

    } catch (error) {

        console.error(error);

        mostrarRespuestaUsuario(
            "respuestaModificarUsuario",
            "No fue posible actualizar el usuario.",
            false
        );

    } finally {

        if (boton) {

            boton.disabled = false;

            boton.innerHTML = `
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar cambios
            `;
        }
    }
}


function cerrarDialogModificarUsuario() {

    const dialogo = document.getElementById(
        "dialogModificarUsuario"
    );

    if (dialogo && dialogo.open) {
        dialogo.close();
    }

    const formulario = document.getElementById(
        "formModificarUsuario"
    );

    if (formulario) {
        formulario.reset();
    }

    const respuesta = document.getElementById(
        "respuestaModificarUsuario"
    );

    if (respuesta) {
        respuesta.className = "";
        respuesta.textContent = "";
    }
}


/* =====================================================
   ELIMINAR USUARIO
===================================================== */

async function eliminarUsuario(
    idUsuario,
    correoUsuario
) {

    const confirmado = await confirmar(
        "Eliminar usuario",
        `¿Desea eliminar la cuenta "${correoUsuario}"?`
    );

    if (!confirmado) {
        return;
    }

    const datos = new FormData();

    datos.append(
        "id_usuario",
        idUsuario
    );

    try {

        const response = await fetch(
            "./php/eliminarUsuario.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (!comprobarRespuestaUsuario(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mensajeModal(
                "No se pudo eliminar",
                resultado.mensaje
            );

            return;
        }

        mensajeModal(
            "Usuario eliminado",
            resultado.mensaje
        );

        await Promise.all([
            cargarUsuariosAdmin(),
            cargarResumenUsuarios()
        ]);

    } catch (error) {

        console.error(
            "Error eliminando usuario:",
            error
        );

        mensajeModal(
            "Error",
            "No fue posible eliminar el usuario."
        );
    }
}


/* =====================================================
   RESPUESTAS Y UTILIDADES
===================================================== */

function mostrarRespuestaUsuario(
    idElemento,
    mensaje,
    correcto
) {

    const respuesta =
        document.getElementById(idElemento);

    if (!respuesta) {

        if (!correcto) {
            alert(mensaje);
        }

        return;
    }

    respuesta.className = correcto
        ? "respuestaUsuario correcta"
        : "respuestaUsuario error";

    respuesta.textContent = mensaje;
}


function obtenerInicialesUsuario(
    nombre,
    apellido
) {

    const primera =
        String(nombre || "")
            .trim()
            .charAt(0);

    const segunda =
        String(apellido || "")
            .trim()
            .charAt(0);

    return (
        primera + segunda
    ).toUpperCase() || "U";
}


function capitalizarUsuario(valor) {

    const texto = String(valor || "");

    if (!texto) {
        return "";
    }

    return (
        texto.charAt(0).toUpperCase() +
        texto.slice(1).toLowerCase()
    );
}


function formatearFechaUsuario(fecha) {

    if (!fecha) {
        return "Sin fecha";
    }

    const objetoFecha = new Date(
        String(fecha).replace(" ", "T")
    );

    if (
        Number.isNaN(
            objetoFecha.getTime()
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
    ).format(objetoFecha);
}


function escaparHTMLUsuario(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


function escaparAtributoUsuario(valor) {

    return escaparHTMLUsuario(valor);
}


document.addEventListener("click", function (event) {

    const dialogo =
        document.getElementById(
            "dialogModificarUsuario"
        );

    if (
        dialogo &&
        dialogo.open &&
        event.target === dialogo
    ) {
        cerrarDialogModificarUsuario();
    }
});