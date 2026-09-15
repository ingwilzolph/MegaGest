<?php
require_once "php/verificarSesion.php";
?>
<!DOCTYPE html>
<html lang="es-es">
<head>
<link href="./images/logoCabeza.webp" rel="icon" type="image/x-icon">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Administración</title>
<link href="css/base.css" rel="stylesheet">
<link rel="stylesheet" type="text/css"  href="smart-form/smart-templates/css/smart-forms.css"> 
<link rel="stylesheet" href="./css/admin.css">
<link rel="stylesheet" href="./css/admin/navegacion.css">
<link rel="stylesheet" href="./css/admin/productos.css">
<link rel="stylesheet" href="./css/admin/servicios.css">
<link rel="stylesheet" href="./css/admin/categorias.css">
<link rel="stylesheet" href="./css/admin/usuarios.css">
<link rel="stylesheet" href="./css/admin/pedidos.css">
<link rel="stylesheet" href="./css/admin/ventas.css">
<link rel="stylesheet" href="./css/admin/cotizaciones.css">
<link rel="stylesheet" href="./css/style.css">
<link rel="stylesheet" href="./css/carrito.css">
<link rel="stylesheet" href="./css/dialog.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="./css/inventario.css">
<link rel="stylesheet" href="./css/rentabilidad.css">
<link rel="stylesheet" href="./css/salarios.css">
<link rel="stylesheet" href="./css/inicioAdmin.css">
</head>
<body>

    <!-- Botón para abrir el menú en teléfonos -->
    <button
        type="button"
        id="btnAbrirMenuAdmin"
        class="btnAbrirMenuAdmin"
        aria-label="Abrir menú de administración"
        aria-controls="menuLateralAdmin"
        aria-expanded="false">

        <i class="fa-solid fa-bars"></i>

    </button>

    <!-- Fondo oscuro detrás del menú móvil -->
    <div
        id="fondoMenuAdmin"
        class="fondoMenuAdmin"
        hidden>
    </div>

    <div class="contenedor">

    <!-- Menú lateral -->
    <aside
        id="menuLateralAdmin"
        class="sidebar">

        <div class="cabeceraSidebarAdmin">

            <button
                type="button"
                id="btnCerrarMenuAdmin"
                class="btnCerrarMenuAdmin"
                aria-label="Cerrar menú de administración">

                <i class="fa-solid fa-xmark"></i>

            </button>

            <img
                src="images/logoAlianzaPro.webp"
                alt="AlianzaPro">

            <p id="bienvenido"></p>

        </div>

        <ul class="menu-admin">

            <li class="menu-enlace" onclick="cargar('inicio')">
                <span class="menu-inicio-contenido">
                    <span class="menu-icono">📊</span>
                    <span class="menu-texto">Inicio</span>
                </span>
            </li>

            <!-- Operación comercial -->
            <li class="menu-categoria categoria-comercial">
                <button type="button" onclick="alternarMenu(this)">
                    <span class="menu-categoria-identidad">
                        <span class="menu-icono">💼</span>
                        <span class="menu-texto">Operación comercial</span>
                    </span>
                    <span class="menu-flecha">⌄</span>
                </button>

                <ul class="submenu">
                    <li onclick="cargar('cotizaciones')">🧾 Cotizaciones</li>
                    <li onclick="cargar('ventas')">💵 Ventas</li>
                    <li onclick="cargar('pedidos')">🛒 Pedidos</li>
                </ul>
            </li>

            <!-- Gestión del taller -->
            <li class="menu-categoria categoria-taller">
                <button type="button" onclick="alternarMenu(this)">
                    <span class="menu-categoria-identidad">
                        <span class="menu-icono">🔧</span>
                        <span class="menu-texto">Gestión del taller</span>
                    </span>
                    <span class="menu-flecha">⌄</span>
                </button>

                <ul class="submenu">
                    <li onclick="cargar('citas')">📅 Citas</li>
                    <li onclick="cargar('servicios')">🚗 Servicios</li>
                </ul>
            </li>

            <!-- Catálogo e inventario -->
            <li class="menu-categoria categoria-inventario">
                <button type="button" onclick="alternarMenu(this)">
                    <span class="menu-categoria-identidad">
                        <span class="menu-icono">📦</span>
                        <span class="menu-texto">Catálogo e inventario</span>
                    </span>
                    <span class="menu-flecha">⌄</span>
                </button>

                <ul class="submenu">
                    <li onclick="cargar('categorias')">🗂️ Categorías</li>
                    <li onclick="cargar('productos')">📦 Productos</li>
                    <li onclick="cargar('inventario')">📚 Inventario</li>
                </ul>
            </li>

            <!-- Finanzas -->
            <li class="menu-categoria categoria-finanzas">
                <button type="button" onclick="alternarMenu(this)">
                    <span class="menu-categoria-identidad">
                        <span class="menu-icono">📈</span>
                        <span class="menu-texto">Finanzas</span>
                    </span>
                    <span class="menu-flecha">⌄</span>
                </button>

                <ul class="submenu">
                    <li onclick="cargar('rentabilidad')">
                        📈 Ganancias y rentabilidad
                    </li>

                    <li onclick="cargar('salarios')">
                        💰 Salarios y comisiones
                    </li>
                </ul>
            </li>

            <!-- Administración -->

            <li class="menu-categoria categoria-administracion">

                <button
                    type="button"
                    onclick="alternarMenu(this)">

                    <span class="menu-categoria-identidad">

                        <span class="menu-icono">
                            ⚙️
                        </span>

                        <span class="menu-texto">
                            Administración
                        </span>

                    </span>

                    <span class="menu-flecha">
                     ⌄
                    </span>

                </button>

                <ul class="submenu">

                    <li onclick="cargar('usuarios')">
                        👥 Usuarios
                    </li>

                </ul>

            </li>

        </ul>

    </aside>

    <!-- Contenido -->
    <main class="contenido">

        <div class="barraSuperiorAdmin">

    <!-- Parte izquierda -->
    <div class="left">

        <a href="index.html" class="breadcrumb-link">
            Inicio
        </a>

        <span class="separador">/</span>

        <span class="breadcrumb-actual">
            Admin
        </span>

    </div>

    <!-- Parte derecha -->
    <div class="accionesSuperiorAdmin">

    <!-- Configuración -->
    <button
        type="button"
        class="btnIconoSuperior"
        onclick="cargar('configuracion')"
        title="Configuración"
        aria-label="Configuración">

        <i class="fa-solid fa-gear"></i>

    </button>

    <!-- Menú del usuario -->
    <div class="contenedorMenuUsuario">

        <button
            type="button"
            id="btnMenuUsuario"
            class="btnIconoSuperior btnUsuarioSuperior"
            title="Menú del usuario"
            aria-label="Menú del usuario"
            aria-expanded="false"
            onclick="mostrarMenuUsuario(event)">

            <i class="fa-solid fa-user"></i>

        </button>

        <div id="menuUsuarioAdmin" class="menuUsuarioAdmin">

            <div class="informacionUsuarioAdmin">

                <i class="fa-solid fa-circle-user"></i>

                <div>
                    <strong id="nombreMenuUsuario">Usuario</strong>
                    <small id="rolMenuUsuario">Administrador</small>
                </div>

            </div>

            <hr>

                <a
                    href="./index.html"
                    class="opcionMenuUsuario opcionIrTiendaAdmin">

                    <i class="fa-solid fa-store"></i>

                    <span>Ir a la tienda</span>

                </a>

                <button
                    type="button"
                    class="opcionMenuUsuario opcionCerrarSesion"
                    onclick="logOut()">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>Cerrar sesión</span>

                </button>

        </div>

    </div>

