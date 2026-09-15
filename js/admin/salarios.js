let remuneracionesAdmin = [];
let remuneracionesFiltradas = [];
let permisosRemuneraciones = {};
let comisionesAdmin = [];
let liquidacionesAdmin = [];
let permisosLiquidaciones = {};
let detalleLiquidacionActual = null;

async function cargarSalariosAdmin() {
    const cuerpo = document.getElementById("tablaSalariosBody");
    if (!cuerpo) return;
    cuerpo.innerHTML = '<tr><td colspan="10" class="salarios-vacio">Cargando remuneraciones...</td></tr>';
    try {
        const respuesta = await fetch("./php/obtenerRemuneraciones.php", {cache: "no-store"});
        const texto = await respuesta.text();
        let resultado;
        try { resultado = JSON.parse(texto); } catch (_) { throw new Error("El servidor no devolvió una respuesta JSON válida."); }
        if (resultado.sesionExpirada) { window.location.href = "page-login.html"; return; }
        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible cargar las remuneraciones.");
        remuneracionesAdmin = Array.isArray(resultado.datos) ? resultado.datos : [];
        permisosRemuneraciones = resultado.permisos || {};
        cargarOpcionesUsuariosComisiones();
        cargarOpcionesUsuariosLiquidaciones();
        actualizarResumenSalarios(remuneracionesAdmin);
        aplicarFiltrosSalarios();
    } catch (error) {
        console.error(error);
        cuerpo.innerHTML = `<tr><td colspan="10" class="salarios-vacio">${escaparSalarios(error.message)}</td></tr>`;
    }
}

function actualizarResumenSalarios(lista) {
    const sumar = campo => lista.reduce((total, fila) => total + Number(fila[campo] || 0), 0);
    asignarTextoSalarios("salariosTotalUsuarios", lista.length);
    asignarTextoSalarios("salariosTotalBase", monedaSalarios(sumar("sueldo_base_mensual")));
    asignarTextoSalarios("salariosComisionesPendientes", monedaSalarios(sumar("comisiones_pendientes")));
    asignarTextoSalarios("salariosTotalEstimado", monedaSalarios(sumar("estimado_actual")));
}

function aplicarFiltrosSalarios() {
    const busqueda = (document.getElementById("buscarSalarioUsuario")?.value || "").trim().toLowerCase();
    const rol = document.getElementById("filtrarSalarioRol")?.value || "";
    const estado = document.getElementById("filtrarSalarioEstado")?.value || "";
    remuneracionesFiltradas = remuneracionesAdmin.filter(fila => {
        const contenido = `${fila.usuario} ${fila.correo} ${fila.rol}`.toLowerCase();
        return (!busqueda || contenido.includes(busqueda)) && (!rol || fila.rol === rol) && (!estado || fila.estado_configuracion === estado);
    });
    renderizarSalarios();
}

function renderizarSalarios() {
    const cuerpo = document.getElementById("tablaSalariosBody");
    const contador = document.getElementById("cantidadResultadosSalarios");
    if (!cuerpo) return;
    if (contador) contador.textContent = `${remuneracionesFiltradas.length} resultado${remuneracionesFiltradas.length === 1 ? "" : "s"}`;
    if (!remuneracionesFiltradas.length) {
        cuerpo.innerHTML = '<tr><td colspan="10" class="salarios-vacio">No se encontraron remuneraciones.</td></tr>';
        return;
    }
    cuerpo.innerHTML = remuneracionesFiltradas.map(fila => {
        const accion = permisosRemuneraciones.gestionar
            ? `<button class="salarios-editar" type="button" data-editar-remuneracion="${fila.id_usuario}" title="Modificar remuneración"><i class="fa-solid fa-pen"></i></button>` : "—";
        return `<tr>
            <td class="salarios-usuario"><strong>${escaparSalarios(fila.usuario)}</strong><small>${escaparSalarios(fila.correo)}</small></td>
            <td>${capitalizarSalarios(fila.rol)}</td>
            <td class="salarios-dinero">${monedaSalarios(fila.sueldo_base_mensual)}</td>
            <td class="salarios-porcentaje">${porcentajeSalarios(fila.porcentaje_ventas)}</td>
            <td class="salarios-porcentaje">${porcentajeSalarios(fila.porcentaje_servicios)}</td>
            <td class="salarios-porcentaje">${porcentajeSalarios(fila.porcentaje_mano_obra)}</td>
            <td class="salarios-dinero">${monedaSalarios(fila.comisiones_pendientes)}</td>
            <td class="salarios-dinero salarios-total">${monedaSalarios(fila.estimado_actual)}</td>
            <td><span class="salarios-insignia ${fila.estado_configuracion === "activo" ? "activo" : "inactivo"}">${fila.estado_configuracion === "activo" ? "Activa" : "Inactiva"}</span></td>
            <td class="salarios-col-acciones">${accion}</td>
        </tr>`;
    }).join("");
}

function abrirDialogRemuneracion(idUsuario) {
    if (!permisosRemuneraciones.gestionar) return;
    const fila = remuneracionesAdmin.find(item => Number(item.id_usuario) === Number(idUsuario));
    const dialogo = document.getElementById("dialogRemuneracion");
    if (!fila || !dialogo) return;
    document.getElementById("remuneracionIdUsuario").value = fila.id_usuario;
    document.getElementById("remuneracionUsuarioTexto").textContent = `${fila.usuario} · ${capitalizarSalarios(fila.rol)}`;
    document.getElementById("remuneracionSueldoBase").value = fila.sueldo_base_mensual;
    document.getElementById("remuneracionVentas").value = fila.porcentaje_ventas;
    document.getElementById("remuneracionServicios").value = fila.porcentaje_servicios;
    document.getElementById("remuneracionManoObra").value = fila.porcentaje_mano_obra;
    document.getElementById("remuneracionEstado").value = fila.estado_configuracion;
    dialogo.showModal();
}

