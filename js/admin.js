document.getElementById("bienvenido").textContent = localStorage.getItem("usuario");

/* =====================================================
   MENÚ ADMINISTRATIVO PARA TELÉFONOS
===================================================== */

function cambiarEstadoMenuAdmin(abrir) {

    const menu =
        document.getElementById(
            "menuLateralAdmin"
        );

    const fondo =
        document.getElementById(
            "fondoMenuAdmin"
        );

    const botonAbrir =
        document.getElementById(
            "btnAbrirMenuAdmin"
        );

    if (
        !menu ||
        !fondo ||
        !botonAbrir
    ) {
        return;
    }

    /*
     * Mostrar u ocultar el menú lateral.
     */

    menu.classList.toggle(
        "menuAdminVisible",
        abrir
    );

    /*
     * Mostrar u ocultar el fondo oscuro.
     */

    fondo.hidden = !abrir;

    /*
     * Bloquear el movimiento de la página
     * mientras el menú esté abierto.
     */

    document.body.classList.toggle(
        "menuAdminAbierto",
        abrir
    );

    /*
     * Información para accesibilidad.
     */

    botonAbrir.setAttribute(
        "aria-expanded",
        String(abrir)
    );
}


/* =====================================================
   INICIALIZAR MENÚ MÓVIL
===================================================== */

function inicializarMenuMovilAdmin() {

    const menu =
        document.getElementById(
            "menuLateralAdmin"
        );

    const botonAbrir =
        document.getElementById(
            "btnAbrirMenuAdmin"
        );

    const botonCerrar =
        document.getElementById(
            "btnCerrarMenuAdmin"
        );

    const fondo =
        document.getElementById(
            "fondoMenuAdmin"
        );

    if (
        !menu ||
        !botonAbrir ||
        !botonCerrar ||
        !fondo
    ) {
        return;
    }

    /*
     * Abrir con el botón hamburguesa.
     */

    botonAbrir.addEventListener(
        "click",
        function () {
            cambiarEstadoMenuAdmin(true);
        }
    );

    /*
     * Cerrar con el botón X.
     */

    botonCerrar.addEventListener(
        "click",
        function () {
            cambiarEstadoMenuAdmin(false);
        }
    );

    /*
     * Cerrar al tocar el fondo oscuro.
     */

    fondo.addEventListener(
        "click",
        function () {
            cambiarEstadoMenuAdmin(false);
        }
    );

    /*
     * Cerrar después de seleccionar una opción.
     *
     * Las categorías principales no cierran el menú,
     * porque primero deben mostrar su submenú.
     */

    menu.addEventListener(
        "click",
        function (evento) {

            const opcionSeleccionada =
                evento.target.closest(
                    ".menu-enlace, .submenu li"
                );

            if (
                window.innerWidth <= 900 &&
                opcionSeleccionada
            ) {
                cambiarEstadoMenuAdmin(false);
            }
        }
    );

    /*
     * Si el teléfono cambia a una pantalla grande,
     * limpiar el estado móvil del menú.
     */

    window.addEventListener(
        "resize",
        function () {

            if (
                window.innerWidth > 900
            ) {
                cambiarEstadoMenuAdmin(false);
            }
        }
    );

    /*
     * Cerrar con la tecla Escape.
     */

    document.addEventListener(
        "keydown",
        function (evento) {

            if (
                evento.key === "Escape"
            ) {
                cambiarEstadoMenuAdmin(false);
            }
        }
    );
}


/*
 * admin.js se carga al final de admin.php,
 * por lo que los elementos ya existen.
 */

inicializarMenuMovilAdmin();