</div>

    </div>

    <hr>

        <div id="contenido">

            <h1>Bienvenido al Panel Administrativo</h1>

            <p>Seleccione una opción del menú.</p>

        </div>


    <!-- ==================================================
     DIÁLOGO MODIFICAR USUARIO
=================================================== -->

<dialog
    id="dialogModificarUsuario"
    class="dialogGestionUsuario">

     <header class="cabeceraDialogAdmin">

        <div class="tituloDialogAdmin">

            <span class="iconoDialogAdmin">
                <i class="fa-solid fa-user-pen"></i>
            </span>

            <div>
                <small>Gestión de Usuarios</small>
                <h2>Modificar usuario</h2>
                <p>
                    Actualice la información y los permisos de la cuenta.
                </p>
            </div>

        </div>

        <button
            type="button"
            class="btnCerrarDialogAdmin"
            title="Cerrar"
            onclick="cerrarDialogModificarUsuario()">

            <i class="fa-solid fa-xmark"></i>

        </button>

    </header>
    <form
        id="formModificarUsuario"
        class="formModificarUsuario"
        method="POST">

        <input
            type="hidden"
            name="id_usuario"
            id="idUsuario">

        <!-- CONTENIDO -->

        <div class="contenidoDialogUsuario">

            <section class="grupoDialogUsuario">

                <h3>

                    <i class="fa-solid fa-address-card"></i>

                    Información personal

                </h3>

                <div class="gridDialogUsuario">

                    <div class="campoDialogUsuario">

                        <label for="nombreModificar">
                            Nombre *
                        </label>

                        <input
                            type="text"
                            name="nombre"
                            id="nombreModificar"
                            minlength="2"
                            maxlength="60"
                            autocomplete="given-name"
                            placeholder="Nombre del usuario"
                            required>

                    </div>

                    <div class="campoDialogUsuario">

                        <label for="apellidoModificar">
                            Apellido *
                        </label>

                        <input
                            type="text"
                            name="apellido"
                            id="apellidoModificar"
                            minlength="2"
                            maxlength="60"
                            autocomplete="family-name"
                            placeholder="Apellido del usuario"
                            required>

                    </div>

                    <div class="
                        campoDialogUsuario
                        campoCompletoDialogUsuario
                    ">

                        <label for="correoModificar">
                            Correo electrónico *
                        </label>

                        <div class="inputIconoDialogUsuario">

                            <i class="fa-regular fa-envelope"></i>

                            <input
                                type="email"
                                name="correo"
                                id="correoModificar"
                                maxlength="150"
                                autocomplete="email"
                                placeholder="usuario@correo.cl"
                                required>

                        </div>

                    </div>

                </div>

            </section>

            <section class="grupoDialogUsuario">

                <h3>

                    <i class="fa-solid fa-shield-halved"></i>

                    Acceso y permisos

                </h3>

                <div class="gridDialogUsuario">

                    <div class="campoDialogUsuario">

                        <label for="rolModificar">
                            Rol *
                        </label>

                        <select
                            name="rol"
                            id="rolModificar"
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

                        <small>
                            El administrador tiene acceso completo.
                        </small>

                    </div>

                    <div class="campoDialogUsuario">

                        <label for="estadoModificar">
                            Estado *
                        </label>

                        <select
                            name="estado"
                            id="estadoModificar"
                            required>

                            <option value="activo">
                                Activo
                            </option>

                            <option value="inactivo">
                                Inactivo
                            </option>

                        </select>

                        <small>
                            Un usuario inactivo no podrá iniciar sesión.
                        </small>

                    </div>

                </div>

            </section>

            <div
                id="respuestaModificarUsuario"
                role="status"
                aria-live="polite">
            </div>

        </div>

        <!-- BOTONES -->

        <div class="botonesDialogUsuario">

            <button
                type="button"
                class="btnCancelarDialogUsuario"
                onclick="cerrarDialogModificarUsuario()">

                Cancelar

            </button>

            <button
                type="reset"
                class="btnReiniciarDialogUsuario">

                <i class="fa-solid fa-rotate-left"></i>

                Restablecer

            </button>

            <button
                type="submit"
                id="btnGuardarModificarUsuario"
                class="btnGuardarDialogUsuario">

                <i class="fa-solid fa-floppy-disk"></i>

                Guardar cambios

            </button>

        </div>

    </form>