async function guardarConfiguracionRemuneracion(evento) {
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const boton = formulario.querySelector('[type="submit"]');
    boton.disabled = true;
    try {
        const respuesta = await fetch("./php/guardarRemuneracion.php", {method: "POST", body: new FormData(formulario)});
        const texto = await respuesta.text();
        let resultado;
        try { resultado = JSON.parse(texto); } catch (_) { throw new Error("El servidor no devolvió una respuesta JSON válida."); }
        if (resultado.sesionExpirada) { window.location.href = "page-login.html"; return; }
        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible guardar la configuración.");
        document.getElementById("dialogRemuneracion").close();
        if (typeof Swal !== "undefined") await Swal.fire({icon: "success", title: "Configuración guardada", text: resultado.mensaje});
        else alert(resultado.mensaje);
        await cargarSalariosAdmin();
    } catch (error) {
        if (typeof Swal !== "undefined") Swal.fire({icon: "error", title: "No fue posible guardar", text: error.message});
        else alert(error.message);
    } finally { boton.disabled = false; }
}

function inicializarSalariosAdmin() {
    const raiz = document.getElementById("salariosAdmin");
    if (!raiz || raiz.dataset.inicializado === "1") return;
    raiz.dataset.inicializado = "1";
    document.getElementById("buscarSalarioUsuario")?.addEventListener("input", aplicarFiltrosSalarios);
    document.getElementById("filtrarSalarioRol")?.addEventListener("change", aplicarFiltrosSalarios);
    document.getElementById("filtrarSalarioEstado")?.addEventListener("change", aplicarFiltrosSalarios);
    document.getElementById("btnRecargarSalarios")?.addEventListener("click", cargarSalariosAdmin);
    document.getElementById("btnLimpiarFiltrosSalarios")?.addEventListener("click", () => {
        document.getElementById("buscarSalarioUsuario").value = "";
        document.getElementById("filtrarSalarioRol").value = "";
        document.getElementById("filtrarSalarioEstado").value = "";
        aplicarFiltrosSalarios();
    });
    document.getElementById("tablaSalariosBody")?.addEventListener("click", e => {
        const boton = e.target.closest("[data-editar-remuneracion]");
        if (boton) abrirDialogRemuneracion(boton.dataset.editarRemuneracion);
    });
    document.getElementById("formRemuneracion")?.addEventListener("submit", guardarConfiguracionRemuneracion);
    document.getElementById("cerrarDialogRemuneracion")?.addEventListener("click", () => document.getElementById("dialogRemuneracion").close());
    document.getElementById("cancelarDialogRemuneracion")?.addEventListener("click", () => document.getElementById("dialogRemuneracion").close());
    document.getElementById("btnFiltrarComisiones")?.addEventListener("click", cargarComisionesAdmin);
    document.getElementById("btnActualizarComisiones")?.addEventListener("click", cargarComisionesAdmin);
    document.getElementById("btnFiltrarLiquidaciones")?.addEventListener("click", cargarLiquidacionesAdmin);
    document.getElementById("btnNuevaLiquidacion")?.addEventListener("click", abrirDialogCrearLiquidacion);
    document.getElementById("formCrearLiquidacion")?.addEventListener("submit", guardarNuevaLiquidacion);
    document.getElementById("cerrarDialogLiquidacion")?.addEventListener("click", cerrarDialogCrearLiquidacion);
    document.getElementById("cancelarDialogLiquidacion")?.addEventListener("click", cerrarDialogCrearLiquidacion);
    document.getElementById("cerrarDetalleLiquidacion")?.addEventListener("click", cerrarDialogDetalleLiquidacion);
    document.getElementById("cerrarDetalleLiquidacionInferior")?.addEventListener("click", cerrarDialogDetalleLiquidacion);
    document.getElementById("imprimirDetalleLiquidacion")?.addEventListener("click", imprimirDetalleLiquidacion);
    document.getElementById("tablaLiquidacionesBody")?.addEventListener("click", evento => {
        const botonVer = evento.target.closest("[data-ver-liquidacion]");
        if (botonVer) {
            mostrarResumenLiquidacion(botonVer.dataset.verLiquidacion);
            return;
        }

        const botonAccion = evento.target.closest("[data-accion-liquidacion]");
        if (botonAccion) {
            procesarLiquidacionAdmin(
                botonAccion.dataset.idLiquidacion,
                botonAccion.dataset.accionLiquidacion,
                botonAccion
            );
        }
    });
    prepararFechasComisiones();
    prepararFechasLiquidaciones();
    cargarSalariosAdmin();
    cargarComisionesAdmin();
    cargarLiquidacionesAdmin();
}

function prepararFechasComisiones() {
    const hoy = new Date();
    const inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const convertir = fecha => {
        const local = new Date(fecha.getTime() - fecha.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 10);
    };
    const desde = document.getElementById("comisionesDesde");
    const hasta = document.getElementById("comisionesHasta");
    if (desde && !desde.value) desde.value = convertir(inicio);
    if (hasta && !hasta.value) hasta.value = convertir(hoy);
}

function cargarOpcionesUsuariosComisiones() {
    const select = document.getElementById("comisionesUsuario");
    const grupo = document.getElementById("grupoUsuarioComision");
    if (!select) return;
    if (!permisosRemuneraciones.consultar_todos) {
        if (grupo) grupo.hidden = true;
        return;
    }
    if (grupo) grupo.hidden = false;
    const actual = select.value;
    select.innerHTML = '<option value="0">Todos los usuarios</option>' + remuneracionesAdmin.map(fila => `<option value="${fila.id_usuario}">${escaparSalarios(fila.usuario)} · ${capitalizarSalarios(fila.rol)}</option>`).join("");
    select.value = actual;
}