function cargar(opcion){

    const contenido = document.getElementById("contenido");

     /* Marcar la opción seleccionada */
    document
        .querySelectorAll(".menu-admin li")
        .forEach(elemento => {
            elemento.classList.remove("active");
        });

    const opcionSeleccionada = document.querySelector(
        `[onclick="cargar('${opcion}')"]`
    );

    if (opcionSeleccionada) {
        opcionSeleccionada.classList.add("active");

        const categoria =
            opcionSeleccionada.closest(".menu-categoria");

        if (categoria) {
            document
                .querySelectorAll(".menu-categoria.abierta")
                .forEach(elemento => {
                    if (elemento !== categoria) {
                        elemento.classList.remove("abierta");
                    }
                });

            categoria.classList.add("abierta");
        }
    }

    switch(opcion){

        case "inicio":

            fetch("./inicioAdmin.html", {
                cache: "no-store"
            })
                .then(response => {

                    if (!response.ok) {
                        throw new Error(
                            "No se encontró inicioAdmin.html. HTTP " +
                            response.status
                        );
                    }

                    return response.text();
                })
                .then(html => {

                    const contenido =
                        document.getElementById("contenido");

                    contenido.innerHTML = html;

                    inicializarInicioAdmin();
                })
                .catch(error => {

                    console.error(
                        "Error cargando Inicio:",
                        error
                    );

                    document.getElementById("contenido").innerHTML = `
                        <div class="mensaje-error">
                            <strong>No fue posible cargar Inicio.</strong>
                            <br>
                            ${error.message}
                        </div>
                    `;
                });

        break;

        case "usuarios":

            contenido.innerHTML = `

                <section class="gestionUsuariosAdmin">

                    <!-- ENCABEZADO -->

                    <div class="encabezadoGestionUsuarios">

                        <div class="tituloGestionUsuarios">

                            <div class="iconoTituloUsuarios">
                                <i class="fa-solid fa-users"></i>
                            </div>

                            <div>
                                <h1>Gestión de usuarios</h1>

                                <p>
                                    Administre las cuentas, permisos
                                    y estados de acceso al sistema.
                                </p>
                            </div>

                        </div>

                        <button
                            type="button"
                            id="btnNuevoUsuarioAdmin"
                            class="btnNuevoUsuarioAdmin"
                            title="Registrar usuario">

                            <i class="fa-solid fa-plus"></i>

                        </button>

                    </div>


                    <!-- TARJETAS -->

                    <div class="resumenUsuariosAdmin">

                        <article
                            class="
                                tarjetaResumenUsuario
                                tarjetaTodosUsuarios
                                seleccionada
                            "
                            data-filtro="Todos">

                            <div class="iconoResumenUsuario">
                                <i class="fa-solid fa-users"></i>
                            </div>

                            <div>
                                <small>Total de usuarios</small>
                                <strong id="totalUsuariosAdmin">0</strong>
                            </div>

                        </article>

                        <article
                            class="
                                tarjetaResumenUsuario
                                tarjetaUsuariosActivos
                            "
                            data-filtro="activo">

                            <div class="iconoResumenUsuario">
                                <i class="fa-solid fa-user-check"></i>
                            </div>

                            <div>
                                <small>Usuarios activos</small>
                                <strong id="usuariosActivosAdmin">0</strong>
                            </div>

                        </article>

                        <article
                            class="
                                tarjetaResumenUsuario
                                tarjetaUsuariosInactivos
                            "
                            data-filtro="inactivo">

                            <div class="iconoResumenUsuario">
                                <i class="fa-solid fa-user-slash"></i>
                            </div>

                            <div>
                                <small>Usuarios inactivos</small>
                                <strong id="usuariosInactivosAdmin">0</strong>
                            </div>

                        </article>

                        <article
                            class="
                                tarjetaResumenUsuario
                                tarjetaAdministradores
                            "
                            data-filtro="administrador">

                            <div class="iconoResumenUsuario">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>

                            <div>
                                <small>Administradores</small>
                                <strong id="administradoresAdmin">0</strong>
                            </div>

                        </article>

                        <article
                            class="
                                tarjetaResumenUsuario
                                tarjetaEmpleados
                            "
                            data-filtro="empleado">

                            <div class="iconoResumenUsuario">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>

                            <div>
                                <small>Empleados</small>
                                <strong id="empleadosAdmin">0</strong>
                            </div>

                        </article>

                    </div>


                    <!-- PESTAÑAS -->

                    <div class="contenedorPestanasUsuarios">

                        <div class="pestanasUsuariosAdmin">

                            <button
                                type="button"
                                class="btnPestanaUsuario activa"
                                data-estado="Todos">
                                Todos
                            </button>

                            <button
                                type="button"
                                class="btnPestanaUsuario"
                                data-estado="activo">
                                Activos
                            </button>

                            <button
                                type="button"
                                class="btnPestanaUsuario"
                                data-estado="inactivo">
                                Inactivos
                            </button>

                            <button
                                type="button"
                                class="btnPestanaUsuario"
                                data-estado="administrador">
                                Administradores
                            </button>

                            <button
                                type="button"
                                class="btnPestanaUsuario"
                                data-estado="cajero">
                                Cajeros
                            </button>

                            <button
                                type="button"
                                class="btnPestanaUsuario"
                                data-estado="vendedor">
                                Vendedores
                            </button>

                            <button
                                type="button"
                                class="btnPestanaUsuario"
                                data-estado="mecanico">
                                Mecanicos
                            </button>

                        </div>

                    </div>


                    <!-- FILTROS -->

                    <div class="barraFiltrosUsuariosAdmin">

                        <div class="buscadorUsuarioAdmin">

                            <label for="buscarUsuarioAdmin">
                                Buscar
                            </label>

                            <div class="contenedorBuscarUsuario">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="search"
                                    id="buscarUsuarioAdmin"
                                    placeholder="Nombre o correo..."
                                    autocomplete="off">

                            </div>

                        </div>


                        <div class="grupoFiltroUsuario">

                            <label for="filtroRolUsuario">
                                Rol
                            </label>

                            <select id="filtroRolUsuario">

                                <option value="">
                                    Todos los roles
                                </option>

                                <option value="administrador">
                                    Administrador
                                </option>

                                <option value="administrador">
                                    cajero
                                </option>


                            </select>

                        </div>


                        <div class="grupoFiltroUsuario">

                            <label for="filtroEstadoUsuario">
                                Estado
                            </label>

                            <select id="filtroEstadoUsuario">

                                <option value="">
                                    Todos los estados
                                </option>

                                <option value="activo">
                                    Activo
                                </option>

                                <option value="inactivo">
                                    Inactivo
                                </option>

                            </select>

                        </div>


                        <div class="grupoFiltroUsuario">

                            <label for="ordenUsuarioAdmin">
                                Ordenar
                            </label>

                            <select id="ordenUsuarioAdmin">

                                <option value="nombre_asc">
                                    Nombre A–Z
                                </option>

                                <option value="nombre_desc">
                                    Nombre Z–A
                                </option>

                                <option value="correo_asc">
                                    Correo A–Z
                                </option>

                                <option value="correo_desc">
                                    Correo Z–A
                                </option>

                                <option value="recientes">
                                    Más recientes
                                </option>

                                <option value="antiguos">
                                    Más antiguos
                                </option>

                            </select>

                        </div>


                        <button
                            type="button"
                            id="btnLimpiarFiltrosUsuario">

                            <i class="fa-solid fa-filter-circle-xmark"></i>

                            Limpiar filtros

                        </button>

                    </div>


                    <!-- RESULTADOS -->

                    <div class="encabezadoResultadosUsuarios">

                        <div>
                            <h2 id="tituloResultadosUsuarios">
                                Todos los usuarios
                            </h2>

                            <p id="descripcionResultadosUsuarios">
                                Listado completo de usuarios registrados.
                            </p>
                        </div>

                        <div class="contadorResultadosUsuarios">

                            <strong id="cantidadResultadosUsuarios">
                                0
                            </strong>

                            <span id="textoResultadosUsuarios">
                                resultados
                            </span>

                        </div>

                    </div>


                    <!-- TABLA -->

                    <div class="contenedorTablaUsuarios">

                        <table id="table_usuario">

                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Correo electrónico</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Fecha de registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="tbody_usuario"></tbody>

                        </table>

                    </div>


                    <!-- SIN RESULTADOS -->

                    <div id="sinUsuariosAdmin">

                        <i class="fa-solid fa-users-slash"></i>

                        <h3>No se encontraron usuarios</h3>

                        <p>
                            Pruebe cambiando los filtros
                            o el texto de búsqueda.
                        </p>

                    </div>

                </section>
            `;

            inicializarGestionUsuarios();

        break;

        case "servicios":

        contenido.innerHTML = `

            <section class="seccionGestionServicios">

                <!-- ENCABEZADO -->

                <div class="encabezadoGestionServicios">

                    <div>
                        <h1>
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            Gestión de servicios
                        </h1>

                        <p>
                            Administre los servicios ofrecidos por el taller.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="btnNuevoServicioAdmin"
                        class="btnNuevoServicioAdmin"
                        title="Crear servicio">

                        <i class="fa-solid fa-plus"></i>

                    </button>

                </div>

                <!-- TARJETAS -->

                <div class="resumenServiciosAdmin">

                    <article
                        class="tarjetaResumenServicio tarjetaTodosServicios"
                        data-filtro="Todos">

                        <div class="iconoResumenServicio">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>

                        <div>
                            <small>Total de servicios</small>
                            <strong id="totalServiciosAdmin">0</strong>
                        </div>

                    </article>

                    <article
                        class="tarjetaResumenServicio tarjetaServiciosActivos"
                        data-filtro="Activo">

                        <div class="iconoResumenServicio">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>

                        <div>
                            <small>Servicios activos</small>
                            <strong id="serviciosActivosAdmin">0</strong>
                        </div>

                    </article>

                    <article
                        class="tarjetaResumenServicio tarjetaServiciosOcultos"
                        data-filtro="Oculto">

                        <div class="iconoResumenServicio">
                            <i class="fa-solid fa-eye-slash"></i>
                        </div>

                        <div>
                            <small>Ocultos en reservas</small>
                            <strong id="serviciosOcultosAdmin">0</strong>
                        </div>

                    </article>

                    <article
                        class="tarjetaResumenServicio tarjetaServiciosDestacados"
                        data-filtro="Destacado">

                        <div class="iconoResumenServicio">
                            <i class="fa-solid fa-star"></i>
                        </div>

                        <div>
                            <small>Servicios destacados</small>
                            <strong id="serviciosDestacadosAdmin">0</strong>
                        </div>

                    </article>

                    <article
                        class="tarjetaResumenServicio tarjetaPrecioPromedioServicio">

                        <div class="iconoResumenServicio">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>

                        <div>
                            <small>Precio mínimo promedio</small>
                            <strong id="precioPromedioServiciosAdmin">$0</strong>
                        </div>

                    </article>

                </div>

                <!-- PESTAÑAS Y BUSCADOR -->

                <div class="barraServiciosAdmin">

                    <div class="pestanasServiciosAdmin">

                        <button
                            type="button"
                            class="btnPestanaServicio activa"
                            data-estado="Todos">

                            Todos

                        </button>

                        <button
                            type="button"
                            class="btnPestanaServicio"
                            data-estado="Activo">

                            Activos

                        </button>

                        <button
                            type="button"
                            class="btnPestanaServicio"
                            data-estado="Inactivo">

                            Inactivos

                        </button>

                        <button
                            type="button"
                            class="btnPestanaServicio"
                            data-estado="Oculto">

                            Ocultos

                        </button>

                        <button
                            type="button"
                            class="btnPestanaServicio"
                            data-estado="Destacado">

                            Destacados

                        </button>

                    </div>

                    <div class="buscadorServiciosAdmin">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="buscarServicioAdmin"
                            placeholder="Buscar servicio...">

                    </div>

                </div>

                <!-- FILTROS -->

                <div class="filtrosServiciosAdmin">

                    <select id="filtroDuracionServicio">

                        <option value="">
                            Todas las duraciones
                        </option>

                        <option value="corta">
                            Hasta 30 minutos
                        </option>

                        <option value="media">
                            De 31 a 60 minutos
                        </option>

                        <option value="larga">
                            Más de 60 minutos
                        </option>

                    </select>

                    <select id="ordenServicioAdmin">

                        <option value="nombre_asc">
                            Nombre: A–Z
                        </option>

                        <option value="nombre_desc">
                            Nombre: Z–A
                        </option>

                        <option value="precio_menor">
                            Menor precio
                        </option>

                        <option value="precio_mayor">
                            Mayor precio
                        </option>

                        <option value="duracion_menor">
                            Menor duración
                        </option>

                        <option value="duracion_mayor">
                            Mayor duración
                        </option>

                        <option value="actualizado">
                            Actualizados recientemente
                        </option>

                    </select>

                    <button
                        type="button"
                        id="btnLimpiarFiltrosServicio">

                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros

                    </button>

                </div>

                <!-- INFORMACIÓN DE RESULTADOS -->

                <div class="encabezadoResultadosServicios">

                    <div>
                        <h2 id="tituloResultadosServicios">
                            Todos los servicios
                        </h2>

                        <p id="descripcionResultadosServicios">
                            Catálogo completo de servicios registrados.
                        </p>
                    </div>

                    <p class="cantidadResultadosServicios">

                        <span id="cantidadResultadosServicios">0</span>

                        <span id="textoResultadosServicios">
                            resultados
                        </span>.

                    </p>

                </div>

                <!-- TABLA -->

                <div class="contenedorTablaServicios">

                    <table id="table_servicio">

                        <thead>

                            <tr>
                                <th>Imagen</th>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th>Duración</th>
                                <th>Precio mínimo</th>
                                <th>Reservas</th>
                                <th>Destacado</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>

                        </thead>

                        <tbody id="tbody_servicio"></tbody>

                    </table>

                </div>

                <!-- SIN RESULTADOS -->

                <div
                    id="sinServiciosAdmin"
                    class="sinServiciosAdmin">

                    <i class="fa-solid fa-screwdriver-wrench"></i>

                    <h3>No se encontraron servicios</h3>

                    <p>
                        Intente cambiar los filtros o el texto de búsqueda.
                    </p>

                </div>

            </section>
        `;

        document
            .getElementById("btnNuevoServicioAdmin")
            .addEventListener("click", registrarServicio);

        acceso(
            localStorage.getItem("rol"),
            document.getElementById("btnNuevoServicioAdmin")
        );

        inicializarGestionServicios();

    break;

        case "categorias":

                contenido.innerHTML = `

                    <section class="seccionGestionCategorias">

                        <!-- ENCABEZADO -->

                        <div class="encabezadoGestionCategorias">

                            <div>

                                <h1>
                                    <i class="fa-solid fa-folder-tree"></i>
                                    Gestión de categorías
                                </h1>

                                <p>
                                    Administre la clasificación de productos de la tienda.
                                </p>

                            </div>

                            <button
                                type="button"
                                id="btnNuevaCategoriaAdmin"
                                class="btnNuevaCategoriaAdmin"
                                title="Crear categoría">

                                <i class="fa-solid fa-plus"></i>

                            </button>

                        </div>

                        <!-- TARJETAS -->

                        <div class="resumenCategoriasAdmin">

                            <article
                                class="tarjetaResumenCategoria tarjetaTodasCategorias"
                                data-filtro="Todas">

                                <div class="iconoResumenCategoria">

                                    <i class="fa-solid fa-folder-tree"></i>

                                </div>

                                <div>

                                    <small>Total de categorías</small>

                                    <strong id="totalCategoriasAdmin">
                                        0
                                    </strong>

                                </div>

                            </article>

                            <article
                                class="tarjetaResumenCategoria tarjetaCategoriasVisibles"
                                data-filtro="Visible">

                                <div class="iconoResumenCategoria">

                                    <i class="fa-solid fa-eye"></i>

                                </div>

                                <div>

                                    <small>Categorías visibles</small>

                                    <strong id="categoriasVisiblesAdmin">
                                        0
                                    </strong>

                                </div>

                            </article>

                            <article
                                class="tarjetaResumenCategoria tarjetaCategoriasOcultas"
                                data-filtro="Oculta">

                                <div class="iconoResumenCategoria">

                                    <i class="fa-solid fa-eye-slash"></i>

                                </div>

                                <div>

                                    <small>Categorías ocultas</small>

                                    <strong id="categoriasOcultasAdmin">
                                        0
                                    </strong>

                                </div>

                            </article>

                            <article
                                class="tarjetaResumenCategoria tarjetaCategoriasVacias"
                                data-filtro="Vacía">

                                <div class="iconoResumenCategoria">

                                    <i class="fa-solid fa-folder-open"></i>

                                </div>

                                <div>

                                    <small>Sin productos</small>

                                    <strong id="categoriasVaciasAdmin">
                                        0
                                    </strong>

                                </div>

                            </article>

                            <article
                                class="tarjetaResumenCategoria tarjetaProductosClasificados">

                                <div class="iconoResumenCategoria">

                                    <i class="fa-solid fa-boxes-stacked"></i>

                                </div>

                                <div>

                                    <small>Productos clasificados</small>

                                    <strong id="productosClasificadosAdmin">
                                        0
                                    </strong>

                                </div>

                            </article>

                        </div>

                        <!-- PESTAÑAS -->

                        <div class="barraCategoriasAdmin">

                            <div class="pestanasCategoriasAdmin">

                                <button
                                    type="button"
                                    class="btnPestanaCategoria activa"
                                    data-estado="Todas">

                                    Todas

                                </button>

                                <button
                                    type="button"
                                    class="btnPestanaCategoria"
                                    data-estado="Visible">

                                    Visibles

                                </button>

                                <button
                                    type="button"
                                    class="btnPestanaCategoria"
                                    data-estado="Oculta">

                                    Ocultas

                                </button>

                                <button
                                    type="button"
                                    class="btnPestanaCategoria"
                                    data-estado="Vacía">

                                    Sin productos

                                </button>

                            </div>

                        </div>

                        <!-- BUSCADOR Y FILTROS -->

                        <div class="filtrosCategoriasAdmin">

                            <div class="grupoFiltroCategoria grupoBuscadorCategoria">

                                <label for="buscarCategoriaAdmin">
                                    Buscar
                                </label>

                                <div class="buscadorCategoriasAdmin">

                                    <i class="fa-solid fa-magnifying-glass"></i>

                                    <input
                                        type="search"
                                        id="buscarCategoriaAdmin"
                                        placeholder="Nombre, descripción o slug..."
                                        autocomplete="off">

                                </div>

                            </div>

                            <div class="grupoFiltroCategoria">

                                <label for="ordenCategoriaAdmin">
                                    Ordenar
                                </label>

                                <select id="ordenCategoriaAdmin">

                                    <option value="orden">
                                        Orden de la tienda
                                    </option>

                                    <option value="nombre_asc">
                                        Nombre A–Z
                                    </option>

                                    <option value="nombre_desc">
                                        Nombre Z–A
                                    </option>

                                    <option value="productos_mayor">
                                        Más productos
                                    </option>

                                    <option value="productos_menor">
                                        Menos productos
                                    </option>

                                    <option value="actualizado">
                                        Actualizadas recientemente
                                    </option>

                                </select>

                            </div>

                            <button
                                type="button"
                                id="btnLimpiarFiltrosCategoria">

                                <i class="fa-solid fa-filter-circle-xmark"></i>
                                Limpiar filtros

                            </button>

                        </div>

                        <!-- RESULTADOS -->

                        <div class="encabezadoResultadosCategorias">

                            <div>

                                <h2 id="tituloResultadosCategorias">
                                    Todas las categorías
                                </h2>

                                <p id="descripcionResultadosCategorias">
                                    Catálogo completo de categorías registradas.
                                </p>

                            </div>

                            <p class="cantidadResultadosCategorias">

                                <span id="cantidadResultadosCategorias">
                                    0
                                </span>

                                <span id="textoResultadosCategorias">
                                    resultados
                                </span>.

                            </p>

                        </div>

                        <!-- TABLA -->

                        <div class="contenedorTablaCategorias">

                            <table id="table_categoria">

                                <thead>

                                    <tr>

                                        <th>ID</th>

                                        <th>Categoría</th>

                                        <th>Descripción</th>

                                        <th>Slug</th>

                                        <th>Productos</th>

                                        <th>Orden</th>

                                        <th>Visibilidad</th>

                                        <th>Actualización</th>

                                        <th>Acciones</th>

                                    </tr>

                                </thead>

                                <tbody id="tbody_categoria"></tbody>

                            </table>

                        </div>

                        <!-- SIN RESULTADOS -->

                        <div
                            id="sinCategoriasAdmin"
                            class="sinCategoriasAdmin">

                            <i class="fa-solid fa-folder-open"></i>

                            <h3>No se encontraron categorías</h3>

                            <p>
                                Intente cambiar la búsqueda o los filtros.
                            </p>

                        </div>

                    </section>
                `;

                const botonNuevaCategoria =
                    document.getElementById(
                        "btnNuevaCategoriaAdmin"
                    );

                botonNuevaCategoria.addEventListener(
                    "click",
                    registrarCategoria
                );

                acceso(
                    localStorage.getItem("rol"),
                    botonNuevaCategoria
                );

                inicializarGestionCategorias();

            break;

case "productos":

            contenido.innerHTML = `

                <section class="seccionGestionProductos">

                    <!-- ENCABEZADO -->

                    <div class="encabezadoGestionProductos">

                        <div>

                            <h1>
                                <i class="fa-solid fa-boxes-stacked"></i>
                                Gestión de productos
                            </h1>

                            <p>
                                Administre el catálogo, los precios y el inventario.
                            </p>

                        </div>

                        <button
                            type="button"
                            id="btnNuevoProductoAdmin"
                            class="btnNuevoProductoAdmin"
                            title="Crear producto">

                            <i class="fa-solid fa-plus"></i>

                        </button>

                    </div>

                    <!-- TARJETAS -->

                    <div class="resumenProductosAdmin">

                        <article
                            class="tarjetaResumenProducto tarjetaTodosProductos"
                            data-filtro="Todos">

                            <div class="iconoResumenProducto">

                                <i class="fa-solid fa-boxes-stacked"></i>

                            </div>

                            <div>

                                <small>Total de productos</small>

                                <strong id="totalProductosAdmin">
                                    0
                                </strong>

                            </div>

                        </article>

                        <article
                            class="tarjetaResumenProducto tarjetaUnidadesProductos"
                            data-filtro="Disponible">

                            <div class="iconoResumenProducto">

                                <i class="fa-solid fa-cubes"></i>

                            </div>

                            <div>

                                <small>Unidades disponibles</small>

                                <strong id="unidadesProductosAdmin">
                                    0
                                </strong>

                            </div>

                        </article>

                        <article
                            class="tarjetaResumenProducto tarjetaBajoStock"
                            data-filtro="Bajo stock">

                            <div class="iconoResumenProducto">

                                <i class="fa-solid fa-triangle-exclamation"></i>

                            </div>

                            <div>

                                <small>Bajo stock</small>

                                <strong id="bajoStockProductosAdmin">
                                    0
                                </strong>

                            </div>

                        </article>

                        <article
                            class="tarjetaResumenProducto tarjetaAgotados"
                            data-filtro="Agotado">

                            <div class="iconoResumenProducto">

                                <i class="fa-solid fa-box-open"></i>

                            </div>

                            <div>

                                <small>Agotados</small>

                                <strong id="agotadosProductosAdmin">
                                    0
                                </strong>

                            </div>

                        </article>

                        <article
                            class="tarjetaResumenProducto tarjetaProductosOferta"
                            data-filtro="Oferta"
                            role="button"
                            tabindex="0"
                            title="Mostrar productos con oferta vigente">

                            <div class="iconoResumenProducto">
                                <i class="fa-solid fa-tags"></i>
                            </div>

                            <div>
                                <small>Productos en oferta</small>
                                <strong id="productosOfertaAdmin">0</strong>
                            </div>

                        </article>

                    </div>

                    <!-- PESTAÑAS -->

                    <div class="barraProductosAdmin">

                        <div class="pestanasProductosAdmin">

                            <button
                                type="button"
                                class="btnPestanaProducto activa"
                                data-estado="Todos">

                                Todos

                            </button>

                            <button
                                type="button"
                                class="btnPestanaProducto"
                                data-estado="Disponible">

                                Disponibles

                            </button>

                            <button
                                type="button"
                                class="btnPestanaProducto"
                                data-estado="Bajo stock">

                                Bajo stock

                            </button>

                            <button
                                type="button"
                                class="btnPestanaProducto"
                                data-estado="Agotado">

                                Agotados

                            </button>

                            <button
                                type="button"
                                class="btnPestanaProducto"
                                data-estado="Oculto">

                                Ocultos

                            </button>

                        </div>

                    </div>

                    <!-- BUSCADOR Y FILTROS -->

                    <div class="filtrosProductosAdmin">

                        <!-- BUSCADOR -->

                        <div class="grupoFiltroProducto grupoBuscadorProducto">

                            <label for="buscarProductoAdmin">
                                Buscar
                            </label>

                            <div class="buscadorProductosAdmin">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="search"
                                    id="buscarProductoAdmin"
                                    placeholder="SKU, producto o marca..."
                                    autocomplete="off"
                                    aria-label="Buscar productos">

                            </div>

                        </div>

                        <!-- CATEGORÍA -->

                        <div class="grupoFiltroProducto">

                            <label for="filtroCategoriaProducto">
                                Categoría
                            </label>

                            <select id="filtroCategoriaProducto">

                                <option value="">
                                    Todas las categorías
                                </option>

                            </select>

                        </div>

                        <!-- MARCA -->

                        <div class="grupoFiltroProducto">

                            <label for="filtroMarcaProducto">
                                Marca
                            </label>

                            <select id="filtroMarcaProducto">

                                <option value="">
                                    Todas las marcas
                                </option>

                            </select>

                        </div>

                        <!-- ORDEN -->

                        <div class="grupoFiltroProducto">

                            <label for="ordenProductoAdmin">
                                Ordenar
                            </label>

                            <select id="ordenProductoAdmin">

                                <option value="nombre_asc">
                                    Nombre A-Z
                                </option>

                                <option value="nombre_desc">
                                    Nombre Z-A
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

                                <option value="actualizado">
                                    Actualizados recientemente
                                </option>

                            </select>

                        </div>

                        <!-- LIMPIAR -->

                        <button
                            type="button"
                            id="btnLimpiarFiltrosProducto">

                            <i class="fa-solid fa-filter-circle-xmark"></i>

                            Limpiar filtros

                        </button>

                    </div>

                    <!-- RESULTADOS -->

                    <div class="encabezadoResultadosProductos">

                        <div>

                            <h2 id="tituloResultadosProductos">
                                Todos los productos
                            </h2>

                            <p id="descripcionResultadosProductos">
                                Catálogo completo de productos registrados.
                            </p>

                        </div>

                        <p class="cantidadResultadosProductos">

                            <span id="cantidadResultadosProductos">
                                0
                            </span>

                            <span id="textoResultadosProductos">
                                resultados
                            </span>.

                        </p>

                    </div>

                    <!-- TABLA -->

                    <div class="contenedorTablaProductos">

                        <table id="table_producto">

                            <thead>

                                <tr>

                                    <th>Imagen</th>

                                    <th>Producto</th>

                                    <th>Categoría</th>

                                    <th>Marca</th>

                                    <th>Stock</th>

                                    <th>P. compra</th>

                                    <th>P. venta</th>

                                    <th>Margen</th>

                                    <th>Estado</th>

                                    <th>Acciones</th>

                                </tr>

                            </thead>

                            <tbody id="tbody_producto"></tbody>

                        </table>

                    </div>

                    <!-- SIN RESULTADOS -->

                    <div
                        id="sinProductosAdmin"
                        class="sinProductosAdmin">

                        <i class="fa-solid fa-box-open"></i>

                        <h3>No se encontraron productos</h3>

                        <p>
                            Intente cambiar la búsqueda o los filtros seleccionados.
                        </p>

                    </div>

                </section>
            `;

            const botonNuevoProducto = document.getElementById(
                "btnNuevoProductoAdmin"
            );

            botonNuevoProducto.addEventListener(
                "click",
                registrarProducto
            );

            acceso(
                localStorage.getItem("rol"),
                botonNuevoProducto
            );

            inicializarGestionProductos();

        break;


        case "ventas":

            iniciarVentaPresencial();

        break;

        case "cotizaciones":

            inicializarGestionCotizaciones();

        break;

       case "citas":

    contenido.innerHTML = `

        <section class="seccionGestionCitas">

           <div class="tituloGestionCitas">

                <div class="tituloConBotonCita">

                    <div>
                        <h1>Gestión de citas</h1>

                        <p>
                            Administra las reservas y órdenes de trabajo del taller.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="btnNuevaCitaAdmin"
                        class="btnNuevaCitaAdmin"
                        title="Reservar una nueva cita"
                        aria-label="Reservar una nueva cita">

                        <i class="fa-solid fa-plus"></i>

                    </button>

                </div>

                <div class="fechaActualCitas">

                    <i class="fa-solid fa-calendar-day"></i>

                    <span id="textoFechaActual"></span>

                </div>

            </div>

            <!-- Tarjetas de resumen -->
            <div class="resumenCitas">

                <article class="tarjetaResumenCita tarjetaCitasHoy tarjetaCitaFiltro" data-estado="Todas">

                    <div class="iconoResumenCita">
                        <i class="fa-regular fa-calendar"></i>
                    </div>

                    <div>
                        <small>Citas de hoy</small>
                        <strong id="cantidadCitasHoy">0</strong>
                    </div>

                </article>

                <article class="tarjetaResumenCita tarjetaPendientes tarjetaCitaFiltro" data-estado="Pendiente">

                    <div class="iconoResumenCita">
                        <i class="fa-regular fa-clock"></i>
                    </div>

                    <div>
                        <small>Pendientes</small>
                        <strong id="cantidadPendientes">0</strong>
                    </div>

                </article>

                <article class="tarjetaResumenCita tarjetaConfirmadas tarjetaCitaFiltro" data-estado="Confirmada">

                    <div class="iconoResumenCita">
                        <i class="fa-regular fa-circle-check"></i>
                    </div>

                    <div>
                        <small>Confirmadas</small>
                        <strong id="cantidadConfirmadas">0</strong>
                    </div>

                </article>

                <article class="tarjetaResumenCita tarjetaEnProceso tarjetaCitaFiltro" data-estado="En proceso">

                    <div class="iconoResumenCita">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>

                    <div>
                        <small>En proceso</small>
                        <strong id="cantidadEnProceso">0</strong>
                    </div>

                </article>

                <article class="tarjetaResumenCita tarjetaFinalizadas tarjetaCitaFiltro" data-estado="Finalizada">

                    <div class="iconoResumenCita">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <div>
                        <small>Finalizadas</small>
                        <strong id="cantidadFinalizadas">0</strong>
                    </div>

                </article>

            </div>

            <!-- Navegación y buscador -->
            <div class="barraGestionCitas">

                <div class="pestanasGestionCitas">

                    <button
                        type="button"
                        id="btnHoy"
                        class="btnPestanaCita pestanaCitaActiva">

                        Hoy

                    </button>

                    <button
                        type="button"
                        id="btnProximas"
                        class="btnPestanaCita">

                        Próximas

                    </button>

                    <button
                        type="button"
                        id="btnCalendario"
                        class="btnPestanaCita">

                        Calendario

                    </button>

                    <button
                        type="button"
                        id="btnHistorial"
                        class="btnPestanaCita">

                        Historial

                    </button>

                </div>

                <div class="buscadorGestionCitas">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="buscarCitaAdmin"
                        placeholder="Buscar reserva, cliente o patente"
                        autocomplete="off">

                </div>

            </div>

            <!-- Panel para consultar fechas -->
            <div id="panelCalendario" class="panelCalendarioAdmin">

                <div class="campoFechaCalendario">

                    <label for="fechaInicio">Desde</label>

                    <input
                        type="date"
                        id="fechaInicio">

                </div>

                <div class="campoFechaCalendario">

                    <label for="fechaFin">Hasta</label>

                    <input
                        type="date"
                        id="fechaFin">

                </div>

                <button
                    type="button"
                    id="buscarRango"
                    class="btnBuscarCalendario">

                    <i class="fa-solid fa-magnifying-glass"></i>
                    Buscar

                </button>

            </div>

            <!-- Mensaje de vista actual -->
            <div class="encabezadoTablaCitas">

                <div>
                    <h2 id="tituloTablaCitas">Citas de hoy</h2>
                    <p id="descripcionTablaCitas">
                        Reservas programadas para la fecha actual.
                    </p>
                </div>

                <span id="totalResultadosCitas">
                    0 resultados
                </span>

            </div>

            <!-- Tabla -->
            <div class="contenedorTablaCitas">

                <table id="table_citas" class="tablaGestionCitas">

                    <thead>

                        <tr>
                            <th>N.º reserva</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody id="tbody_cita"></tbody>

                </table>

                <div id="sinCitasAdmin" class="sinCitasAdmin">

                    <i class="fa-regular fa-calendar-xmark"></i>

                    <h3>No hay citas para mostrar</h3>

                    <p>No se encontraron reservas en esta sección.</p>

                </div>

            </div>

        </section>
    `;

    document.getElementById("btnNuevaCitaAdmin").addEventListener("click", abrirDialogNuevaCita);

    document.getElementById("btnHoy").addEventListener("click", cargarCitasHoy);

    document.getElementById("btnProximas").addEventListener("click", cargarCitasProximas);

    document.getElementById("btnCalendario").addEventListener("click", mostrarCalendario);

    document.getElementById("btnHistorial").addEventListener("click", cargarHistorialCitas);

    document.getElementById("buscarRango").addEventListener("click", buscarCitasPorRango);

    document.getElementById("buscarCitaAdmin").addEventListener("input", filtrarCitasAdmin);

    document.querySelectorAll(".tarjetaCitaFiltro").forEach(tarjeta => {
        tarjeta.addEventListener("click",filtrarCitasPorTarjeta);
    });

    mostrarFechaActualCitas();

    cargarCitasHoy();

break;

        case "pedidos":

            contenido.innerHTML = `

                <section class="gestionPedidosAdmin">

                    <!-- ==========================================
                        ENCABEZADO
                    =========================================== -->

                    <header class="encabezadoModuloPedidos">

                        <div class="tituloModuloPedidos">

                            <div class="iconoTituloPedidos">
                                <i class="fa-solid fa-box-open"></i>
                            </div>

                            <div>

                                <h1>Gestión de pedidos</h1>

                                <p>
                                    Administre las ventas en línea,
                                    pagos y entregas de productos.
                                </p>

                            </div>

                        </div>


                    </header>


                    <!-- ==========================================
                        TARJETAS DE RESUMEN
                    =========================================== -->

                    <div class="resumenPedidosAdmin">

                        <article
                            class="tarjetaResumenPedido seleccionada"
                            data-filtro="Todos">

                            <div class="iconoResumenPedido total">

                                <i class="fa-solid fa-receipt"></i>

                            </div>

                            <div>

                                <span>Total de pedidos</span>

                                <strong id="totalPedidosAdmin">
                                    0
                                </strong>

                            </div>

                        </article>


                        <article
                            class="tarjetaResumenPedido"
                            data-filtro="pendiente_pago">

                            <div class="iconoResumenPedido pendiente">

                                <i class="fa-solid fa-clock"></i>

                            </div>

                            <div>

                                <span>Pendientes de pago</span>

                                <strong id="pedidosPendientesAdmin">
                                    0
                                </strong>

                            </div>

                        </article>


                        <article
                            class="tarjetaResumenPedido"
                            data-filtro="pagado">

                            <div class="iconoResumenPedido pagado">

                                <i class="fa-solid fa-circle-check"></i>

                            </div>

                            <div>

                                <span>Pagados</span>

                                <strong id="pedidosPagadosAdmin">
                                    0
                                </strong>

                            </div>

                        </article>


                        <article
                            class="tarjetaResumenPedido"
                            data-filtro="preparando">

                            <div class="iconoResumenPedido preparando">

                                <i class="fa-solid fa-box"></i>

                            </div>

                            <div>

                                <span>En preparación</span>

                                <strong id="pedidosPreparandoAdmin">
                                    0
                                </strong>

                            </div>

                        </article>


                        <article
                            class="tarjetaResumenPedido"
                            data-filtro="listo_retiro">

                            <div class="iconoResumenPedido listo">

                                <i class="fa-solid fa-store"></i>

                            </div>

                            <div>

                                <span>Listos para retirar</span>

                                <strong id="pedidosListosAdmin">
                                    0
                                </strong>

                            </div>

                        </article>


                        <article class="tarjetaResumenPedido ventas">

                            <div class="iconoResumenPedido ingresos">

                                <i class="fa-solid fa-sack-dollar"></i>

                            </div>

                            <div>

                                <span>Ventas del mes</span>

                                <strong id="ventasMesPedidosAdmin">
                                    $0
                                </strong>

                                <small id="ventasHoyPedidosAdmin">
                                    Hoy: $0
                                </small>

                            </div>

                        </article>

                    </div>


                    <!-- ==========================================
                        PESTAÑAS
                    =========================================== -->

                    <nav class="pestanasPedidosAdmin">

                        <button
                            type="button"
                            class="btnPestanaPedido activa"
                            data-estado="Todos">

                            Todos

                        </button>

                        <button
                            type="button"
                            class="btnPestanaPedido"
                            data-estado="pendiente_pago">

                            Pendientes

                        </button>

                        <button
                            type="button"
                            class="btnPestanaPedido"
                            data-estado="pagado">

                            Pagados

                        </button>

                        <button
                            type="button"
                            class="btnPestanaPedido"
                            data-estado="preparando">

                            Preparando

                        </button>

                        <button
                            type="button"
                            class="btnPestanaPedido"
                            data-estado="listo_retiro">

                            Listos para retirar

                        </button>

                        <button
                            type="button"
                            class="btnPestanaPedido"
                            data-estado="entregado">

                            Entregados

                        </button>

                        <button
                            type="button"
                            class="btnPestanaPedido"
                            data-estado="cancelado">

                            Cancelados

                        </button>

                        <button
                            type="button"
                            class="btnPestanaPedido"
                            data-estado="cancelacion_solicitada">
                            Cancelación solicitada
                        </button>

                    </nav>


                    <!-- ==========================================
                        FILTROS
                    =========================================== -->

                    <div class="filtrosPedidosAdmin">

                        <div class="campoFiltroPedido buscadorPedido">

                            <label for="buscarPedidoAdmin">
                                Buscar pedido
                            </label>

                            <div class="inputBusquedaPedido">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="search"
                                    id="buscarPedidoAdmin"
                                    placeholder="Pedido, cliente, correo o teléfono">

                            </div>

                        </div>


                        <div class="campoFiltroPedido">

                            <label for="filtroCanalPedido">
                                Canal
                            </label>

                            <select id="filtroCanalPedido">

                                <option value="">
                                    Todos los canales
                                </option>

                                <option value="online">
                                    Venta en línea
                                </option>

                                <option value="presencial">
                                    Venta presencial
                                </option>

                            </select>

                        </div>


                        <div class="campoFiltroPedido">

                            <label for="filtroPagoPedido">
                                Estado del pago
                            </label>

                            <select id="filtroPagoPedido">

                                <option value="">
                                    Todos los pagos
                                </option>

                                <option value="pendiente">
                                    Pendiente
                                </option>

                                <option value="aprobado">
                                    Aprobado
                                </option>

                                <option value="rechazado">
                                    Rechazado
                                </option>

                                <option value="anulado">
                                    Anulado
                                </option>

                                <option value="reembolsado">
                                    Reembolsado
                                </option>

                            </select>

                        </div>

                        <div class="campoFiltroPedido">

                            <label for="filtroEstadoPedidoSelect">
                                Estado del pedido
                            </label>

                            <select id="filtroEstadoPedidoSelect">

                                <option value="">
                                    Todos los estados
                                </option>

                                <option value="pendiente_pago">
                                    Pendiente de pago
                                </option>

                                <option value="pagado">
                                    Pagado
                                </option>

                                <option value="preparando">
                                    Preparando
                                </option>

                                <option value="listo_retiro">
                                    Listo para retirar
                                </option>

                                <option value="enviado">
                                    Enviado
                                </option>

                                <option value="entregado">
                                    Entregado
                                </option>

                                <option value="cancelado">
                                    Cancelado
                                </option>

                            </select>

                        </div>


                        <div class="campoFiltroPedido">

                            <label for="ordenPedidoAdmin">
                                Ordenar
                            </label>

                            <select id="ordenPedidoAdmin">

                                <option value="reciente">
                                    Más recientes
                                </option>

                                <option value="antiguo">
                                    Más antiguos
                                </option>

                                <option value="total_mayor">
                                    Mayor total
                                </option>

                                <option value="total_menor">
                                    Menor total
                                </option>

                                <option value="cliente_asc">
                                    Cliente A–Z
                                </option>

                            </select>

                        </div>


                        <button
                            type="button"
                            id="btnLimpiarFiltrosPedido"
                            class="btnLimpiarFiltrosPedido">

                            <i class="fa-solid fa-filter-circle-xmark"></i>

                            Limpiar filtros

                        </button>

                    </div>


                    <!-- ==========================================
                        ENCABEZADO DE RESULTADOS
                    =========================================== -->

                    <div class="encabezadoResultadosPedidos">

                        <div>

                            <h2 id="tituloResultadosPedidos">
                                Todos los pedidos
                            </h2>

                            <p id="descripcionResultadosPedidos">
                                Listado completo de pedidos registrados.
                            </p>

                        </div>

                        <div class="contadorResultadosPedidos">

                            <strong id="cantidadResultadosPedidos">
                                0
                            </strong>

                            <span id="textoResultadosPedidos">
                                resultados
                            </span>

                        </div>

                    </div>


                    <!-- ==========================================
                        TABLA
                    =========================================== -->

                    <div class="contenedorTablaPedidos">

                        <table
                            id="table_pedido"
                            class="tablaPedidosAdmin">

                            <thead>

                                <tr>

                                    <th>Pedido</th>

                                    <th>Cliente</th>

                                    <th>Canal</th>

                                    <th>Productos</th>

                                    <th>Total</th>

                                    <th>Pago</th>

                                    <th>Estado</th>

                                    <th>Fecha</th>

                                    <th>Acciones</th>

                                </tr>

                            </thead>

                            <tbody id="tbody_pedido">

                                <tr>

                                    <td
                                        colspan="9"
                                        class="cargandoPedidosAdmin">

                                        <i class="fa-solid fa-spinner fa-spin"></i>

                                        Cargando pedidos...

                                    </td>

                                </tr>

                            </tbody>

                        </table>


                        <div
                            id="sinPedidosAdmin"
                            class="sinPedidosAdmin"
                            hidden>

                            <div>

                                <i class="fa-solid fa-box-open"></i>

                            </div>

                            <h3>No se encontraron pedidos</h3>

                            <p>
                                No existen pedidos que coincidan
                                con los filtros seleccionados.
                            </p>

                            <button
                                type="button"
                                id="btnLimpiarSinPedidos">

                                Limpiar filtros

                            </button>

                        </div>

                    </div>

                </section>


                <!-- ==============================================
                    DIÁLOGO VER PEDIDO
                =============================================== -->

                <dialog
                    id="dialogDetallePedido"
                    class="dialogDetallePedido">

                    <form
                        id="formEstadoPedido"
                        method="dialog">

                        <header class="encabezadoDialogPedido">

                            <div>

                                <span class="etiquetaDialogPedido">
                                    Detalle del pedido
                                </span>

                                <h2 id="numeroPedidoDialog">
                                    Pedido
                                </h2>

                                <p id="fechaPedidoDialog"></p>

                            </div>

                            <button
                                type="button"
                                id="btnCerrarDialogPedido"
                                class="btnCerrarDialogPedido"
                                title="Cerrar">

                                <i class="fa-solid fa-xmark"></i>

                            </button>

                        </header>


                        <div class="contenidoDialogPedido">

                            <!-- CLIENTE -->

                            <section class="seccionDialogPedido">

                                <h3>

                                    <i class="fa-solid fa-user"></i>

                                    Información del cliente

                                </h3>

                                <div class="gridInformacionPedido">

                                    <div>

                                        <span>Nombre</span>

                                        <strong id="clientePedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                    <div>

                                        <span>RUT</span>

                                        <strong id="rutPedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                    <div>

                                        <span>Correo electrónico</span>

                                        <strong id="correoPedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                    <div>

                                        <span>Teléfono</span>

                                        <strong id="telefonoPedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                </div>

                            </section>


                            <!-- ENTREGA -->

                            <section class="seccionDialogPedido">

                                <h3>

                                    <i class="fa-solid fa-store"></i>

                                    Venta y entrega

                                </h3>

                                <div class="gridInformacionPedido">

                                    <div>

                                        <span>Canal</span>

                                        <strong id="canalPedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                    <div>

                                        <span>Tipo de entrega</span>

                                        <strong id="entregaPedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                    <div>

                                        <span>Responsable</span>

                                        <strong id="usuarioPedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                    <div>

                                        <span>Estado del pago</span>

                                        <strong id="pagoPedidoDialog">
                                            —
                                        </strong>

                                    </div>

                                </div>

                            </section>


                            <!-- PRODUCTOS -->

                            <section class="seccionDialogPedido">

                                <h3>

                                    <i class="fa-solid fa-box"></i>

                                    Productos del pedido

                                </h3>

                                <div class="tablaDetallePedido">

                                    <table>

                                        <thead>

                                            <tr>

                                                <th>Producto</th>

                                                <th>Precio</th>

                                                <th>Cantidad</th>

                                                <th>Total</th>

                                            </tr>

                                        </thead>

                                        <tbody id="tbodyDetallePedido">

                                            <tr>

                                                <td colspan="4">
                                                    Cargando productos...
                                                </td>

                                            </tr>

                                        </tbody>

                                    </table>

                                </div>

                            </section>


                            <!-- TOTALES -->

                            <section class="resumenTotalDialogPedido">

                                <div>

                                    <span>Neto</span>

                                    <strong id="netoPedidoDialog">
                                        $0
                                    </strong>

                                </div>

                                <div>

                                    <span>IVA incluido</span>

                                    <strong id="ivaPedidoDialog">
                                        $0
                                    </strong>

                                </div>

                                <div>

                                    <span id="etiquetaDespachoPedidoDialog">
                                        Entrega
                                    </span>

                                    <strong id="despachoPedidoDialog">
                                        $0
                                    </strong>

                                </div>

                                <div class="totalFinalDialogPedido">

                                    <span>Total</span>

                                    <strong id="totalPedidoDialog">
                                        $0
                                    </strong>

                                </div>

                            </section>


                            <!-- OBSERVACIONES -->

                            <section class="seccionDialogPedido">

                                <h3>

                                    <i class="fa-solid fa-message"></i>

                                    Observaciones

                                </h3>

                                <p
                                    id="observacionesPedidoDialog"
                                    class="observacionesPedidoDialog">

                                    Sin observaciones.

                                </p>

                            </section>


                            <!-- CAMBIAR ESTADO -->

                            <section class="seccionDialogPedido">

                                <h3>

                                    <i class="fa-solid fa-arrows-rotate"></i>

                                    Actualizar estado

                                </h3>

                                <div class="campoEstadoPedido">

                                    <label for="estadoPedidoModificar">
                                        Estado del pedido
                                    </label>

                                    <select
                                        id="estadoPedidoModificar"
                                        required>

                                        <option value="pendiente_pago">
                                            Pendiente de pago
                                        </option>

                                        <option value="pagado">
                                            Pagado
                                        </option>

                                        <option value="preparando">
                                            Preparando
                                        </option>

                                        <option value="listo_retiro">
                                            Listo para retirar
                                        </option>

                                        <option value="enviado">
                                            Enviado
                                        </option>

                                        <option value="entregado">
                                            Entregado
                                        </option>

                                        <option value="cancelado">
                                            Cancelado
                                        </option>

                                    </select>

                                    <small>
                                        El estado de pago solo puede cambiarse
                                        mediante Webpay o una operación autorizada.
                                    </small>

                                </div>

                                <input
                                    type="hidden"
                                    id="idPedidoModificar">

                                <div
                                    id="respuestaEstadoPedido"
                                    class="respuestaEstadoPedido">
                                </div>

                            </section>

                        </div>


                        <footer class="accionesDialogPedido">

                            <button
                                type="button"
                                id="btnCancelarDialogPedido"
                                class="btnCancelarDialogPedido">

                                Cancelar

                            </button>

                            <button
                                type="submit"
                                id="btnGuardarEstadoPedido"
                                class="btnGuardarEstadoPedido">

                                <i class="fa-solid fa-floppy-disk"></i>

                                Guardar estado

                            </button>

                        </footer>

                    </form>

                </dialog>
            `;

            inicializarGestionPedidos();

        break;

        case "inventario":

            contenido.innerHTML = `

                <section class="gestionInventarioAdmin">

                    <div class="encabezadoInventarioAdmin">

                        <div class="tituloInventarioAdmin">

                            <div class="iconoTituloInventario">
                                <i class="fa-solid fa-warehouse"></i>
                            </div>

                            <div>
                                <h1>Gestión de inventario</h1>
                                <p>
                                    Controle entradas, salidas, ajustes,
                                    devoluciones y movimientos de existencias.
                                </p>
                            </div>

                        </div>

                        <div class="accionesEncabezadoInventario">

                            <button
                                type="button"
                                id="btnNuevoProveedor"
                                class="btnSecundarioInventario">

                                <i class="fa-solid fa-truck-field"></i>
                                Nuevo proveedor

                            </button>

                            <button
                                type="button"
                                id="btnNuevaEntradaInventario"
                                class="btnPrincipalInventario">

                                <i class="fa-solid fa-plus"></i>
                                Registrar entrada

                            </button>

                        </div>

                    </div>


                    <!-- INDICADORES OPERATIVOS -->

                    <div class="resumenInventarioAdmin">

                        <article class="tarjetaResumenInventario tarjetaMovimientosHoy">
                            <div class="iconoResumenInventario azul">
                                <i class="fa-solid fa-arrow-right-arrow-left"></i>
                            </div>
                            <div>
                                <small>Movimientos de hoy</small>
                                <strong id="movimientosHoyInventario">0</strong>
                            </div>
                        </article>

                        <article class="tarjetaResumenInventario tarjetaEntradasMes">
                            <div class="iconoResumenInventario verde">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            </div>
                            <div>
                                <small>Entradas del mes</small>
                                <strong id="entradasMesInventario">0</strong>
                            </div>
                        </article>

                        <article class="tarjetaResumenInventario tarjetaSalidasMes">
                            <div class="iconoResumenInventario rojo">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </div>
                            <div>
                                <small>Salidas del mes</small>
                                <strong id="salidasMesInventario">0</strong>
                            </div>
                        </article>

                        <article class="tarjetaResumenInventario tarjetaAjustesMes">
                            <div class="iconoResumenInventario amarillo">
                                <i class="fa-solid fa-sliders"></i>
                            </div>
                            <div>
                                <small>Ajustes y mermas</small>
                                <strong id="ajustesMesInventario">0</strong>
                            </div>
                        </article>

                        <article class="tarjetaResumenInventario tarjetaAlertasStock">
                            <div class="iconoResumenInventario morado">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <div>
                                <small>Alertas pendientes</small>
                                <strong id="alertasInventario">0</strong>
                            </div>
                        </article>

                        <article class="tarjetaResumenInventario tarjetaValorInventarioAdmin">
                            <div class="iconoResumenInventario morado">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </div>
                            <div>
                                <small>Valor del inventario</small>
                                <strong id="valorTotalInventarioAdmin">$0</strong>
                            </div>
                        </article>


                    </div>


                    <!-- PESTAÑAS -->

                    <div class="pestanasInventarioAdmin">

                        <button
                            type="button"
                            class="btnPestanaInventario activa"
                            data-seccion="existencias">
                            <i class="fa-solid fa-box"></i>
                            Existencias
                        </button>

                        <button
                            type="button"
                            class="btnPestanaInventario"
                            data-seccion="movimientos">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            Movimientos
                        </button>

                        <button
                            type="button"
                            class="btnPestanaInventario"
                            data-seccion="entradas">
                            <i class="fa-solid fa-file-invoice"></i>
                            Entradas
                        </button>

                        <button
                            type="button"
                            class="btnPestanaInventario"
                            data-seccion="proveedores">
                            <i class="fa-solid fa-truck"></i>
                            Proveedores
                        </button>

                    </div>


                    <!-- EXISTENCIAS -->

                    <div
                        id="seccionExistenciasInventario"
                        class="seccionInventario activa">

                        <div class="encabezadoTablaInventario">
                            <div>
                                <h2>Existencias actuales</h2>
                                <p>Consulta operativa del stock disponible.</p>
                            </div>
                            <div class="contadorInventario">
                                <strong id="cantidadResultadosInventario">0</strong>
                                <span>resultados</span>
                            </div>
                        </div>

                        <div class="filtrosInventarioAdmin">

                            <div class="buscadorInventarioAdmin">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input
                                    type="search"
                                    id="buscarProductoInventario"
                                    placeholder="SKU, producto, categoría o marca...">
                            </div>

                            <select id="filtroEstadoInventario">
                                <option value="">Todos los estados</option>
                                <option value="disponible">Disponible</option>
                                <option value="bajo_stock">Bajo stock</option>
                                <option value="agotado">Agotado</option>
                                <option value="oculto">Oculto</option>
                            </select>

                            <select id="ordenInventario">
                                <option value="nombre">Nombre A–Z</option>
                                <option value="stock_menor">Menor stock</option>
                                <option value="stock_mayor">Mayor stock</option>
                                <option value="valor_mayor">Mayor valor</option>
                            </select>

                            <button
                                type="button"
                                id="btnLimpiarFiltrosInventario"
                                class="btnLimpiarInventario">
                                <i class="fa-solid fa-filter-circle-xmark"></i>
                                Limpiar filtros
                            </button>

                        </div>

                        <div class="contenedorTablaInventario">
                            <table id="tablaInventarioAdmin">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>SKU</th>
                                        <th>Marca</th>
                                        <th>Stock</th>
                                        <th>Stock mínimo</th>
                                        <th>Costo</th>
                                        <th>Valor stock</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyInventario">
                                    <tr>
                                        <td colspan="9" class="cargandoInventarioAdmin">
                                            <i class="fa-solid fa-spinner fa-spin"></i>
                                            Cargando inventario...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            id="sinProductosInventario"
                            class="sinResultadosInventario"
                            hidden>
                            <i class="fa-solid fa-box-open"></i>
                            <h3>No se encontraron productos</h3>
                            <p>Cambie o limpie los filtros de búsqueda.</p>
                        </div>

                    </div>


                    <!-- MOVIMIENTOS -->

                    <div
                        id="seccionMovimientosInventario"
                        class="seccionInventario"
                        hidden>
                        <div class="encabezadoTablaInventario">
                            <div>
                                <h2>Historial de movimientos</h2>
                                <p>Entradas, salidas, devoluciones, ajustes y mermas.</p>
                            </div>
                        </div>
                        <div id="contenidoMovimientosInventario"></div>
                    </div>


                    <!-- ENTRADAS -->

                    <div
                        id="seccionEntradasInventario"
                        class="seccionInventario"
                        hidden>
                        <div class="encabezadoTablaInventario">
                            <div>
                                <h2>Entradas de mercadería</h2>
                                <p>Compras y recepciones registradas.</p>
                            </div>
                        </div>
                        <div id="contenidoEntradasInventario"></div>
                    </div>


                    <!-- PROVEEDORES -->

                    <div
                        id="seccionProveedoresInventario"
                        class="seccionInventario"
                        hidden>

                        <div class="encabezadoTablaInventario">
                            <div>
                                <h2>Proveedores</h2>
                                <p>Empresas que abastecen el inventario.</p>
                            </div>
                            <div class="contadorInventario">
                                <strong id="cantidadProveedoresInventario">0</strong>
                                <span>proveedores</span>
                            </div>
                        </div>

                        <div class="buscadorInventarioAdmin buscadorProveedorInventario">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                type="search"
                                id="buscarProveedorInventario"
                                placeholder="RUT, razón social, contacto o correo...">
                        </div>

                        <div class="contenedorTablaInventario">
                            <table id="tablaProveedoresInventario">
                                <thead>
                                    <tr>
                                        <th>Razón social</th>
                                        <th>RUT</th>
                                        <th>Contacto</th>
                                        <th>Teléfono</th>
                                        <th>Entradas</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyProveedoresInventario">
                                    <tr>
                                        <td colspan="7" class="cargandoInventarioAdmin">
                                            <i class="fa-solid fa-spinner fa-spin"></i>
                                            Cargando proveedores...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </section>


                <!-- DIÁLOGO GRANDE DE PROVEEDOR -->

                <dialog
                    id="dialogProveedorInventario"
                    class="dialogModificarProductoCompleto">

                    <header class="cabeceraDialogAdmin">

                        <div class="tituloDialogAdmin">

                            <span class="iconoDialogAdmin">
                                <i class="fa-solid fa-truck"></i>
                            </span>

                            <div>
                                <small>Gestión de proveedores</small>

                                <h2 id="tituloDialogProveedor">
                                    Registrar proveedor
                                </h2>

                                <p>
                                    Registre la información comercial y
                                    de contacto del proveedor.
                                </p>
                            </div>

                        </div>

                        <button
                            type="button"
                            id="btnCerrarDialogProveedor"
                            class="btnCerrarDialogAdmin"
                            title="Cerrar">

                            <i class="fa-solid fa-xmark"></i>

                        </button>

                    </header>

                    <form id="formProveedorInventario">

                        <input
                            type="hidden"
                            id="idProveedorInventario"
                            name="id_proveedor"
                            value="0">

                        <div class="contenidoDialogModificarProducto contenidoDialogProveedor">

                            <aside class="columnaImagenModificarProducto">

                                <div class="contenedorPreviewProducto previewProveedorInventario">

                                    <i class="fa-solid fa-truck-field"></i>

                                </div>

                                <div class="informacionLateralProveedor">

                                    <h3>Proveedor</h3>

                                    <p>
                                        Registre empresas o personas que
                                        abastecen productos al inventario.
                                    </p>

                                </div>

                                <div class="opcionesPublicacionProducto">

                                    <div class="opcionCheckProducto">

                                        <i class="fa-solid fa-circle-check"></i>

                                        <span>
                                            <strong>Información protegida</strong>
                                            <small>
                                                Los cambios quedan asociados al usuario.
                                            </small>
                                        </span>

                                    </div>

                                </div>

                            </aside>

                            <div class="camposDialogModificarProducto">

                                <section class="grupoFormularioProducto">

                                    <h3>
                                        <i class="fa-solid fa-circle-info"></i>
                                        Información del proveedor
                                    </h3>

                                    <div class="gridCamposProducto">

                                        <div class="campoProducto">

                                            <label for="razonSocialProveedor">
                                                Razón social *
                                            </label>

                                            <input
                                                type="text"
                                                id="razonSocialProveedor"
                                                name="razon_social"
                                                minlength="2"
                                                maxlength="120"
                                                required>

                                        </div>

                                        <div class="campoProducto">

                                            <label for="rutProveedor">
                                                RUT
                                            </label>

                                            <input
                                                type="text"
                                                id="rutProveedor"
                                                name="rut"
                                                maxlength="12"
                                                placeholder="12.345.678-5">

                                        </div>

                                        <div class="campoProducto">

                                            <label for="contactoProveedor">
                                                Nombre de contacto
                                            </label>

                                            <input
                                                type="text"
                                                id="contactoProveedor"
                                                name="nombre_contacto"
                                                maxlength="100">

                                        </div>

                                        <div class="campoProducto">

                                            <label for="estadoProveedor">
                                                Estado *
                                            </label>

                                            <select
                                                id="estadoProveedor"
                                                name="estado"
                                                required>

                                                <option value="activo">
                                                    Activo
                                                </option>

                                                <option value="inactivo">
                                                    Inactivo
                                                </option>

                                            </select>

                                        </div>

                                    </div>

                                </section>

                                <section class="grupoFormularioProducto">

                                    <h3>
                                        <i class="fa-solid fa-address-book"></i>
                                        Información de contacto
                                    </h3>

                                    <div class="gridCamposProducto">

                                        <div class="campoProducto">

                                            <label for="correoProveedor">
                                                Correo electrónico
                                            </label>

                                            <input
                                                type="email"
                                                id="correoProveedor"
                                                name="correo"
                                                maxlength="150">

                                        </div>

                                        <div class="campoProducto">

                                            <label for="telefonoProveedor">
                                                Teléfono
                                            </label>

                                            <input
                                                type="tel"
                                                id="telefonoProveedor"
                                                name="telefono"
                                                maxlength="20">

                                        </div>

                                        <div class="campoProducto campoCompleto">

                                            <label for="direccionProveedor">
                                                Dirección
                                            </label>

                                            <input
                                                type="text"
                                                id="direccionProveedor"
                                                name="direccion"
                                                maxlength="200">

                                        </div>

                                    </div>

                                </section>

                                <div id="respuestaProveedorInventario"></div>

                            </div>

                        </div>

                        <div class="botonesFormularioProducto">

                                    <button
                                        type="button"
                                        id="btnCancelarProveedor"
                                        class="btnCancelarProducto">

                                        Cancelar

                                    </button>

                                    <button
                                        type="submit"
                                        id="btnGuardarProveedor"
                                        class="btnGuardarProducto">

                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Guardar proveedor

                                    </button>

                                </div>

                    </form>

                </dialog>

            `;

            inicializarGestionInventario();

        break;


        case "rentabilidad":

        contenido.innerHTML = `

            <section class="gestionRentabilidadAdmin">

                <div class="encabezadoRentabilidadAdmin">
                    <div class="tituloRentabilidadAdmin">
                        <div class="iconoTituloRentabilidad">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <h1>Ganancias y rentabilidad</h1>
                            <p>
                                Analice ventas, costos históricos, ganancias
                                y márgenes del negocio.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="filtrosRentabilidadAdmin">

                    <div class="campoFiltroRentabilidad">
                        <label for="desdeRentabilidad">Desde</label>
                        <input type="date" id="desdeRentabilidad">
                    </div>

                    <div class="campoFiltroRentabilidad">
                        <label for="hastaRentabilidad">Hasta</label>
                        <input type="date" id="hastaRentabilidad">
                    </div>

                    <div class="campoFiltroRentabilidad">
                        <label for="canalRentabilidad">Canal</label>
                        <select id="canalRentabilidad">
                            <option value="">Todos los canales</option>
                            <option value="online">En línea</option>
                            <option value="presencial">Presencial</option>
                            <option value="taller">Taller y servicios</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        id="btnAplicarRentabilidad"
                        class="btnAplicarRentabilidad">
                        <i class="fa-solid fa-magnifying-glass-chart"></i>
                        Consultar
                    </button>

                    <button
                        type="button"
                        id="btnLimpiarRentabilidad"
                        class="btnLimpiarRentabilidad">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Mes actual
                    </button>

                </div>

                <div
                    id="alertaCostosRentabilidad"
                    class="alertaCostosRentabilidad"
                    hidden></div>

                <div class="resumenRentabilidadAdmin">

                    <article class="tarjetaRentabilidad tarjetaVentasRentabilidad">
                        <div class="iconoTarjetaRentabilidad azul">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <div>
                            <small>Ingresos totales</small>
                            <strong id="ventasRentabilidad">$0</strong>
                        </div>
                    </article>

                    <article class="tarjetaRentabilidad tarjetaCostosRentabilidad">
                        <div class="iconoTarjetaRentabilidad naranja">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div>
                            <small>Costos totales</small>
                            <strong id="costosRentabilidad">$0</strong>
                        </div>
                    </article>

                    <article class="tarjetaRentabilidad tarjetaGananciaRentabilidad">
                        <div class="iconoTarjetaRentabilidad verde">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                        </div>
                        <div>
                            <small>Ganancia bruta</small>
                            <strong id="gananciaRentabilidad">$0</strong>
                        </div>
                    </article>

                    <article class="tarjetaRentabilidad tarjetaMargenRentabilidad">
                        <div class="iconoTarjetaRentabilidad morado">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                        <div>
                            <small>Margen bruto</small>
                            <strong id="margenRentabilidad">0%</strong>
                        </div>
                    </article>

                    <article class="tarjetaRentabilidad tarjetaTicketRentabilidad">
                        <div class="iconoTarjetaRentabilidad celeste">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <small>Ticket promedio</small>
                            <strong id="ticketRentabilidad">$0</strong>
                        </div>
                    </article>

                    <article class="tarjetaRentabilidad tarjetaPedidosRentabilidad">
                        <div class="iconoTarjetaRentabilidad rojo">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div>
                            <small>Operaciones facturadas</small>
                            <strong id="pedidosRentabilidad">0</strong>
                        </div>
                    </article>

                </div>

                <div class="gridAnalisisRentabilidad">

                    <section class="panelRentabilidad evolucionRentabilidad">
                        <div class="encabezadoPanelRentabilidad">
                            <div>
                                <h2>Evolución diaria</h2>
                                <p>Ventas, costos y ganancias por día.</p>
                            </div>
                        </div>
                        <div id="graficoEvolucionRentabilidad"></div>
                    </section>

                    <section class="panelRentabilidad canalesRentabilidad">
                        <div class="encabezadoPanelRentabilidad">
                            <div>
                                <h2>Resultados por canal</h2>
                                <p>Comparación presencial, en línea y taller.</p>
                            </div>
                        </div>
                        <div id="contenidoCanalesRentabilidad"></div>
                    </section>

                </div>

                <section class="panelRentabilidad productosRentabilidad">
                    <div class="encabezadoPanelRentabilidad">
                        <div>
                            <h2>Productos más rentables</h2>
                            <p>Ordenados por ganancia bruta generada.</p>
                        </div>
                        <span id="periodoRentabilidad">—</span>
                    </div>

                    <div class="contenedorTablaRentabilidad">
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Unidades</th>
                                    <th>Ventas</th>
                                    <th>Costo</th>
                                    <th>Ganancia</th>
                                    <th>Margen</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyProductosRentabilidad">
                                <tr>
                                    <td colspan="6" class="cargandoRentabilidad">
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                        Calculando rentabilidad...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="resumenReembolsosRentabilidad">
                    <i class="fa-solid fa-rotate-left"></i>
                    <div>
                        <span>Reembolsos procesados en el período</span>
                        <strong id="reembolsosRentabilidad">0 reembolsos · $0</strong>
                    </div>
                </section>

            </section>
        `;

        inicializarGestionRentabilidad();

        break;

        case "salarios":
            
        contenido.innerHTML=`

            <section class="salarios-admin" id="salariosAdmin">
                <header class="salarios-encabezado">
                    <div>
                        <span class="salarios-etiqueta">GESTIÓN DE REMUNERACIONES</span>
                        <h1><i class="fa-solid fa-wallet"></i> Salarios y comisiones</h1>
                        <p>Consulte su remuneración y las comisiones generadas por ventas y trabajos.</p>
                    </div>
                    <button type="button" class="salarios-recargar" id="btnRecargarSalarios" title="Actualizar información">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                </header>

                <div class="salarios-resumen">
                    <article class="salario-tarjeta salario-tarjeta-azul">
                        <span class="salario-icono"><i class="fa-solid fa-users"></i></span>
                        <div><small>Usuarios visibles</small><strong id="salariosTotalUsuarios">0</strong></div>
                    </article>
                    <article class="salario-tarjeta salario-tarjeta-verde">
                        <span class="salario-icono"><i class="fa-solid fa-money-bill-wave"></i></span>
                        <div><small>Sueldos base</small><strong id="salariosTotalBase">$0</strong></div>
                    </article>
                    <article class="salario-tarjeta salario-tarjeta-naranja">
                        <span class="salario-icono"><i class="fa-solid fa-percent"></i></span>
                        <div><small>Comisiones pendientes</small><strong id="salariosComisionesPendientes">$0</strong></div>
                    </article>
                    <article class="salario-tarjeta salario-tarjeta-morada">
                        <span class="salario-icono"><i class="fa-solid fa-sack-dollar"></i></span>
                        <div><small>Total estimado</small><strong id="salariosTotalEstimado">$0</strong></div>
                    </article>
                </div>

                <section class="salarios-panel">
                    <div class="salarios-filtros">
                        <label class="salarios-buscador">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" id="buscarSalarioUsuario" placeholder="Buscar usuario, correo o rol...">
                        </label>
                        <select id="filtrarSalarioRol" aria-label="Filtrar por rol">
                            <option value="">Todos los roles</option>
                            <option value="administrador">Administrador</option>
                            <option value="cajero">Cajero</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="mecanico">Mecánico</option>
                        </select>
                        <select id="filtrarSalarioEstado" aria-label="Filtrar por estado">
                            <option value="">Todos los estados</option>
                            <option value="activo">Configuración activa</option>
                            <option value="inactivo">Configuración inactiva</option>
                        </select>
                        <button type="button" class="salarios-limpiar" id="btnLimpiarFiltrosSalarios">
                            <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar filtros
                        </button>
                    </div>

                    <div class="salarios-titulo-tabla">
                        <div><h2 id="tituloTablaSalarios">Remuneraciones</h2><p>Configuración vigente y comisiones acumuladas.</p></div>
                        <span id="cantidadResultadosSalarios">0 resultados</span>
                    </div>

                    <div class="salarios-tabla-contenedor">
                        <table class="salarios-tabla">
                            <thead><tr>
                                <th>Usuario</th><th>Rol</th><th>Sueldo base</th><th>Ventas</th>
                                <th>Servicios</th><th>Mano de obra</th><th>Pendiente</th>
                                <th>Total estimado</th><th>Estado</th><th class="salarios-col-acciones">Acciones</th>
                            </tr></thead>
                            <tbody id="tablaSalariosBody">
                                <tr><td colspan="10" class="salarios-vacio">Cargando remuneraciones...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="comisiones-panel">
                    <div class="comisiones-cabecera">
                        <div>
                            <h2>Historial de comisiones</h2>
                            <p>Ventas, servicios y mano de obra que generan remuneración variable.</p>
                        </div>
                        <button type="button" id="btnActualizarComisiones" class="comisiones-actualizar">
                            <i class="fa-solid fa-rotate"></i> Actualizar
                        </button>
                    </div>

                    <div class="comisiones-filtros">
                        <label>Desde<input type="date" id="comisionesDesde"></label>
                        <label>Hasta<input type="date" id="comisionesHasta"></label>
                        <label id="grupoUsuarioComision">Usuario<select id="comisionesUsuario"><option value="0">Todos los usuarios</option></select></label>
                        <label>Tipo<select id="comisionesTipo"><option value="">Todos los tipos</option><option value="venta">Venta</option><option value="servicio">Servicio</option><option value="mano_obra">Mano de obra</option></select></label>
                        <label>Estado<select id="comisionesEstado"><option value="">Todos los estados</option><option value="pendiente">Pendiente</option><option value="liquidada">Liquidada</option><option value="pagada">Pagada</option><option value="anulada">Anulada</option></select></label>
                        <button type="button" id="btnFiltrarComisiones" class="comisiones-filtrar"><i class="fa-solid fa-filter"></i> Aplicar</button>
                    </div>

                    <div class="comisiones-resumen">
                        <div><small>Registros</small><strong id="comisionesCantidad">0</strong></div>
                        <div><small>Base calculada</small><strong id="comisionesBaseTotal">$0</strong></div>
                        <div><small>Comisión pendiente</small><strong id="comisionesMontoPendiente">$0</strong></div>
                        <div><small>Total de comisiones</small><strong id="comisionesMontoTotal">$0</strong></div>
                    </div>

                    <div class="comisiones-tabla-contenedor">
                        <table class="comisiones-tabla">
                            <thead><tr><th>Fecha</th><th>Usuario</th><th>Tipo</th><th>Origen</th><th>Referencia</th><th>Concepto</th><th>Base</th><th>Porcentaje</th><th>Comisión</th><th>Estado</th></tr></thead>
                            <tbody id="tablaComisionesBody"><tr><td colspan="10" class="salarios-vacio">Cargando comisiones...</td></tr></tbody>
                        </table>
                    </div>
                </section>

                <section class="liquidaciones-panel">
                    <div class="liquidaciones-cabecera">
                        <div><h2>Liquidaciones salariales</h2><p>Sueldos, comisiones, bonos, descuentos y pagos por período.</p></div>
                        <button type="button" id="btnNuevaLiquidacion" class="liquidaciones-nueva" hidden><i class="fa-solid fa-plus"></i> Crear liquidación</button>
                    </div>

                    <div class="liquidaciones-filtros">
                        <label>Desde<input type="date" id="liquidacionesDesde"></label>
                        <label>Hasta<input type="date" id="liquidacionesHasta"></label>
                        <label id="grupoUsuarioLiquidacion">Usuario<select id="liquidacionesUsuario"><option value="0">Todos los usuarios</option></select></label>
                        <label>Estado<select id="liquidacionesEstado"><option value="">Todos los estados</option><option value="borrador">Borrador</option><option value="cerrada">Cerrada</option><option value="pagada">Pagada</option><option value="anulada">Anulada</option></select></label>
                        <button type="button" id="btnFiltrarLiquidaciones" class="liquidaciones-filtrar"><i class="fa-solid fa-filter"></i> Aplicar</button>
                    </div>

                    <div class="liquidaciones-resumen">
                        <div><small>Liquidaciones</small><strong id="liquidacionesCantidad">0</strong></div>
                        <div><small>Comisiones incluidas</small><strong id="liquidacionesComisiones">$0</strong></div>
                        <div><small>Pendiente de pago</small><strong id="liquidacionesPendiente">$0</strong></div>
                        <div><small>Total pagado</small><strong id="liquidacionesPagado">$0</strong></div>
                    </div>

                    <div class="liquidaciones-tabla-contenedor">
                        <table class="liquidaciones-tabla">
                            <thead><tr><th>Período</th><th>Usuario</th><th>Sueldo base</th><th>Comisiones</th><th>Bonos</th><th>Descuentos</th><th>Total</th><th>Estado</th><th>Fecha de pago</th><th>Acciones</th></tr></thead>
                            <tbody id="tablaLiquidacionesBody"><tr><td colspan="10" class="salarios-vacio">Cargando liquidaciones...</td></tr></tbody>
                        </table>
                    </div>
                </section>
            </section>

            <dialog id="dialogRemuneracion" class="dialog-remuneracion">
                <form id="formRemuneracion" method="dialog">
                    <header class="remuneracion-dialog-header">
                        <span class="remuneracion-dialog-icono"><i class="fa-solid fa-wallet"></i></span>
                        <div><small>GESTIÓN DE REMUNERACIONES</small><h2>Modificar remuneración</h2><p id="remuneracionUsuarioTexto"></p></div>
                        <button type="button" id="cerrarDialogRemuneracion" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
                    </header>
                    <div class="remuneracion-dialog-cuerpo">
                        <input type="hidden" id="remuneracionIdUsuario" name="id_usuario">
                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-circle-info"></i> Configuración de pago</h3>
                            <div class="remuneracion-grid">
                                <label>Sueldo base mensual *<input type="number" id="remuneracionSueldoBase" name="sueldo_base_mensual" min="0" step="1" required></label>
                                <label>Estado *<select id="remuneracionEstado" name="estado" required><option value="activo">Activa</option><option value="inactivo">Inactiva</option></select></label>
                            </div>
                        </section>
                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-percent"></i> Porcentajes de comisión</h3>
                            <div class="remuneracion-grid remuneracion-grid-tres">
                                <label>Ventas (%) *<input type="number" id="remuneracionVentas" name="porcentaje_ventas" min="0" max="100" step="0.01" required></label>
                                <label>Servicios (%) *<input type="number" id="remuneracionServicios" name="porcentaje_servicios" min="0" max="100" step="0.01" required></label>
                                <label>Mano de obra (%) *<input type="number" id="remuneracionManoObra" name="porcentaje_mano_obra" min="0" max="100" step="0.01" required></label>
                            </div>
                            <p class="remuneracion-ayuda">Los porcentajes se aplicarán a operaciones nuevas. No modifican las comisiones históricas.</p>
                        </section>
                    </div>
                    <footer class="remuneracion-dialog-footer">
                        <button type="button" class="remuneracion-cancelar" id="cancelarDialogRemuneracion">Cancelar</button>
                        <button type="submit" class="remuneracion-guardar"><i class="fa-solid fa-floppy-disk"></i> Guardar configuración</button>
                    </footer>
                </form>
            </dialog>

            <dialog id="dialogDetalleLiquidacion" class="dialog-remuneracion dialog-detalle-liquidacion">
                <div class="detalle-liquidacion-contenedor">
                    <header class="remuneracion-dialog-header">
                        <span class="remuneracion-dialog-icono"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <div>
                            <small>GESTIÓN DE REMUNERACIONES</small>
                            <h2>Detalle de liquidación</h2>
                            <p id="detalleLiquidacionUsuario">Cargando información...</p>
                        </div>
                        <button type="button" id="cerrarDetalleLiquidacion" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
                    </header>

                    <div class="remuneracion-dialog-cuerpo detalle-liquidacion-cuerpo" id="detalleLiquidacionImprimible">
                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-circle-info"></i> Información general</h3>
                            <div class="detalle-liquidacion-datos">
                                <div><small>Período</small><strong id="detalleLiquidacionPeriodo">—</strong></div>
                                <div><small>Estado</small><strong id="detalleLiquidacionEstado">—</strong></div>
                                <div><small>Responsable</small><strong id="detalleLiquidacionResponsable">—</strong></div>
                                <div><small>Fecha de pago</small><strong id="detalleLiquidacionFechaPago">—</strong></div>
                            </div>
                        </section>

                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-receipt"></i> Resumen de pago</h3>
                            <div class="detalle-liquidacion-totales">
                                <div><small>Sueldo base</small><strong id="detalleLiquidacionSueldo">$0</strong></div>
                                <div><small>Comisiones</small><strong id="detalleLiquidacionComisiones">$0</strong></div>
                                <div><small>Bonos</small><strong id="detalleLiquidacionBonos">$0</strong></div>
                                <div><small>Descuentos</small><strong id="detalleLiquidacionDescuentos">$0</strong></div>
                                <div class="detalle-liquidacion-total"><small>Total liquidación</small><strong id="detalleLiquidacionTotal">$0</strong></div>
                            </div>
                        </section>

                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-percent"></i> Comisiones incluidas</h3>
                            <div class="detalle-liquidacion-tabla-contenedor">
                                <table class="detalle-liquidacion-tabla">
                                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Base</th><th>%</th><th>Comisión</th></tr></thead>
                                    <tbody id="detalleLiquidacionComisionesBody"><tr><td colspan="6" class="salarios-vacio">Sin comisiones.</td></tr></tbody>
                                </table>
                            </div>
                        </section>

                        <section class="remuneracion-bloque" id="detalleLiquidacionAjustesBloque">
                            <h3><i class="fa-solid fa-sliders"></i> Ajustes automáticos</h3>
                            <div class="detalle-liquidacion-tabla-contenedor">
                                <table class="detalle-liquidacion-tabla">
                                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Descripción</th><th>Monto</th></tr></thead>
                                    <tbody id="detalleLiquidacionAjustesBody"></tbody>
                                </table>
                            </div>
                        </section>

                        <section class="remuneracion-bloque detalle-liquidacion-observaciones">
                            <h3><i class="fa-solid fa-note-sticky"></i> Observaciones</h3>
                            <p id="detalleLiquidacionObservaciones">Sin observaciones.</p>
                        </section>

                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-clock-rotate-left"></i> Historial de la liquidación</h3>
                            <div class="detalle-liquidacion-tabla-contenedor">
                                <table class="detalle-liquidacion-tabla">
                                    <thead><tr><th>Fecha</th><th>Acción</th><th>Cambio de estado</th><th>Responsable</th><th>Descripción</th></tr></thead>
                                    <tbody id="detalleLiquidacionHistorialBody"><tr><td colspan="5" class="salarios-vacio">Sin eventos registrados.</td></tr></tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <footer class="remuneracion-dialog-footer">
                        <button type="button" class="remuneracion-cancelar" id="cerrarDetalleLiquidacionInferior">Cerrar</button>
                        <button type="button" class="remuneracion-guardar" id="imprimirDetalleLiquidacion"><i class="fa-solid fa-print"></i> Imprimir comprobante</button>
                    </footer>
                </div>
            </dialog>

            <dialog id="dialogCrearLiquidacion" class="dialog-remuneracion">
                <form id="formCrearLiquidacion" method="dialog">
                    <header class="remuneracion-dialog-header">
                        <span class="remuneracion-dialog-icono"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <div><small>GESTIÓN DE REMUNERACIONES</small><h2>Crear liquidación</h2><p>Prepare el salario y las comisiones de un período.</p></div>
                        <button type="button" id="cerrarDialogLiquidacion" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
                    </header>
                    <div class="remuneracion-dialog-cuerpo">
                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-calendar-days"></i> Usuario y período</h3>
                            <div class="remuneracion-grid remuneracion-grid-tres">
                                <label>Usuario *<select id="liquidacionIdUsuario" name="id_usuario" required><option value="">Seleccione un usuario</option></select></label>
                                <label>Desde *<input type="date" id="liquidacionDesde" name="periodo_desde" required></label>
                                <label>Hasta *<input type="date" id="liquidacionHasta" name="periodo_hasta" required></label>
                            </div>
                        </section>
                        <section class="remuneracion-bloque">
                            <h3><i class="fa-solid fa-calculator"></i> Ajustes manuales</h3>
                            <div class="remuneracion-grid">
                                <label>Bonos adicionales<input type="number" id="liquidacionBonos" name="bonos" min="0" step="1" value="0"></label>
                                <label>Descuentos adicionales<input type="number" id="liquidacionDescuentos" name="descuentos" min="0" step="1" value="0"></label>
                            </div>
                            <label class="liquidacion-observaciones">Observaciones<textarea id="liquidacionObservaciones" name="observaciones" maxlength="500" rows="3"></textarea></label>
                            <p class="remuneracion-ayuda">Los descuentos pendientes por reembolsos se agregarán automáticamente.</p>
                        </section>
                    </div>
                    <footer class="remuneracion-dialog-footer">
                        <button type="button" class="remuneracion-cancelar" id="cancelarDialogLiquidacion">Cancelar</button>
                        <button type="submit" class="remuneracion-guardar"><i class="fa-solid fa-floppy-disk"></i> Crear borrador</button>
                    </footer>
                </form>
            </dialog>
        `;

        break;



        case "configuracion":

            contenido.innerHTML=`
                <h1>⚙ Configuración</h1>

                <button>Editar Configuración</button>

            `;
        break;
    }

}