</dialog>


 <dialog
    id="dialogModificarServicio"
    class="dialogModificarProductoCompleto">

    <header class="cabeceraDialogAdmin">

        <div class="tituloDialogAdmin">

            <span class="iconoDialogAdmin">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </span>

            <div>
                <small>Gestión de servicios</small>
                <h2>Modificar servicio</h2>
                <p>
                    Actualice la información y disponibilidad del servicio.
                </p>
            </div>

        </div>

        <button
            type="button"
            class="btnCerrarDialogAdmin"
            title="Cerrar"
            onclick="cerrarDialogModificarServicio()">

            <i class="fa-solid fa-xmark"></i>

        </button>

    </header>

    <form
        id="formModificarServicio"
        enctype="multipart/form-data">

        <input
            type="hidden"
            name="id_servicio"
            id="idServicio">

        <div class="contenidoDialogModificarProducto">

            <!-- IMAGEN -->

            <aside class="columnaImagenModificarProducto">

                <div class="contenedorPreviewProducto">

                    <img
                        id="previewModificarServicio"
                        src="./images/page-bg.webp"
                        alt="Imagen del servicio">

                </div>

                <label
                    for="imagenModificarServicio"
                    class="btnSeleccionarImagen">

                    <i class="fa-solid fa-image"></i>
                    Cambiar imagen

                </label>

                <input
                    type="file"
                    name="imagen"
                    id="imagenModificarServicio"
                    accept=".jpg,.jpeg,.png,.webp,.gif,.webp">

                <small>
                    La imagen actual se conservará si no selecciona otra.
                </small>

                <div class="opcionesPublicacionProducto">

                    <label class="opcionCheckProducto">

                        <input
                            type="checkbox"
                            name="visible_citas"
                            id="visibleCitasServicioModificar"
                            value="1">

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
                            id="destacadoServicioModificar"
                            value="1">

                        <span>
                            <strong>Servicio destacado</strong>
                            <small>
                                Aparecerá en áreas promocionales.
                            </small>
                        </span>

                    </label>

                </div>

            </aside>

            <!-- CAMPOS -->

            <div class="camposDialogModificarProducto">

                <section class="grupoFormularioProducto">

                    <h3>
                        <i class="fa-solid fa-circle-info"></i>
                        Información del servicio
                    </h3>

                    <div class="gridCamposProducto">

                        <div class="campoProducto campoCompleto">

                            <label for="nombreServicioModificar">
                                Nombre *
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                id="nombreServicioModificar"
                                minlength="3"
                                maxlength="100"
                                required>

                        </div>

                        <div class="campoProducto campoCompleto">

                            <label for="descripcionServicioModificar">
                                Descripción *
                            </label>

                            <textarea
                                name="descripcion"
                                id="descripcionServicioModificar"
                                minlength="10"
                                maxlength="1500"
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

                            <label for="precioMinServicioModificar">
                                Precio mínimo *
                            </label>

                            <input
                                type="number"
                                name="precio_min"
                                id="precioMinServicioModificar"
                                min="1"
                                step="1"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="duracionServicioModificar">
                                Duración estimada *
                            </label>

                            <select
                                name="duracion_minutos"
                                id="duracionServicioModificar"
                                required>

                                <option value="30">30 minutos</option>
                                <option value="45">45 minutos</option>
                                <option value="60">1 hora</option>
                                <option value="90">1 hora 30 minutos</option>
                                <option value="120">2 horas</option>
                                <option value="180">3 horas</option>
                                <option value="240">4 horas</option>

                            </select>

                        </div>

                        <div class="campoProducto">

                            <label for="estadoServicioModificar">
                                Estado *
                            </label>

                            <select
                                name="estado"
                                id="estadoServicioModificar"
                                required>

                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>

                            </select>

                        </div>

                    </div>

                </section>

                <div id="respuestaModificarServicio"></div>

                <div class="botonesFormularioProducto">

                    <button
                        type="button"
                        class="btnCancelarProducto"
                        onclick="cerrarDialogModificarServicio()">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        id="btnGuardarModificarServicio"
                        class="btnGuardarProducto">

                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar cambios

                    </button>

                </div>

            </div>

        </div>

    </form>

</dialog>



<dialog id="dialogModificarCategoria">

    <header class="cabeceraDialogAdmin">

    <div class="tituloDialogAdmin">

        <span class="iconoDialogAdmin">
            <i class="fa-solid fa-tags"></i>
        </span>

        <div>
            <small>Gestión de categorías</small>
            <h2>Modificar categoría</h2>
            <p>
                Actualice la información de la categoría seleccionada.
            </p>
        </div>

    </div>

    <button
        type="button"
        class="btnCerrarDialogAdmin"
        title="Cerrar"
        onclick="cerrarDialogModificarCategoria()">

        <i class="fa-solid fa-xmark"></i>

    </button>

</header>

    <form id="formModificarCategoria" onsubmit="event.preventDefault(); guardarCategoria();">
         
      <input type="hidden" id="idCategoria">

        <div>
            <label>Nombre categoria</label>
            <input type="text" id="categoriaModificar" required>
        </div>

        <div class="botones">

            <button type="submit" class="btnGuardar">Guardar Cambios</button>

            <button type="button" class="btnCancelar" onclick="document.getElementById('dialogModificarCategoria').close()">Cancelar</button>

        </div>

      </form>

    </dialog>