async function cargarComisionesAdmin() {
    const cuerpo = document.getElementById("tablaComisionesBody");
    if (!cuerpo) return;
    cuerpo.innerHTML = '<tr><td colspan="10" class="salarios-vacio">Cargando comisiones...</td></tr>';
    const parametros = new URLSearchParams({
        desde: document.getElementById("comisionesDesde")?.value || "",
        hasta: document.getElementById("comisionesHasta")?.value || "",
        id_usuario: document.getElementById("comisionesUsuario")?.value || "0",
        tipo: document.getElementById("comisionesTipo")?.value || "",
        estado: document.getElementById("comisionesEstado")?.value || ""
    });
    try {
        const respuesta = await fetch(`./php/obtenerComisiones.php?${parametros}`, {cache: "no-store"});
        const texto = await respuesta.text();
        let resultado;
        try { resultado = JSON.parse(texto); } catch (_) { throw new Error("El servidor no devolvió una respuesta JSON válida."); }
        if (resultado.sesionExpirada) { window.location.href = "page-login.html"; return; }
        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible cargar las comisiones.");
        comisionesAdmin = Array.isArray(resultado.datos) ? resultado.datos : [];
        mostrarResumenComisiones(resultado.resumen || {});
        renderizarComisionesAdmin();
    } catch (error) {
        console.error(error);
        cuerpo.innerHTML = `<tr><td colspan="10" class="salarios-vacio">${escaparSalarios(error.message)}</td></tr>`;
    }
}

function mostrarResumenComisiones(resumen) {
    asignarTextoSalarios("comisionesCantidad", Number(resumen.cantidad || 0));
    asignarTextoSalarios("comisionesBaseTotal", monedaSalarios(resumen.base_total));
    asignarTextoSalarios("comisionesMontoPendiente", monedaSalarios(resumen.pendiente));
    asignarTextoSalarios("comisionesMontoTotal", monedaSalarios(resumen.monto_total));
}

function renderizarComisionesAdmin() {
    const cuerpo = document.getElementById("tablaComisionesBody");
    if (!cuerpo) return;
    if (!comisionesAdmin.length) {
        cuerpo.innerHTML = '<tr><td colspan="10" class="salarios-vacio">No existen comisiones para los filtros seleccionados.</td></tr>';
        return;
    }
    cuerpo.innerHTML = comisionesAdmin.map(fila => `<tr>
        <td>${fechaSalarios(fila.fecha_generacion)}</td>
        <td class="salarios-usuario"><strong>${escaparSalarios(fila.usuario)}</strong><small>${capitalizarSalarios(fila.rol)}</small></td>
        <td><span class="comisiones-tipo">${nombreTipoComision(fila.tipo_comision)}</span></td>
        <td>${nombreOrigenComision(fila.origen)}</td>
        <td>${escaparSalarios(fila.numero_pedido || fila.numero_ot || "—")}</td>
        <td>${escaparSalarios(fila.concepto || fila.observaciones || "—")}</td>
        <td class="salarios-dinero">${monedaSalarios(fila.base_calculo)}</td>
        <td class="salarios-porcentaje">${porcentajeSalarios(fila.porcentaje_aplicado)}</td>
        <td class="salarios-dinero salarios-total">${monedaSalarios(fila.monto_comision)}</td>
        <td><span class="comisiones-estado ${escaparSalarios(fila.estado)}">${capitalizarSalarios(fila.estado)}</span></td>
    </tr>`).join("");
}

function nombreTipoComision(tipo) {
    return ({venta: "Venta", servicio: "Servicio", mano_obra: "Mano de obra"})[tipo] || tipo;
}

function nombreOrigenComision(origen) {
    return ({venta_presencial: "Venta presencial", venta_online: "Venta en línea", orden_trabajo: "Orden de trabajo"})[origen] || origen;
}

function fechaSalarios(valor) {
    if (!valor) return "—";
    const fecha = new Date(String(valor).replace(" ", "T"));
    return Number.isNaN(fecha.getTime()) ? valor : fecha.toLocaleString("es-CL", {dateStyle: "short", timeStyle: "short"});
}

function prepararFechasLiquidaciones() {
    const hoy = new Date();
    const inicioAnio = new Date(hoy.getFullYear(), 0, 1);
    const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const convertir = fecha => {
        const local = new Date(fecha.getTime() - fecha.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 10);
    };
    asignarValorFecha("liquidacionesDesde", convertir(inicioAnio));
    asignarValorFecha("liquidacionesHasta", convertir(hoy));
    asignarValorFecha("liquidacionDesde", convertir(inicioMes));
    asignarValorFecha("liquidacionHasta", convertir(hoy));
}

function asignarValorFecha(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento && !elemento.value) elemento.value = valor;
}

function cargarOpcionesUsuariosLiquidaciones() {
    const filtro = document.getElementById("liquidacionesUsuario");
    const formulario = document.getElementById("liquidacionIdUsuario");
    const opciones = remuneracionesAdmin.map(fila => `<option value="${fila.id_usuario}">${escaparSalarios(fila.usuario)} · ${capitalizarSalarios(fila.rol)}</option>`).join("");
    if (filtro) filtro.innerHTML = '<option value="0">Todos los usuarios</option>' + opciones;
    if (formulario) formulario.innerHTML = '<option value="">Seleccione un usuario</option>' + opciones;
}