// =====================================================
// VOLVER A LA SECCIÓN DESDE LA ORDEN DE TRABAJO
// =====================================================

document.addEventListener("DOMContentLoaded", function () {

    const seccion = sessionStorage.getItem("seccionVolverAdmin");

    if (!seccion) {
        return;
    }

    sessionStorage.removeItem("seccionVolverAdmin");

    setTimeout(() => {

        if (typeof cargar === "function") {
            cargar(seccion);
        }

    }, 0);
});





    function alternarMenu(boton) {
        const categoria = boton.closest(".menu-categoria");
        const estabaAbierta = categoria.classList.contains("abierta");

        document
            .querySelectorAll(".menu-categoria.abierta")
            .forEach(elemento => {
                elemento.classList.remove("abierta");
            });

        if (!estabaAbierta) {
            categoria.classList.add("abierta");
        }
    }


    function mostrarMenuUsuario(event) {

    event.stopPropagation();

    const menu = document.getElementById("menuUsuarioAdmin");
    const boton = document.getElementById("btnMenuUsuario");

    const estaVisible =
        menu.classList.toggle("menuUsuarioVisible");

    boton.setAttribute(
        "aria-expanded",
        estaVisible ? "true" : "false"
    );

    // Mostrar información guardada
    document.getElementById("nombreMenuUsuario").textContent =
        localStorage.getItem("usuario") || "Usuario";

    document.getElementById("rolMenuUsuario").textContent =
        localStorage.getItem("rol") || "Sin rol";
}

document.addEventListener("click", function(event) {

    const contenedor =
        document.querySelector(".contenedorMenuUsuario");

    const menu =
        document.getElementById("menuUsuarioAdmin");

    const boton =
        document.getElementById("btnMenuUsuario");

    if (!contenedor || !menu || !boton) {
        return;
    }

    if (!contenedor.contains(event.target)) {

        menu.classList.remove("menuUsuarioVisible");

        boton.setAttribute("aria-expanded", "false");
    }
});

document.addEventListener("keydown", function(event) {

    if (event.key !== "Escape") {
        return;
    }

    const menu =
        document.getElementById("menuUsuarioAdmin");

    const boton =
        document.getElementById("btnMenuUsuario");

    if (menu) {
        menu.classList.remove("menuUsuarioVisible");
    }

    if (boton) {
        boton.setAttribute("aria-expanded", "false");
    }
});



function logOut() {

    if (!confirm("¿Desea cerrar sesión?")) {
        return;
    }
    window.location.href = "./php/logout.php";
    localStorage.clear();
}