<dialog
    id="dialogModificarProducto"
    class="dialogModificarProductoCompleto">

    <header class="cabeceraDialogAdmin">

        <div class="tituloDialogAdmin">

            <span class="iconoDialogAdmin">
                <i class="fa-solid fa-box"></i>
            </span>

            <div>
                <small>Gestión de productos</small>
                <h2>Modificar producto</h2>
                <p>
                    Actualice la información comercial y de inventario.
                </p>
            </div>

        </div>

        <button
            type="button"
            class="btnCerrarDialogAdmin"
            title="Cerrar"
            onclick="cerrarDialogModificarProducto()">

            <i class="fa-solid fa-xmark"></i>

        </button>

    </header>

    <form
        id="formModificarProducto"
        enctype="multipart/form-data">

        <input
            type="hidden"
            name="idProducto"
            id="idProducto">

        <div class="contenidoDialogModificarProducto">

            <!-- IMAGEN Y PUBLICACIÓN -->

            <aside class="columnaImagenModificarProducto">

                <div class="contenedorPreviewProducto">

                    <img
                        id="previewModificarProducto"
                        src="./images/page-bg.webp"
                        alt="Imagen del producto">

                </div>

                <label
                    for="imagenProductoModificar"
                    class="btnSeleccionarImagen">

                    <i class="fa-solid fa-image"></i>
                    Cambiar imagen

                </label>

                <input
                    type="file"
                    name="imagen"
                    id="imagenProductoModificar"
                    accept=".jpg,.jpeg,.png,.webp,.gif,.webp">

                <small>
                    La imagen actual se conservará si no selecciona otra.
                </small>

                <div class="opcionesPublicacionProducto">

                    <label class="opcionCheckProducto">

                        <input
                            type="checkbox"
                            name="visible_tienda"
                            id="visibleTiendaModificar"
                            value="1">

                        <span>
                            <strong>Visible en la tienda</strong>
                            <small>Disponible para la compra en línea.</small>
                        </span>

                    </label>

                    <label class="opcionCheckProducto">

                        <input
                            type="checkbox"
                            name="destacado"
                            id="destacadoModificar"
                            value="1">

                        <span>
                            <strong>Producto destacado</strong>
                            <small>Mostrar en áreas promocionales.</small>
                        </span>

                    </label>

                </div>

            </aside>

            <!-- CAMPOS -->

            <div class="camposDialogModificarProducto">

                <section class="grupoFormularioProducto">

                    <h3>
                        <i class="fa-solid fa-circle-info"></i>
                        Información básica
                    </h3>

                    <div class="gridCamposProducto">

                        <div class="campoProducto">

                            <label for="nombreProductoModificar">
                                Nombre *
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                id="nombreProductoModificar"
                                minlength="3"
                                maxlength="100"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="skuProductoModificar">
                                SKU *
                            </label>

                            <input
                                type="text"
                                name="sku"
                                id="skuProductoModificar"
                                maxlength="40"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="categoriaProductoModificar">
                                Categoría *
                            </label>

                            <input
                                type="text"
                                name="categoria"
                                id="categoriaProductoModificar"
                                minlength="3"
                                maxlength="50"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="marcaModificar">
                                Marca *
                            </label>

                            <input
                                type="text"
                                name="marca"
                                id="marcaModificar"
                                minlength="2"
                                maxlength="50"
                                required>

                        </div>

                        <div class="campoProducto campoCompleto">

                            <label for="descripcionProductoModificar">
                                Descripción *
                            </label>

                            <textarea
                                name="descripcion"
                                id="descripcionProductoModificar"
                                minlength="10"
                                maxlength="1000"
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

                            <label for="cantidadModificar">
                                Stock actual *
                            </label>

                            <input
                                type="number"
                                name="cantidad"
                                id="cantidadModificar"
                                min="0"
                                step="1"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="stockMinimoModificar">
                                Stock mínimo *
                            </label>

                            <input
                                type="number"
                                name="stock_minimo"
                                id="stockMinimoModificar"
                                min="0"
                                step="1"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="ubicacionModificar">
                                Ubicación
                            </label>

                            <input
                                type="text"
                                name="ubicacion"
                                id="ubicacionModificar"
                                maxlength="100">

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

                            <label for="compraProductoModificar">
                                Precio de compra *
                            </label>

                            <input
                                type="number"
                                name="compra"
                                id="compraProductoModificar"
                                min="1"
                                step="1"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="precioProductoModificar">
                                Precio de venta *
                            </label>

                            <input
                                type="number"
                                name="precio"
                                id="precioProductoModificar"
                                min="1"
                                step="1"
                                required>

                        </div>

                        <div class="campoProducto">

                            <label for="precioOfertaModificar">
                                Precio de oferta
                            </label>

                            <input
                                type="number"
                                name="precio_oferta"
                                id="precioOfertaModificar"
                                min="1"
                                step="1">

                        </div>

                        <div class="campoProducto">

                            <label for="inicioOfertaModificar">
                                Inicio de oferta
                            </label>

                            <input
                                type="datetime-local"
                                name="inicio_oferta"
                                id="inicioOfertaModificar">

                        </div>

                        <div class="campoProducto">

                            <label for="finOfertaModificar">
                                Fin de oferta
                            </label>

                            <input
                                type="datetime-local"
                                name="fin_oferta"
                                id="finOfertaModificar">

                        </div>

                        <div class="campoProducto">

                            <label>Margen estimado</label>

                            <div
                                id="margenEstimadoModificar"
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

                            <label for="garantiaModificar">
                                Garantía
                            </label>

                            <input
                                type="text"
                                name="garantia"
                                id="garantiaModificar"
                                maxlength="100">

                        </div>

                        <div class="campoProducto">

                            <label for="compatibilidadModificar">
                                Compatibilidad
                            </label>

                            <textarea
                                name="compatibilidad"
                                id="compatibilidadModificar"
                                maxlength="1000"></textarea>

                        </div>

                    </div>

                </section>

                <div id="respuestaModificarProducto"></div>

                <div class="botonesFormularioProducto">

                    <button
                        type="button"
                        class="btnCancelarProducto"
                        onclick="cerrarDialogModificarProducto()">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        id="btnGuardarModificarProducto"
                        class="btnGuardarProducto">

                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar cambios

                    </button>

                </div>

            </div>

        </div>

    </form>

</dialog>

<dialog id="dialogFinalizarCita">

    <h2>Finalizar Cita</h2>

    <form id="formFinalizarCita">

        <input type="hidden" id="idCitaFinalizar">

        <label>Observaciones del trabajo</label>

        <textarea
            id="observacionesFinalizar"
            rows="6"
            placeholder="Ej.: Cambio de pastillas de freno, limpieza de discos, prueba en ruta..."
            required>
        </textarea>

        <div class="botones">

            <button
                type="button"
                onclick="guardarFinalizacion()">
                Finalizar
            </button>

            <button
                type="button"
                onclick="dialogFinalizarCita.close()">
                Cancelar
            </button>

        </div>

    </form>