async function cargarLiquidacionesAdmin() {
    const cuerpo = document.getElementById("tablaLiquidacionesBody");
    if (!cuerpo) return;
    cuerpo.innerHTML = '<tr><td colspan="10" class="salarios-vacio">Cargando liquidaciones...</td></tr>';
    const parametros = new URLSearchParams({
        desde: document.getElementById("liquidacionesDesde")?.value || "",
        hasta: document.getElementById("liquidacionesHasta")?.value || "",
        id_usuario: document.getElementById("liquidacionesUsuario")?.value || "0",
        estado: document.getElementById("liquidacionesEstado")?.value || ""
    });
    try {
        const respuesta = await fetch(`./php/obtenerLiquidaciones.php?${parametros}`, {cache: "no-store"});
        const texto = await respuesta.text();
        let resultado;
        try { resultado = JSON.parse(texto); } catch (_) { throw new Error("El servidor no devolvió una respuesta JSON válida."); }
        if (resultado.sesionExpirada) { window.location.href = "page-login.html"; return; }
        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible cargar las liquidaciones.");
        liquidacionesAdmin = Array.isArray(resultado.datos) ? resultado.datos : [];
        permisosLiquidaciones = resultado.permisos || {};
        aplicarPermisosLiquidaciones();
        mostrarResumenLiquidaciones(resultado.resumen || {});
        renderizarLiquidacionesAdmin();
    } catch (error) {
        console.error(error);
        cuerpo.innerHTML = `<tr><td colspan="10" class="salarios-vacio">${escaparSalarios(error.message)}</td></tr>`;
    }
}

function aplicarPermisosLiquidaciones() {
    const boton = document.getElementById("btnNuevaLiquidacion");
    const grupo = document.getElementById("grupoUsuarioLiquidacion");
    if (boton) boton.hidden = !permisosLiquidaciones.crear;
    if (grupo) grupo.hidden = !permisosLiquidaciones.consultar_todos;
}

function mostrarResumenLiquidaciones(resumen) {
    asignarTextoSalarios("liquidacionesCantidad", Number(resumen.cantidad || 0));
    asignarTextoSalarios("liquidacionesComisiones", monedaSalarios(resumen.total_comisiones));
    asignarTextoSalarios("liquidacionesPendiente", monedaSalarios(resumen.total_pendiente_pago));
    asignarTextoSalarios("liquidacionesPagado", monedaSalarios(resumen.total_pagado));
}

function renderizarLiquidacionesAdmin() {
    const cuerpo = document.getElementById("tablaLiquidacionesBody");
    if (!cuerpo) return;
    if (!liquidacionesAdmin.length) {
        cuerpo.innerHTML = '<tr><td colspan="10" class="salarios-vacio">No existen liquidaciones para los filtros seleccionados.</td></tr>';
        return;
    }
    cuerpo.innerHTML = liquidacionesAdmin.map(fila => `<tr>
        <td><strong>${fechaCortaSalarios(fila.periodo_desde)}</strong><br><small>al ${fechaCortaSalarios(fila.periodo_hasta)}</small></td>
        <td class="salarios-usuario"><strong>${escaparSalarios(fila.usuario)}</strong><small>${capitalizarSalarios(fila.rol)}</small></td>
        <td class="salarios-dinero">${monedaSalarios(fila.sueldo_base)}</td>
        <td class="salarios-dinero">${monedaSalarios(fila.total_comisiones)}<br><small>${fila.cantidad_comisiones} registros</small></td>
        <td class="salarios-dinero">${monedaSalarios(fila.bonos)}</td>
        <td class="salarios-dinero">${monedaSalarios(fila.descuentos)}</td>
        <td class="salarios-dinero salarios-total">${monedaSalarios(fila.total_liquidacion)}</td>
        <td><span class="liquidacion-estado ${escaparSalarios(fila.estado)}">${capitalizarSalarios(fila.estado)}</span></td>
        <td>${fila.fecha_pago ? fechaSalarios(fila.fecha_pago) : "—"}</td>
        <td class="liquidacion-acciones">${crearAccionesLiquidacion(fila)}</td>
    </tr>`).join("");
}

function crearAccionesLiquidacion(fila) {
    const id = Number(fila.id_liquidacion);
    const estado = String(fila.estado || "").toLowerCase();
    const botones = [
        `<button type="button" class="liquidacion-accion liquidacion-ver" data-ver-liquidacion="${id}" title="Ver resumen" aria-label="Ver resumen"><i class="fa-solid fa-eye"></i></button>`
    ];

    if (estado === "borrador" && permisosLiquidaciones.cerrar) {
        botones.push(`<button type="button" class="liquidacion-accion liquidacion-cerrar" data-id-liquidacion="${id}" data-accion-liquidacion="cerrar" title="Cerrar liquidación" aria-label="Cerrar liquidación"><i class="fa-solid fa-lock"></i></button>`);
    }

    if (estado === "cerrada" && permisosLiquidaciones.pagar) {
        botones.push(`<button type="button" class="liquidacion-accion liquidacion-pagar" data-id-liquidacion="${id}" data-accion-liquidacion="pagar" title="Registrar pago" aria-label="Registrar pago"><i class="fa-solid fa-money-check-dollar"></i></button>`);
    }

    if (["borrador", "cerrada"].includes(estado) && permisosLiquidaciones.anular) {
        botones.push(`<button type="button" class="liquidacion-accion liquidacion-anular" data-id-liquidacion="${id}" data-accion-liquidacion="anular" title="Anular liquidación" aria-label="Anular liquidación"><i class="fa-solid fa-ban"></i></button>`);
    }

    return botones.join("");
}