</dialog>


   <!-- =============================================
     DIÁLOGO PARA VER INFORMACIÓN DE LA CITA
============================================= -->

<dialog id="dialogVerCita" class="dialogVerCita">

    <div class="encabezadoVerCita">

        <div>
            <span class="iconoCita">
                <i class="fa-solid fa-calendar-check"></i>
            </span>

            <div>
                <h2>Información de la cita</h2>
                <p id="verNumeroReserva">Reserva</p>
            </div>
        </div>

        <button
            type="button"
            class="btnCerrarDialogo"
            onclick="cerrarDialogVerCita()"
            aria-label="Cerrar">

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>

    <div id="cargandoCita" class="cargandoCita">
        <i class="fa-solid fa-spinner fa-spin"></i>
        Cargando información...
    </div>

    <div id="contenidoVerCita" class="contenidoVerCita">

        <!-- Estado -->
        <div class="filaEstadoCita">

            <span>Estado de la cita</span>

            <span id="verEstado" class="estadoDetalleCita"></span>

        </div>

        <!-- Cliente -->
        <section class="seccionDetalleCita">

            <h3>
                <i class="fa-solid fa-user"></i>
                Información del cliente
            </h3>

            <div class="gridDetalleCita">

                <div class="campoDetalleCita">
                    <label>Nombre</label>
                    <p id="verNombre"></p>
                </div>

                <div class="campoDetalleCita">
                    <label>Teléfono</label>
                    <p id="verTelefono"></p>
                </div>

                <div class="campoDetalleCita campoCompleto">
                    <label>Correo electrónico</label>
                    <p id="verCorreo"></p>
                </div>

            </div>

        </section>

        <!-- Vehículo -->
        <section class="seccionDetalleCita">

            <h3>
                <i class="fa-solid fa-car"></i>
                Información del vehículo
            </h3>

            <div class="gridDetalleCita">

                <div class="campoDetalleCita">
                    <label>Vehículo</label>
                    <p id="verVehiculo"></p>
                </div>

                <div class="campoDetalleCita">
                    <label>Patente</label>
                    <p id="verPatente"></p>
                </div>

            </div>

        </section>

        <!-- Cita -->
        <section class="seccionDetalleCita">

            <h3>
                <i class="fa-solid fa-screwdriver-wrench"></i>
                Datos de la cita
            </h3>

            <div class="gridDetalleCita">

                <div class="campoDetalleCita campoCompleto">
                    <label>Servicio solicitado</label>
                    <p id="verServicio"></p>
                </div>

                <div class="campoDetalleCita">
                    <label>Fecha de reserva</label>
                    <p id="verFecha"></p>
                </div>

                <div class="campoDetalleCita">
                    <label>Fecha de registro</label>
                    <p id="verFechaRegistro"></p>
                </div>

                <div class="campoDetalleCita">
                    <label>Inicio del trabajo</label>
                    <p id="verFechaInicio"></p>
                </div>

                <div class="campoDetalleCita">
                    <label>Finalización del trabajo</label>
                    <p id="verFechaFin"></p>
                </div>

            </div>

        </section>

        <!-- Comentarios -->
        <section class="seccionDetalleCita">

            <h3>
                <i class="fa-solid fa-comment-dots"></i>
                Comentarios y observaciones
            </h3>

            <div class="campoDetalleCita campoTexto">

                <label>Comentario del cliente</label>

                <p id="verComentario"></p>

            </div>

            <div class="campoDetalleCita campoTexto">

                <label>Observaciones finales</label>

                <p id="verObservaciones"></p>

            </div>

        </section>

    </div>

</dialog>



<dialog id="dialogModificarCita" class="dialogModificarCita">

    <div class="encabezadoModificarCita">

        <h2>
            <i class="fa-solid fa-calendar-pen"></i>
            Modificar cita
        </h2>

        <button
            type="button"
            class="btnCerrarModificarCita"
            onclick="cerrarModificarCita()">

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>

    <form
        id="formModificarCita"
        onsubmit="event.preventDefault(); guardarModificacionCita();">

        <input type="hidden" id="idCitaModificar">

        <div class="gridModificarCita">

            <div class="campoModificarCita campoCompletoCita">
                <label>Número de reserva</label>
                <input
                    type="text"
                    id="reservaCitaModificar"
                    disabled>
            </div>

            <div class="campoModificarCita">
                <label>Nombre</label>
                <input
                    type="text"
                    id="nombreCitaModificar"
                    required>
            </div>

            <div class="campoModificarCita">
                <label>Teléfono</label>
                <input
                    type="tel"
                    id="telefonoCitaModificar"
                    maxlength="9"
                    required>
            </div>

            <div class="campoModificarCita campoCompletoCita">
                <label>Correo</label>
                <input
                    type="email"
                    id="correoCitaModificar"
                    required>
            </div>

            <div class="campoModificarCita">
                <label>Patente</label>
                <input
                    type="text"
                    id="patenteCitaModificar"
                    required>
            </div>

            <div class="campoModificarCita">
                <label>Vehículo y año</label>
                <input
                    type="text"
                    id="vehiculoCitaModificar">
            </div>

            <div class="campoModificarCita campoCompletoCita">
                <label>Servicio</label>

                <select id="servicioCitaModificar" required>
                    <option value="">Seleccione un servicio</option>
                </select>
            </div>

            <div class="campoModificarCita">
                <label>Fecha</label>

                <input
                    type="date"
                    id="fechaCitaModificar"
                    required>
            </div>

            <div class="campoModificarCita">
                <label>Hora</label>

                <select id="horaCitaModificar" required>
                    <option value="">Seleccione una hora</option>
                </select>
            </div>

            <div class="campoModificarCita campoCompletoCita">
                <label>Comentario</label>

                <textarea
                    id="comentarioCitaModificar"
                    rows="4"
                    placeholder="Comentario adicional"></textarea>
            </div>

        </div>

        <div id="mensajeModificarCita"></div>

        <div class="accionesModificarCita">

            <button
                type="button"
                class="btnCancelarModificacion"
                onclick="cerrarModificarCita()">

                Cancelar

            </button>

            <button
                type="submit"
                class="btnGuardarModificacion">

                <i class="fa-solid fa-floppy-disk"></i>
                Guardar cambios

            </button>

        </div>

    </form>

</dialog>


<dialog id="dialogNuevaCita" class="dialogModificarCita">

    <div class="encabezadoModificarCita">

        <h2>
            <i class="fa-solid fa-calendar-plus"></i>
            Reservar nueva cita
        </h2>

        <button
            type="button"
            class="btnCerrarModificarCita"
            onclick="cerrarDialogNuevaCita()"
            title="Cerrar">

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>

    <form
        id="formNuevaCitaAdmin"
        class="formularioCitaAdmin"
        onsubmit="event.preventDefault(); guardarNuevaCitaAdmin();">

        <div class="gridModificarCita">

            <div class="campoModificarCita campoCompletoCita">

                <label>Nombre completo</label>

                <input
                    type="text"
                    id="nombreNuevaCita"
                    maxlength="100"
                    required>

            </div>

            <div class="campoModificarCita">

                <label>Teléfono</label>

                <input
                    type="tel"
                    id="telefonoNuevaCita"
                    maxlength="9"
                    placeholder="9 dígitos"
                    required>

            </div>

            <div class="campoModificarCita">

                <label>Correo electrónico</label>

                <input
                    type="email"
                    id="correoNuevaCita"
                    maxlength="100"
                    required>

            </div>

            <div class="campoModificarCita">

                <label>Patente</label>

                <input
                    type="text"
                    id="patenteNuevaCita"
                    maxlength="20"
                    required>

            </div>

            <div class="campoModificarCita">

                <label>Vehículo y año</label>

                <input
                    type="text"
                    id="vehiculoNuevaCita"
                    maxlength="100"
                    required>

            </div>

            <div class="campoModificarCita campoCompletoCita">

                <label>Servicio</label>

                <select id="servicioNuevaCita" required>

                    <option value="">
                        Seleccione un servicio
                    </option>

                </select>

            </div>

            <div class="campoModificarCita">

                <label>Fecha</label>

                <input
                    type="date"
                    id="fechaNuevaCita"
                    required>

            </div>

            <div class="campoModificarCita">

                <label>Hora</label>

                <select id="horaNuevaCita" required>

                    <option value="">
                        Seleccione una fecha
                    </option>

                </select>

            </div>

            <div class="campoModificarCita campoCompletoCita">

                <label>Comentario</label>

                <textarea
                    id="comentarioNuevaCita"
                    rows="4"
                    placeholder="Comentarios o indicaciones del cliente"></textarea>

            </div>

        </div>

        <div id="mensajeNuevaCita"></div>

        <div class="accionesModificarCita">

            <button
                type="button"
                class="btnCancelarModificacion"
                onclick="cerrarDialogNuevaCita()">

                Cancelar

            </button>

            <button
                type="submit"
                id="btnGuardarNuevaCita"
                class="btnGuardarModificacion">

                <i class="fa-solid fa-calendar-check"></i>
                Reservar cita

            </button>

        </div>

    </form>

</dialog>



<!-- =====================================================
     DIÁLOGO: REGISTRAR ENTRADA DE MERCADERÍA
     Péguelo junto a los demás dialog del panel admin.
====================================================== -->