async function procesarLiquidacionAdmin(idLiquidacion, accion, boton) {
    const fila = liquidacionesAdmin.find(
        item => Number(item.id_liquidacion) === Number(idLiquidacion)
    );

    if (!fila || !["cerrar", "pagar", "anular"].includes(accion)) return;

    const textos = {
        cerrar: {
            titulo: "¿Cerrar liquidación?",
            mensaje: "Después de cerrarla quedará preparada para registrar el pago.",
            confirmar: "Sí, cerrar"
        },
        pagar: {
            titulo: "¿Confirmar el pago?",
            mensaje: `Se registrará como pagada la liquidación de ${fila.usuario} por ${monedaSalarios(fila.total_liquidacion)}.`,
            confirmar: "Sí, registrar pago"
        },
        anular: {
            titulo: "¿Anular liquidación?",
            mensaje: "Las comisiones y ajustes vinculados volverán a quedar disponibles.",
            confirmar: "Sí, anular"
        }
    };

    const contenido = textos[accion];
    let confirmado = false;

    if (typeof Swal !== "undefined") {
        const respuesta = await Swal.fire({
            icon: accion === "anular" ? "warning" : "question",
            title: contenido.titulo,
            text: contenido.mensaje,
            showCancelButton: true,
            confirmButtonText: contenido.confirmar,
            cancelButtonText: "Cancelar",
            confirmButtonColor: accion === "anular" ? "#dc2626" : "#2878e8",
            reverseButtons: true
        });
        confirmado = respuesta.isConfirmed;
    } else {
        confirmado = window.confirm(`${contenido.titulo}\n\n${contenido.mensaje}`);
    }

    if (!confirmado) return;

    boton.disabled = true;

    try {
        const datos = new FormData();
        datos.append("id_liquidacion", String(idLiquidacion));
        datos.append("accion", accion);

        const respuesta = await fetch(
            "./php/procesarLiquidacionRemuneracion.php",
            {method: "POST", body: datos}
        );

        const texto = await respuesta.text();
        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (_) {
            throw new Error("El servidor no devolvió una respuesta JSON válida.");
        }

        if (resultado.sesionExpirada) {
            window.location.href = "page-login.html";
            return;
        }

        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.mensaje || "No fue posible procesar la liquidación.");
        }

        if (typeof Swal !== "undefined") {
            await Swal.fire({
                icon: "success",
                title: "Operación realizada",
                text: resultado.mensaje
            });
        } else {
            alert(resultado.mensaje);
        }

        await Promise.all([
            cargarLiquidacionesAdmin(),
            cargarComisionesAdmin(),
            cargarSalariosAdmin()
        ]);
    } catch (error) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "No fue posible procesar",
                text: error.message
            });
        } else {
            alert(error.message);
        }
    } finally {
        if (boton?.isConnected) boton.disabled = false;
    }
}

function abrirDialogCrearLiquidacion() {
    if (!permisosLiquidaciones.crear) return;
    document.getElementById("formCrearLiquidacion")?.reset();
    prepararFechasLiquidaciones();
    document.getElementById("dialogCrearLiquidacion")?.showModal();
}

function cerrarDialogCrearLiquidacion() {
    document.getElementById("dialogCrearLiquidacion")?.close();
}

async function guardarNuevaLiquidacion(evento) {
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const boton = formulario.querySelector('[type="submit"]');
    boton.disabled = true;
    try {
        const respuesta = await fetch("./php/crearLiquidacionRemuneracion.php", {method: "POST", body: new FormData(formulario)});
        const texto = await respuesta.text();
        let resultado;
        try { resultado = JSON.parse(texto); } catch (_) { throw new Error("El servidor no devolvió una respuesta JSON válida."); }
        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible crear la liquidación.");
        cerrarDialogCrearLiquidacion();
        if (typeof Swal !== "undefined") await Swal.fire({icon: "success", title: "Liquidación creada", text: resultado.mensaje});
        else alert(resultado.mensaje);
        await Promise.all([cargarLiquidacionesAdmin(), cargarComisionesAdmin(), cargarSalariosAdmin()]);
    } catch (error) {
        if (typeof Swal !== "undefined") Swal.fire({icon: "error", title: "No fue posible crear", text: error.message});
        else alert(error.message);
    } finally { boton.disabled = false; }
}

async function mostrarResumenLiquidacion(idLiquidacion) {
    const dialogo = document.getElementById("dialogDetalleLiquidacion");
    if (!dialogo) return;

    asignarTextoSalarios("detalleLiquidacionUsuario", "Cargando información...");
    asignarTextoSalarios("detalleLiquidacionPeriodo", "—");
    asignarTextoSalarios("detalleLiquidacionEstado", "—");
    asignarTextoSalarios("detalleLiquidacionResponsable", "—");
    asignarTextoSalarios("detalleLiquidacionFechaPago", "—");
    document.getElementById("detalleLiquidacionComisionesBody").innerHTML = '<tr><td colspan="6" class="salarios-vacio">Cargando comisiones...</td></tr>';
    document.getElementById("detalleLiquidacionAjustesBody").innerHTML = "";
    document.getElementById("detalleLiquidacionHistorialBody").innerHTML = '<tr><td colspan="5" class="salarios-vacio">Cargando historial...</td></tr>';
    dialogo.showModal();

    try {
        const parametros = new URLSearchParams({
            id_liquidacion: String(idLiquidacion)
        });
        const respuesta = await fetch(
            `./php/obtenerDetalleLiquidacion.php?${parametros}`,
            {cache: "no-store"}
        );
        const texto = await respuesta.text();
        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (_) {
            throw new Error("El servidor no devolvió una respuesta JSON válida.");
        }

        if (resultado.sesionExpirada) {
            window.location.href = "page-login.html";
            return;
        }
        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.mensaje || "No fue posible cargar la liquidación.");
        }

        renderizarDetalleLiquidacion(resultado.datos || {});
    } catch (error) {
        dialogo.close();
        if (typeof Swal !== "undefined") {
            Swal.fire({icon: "error", title: "No fue posible cargar", text: error.message});
        } else {
            alert(error.message);
        }
    }
}

function renderizarDetalleLiquidacion(datos) {
    detalleLiquidacionActual = datos;
    const liquidacion = datos.liquidacion || {};
    const comisiones = Array.isArray(datos.comisiones) ? datos.comisiones : [];
    const ajustes = Array.isArray(datos.ajustes) ? datos.ajustes : [];
    const historial = Array.isArray(datos.historial) ? datos.historial : [];

    asignarTextoSalarios("detalleLiquidacionUsuario", `${liquidacion.usuario || "—"} · ${capitalizarSalarios(liquidacion.rol)}`);
    asignarTextoSalarios("detalleLiquidacionPeriodo", `${fechaCortaSalarios(liquidacion.periodo_desde)} al ${fechaCortaSalarios(liquidacion.periodo_hasta)}`);
    asignarTextoSalarios("detalleLiquidacionEstado", capitalizarSalarios(liquidacion.estado));
    asignarTextoSalarios("detalleLiquidacionResponsable", liquidacion.responsable || "—");
    asignarTextoSalarios("detalleLiquidacionFechaPago", liquidacion.fecha_pago ? fechaSalarios(liquidacion.fecha_pago) : "Pendiente");
    asignarTextoSalarios("detalleLiquidacionSueldo", monedaSalarios(liquidacion.sueldo_base));
    asignarTextoSalarios("detalleLiquidacionComisiones", monedaSalarios(liquidacion.total_comisiones));
    asignarTextoSalarios("detalleLiquidacionBonos", monedaSalarios(liquidacion.bonos));
    asignarTextoSalarios("detalleLiquidacionDescuentos", monedaSalarios(liquidacion.descuentos));
    asignarTextoSalarios("detalleLiquidacionTotal", monedaSalarios(liquidacion.total_liquidacion));
    asignarTextoSalarios("detalleLiquidacionObservaciones", liquidacion.observaciones || "Sin observaciones.");

    const cuerpoComisiones = document.getElementById("detalleLiquidacionComisionesBody");
    cuerpoComisiones.innerHTML = comisiones.length
        ? comisiones.map(fila => `<tr>
            <td>${fechaSalarios(fila.fecha_generacion)}</td>
            <td>${nombreTipoComision(fila.tipo_comision)}</td>
            <td>${nombreOrigenComision(fila.origen)}</td>
            <td class="salarios-dinero">${monedaSalarios(fila.base_calculo)}</td>
            <td class="salarios-porcentaje">${porcentajeSalarios(fila.porcentaje_aplicado)}</td>
            <td class="salarios-dinero salarios-total">${monedaSalarios(fila.monto_comision)}</td>
        </tr>`).join("")
        : '<tr><td colspan="6" class="salarios-vacio">Esta liquidación no incluye comisiones.</td></tr>';

    const bloqueAjustes = document.getElementById("detalleLiquidacionAjustesBloque");
    const cuerpoAjustes = document.getElementById("detalleLiquidacionAjustesBody");
    bloqueAjustes.hidden = ajustes.length === 0;
    cuerpoAjustes.innerHTML = ajustes.map(fila => `<tr>
        <td>${fechaSalarios(fila.fecha_registro)}</td>
        <td>${fila.tipo_ajuste === "bono" ? "Bono" : "Descuento"}</td>
        <td>${fila.origen === "reembolso" ? "Reembolso" : "Administrativo"}</td>
        <td>${escaparSalarios(fila.observaciones || "—")}</td>
        <td class="salarios-dinero ${fila.tipo_ajuste === "descuento" ? "detalle-descuento" : "detalle-bono"}">${monedaSalarios(fila.monto)}</td>
    </tr>`).join("");

    const cuerpoHistorial = document.getElementById("detalleLiquidacionHistorialBody");
    cuerpoHistorial.innerHTML = historial.length
        ? historial.map(fila => `<tr>
            <td>${fechaSalarios(fila.fecha_evento)}</td>
            <td><strong>${capitalizarSalarios(fila.accion)}</strong></td>
            <td>${fila.estado_anterior ? `${capitalizarSalarios(fila.estado_anterior)} → ` : ""}${capitalizarSalarios(fila.estado_nuevo)}</td>
            <td>${escaparSalarios(fila.responsable || "—")}</td>
            <td>${escaparSalarios(fila.observaciones || "—")}</td>
        </tr>`).join("")
        : '<tr><td colspan="5" class="salarios-vacio">Esta liquidación antigua no tiene eventos registrados.</td></tr>';
}

function cerrarDialogDetalleLiquidacion() {
    document.getElementById("dialogDetalleLiquidacion")?.close();
}