<dialog
    id="dialogEntradaInventario"
    class="dialogModificarProductoCompleto dialogEntradaInventario">

    <form
        id="formEntradaInventario"
        method="dialog">

        <div class="cabeceraDialogAdmin">

            <div class="tituloDialogAdmin">

                <div class="iconoDialogAdmin">
                    <i class="fa-solid fa-dolly"></i>
                </div>

                <div>
                    <span>GESTIÓN DE INVENTARIO</span>
                    <h2>Registrar entrada de mercadería</h2>
                    <p>
                        Registre los productos recibidos y actualice
                        las existencias del inventario.
                    </p>
                </div>

            </div>

            <button
                type="button"
                id="btnCerrarDialogEntradaInventario"
                class="btnCerrarDialogAdmin"
                aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div class="contenidoDialogModificarProducto contenidoEntradaInventario">

            <aside class="columnaImagenModificarProducto resumenLateralEntrada">

                <div class="contenedorPreviewProducto iconoEntradaMercaderia">
                    <i class="fa-solid fa-dolly"></i>
                </div>

                <h3>Entrada de mercadería</h3>
                <p>
                    El stock y el costo promedio se actualizarán
                    cuando confirme el registro.
                </p>

                <div class="resumenTotalesEntrada">
                    <div>
                        <span>Neto</span>
                        <strong id="netoEntradaInventario">$0</strong>
                    </div>
                    <div>
                        <span>IVA 19%</span>
                        <strong id="ivaEntradaInventario">$0</strong>
                    </div>
                    <div class="totalDestacadoEntrada">
                        <span>Total</span>
                        <strong id="totalEntradaInventario">$0</strong>
                    </div>
                </div>

                <div class="opcionesPublicacionProducto">
                    <div class="opcionCheckProducto informacionProtegidaEntrada">
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Movimiento protegido</strong>
                            <small>
                                La operación quedará asociada al usuario responsable.
                            </small>
                        </div>
                    </div>
                </div>

            </aside>

            <div class="camposDialogModificarProducto">

                <section class="grupoFormularioProducto">

                    <h3>
                        <i class="fa-solid fa-circle-info"></i>
                        Información de la entrada
                    </h3>

                    <div class="gridCamposProducto">

                        <div class="campoProducto">
                            <label for="proveedorEntradaInventario">
                                Proveedor
                            </label>
                            <select id="proveedorEntradaInventario">
                                <option value="">Sin proveedor</option>
                            </select>
                        </div>

                        <div class="campoProducto">
                            <label for="tipoDocumentoEntrada">
                                Tipo de documento *
                            </label>
                            <select id="tipoDocumentoEntrada" required>
                                <option value="factura">Factura</option>
                                <option value="boleta">Boleta</option>
                                <option value="guia_despacho">Guía de despacho</option>
                                <option value="sin_documento">Sin documento</option>
                            </select>
                        </div>

                        <div class="campoProducto">
                            <label for="numeroDocumentoEntrada">
                                Número de documento *
                            </label>
                            <input
                                type="text"
                                id="numeroDocumentoEntrada"
                                maxlength="60"
                                autocomplete="off"
                                required>
                        </div>

                        <div class="campoProducto">
                            <label for="fechaDocumentoEntrada">
                                Fecha del documento
                            </label>
                            <input
                                type="date"
                                id="fechaDocumentoEntrada">
                        </div>

                        <div class="campoProducto campoCompleto">
                            <label for="observacionesEntradaInventario">
                                Observaciones
                            </label>
                            <textarea
                                id="observacionesEntradaInventario"
                                maxlength="500"
                                rows="3"
                                placeholder="Información adicional de la recepción..."></textarea>
                        </div>

                    </div>

                </section>

                <section class="grupoFormularioProducto">

                    <h3>
                        <i class="fa-solid fa-boxes-stacked"></i>
                        Productos recibidos
                    </h3>

                    <div class="gridAgregarProductoEntrada">

                        <div class="campoProducto selectorProductoEntrada">
                            <label for="productoNuevaEntrada">Producto *</label>
                            <select id="productoNuevaEntrada">
                                <option value="">Seleccione un producto</option>
                            </select>
                        </div>

                        <div class="campoProducto">
                            <label for="cantidadNuevaEntrada">Cantidad *</label>
                            <input
                                type="number"
                                id="cantidadNuevaEntrada"
                                min="1"
                                max="100000"
                                step="1"
                                value="1">
                        </div>

                        <div class="campoProducto">
                            <label for="costoNuevaEntrada">
                                Costo unitario con IVA *
                            </label>
                            <input
                                type="number"
                                id="costoNuevaEntrada"
                                min="1"
                                step="1"
                                placeholder="$0">
                        </div>

                        <button
                            type="button"
                            id="btnAgregarProductoEntrada"
                            class="btnAgregarProductoEntrada">
                            <i class="fa-solid fa-plus"></i>
                            Agregar
                        </button>

                    </div>

                    <div class="contenedorTablaInventario tablaProductosEntrada">
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Costo unitario</th>
                                    <th>Total</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyProductosEntradaInventario">
                                <tr>
                                    <td colspan="5">
                                        Todavía no agregó productos.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        id="respuestaEntradaInventario"
                        class="respuestaInventario"
                        role="status"></div>

                </section>

            </div>

        </div>

        <div class="botonesFormularioProducto pieFormularioEntrada">

            <button
                type="button"
                id="btnCancelarEntradaInventario"
                class="btnCancelarProducto">
                Cancelar
            </button>

            <button
                type="submit"
                id="btnGuardarEntradaInventario"
                class="btnGuardarProducto">
                <i class="fa-solid fa-floppy-disk"></i>
                Registrar entrada
            </button>

        </div>

    </form>

</dialog>



<dialog
    id="dialogAjusteInventario"
    class="dialogModificarProductoCompleto dialogAjusteInventario">

    <form id="formAjusteInventario" method="dialog">

        <input type="hidden" id="idProductoAjusteInventario">

        <div class="cabeceraDialogAdmin">

            <div class="tituloDialogAdmin">

                <div class="iconoDialogAdmin">
                    <i class="fa-solid fa-sliders"></i>
                </div>

                <div>
                    <span>GESTIÓN DE INVENTARIO</span>
                    <h2>Ajustar stock</h2>
                    <p>
                        Registre una corrección de existencias
                        indicando su motivo.
                    </p>
                </div>

            </div>

            <button
                type="button"
                id="btnCerrarDialogAjusteInventario"
                class="btnCerrarDialogAdmin"
                aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div class="contenidoDialogModificarProducto contenidoAjusteInventario">

            <aside class="columnaImagenModificarProducto resumenLateralAjuste">

                <div class="contenedorPreviewProducto iconoAjusteInventario">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>

                <h3 id="nombreProductoAjusteInventario">Producto</h3>
                <p>
                    SKU:
                    <strong id="skuProductoAjusteInventario">—</strong>
                </p>

                <div class="comparacionStockAjuste">
                    <div>
                        <span>Stock actual</span>
                        <strong id="stockActualAjusteInventario">0</strong>
                    </div>

                    <i class="fa-solid fa-arrow-right"></i>

                    <div>
                        <span>Stock resultante</span>
                        <strong id="stockResultadoAjusteInventario">0</strong>
                    </div>
                </div>

                <div class="opcionesPublicacionProducto">
                    <div class="opcionCheckProducto informacionProtegidaEntrada">
                        <i class="fa-solid fa-shield-halved"></i>
                        <div>
                            <strong>Ajuste auditable</strong>
                            <small>
                                El cambio quedará asociado al usuario responsable.
                            </small>
                        </div>
                    </div>
                </div>

            </aside>

            <div class="camposDialogModificarProducto">

                <section class="grupoFormularioProducto">

                    <h3>
                        <i class="fa-solid fa-circle-info"></i>
                        Información del ajuste
                    </h3>

                    <div class="gridCamposProducto">

                        <div class="campoProducto">
                            <label for="tipoAjusteInventario">
                                Tipo de ajuste *
                            </label>
                            <select id="tipoAjusteInventario" required>
                                <option value="entrada">Aumentar stock</option>
                                <option value="salida">Disminuir stock</option>
                                <option value="merma">Registrar merma</option>
                            </select>
                        </div>

                        <div class="campoProducto">
                            <label for="cantidadAjusteInventario">
                                Cantidad *
                            </label>
                            <input
                                type="number"
                                id="cantidadAjusteInventario"
                                min="1"
                                max="100000"
                                step="1"
                                value="1"
                                required>
                        </div>

                        <div class="campoProducto campoCompleto">
                            <label for="motivoAjusteInventario">
                                Motivo del ajuste *
                            </label>
                            <textarea
                                id="motivoAjusteInventario"
                                minlength="5"
                                maxlength="500"
                                rows="5"
                                placeholder="Explique por qué se modifica el stock..."
                                required></textarea>
                            <small>
                                El motivo debe tener al menos 5 caracteres.
                            </small>
                        </div>

                    </div>

                    <div
                        id="respuestaAjusteInventario"
                        class="respuestaInventario"
                        role="status"></div>

                </section>

            </div>

        </div>

        <div class="botonesFormularioProducto pieFormularioAjuste">

            <button
                type="button"
                id="btnCancelarAjusteInventario"
                class="btnCancelarProducto">
                Cancelar
            </button>

            <button
                type="submit"
                id="btnGuardarAjusteInventario"
                class="btnGuardarProducto">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar ajuste
            </button>

        </div>

    </form>