function imprimirDetalleLiquidacion() {
    if (!detalleLiquidacionActual?.liquidacion) {
        alert("Primero debe cargar una liquidación.");
        return;
    }

    const liquidacion = detalleLiquidacionActual.liquidacion;
    const comisiones = Array.isArray(detalleLiquidacionActual.comisiones)
        ? detalleLiquidacionActual.comisiones
        : [];
    const ajustes = Array.isArray(detalleLiquidacionActual.ajustes)
        ? detalleLiquidacionActual.ajustes
        : [];
    const historial = Array.isArray(detalleLiquidacionActual.historial)
        ? detalleLiquidacionActual.historial
        : [];

    const logoUrl = new URL(
        "./images/logoAlianzaPro.webp",
        window.location.href
    ).href;
    
    /*
    * El servidor genera un enlace firmado para esta liquidación.
    * El QR abrirá verLiquidacion.php en lugar de realizar
    * una búsqueda con el texto de la liquidación.
    */

    const textoQr =
        String(liquidacion.url_qr || "").trim();

    if (!textoQr) {
        alert(
            "No fue posible generar el enlace QR de la liquidación. " +
            "Actualice la página y vuelva a intentarlo."
        );

        return;
    }

    const qrUrl =
        "https://api.qrserver.com/v1/create-qr-code/" +
        "?size=180x180" +
        "&margin=8" +
        "&data=" +
        encodeURIComponent(textoQr);

    const filasComisiones = comisiones.length
        ? comisiones.map(fila => `<tr>
            <td>${escaparSalarios(fechaSalarios(fila.fecha_generacion))}</td>
            <td>${escaparSalarios(nombreTipoComision(fila.tipo_comision))}</td>
            <td>${escaparSalarios(nombreOrigenComision(fila.origen))}</td>
            <td class="numero">${monedaSalarios(fila.base_calculo)}</td>
            <td class="numero">${porcentajeSalarios(fila.porcentaje_aplicado)}</td>
            <td class="numero fuerte">${monedaSalarios(fila.monto_comision)}</td>
        </tr>`).join("")
        : '<tr><td colspan="6" class="vacio">Esta liquidación no incluye comisiones.</td></tr>';

    const seccionAjustes = ajustes.length
        ? `<section>
            <h2>Ajustes incluidos</h2>
            <table>
                <thead><tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Descripción</th><th class="numero">Monto</th></tr></thead>
                <tbody>${ajustes.map(fila => `<tr>
                    <td>${escaparSalarios(fechaSalarios(fila.fecha_registro))}</td>
                    <td>${fila.tipo_ajuste === "bono" ? "Bono" : "Descuento"}</td>
                    <td>${fila.origen === "reembolso" ? "Reembolso" : "Administrativo"}</td>
                    <td>${escaparSalarios(fila.observaciones || "—")}</td>
                    <td class="numero fuerte">${monedaSalarios(fila.monto)}</td>
                </tr>`).join("")}</tbody>
            </table>
        </section>`
        : "";

    const filasHistorial = historial.length
        ? historial.map(fila => `<tr>
            <td>${escaparSalarios(fechaSalarios(fila.fecha_evento))}</td>
            <td>${escaparSalarios(capitalizarSalarios(fila.accion))}</td>
            <td>${fila.estado_anterior ? `${escaparSalarios(capitalizarSalarios(fila.estado_anterior))} → ` : ""}${escaparSalarios(capitalizarSalarios(fila.estado_nuevo))}</td>
            <td>${escaparSalarios(fila.responsable || "—")}</td>
            <td>${escaparSalarios(fila.observaciones || "—")}</td>
        </tr>`).join("")
        : '<tr><td colspan="5" class="vacio">Sin eventos registrados.</td></tr>';

    document.getElementById("iframeImpresionLiquidacion")?.remove();

    const iframe = document.createElement("iframe");
    iframe.id = "iframeImpresionLiquidacion";
    iframe.setAttribute("title", "Impresión de liquidación");
    iframe.style.position = "fixed";
    iframe.style.right = "0";
    iframe.style.bottom = "0";
    iframe.style.width = "1px";
    iframe.style.height = "1px";
    iframe.style.border = "0";
    iframe.style.opacity = "0";
    iframe.style.pointerEvents = "none";
    document.body.appendChild(iframe);

    const ventana = iframe.contentWindow;
    const documento = iframe.contentDocument || ventana.document;

    documento.open();
    documento.write(`<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Liquidación ${Number(liquidacion.id_liquidacion) || ""}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #202938; background: #fff; font-family: Arial, Helvetica, sans-serif; font-size: 11px; }
        .hoja { width: 100%; }
        header { display: grid; grid-template-columns: 92px 1fr 180px; gap: 14px; align-items: center; padding-bottom: 16px; border-bottom: 3px solid #2479ed; }
        .logo { display: block; width: 86px; max-height: 62px; object-fit: contain; object-position: left center; }
        .marca { color: #2479ed; font-size: 13px; font-weight: 800; letter-spacing: .08em; }
        h1 { margin: 5px 0 0; font-size: 25px; }
        .cabecera-derecha { display: flex; align-items: center; gap: 10px; justify-content: flex-end; }
        .numero-doc { white-space: nowrap; text-align: right; }
        .numero-doc strong { display: block; margin-top: 4px; font-size: 16px; }
        .qr { display: block; width: 72px; height: 72px; border: 1px solid #dce2e9; }
        .qr-texto { margin-top: 3px; color: #6d7786; font-size: 7px; text-align: center; }
        section { margin-top: 18px; break-inside: avoid; }
        h2 { margin: 0 0 9px; padding-bottom: 6px; color: #344000; border-bottom: 1px solid #dce2e9; font-size: 15px; }
        .datos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .dato { min-height: 55px; padding: 10px; border: 1px solid #dce2e9; border-radius: 7px; }
        .dato small { display: block; margin-bottom: 6px; color: #6d7786; font-weight: 700; }
        .dato strong { font-size: 12px; }
        .totales { display: grid; grid-template-columns: repeat(5, 1fr); gap: 7px; }
        .total-item { padding: 10px; border: 1px solid #dce2e9; border-radius: 7px; }
        .total-item small { display: block; margin-bottom: 6px; color: #6d7786; font-weight: 700; }
        .total-final { color: #1768d3; background: #eaf3ff; border-color: #bcd5f8; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { padding: 8px 7px; color: #fff; background: #344000; font-size: 10px; text-align: left; }
        td { padding: 8px 7px; border: 1px solid #dfe4eb; vertical-align: top; overflow-wrap: anywhere; }
        .numero { text-align: right; }
        .fuerte { font-weight: 800; }
        .vacio { padding: 18px; color: #6d7786; text-align: center; }
        .observaciones { min-height: 48px; padding: 10px; border: 1px solid #dce2e9; border-radius: 7px; white-space: pre-wrap; }
        footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #dce2e9; color: #768092; font-size: 9px; text-align: center; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
<main class="hoja">
    <header>
        <img class="logo" src="${logoUrl}" alt="AlianzaPro SPA">
        <div><div class="marca">ALIANZAPRO SPA · MEGAGEST</div><h1>Comprobante de liquidación</h1></div>
        <div>
            <div class="cabecera-derecha">
                <div class="numero-doc">Liquidación<strong>N.º ${Number(liquidacion.id_liquidacion) || "—"}</strong></div>
                <img class="qr" src="${qrUrl}" alt="Código QR de la liquidación">
            </div>
            <div class="qr-texto">Escanear para consultar</div>
        </div>
    </header>

    <section>
        <h2>Información general</h2>
        <div class="datos">
            <div class="dato"><small>Usuario</small><strong>${escaparSalarios(liquidacion.usuario || "—")}</strong></div>
            <div class="dato"><small>Rol</small><strong>${escaparSalarios(capitalizarSalarios(liquidacion.rol))}</strong></div>
            <div class="dato"><small>Período</small><strong>${fechaCortaSalarios(liquidacion.periodo_desde)} al ${fechaCortaSalarios(liquidacion.periodo_hasta)}</strong></div>
            <div class="dato"><small>Estado</small><strong>${escaparSalarios(capitalizarSalarios(liquidacion.estado))}</strong></div>
            <div class="dato"><small>Responsable</small><strong>${escaparSalarios(liquidacion.responsable || "—")}</strong></div>
            <div class="dato"><small>Fecha de emisión</small><strong>${fechaSalarios(liquidacion.fecha_registro)}</strong></div>
            <div class="dato"><small>Fecha de pago</small><strong>${liquidacion.fecha_pago ? fechaSalarios(liquidacion.fecha_pago) : "Pendiente"}</strong></div>
            <div class="dato"><small>Correo</small><strong>${escaparSalarios(liquidacion.correo || "—")}</strong></div>
        </div>
    </section>

    <section>
        <h2>Resumen de pago</h2>
        <div class="totales">
            <div class="total-item"><small>Sueldo base</small><strong>${monedaSalarios(liquidacion.sueldo_base)}</strong></div>
            <div class="total-item"><small>Comisiones</small><strong>${monedaSalarios(liquidacion.total_comisiones)}</strong></div>
            <div class="total-item"><small>Bonos</small><strong>${monedaSalarios(liquidacion.bonos)}</strong></div>
            <div class="total-item"><small>Descuentos</small><strong>${monedaSalarios(liquidacion.descuentos)}</strong></div>
            <div class="total-item total-final"><small>Total liquidación</small><strong>${monedaSalarios(liquidacion.total_liquidacion)}</strong></div>
        </div>
    </section>

    <section>
        <h2>Comisiones incluidas</h2>
        <table>
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th class="numero">Base</th><th class="numero">%</th><th class="numero">Comisión</th></tr></thead>
            <tbody>${filasComisiones}</tbody>
        </table>
    </section>

    ${seccionAjustes}

    <section>
        <h2>Observaciones</h2>
        <div class="observaciones">${escaparSalarios(liquidacion.observaciones || "Sin observaciones.")}</div>
    </section>

    <section>
        <h2>Historial de la liquidación</h2>
        <table>
            <thead><tr><th>Fecha</th><th>Acción</th><th>Cambio</th><th>Responsable</th><th>Descripción</th></tr></thead>
            <tbody>${filasHistorial}</tbody>
        </table>
    </section>

    <footer>Documento generado por MegaGest · AlianzaPro SPA · ${new Date().toLocaleString("es-CL")}</footer>
</main>
</body>
</html>`);
    documento.close();

    const imagenes = Array.from(documento.images);
    const esperarImagenes = imagenes.map(imagen => {
        if (imagen.complete) return Promise.resolve();
        return new Promise(resolve => {
            imagen.addEventListener("load", resolve, {once: true});
            imagen.addEventListener("error", resolve, {once: true});
        });
    });

    Promise.race([
        Promise.all(esperarImagenes),
        new Promise(resolve => ventana.setTimeout(resolve, 2500))
    ]).then(() => {
        const limpiarIframe = () => {
            window.setTimeout(() => iframe.remove(), 500);
        };

        ventana.addEventListener("afterprint", limpiarIframe, {once: true});
        window.setTimeout(limpiarIframe, 60000);
        ventana.focus();
        ventana.print();
    });
}

function fechaCortaSalarios(valor) {
    if (!valor) return "—";
    const partes = String(valor).split("-");
    return partes.length === 3 ? `${partes[2]}-${partes[1]}-${partes[0]}` : valor;
}

function asignarTextoSalarios(id, valor) { const elemento = document.getElementById(id); if (elemento) elemento.textContent = valor; }
function monedaSalarios(valor) { return new Intl.NumberFormat("es-CL", {style: "currency", currency: "CLP", maximumFractionDigits: 0}).format(Number(valor) || 0); }
function porcentajeSalarios(valor) { return `${Number(valor || 0).toLocaleString("es-CL", {maximumFractionDigits: 2})}%`; }
function capitalizarSalarios(valor) { valor = String(valor || ""); return valor.charAt(0).toUpperCase() + valor.slice(1); }
function escaparSalarios(valor) { const div = document.createElement("div"); div.textContent = String(valor ?? ""); return div.innerHTML; }

/*
 * admin.php carga las cases de forma dinámica. El script puede ejecutarse
 * antes de que exista #salariosAdmin, por eso se observa el contenido hasta
 * que la vista sea insertada. La marca data-inicializado evita duplicados.
 */
function observarCargaSalariosAdmin() {
    inicializarSalariosAdmin();

    const observador = new MutationObserver(() => {
        if (document.getElementById("salariosAdmin")) {
            inicializarSalariosAdmin();
        }
    });

    observador.observe(document.body, {
        childList: true,
        subtree: true
    });
}

if (document.readyState === "loading") {
    document.addEventListener(
        "DOMContentLoaded",
        observarCargaSalariosAdmin,
        {once: true}
    );
} else {
    observarCargaSalariosAdmin();
}