</dialog>



<dialog
    id="dialogDetalleEntradaInventario"
    class="dialogModificarProductoCompleto dialogDetalleEntradaInventario">

    <div class="cabeceraDialogAdmin">

        <div class="tituloDialogAdmin">

            <div class="iconoDialogAdmin">
                <i class="fa-solid fa-file-invoice"></i>
            </div>

            <div>
                <span>GESTIÓN DE INVENTARIO</span>
                <h2>Detalle de entrada</h2>
                <p>Información completa de la mercadería recibida.</p>
            </div>

        </div>

        <button
            type="button"
            id="btnCerrarDetalleEntradaInventario"
            class="btnCerrarDialogAdmin"
            aria-label="Cerrar">
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

    <div class="contenidoDetalleEntradaInventario">

        <section class="grupoFormularioProducto">

            <h3>
                <i class="fa-solid fa-circle-info"></i>
                Información general
            </h3>

            <div class="gridDatosDetalleEntrada">

                <div>
                    <span>Número de entrada</span>
                    <strong id="numeroDetalleEntradaInventario">—</strong>
                </div>

                <div>
                    <span>Fecha</span>
                    <strong id="fechaDetalleEntradaInventario">—</strong>
                </div>

                <div>
                    <span>Proveedor</span>
                    <strong id="proveedorDetalleEntradaInventario">—</strong>
                </div>

                <div>
                    <span>RUT del proveedor</span>
                    <strong id="rutDetalleEntradaInventario">—</strong>
                </div>

                <div>
                    <span>Documento</span>
                    <strong id="documentoDetalleEntradaInventario">—</strong>
                </div>

                <div>
                    <span>Responsable</span>
                    <strong id="responsableDetalleEntradaInventario">—</strong>
                </div>

            </div>

        </section>

        <section class="grupoFormularioProducto">

            <h3>
                <i class="fa-solid fa-boxes-stacked"></i>
                Productos recibidos
            </h3>

            <div class="contenedorTablaInventario tablaDetalleEntrada">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Costo unitario</th>
                            <th>Total</th>
                            <th>Stock actual</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDetalleEntradaInventario">
                        <tr>
                            <td colspan="5">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                Cargando productos...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </section>

        <div class="seccionInferiorDetalleEntrada">

            <section class="grupoFormularioProducto observacionesDetalleEntrada">
                <h3>
                    <i class="fa-solid fa-note-sticky"></i>
                    Observaciones
                </h3>
                <p id="observacionesDetalleEntradaInventario">
                    Sin observaciones.
                </p>
            </section>

            <section class="totalesDetalleEntrada">
                <div>
                    <span>Neto</span>
                    <strong id="netoDetalleEntradaInventario">$0</strong>
                </div>
                <div>
                    <span>IVA 19%</span>
                    <strong id="ivaDetalleEntradaInventario">$0</strong>
                </div>
                <div class="totalFinalDetalleEntrada">
                    <span>Total</span>
                    <strong id="totalDetalleEntradaInventario">$0</strong>
                </div>
            </section>

        </div>

    </div>

    <div class="botonesFormularioProducto pieDetalleEntradaInventario">
        <button
            type="button"
            id="btnAceptarDetalleEntradaInventario"
            class="btnGuardarProducto">
            <i class="fa-solid fa-check"></i>
            Aceptar
        </button>
    </div>

</dialog>



   
   <dialog id="modalConfirmacion">

    <h2 id="headDialog"></h2>

    <p id="bodyDialog"></p>

    <div class="botones">

        <button id="btnConfirmacion" class="btnSi">SI</button>

        <button type="button" class="btnNo" onclick="document.getElementById('modalConfirmacion').close()">NO</button>

    </div>

    </dialog>


    <dialog id="modalText">

    <h2 id="headDialog1"></h2>

    <p id="bodyDialog1"></p>

    <div class="botones">

        <button class="btnNo" onclick="document.getElementById('modalText').close()">OK</button>
        
    </div>

    </dialog>

    </main>


</div>

<script src="./js/admin/acceso.js"></script>

<script src="./js/admin.js"></script>

<script src="./js/admin/sesionAdmin.js"></script>

<script src="./js/admin/usuarios.js"></script>
<script src="./js/admin/categorias.js"></script>
<script src="./js/admin/productos.js"></script>
<script src="./js/admin/servicios.js"></script>
<script src="./js/admin/citas.js"></script>
<script src="./js/admin/pedidos.js"></script>
<script src="./js/admin/inventario.js"></script>
<script src="./js/admin/rentabilidad.js"></script>
<script src="./js/admin/ventas.js"></script>
<script src="./js/admin/cotizaciones.js"></script>
<script src="./js/admin/salarios.js"></script>
<script src="./js/admin/inicioAdmin.js"></script>

<script src="./js/iniciarSesion.js"></script>
<script src="./js/admin/dialog.js"></script>


</body>
</html>