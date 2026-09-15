let citasHoyAdmin = [];

async function abrirDialogNuevaCita() {

    const formulario =
        document.getElementById("formNuevaCitaAdmin");

    const dialogo =
        document.getElementById("dialogNuevaCita");

    formulario.reset();

    document.getElementById(
        "mensajeNuevaCita"
    ).textContent = "";

    prepararRangoNuevaCita();

    await cargarServiciosNuevaCita();

    const selectHora =
        document.getElementById("horaNuevaCita");

    selectHora.innerHTML = `
        <option value="">
            Seleccione una fecha
        </option>
    `;

    dialogo.showModal();
}


function prepararRangoNuevaCita() {

    const inputFecha =
        document.getElementById("fechaNuevaCita");

    const hoy = new Date();

    const anio = hoy.getFullYear();

    const mes =
        String(hoy.getMonth() + 1).padStart(2, "0");

    const dia =
        String(hoy.getDate()).padStart(2, "0");

    const ultimoDia =
        new Date(
            hoy.getFullYear(),
            hoy.getMonth() + 1,
            0
        );

    const diaFinal =
        String(ultimoDia.getDate()).padStart(2, "0");

    inputFecha.min =
        `${anio}-${mes}-${dia}`;

    inputFecha.max =
        `${anio}-${mes}-${diaFinal}`;
}

async function cargarServiciosNuevaCita() {

    const select =
        document.getElementById("servicioNuevaCita");

    select.innerHTML = `
        <option value="">
            Cargando servicios...
        </option>
    `;

    try {

        const response = await fetch("./php/obtenerServicios.php");

        const resultado = await response.json();

        select.innerHTML = `
            <option value="">
                Seleccione un servicio
            </option>
        `;

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {

            mostrarMensajeNuevaCita(
                resultado.mensaje,
                "error"
            );

            return;
        }

        resultado.datos.forEach(servicio => {

            const option =
                document.createElement("option");

            option.value = servicio.nombre;
            option.textContent = servicio.nombre;

            select.appendChild(option);
        });

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Error al cargar servicios
            </option>
        `;
    }
}


document.addEventListener("change", function(event) {

        if (event.target.id === "fechaNuevaCita") {

            cargarHorasNuevaCita(event.target.value);
        }
    }
);


async function cargarHorasNuevaCita(fecha) {

    const select =
        document.getElementById("horaNuevaCita");

    if (!fecha) {

        select.innerHTML = `
            <option value="">
                Seleccione una fecha
            </option>
        `;

        return;
    }

    const fechaSeleccionada =
        new Date(fecha + "T12:00:00");

    if (fechaSeleccionada.getDay() === 0) {

        select.innerHTML = `
            <option value="">
                Domingo cerrado
            </option>
        `;

        mostrarMensajeNuevaCita(
            "El taller no atiende los domingos.",
            "error"
        );

        return;
    }

    select.innerHTML = `
        <option value="">
            Cargando horarios...
        </option>
    `;

    try {

        const response = await fetch(
            "./php/obtenerHorasDisponibles.php" +
            `?fecha=${encodeURIComponent(fecha)}`
        );

        const horarios =
            await response.json();

        select.innerHTML = "";

        if (horarios.length === 0) {

            select.innerHTML = `
                <option value="">
                    No hay horarios disponibles
                </option>
            `;

            return;
        }

        select.innerHTML = `
            <option value="">
                Seleccione una hora
            </option>
        `;

        horarios.forEach(hora => {

            const option =
                document.createElement("option");

            option.value = hora;
            option.textContent = hora;

            select.appendChild(option);
        });

        mostrarMensajeNuevaCita("", "");

    } catch (error) {

        console.error(error);

        select.innerHTML = `
            <option value="">
                Error al cargar horarios
            </option>
        `;
    }
}

async function guardarNuevaCitaAdmin() {

    const boton =
        document.getElementById(
            "btnGuardarNuevaCita"
        );

    const telefono =
        document.getElementById(
            "telefonoNuevaCita"
        ).value.replace(/\D/g, "");

    if (telefono.length !== 9) {

        mostrarMensajeNuevaCita(
            "El teléfono debe tener 9 dígitos.",
            "error"
        );

        return;
    }

    const datos = new FormData();

    datos.append(
        "nombre",
        document.getElementById(
            "nombreNuevaCita"
        ).value.trim()
    );

    datos.append("telefono", telefono);

    datos.append(
        "correo",
        document.getElementById(
            "correoNuevaCita"
        ).value.trim()
    );

    datos.append(
        "patente",
        document.getElementById(
            "patenteNuevaCita"
        ).value.trim().toUpperCase()
    );

    datos.append(
        "vehiculo",
        document.getElementById(
            "vehiculoNuevaCita"
        ).value.trim()
    );

    datos.append(
        "servicio",
        document.getElementById(
            "servicioNuevaCita"
        ).value
    );

    datos.append(
        "fecha",
        document.getElementById(
            "fechaNuevaCita"
        ).value
    );

    datos.append(
        "hora",
        document.getElementById(
            "horaNuevaCita"
        ).value
    );

    datos.append(
        "comentario",
        document.getElementById(
            "comentarioNuevaCita"
        ).value.trim()
    );

    try {

        boton.disabled = true;

        boton.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            Guardando...
        `;

        const response = await fetch(
            "./php/registrarCitaAdmin.php",
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

            mostrarMensajeNuevaCita(
                resultado.mensaje,
                "error"
            );

            return;
        }

        cerrarDialogNuevaCita();

        mensajeModal(
            "Cita registrada",
            resultado.mensaje
        );

        cargarCitasHoy();

    } catch (error) {

        console.error(error);

        mostrarMensajeNuevaCita(
            "No fue posible registrar la cita.",
            "error"
        );

    } finally {

        boton.disabled = false;

        boton.innerHTML = `
            <i class="fa-solid fa-calendar-check"></i>
            Reservar cita
        `;
    }
}


function mostrarMensajeNuevaCita(
    mensaje,
    tipo
) {

    const elemento =
        document.getElementById(
            "mensajeNuevaCita"
        );

    elemento.textContent = mensaje;

    elemento.className = "";

    if (tipo === "error") {
        elemento.classList.add(
            "mensajeCitaError"
        );
    }

    if (tipo === "exito") {
        elemento.classList.add(
            "mensajeCitaExito"
        );
    }
}


function cerrarDialogNuevaCita() {

    const dialogo =
        document.getElementById(
            "dialogNuevaCita"
        );

    if (dialogo && dialogo.open) {
        dialogo.close();
    }
}


function mostrarCalendario(){

    const panel = document.getElementById("panelCalendario");

    if(panel.style.display==="block"){

        panel.style.display="none";

    }else{

        panel.style.display="block";

    }

}

async function cargarCitasHoy() {

    activarPestanaCita("btnHoy");

    activarTarjetaCita("Todas");

    cambiarTituloTablaCitas("Citas de hoy", "Reservas programadas para la fecha actual.");

    mostrarCargaTablaCitas();

    try {

        const response = await fetch("./php/obtenerCitasHoy.php");

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }


        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            cargarTablaCitas([]);

            return;
        }

        citasHoyAdmin = resultado.datos;

        actualizarResumenCitas(citasHoyAdmin);

        cargarTablaCitas(citasHoyAdmin);

    } catch (error) {

        console.error(error);

        alert("No fue posible cargar las citas de hoy.");

        cargarTablaCitas([]);
    }
}


function cargarTablaCitas(citas){

    const tbody = document.getElementById("tbody_cita");

    tbody.innerHTML = "";

    citas.forEach(cita => {

        let botones = "";


            switch(cita.estado){

    case "Pendiente":

        botones = `
            <button class="btnAccion btnModificar"
                onclick="modificarCita(${cita.id_cita})">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>

            <button class="btnAccion btnEliminar"
                onclick="cancelarCita(${cita.id_cita}, '${cita.numeroReserva}')">
                <i class="fa-solid fa-ban"></i>
            </button>

            <button class="btnAccion btnVer"
                        onclick="verCita(${cita.id_cita})">
                        <i class="fa-solid fa-eye"></i>
            </button>

            <button class="btnAccion btnConfirmar"
                onclick="confirmarCita(${cita.id_cita})">
                <i class="fa-solid fa-check"></i>
            </button>
        `;
    break;

    case "Confirmada":

        botones = `
                    <button class="btnAccion btnModificar"
                        onclick="modificarCita(${cita.id_cita})">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button class="btnAccion btnEliminar"
                        onclick="cancelarCita(${cita.id_cita}, '${cita.numeroReserva}')">
                        <i class="fa-solid fa-ban"></i>
                    </button>

                    <button class="btnAccion btnVer"
                        onclick="verCita(${cita.id_cita})">
                        <i class="fa-solid fa-eye"></i>
                    </button>

                    <button class="btnAccion btnIniciar"
                        onclick="iniciarCita(${cita.id_cita})">
                        <i class="fa-solid fa-play"></i>
                    </button>
                `;
            break;

            case "En proceso":

                botones = `
                    <button class="btnOT btnAbrirOT"
                        onclick="abrirOT(${cita.id_cita})">
                        <i class="fa-solid fa-folder-open"></i>
                        Abrir OT
                    </button>
                    

                    <button class="btnAccion btnVer"
                        onclick="verCita(${cita.id_cita})">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                `;
            break;

            case "Finalizada":

                botones = `

                    <button class="btnOT btnAbrirOT"
                        onclick="abrirOT(${cita.id_cita})">
                        <i class="fa-solid fa-folder-open"></i>
                        Abrir OT
                    </button>

                    <button class="btnAccion btnVer"
                        onclick="verCita(${cita.id_cita})">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                `;
            break;

            case "Cancelada":

                botones = `
                    <button
                        class="btnAccion btnVer"
                        onclick="verCita(${cita.id_cita})"
                        title="Ver cita">

                        <i class="fa-solid fa-eye"></i>

                    </button>
                `;

            break;
        }

        tbody.innerHTML += `
            <tr>

                <td>${cita.numeroReserva}</td>

                <td>${formatearFechaCitaTabla(cita.fecha)}</td>

                <td>${formatearHoraCitaTabla(cita.hora)}</td>

                <td class="clienteTablaCita">

                    <strong>
                        ${escaparHTMLCita(cita.nombre)}
                    </strong>

                    <small>
                        ${escaparHTMLCita(cita.patente)}
                    </small>

                </td>

                <td>${cita.servicio}</td>

                <td>${obtenerEstado(cita.estado)}</td>

                <td style="padding:3px">
                    ${botones}
                </td>

                            </tr>
                        `;

    });

    document.querySelectorAll(".btnModificar").forEach(btn=>{
        acceso(localStorage.getItem("rol"), btn);
    });

    document.querySelectorAll(".btnEliminar").forEach(btn=>{
        acceso(localStorage.getItem("rol"), btn);
    });

    actualizarTotalResultadosCitas(citas.length);

const tabla =
    document.getElementById("table_citas");

const sinCitas =
    document.getElementById("sinCitasAdmin");

sinCitas.innerHTML = `
    <i class="fa-regular fa-calendar-xmark"></i>
    <h3>No hay citas para mostrar</h3>
    <p>No se encontraron reservas en esta sección.</p>
`;

if (citas.length === 0) {

    tabla.style.display = "none";
    sinCitas.style.display = "block";

} else {

    tabla.style.display = "table";
    sinCitas.style.display = "none";
}

}

function formatearFechaCitaTabla(fecha) {

    if (!fecha) {
        return "Sin fecha";
    }

    const partes = fecha.split("-");

    if (partes.length !== 3) {
        return fecha;
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}


function formatearHoraCitaTabla(hora) {

    if (!hora) {
        return "Sin hora";
    }

    return hora.substring(0, 5);
}

function actualizarResumenCitas(citas) {

    const pendientes = citas.filter(
        cita => cita.estado === "Pendiente"
    ).length;

    const confirmadas = citas.filter(
        cita => cita.estado === "Confirmada"
    ).length;

    const enProceso = citas.filter(
        cita => cita.estado === "En proceso"
    ).length;

    const finalizadas = citas.filter(
        cita => cita.estado === "Finalizada"
    ).length;

    document.getElementById(
        "cantidadCitasHoy"
    ).textContent = citas.length;

    document.getElementById(
        "cantidadPendientes"
    ).textContent = pendientes;

    document.getElementById(
        "cantidadConfirmadas"
    ).textContent = confirmadas;

    document.getElementById(
        "cantidadEnProceso"
    ).textContent = enProceso;

    document.getElementById(
        "cantidadFinalizadas"
    ).textContent = finalizadas;
}

/*=============================================
VER INFORMACIÓN DE LA CITA
=============================================*/

    window.verCita = async function(idCita) {
    const dialogo = document.getElementById("dialogVerCita");
    const cargando = document.getElementById("cargandoCita");
    const contenido = document.getElementById("contenidoVerCita");

    if (!dialogo || !cargando || !contenido) {
        alert("No se encontró el diálogo de información de la cita.");
        return;
    }

    // Mostrar diálogo en estado de carga
    cargando.style.display = "flex";
    contenido.style.display = "none";

    dialogo.showModal();

    try {

        const response = await fetch(
            `./php/obtenerCita.php?id=${encodeURIComponent(idCita)}`
        );

        if (!response.ok) {
            throw new Error("Error HTTP: " + response.status);
        }

        const resultado = await response.json();

        // Comprobar si la sesión expiró
        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            dialogo.close();

            return;
        }

        const cita = resultado.datos;

        asignarTexto("verNumeroReserva", cita.numeroReserva || "Sin número de reserva");
        asignarTexto("verNombre", cita.nombre);
        asignarTexto("verTelefono", cita.telefono);
        asignarTexto("verCorreo", cita.correo);
        asignarTexto("verVehiculo", cita.vehiculo);
        asignarTexto("verPatente", cita.patente);
        asignarTexto("verServicio", cita.servicio);
        asignarTexto("verFecha", formatearFechaHoraCita(cita.fecha, cita.hora));
        asignarTexto("verFechaRegistro", formatearFechaRegistro(cita.fecha_registro));
        asignarTexto("verComentario", cita.comentario);
        asignarTexto("verObservaciones", cita.observaciones);
        asignarTexto("verFechaInicio",formatearFechaRegistro(cita.fechaInicio));
        asignarTexto("verFechaFin",formatearFechaRegistro(cita.fechaFin));

        mostrarEstadoDetalleCita(cita.estado);

        cargando.style.display = "none";
        contenido.style.display = "block";

    } catch (error) {

        console.error("Error al obtener la cita:", error);

        cargando.style.display = "none";

        alert("No fue posible cargar la información de la cita.");

        dialogo.close();
    }
}


/*=============================================
ASIGNAR TEXTO DE FORMA SEGURA
=============================================*/

function asignarTexto(idElemento, valor) {

    const elemento = document.getElementById(idElemento);

    if (!elemento) {
        return;
    }

    const texto = String(valor ?? "").trim();

    elemento.textContent = texto !== "" ? texto: "Sin información";
}


/*=============================================
MOSTRAR ESTADO
=============================================*/

function mostrarEstadoDetalleCita(estado) {

    const elemento = document.getElementById("verEstado");

    if (!elemento) {
        return;
    }

    elemento.className = "estadoDetalleCita";

    switch (String(estado).toLowerCase()) {

        case "pendiente":
            elemento.textContent = "Pendiente";
            elemento.classList.add("estadoCitaPendiente");
            break;

        case "confirmada":
            elemento.textContent = "Confirmada";
            elemento.classList.add("estadoCitaConfirmada");
            break;

        case "en proceso":
            elemento.textContent = "En proceso";
            elemento.classList.add("estadoCitaProceso");
            break;

        case "finalizada":
            elemento.textContent = "Finalizada";
            elemento.classList.add("estadoCitaFinalizada");
            break;

        case "cancelada":
            elemento.textContent = "Cancelada";
            elemento.classList.add("estadoCitaCancelada");
            break;

        default:
            elemento.textContent = estado || "Sin información";
    }
}


/*=============================================
FORMATEAR FECHA Reserva
=============================================*/

function formatearFechaHoraCita(fecha, hora) {

    let fechaFormateada = "Sin información";
    let horaFormateada = "Sin información";

    if (fecha) {

        const partes = fecha.split("-");

        if (partes.length === 3) {
            fechaFormateada = `${partes[2]}/${partes[1]}/${partes[0]}`;
        } else {
            fechaFormateada = fecha;
        }
    }

    if (hora) {
        horaFormateada = hora.substring(0, 5);
    }

    return `${fechaFormateada} - ${horaFormateada}`;
}


/*=============================================
FORMATEAR FECHA Registro
=============================================*/

function formatearFechaRegistro(fechaHora) {

    if (!fechaHora) {
        return "Sin información";
    }

    // Funciona con:
    // 2026-08-20 14:30:45
    // 2026-08-20T14:30:45
    const partes = fechaHora.replace("T", " ").split(" ");

    const fecha = partes[0];
    const hora = partes[1] ?? "";

    const partesFecha = fecha.split("-");

    if (partesFecha.length !== 3) {
        return fechaHora;
    }

    const fechaFormateada =
        `${partesFecha[2]}/${partesFecha[1]}/${partesFecha[0]}`;

    const horaFormateada = hora
        ? hora.substring(0, 5)
        : "";

    return horaFormateada ? `${fechaFormateada} - ${horaFormateada}` : fechaFormateada;
}


/*=============================================
CERRAR DIÁLOGO
=============================================*/

function cerrarDialogVerCita() {

    const dialogo = document.getElementById("dialogVerCita");

    if (dialogo && dialogo.open) {
        dialogo.close();
    }
}

function obtenerEstado(estado) {

    switch (String(estado).toLowerCase()) {

        case "pendiente":

            return `
                <span class="estadoTablaCita estadoTablaPendiente">
                    <i class="fa-solid fa-circle"></i>
                    Pendiente
                </span>
            `;

        case "confirmada":

            return `
                <span class="estadoTablaCita estadoTablaConfirmada">
                    <i class="fa-solid fa-circle"></i>
                    Confirmada
                </span>
            `;

        case "en proceso":

            return `
                <span class="estadoTablaCita estadoTablaProceso">
                    <i class="fa-solid fa-circle"></i>
                    En proceso
                </span>
            `;

        case "finalizada":

            return `
                <span class="estadoTablaCita estadoTablaFinalizada">
                    <i class="fa-solid fa-circle"></i>
                    Finalizada
                </span>
            `;

        case "cancelada":

            return `
                <span class="estadoTablaCita estadoTablaCancelada">
                    <i class="fa-solid fa-circle"></i>
                    Cancelada
                </span>
            `;

        default:

            return `
                <span class="estadoTablaCita">
                    ${estado}
                </span>
            `;
    }
}

window.confirmarCita = async function(idCita) {

    const respuesta = await confirmar(
        "Confirmar cita",
        "¿Desea confirmar esta cita?"
    );

    if(!respuesta){
        return;
    }

    try{

        const response = await fetch(
            `./php/confirmarCita.php?id=${idCita}`
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (manejarPermisoDenegado(resultado)) {
            return;
        }

        if(!resultado.ok){
            alert(resultado.mensaje);
            return;
        }

        mensajeModal(
            "Confirmación",
            resultado.mensaje
        );

        cargarCitasHoy();

    }catch(error){

        alert(error);

    }

}

window.iniciarCita = async function(idCita) {

    const respuesta = await confirmar("Iniciar cita", "¿Desea iniciar esta cita?");

    if(!respuesta){
        return;
    }

    try{

        const response = await fetch(`./php/iniciarCita.php?id=${idCita}`);

        const resultado = await response.json();

        if(!resultado.ok){
            alert(resultado.mensaje);
            return;
        }

        window.location.href = "adminOrdenTrabajo.html?id=" + idCita;


    }catch(error){

        alert("Error: "+error);

    }

}

async function guardarFinalizacion(){

    try{

        const formData = new FormData();

        formData.append("idCita", document.getElementById("idCitaFinalizar").value);

        formData.append("observaciones", document.getElementById("observacionesFinalizar").value.trim());

        const response = await fetch(
            "./php/finalizarCita.php",
            {
                method:"POST",
                body:formData
            }
        );

        const resultado = await response.json();

        if(!resultado.ok){

            alert(resultado.mensaje);

            return;

        }

        mensajeModal("Mensaje", resultado.mensaje);

        document.getElementById("dialogFinalizarCita").close();

        cargarCitasHoy();

    }
    catch(error){

        alert("Error: " + error);

    }

}

/*=============================================
ABRIR DIÁLOGO PARA MODIFICAR CITA
=============================================*/

window.modificarCita = async function(idCita) {

    try {

        const response = await fetch(
            `./php/obtenerCita.php?id=${encodeURIComponent(idCita)}`
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        const cita = resultado.datos;

        if (
            cita.estado !== "Pendiente" &&
            cita.estado !== "Confirmada"
        ) {

            alert("Esta cita ya no puede modificarse.");

            cargarCitasHoy();

            return;
        }

        document.getElementById("idCitaModificar").value =
            cita.id_cita;

        document.getElementById("reservaCitaModificar").value =
            cita.numeroReserva ?? "";

        document.getElementById("nombreCitaModificar").value =
            cita.nombre ?? "";

        document.getElementById("telefonoCitaModificar").value =
            cita.telefono ?? "";

        document.getElementById("correoCitaModificar").value =
            cita.correo ?? "";

        document.getElementById("patenteCitaModificar").value =
            cita.patente ?? "";

        document.getElementById("vehiculoCitaModificar").value =
            cita.vehiculo ?? "";

        document.getElementById("comentarioCitaModificar").value =
            cita.comentario ?? "";

        prepararRangoFechaModificar();

        document.getElementById("fechaCitaModificar").value =
            cita.fecha;

        await cargarServiciosModificarCita(cita.servicio);

        await cargarHorasModificarCita(
            cita.fecha,
            cita.id_cita,
            cita.hora
        );

        document.getElementById("mensajeModificarCita").textContent = "";

        document.getElementById("dialogModificarCita").showModal();

    } catch (error) {

        console.error(error);

        alert("No fue posible cargar la cita.");
    }
};


/*=============================================
RANGO DE FECHA
=============================================*/

function prepararRangoFechaModificar() {

    const inputFecha =
        document.getElementById("fechaCitaModificar");

    const hoy = new Date();

    const anio = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, "0");
    const dia = String(hoy.getDate()).padStart(2, "0");

    const ultimoDia = new Date(
        hoy.getFullYear(),
        hoy.getMonth() + 1,
        0
    );

    const diaFinal =
        String(ultimoDia.getDate()).padStart(2, "0");

    inputFecha.min = `${anio}-${mes}-${dia}`;
    inputFecha.max = `${anio}-${mes}-${diaFinal}`;
}


/*=============================================
CARGAR HORAS DISPONIBLES
=============================================*/

async function cargarHorasModificarCita(
    fecha,
    idCita,
    horaActual = ""
) {

    const select =
        document.getElementById("horaCitaModificar");

    select.innerHTML =
        "<option value=''>Cargando horarios...</option>";

    if (!fecha) {

        select.innerHTML =
            "<option value=''>Seleccione una fecha</option>";

        return;
    }

    const diaSeleccionado =
        new Date(fecha + "T12:00:00");

    if (diaSeleccionado.getDay() === 0) {

        select.innerHTML =
            "<option value=''>Domingo cerrado</option>";

        return;
    }

    try {

        const response = await fetch(
            `./php/obtenerHorasDisponibles.php?fecha=${encodeURIComponent(fecha)}&id_cita=${encodeURIComponent(idCita)}`
        );

        const horarios = await response.json();

        select.innerHTML =
            "<option value=''>Seleccione una hora</option>";

        if (
            horaActual &&
            !horarios.includes(horaActual.substring(0, 5))
        ) {
            horarios.unshift(horaActual.substring(0, 5));
        }

        if (horarios.length === 0) {

            select.innerHTML =
                "<option value=''>No hay horarios disponibles</option>";

            return;
        }

        horarios.forEach(hora => {

            const option = document.createElement("option");

            option.value = hora;
            option.textContent = hora;

            if (
                horaActual &&
                hora === horaActual.substring(0, 5)
            ) {
                option.selected = true;
            }

            select.appendChild(option);
        });

    } catch (error) {

        console.error(error);

        select.innerHTML =
            "<option value=''>Error al cargar horarios</option>";
    }
}


/*=============================================
CAMBIO DE FECHA
=============================================*/

document.addEventListener("change", function(event) {

    if (event.target.id !== "fechaCitaModificar") {
        return;
    }

    const fecha = event.target.value;

    const idCita =
        document.getElementById("idCitaModificar").value;

    cargarHorasModificarCita(fecha, idCita);
});


/*=============================================
GUARDAR MODIFICACIÓN
=============================================*/

async function guardarModificacionCita() {

    const mensaje =
        document.getElementById("mensajeModificarCita");

    const boton =
        document.querySelector(".btnGuardarModificacion");

    const telefono =
        document.getElementById("telefonoCitaModificar")
            .value
            .replace(/\D/g, "");

    if (telefono.length !== 9) {

        mensaje.textContent =
            "El teléfono debe tener 9 dígitos.";

        mensaje.className = "mensajeCitaError";

        return;
    }

    const datos = new FormData();

    datos.append(
        "id_cita",
        document.getElementById("idCitaModificar").value
    );

    datos.append(
        "nombre",
        document.getElementById("nombreCitaModificar").value.trim()
    );

    datos.append("telefono", telefono);

    datos.append(
        "correo",
        document.getElementById("correoCitaModificar").value.trim()
    );

    datos.append(
        "patente",
        document.getElementById("patenteCitaModificar")
            .value
            .trim()
            .toUpperCase()
    );

    datos.append(
        "vehiculo",
        document.getElementById("vehiculoCitaModificar").value.trim()
    );

    datos.append(
        "servicio",
        document.getElementById("servicioCitaModificar").value
    );

    datos.append(
        "fecha",
        document.getElementById("fechaCitaModificar").value
    );

    datos.append(
        "hora",
        document.getElementById("horaCitaModificar").value
    );

    datos.append(
        "comentario",
        document.getElementById("comentarioCitaModificar").value.trim()
    );

    try {

        boton.disabled = true;
        boton.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        const response = await fetch(
            "./php/modificarCita.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (!resultado.ok) {

            mensaje.textContent = resultado.mensaje;
            mensaje.className = "mensajeCitaError";

            return;
        }

        cerrarModificarCita();

        mensajeModal(
            "Cita modificada",
            resultado.mensaje
        );

        cargarCitasHoy();

    } catch (error) {

        console.error(error);

        mensaje.textContent =
            "No fue posible modificar la cita.";

        mensaje.className = "mensajeCitaError";

    } finally {

        boton.disabled = false;

        boton.innerHTML =
            '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
    }
}


/*=============================================
CERRAR MODIFICACIÓN
=============================================*/

function cerrarModificarCita() {

    const dialogo =
        document.getElementById("dialogModificarCita");

    if (dialogo && dialogo.open) {
        dialogo.close();
    }
}


window.cancelarCita = async function(idCita, numeroReserva) {

    const respuesta = await confirmar("Cancelar cita", `¿Desea cancelar la reserva ${numeroReserva}?`);

    if (!respuesta) {
        return;
    }

    const datos = new FormData();

    datos.append("id_cita", idCita);

    try {

        const response = await fetch(
            "./php/cancelarCita.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (!resultado.ok) {

            alert(resultado.mensaje);

            return;
        }

        mensajeModal(
            "Cita cancelada",
            resultado.mensaje
        );

        cargarCitasHoy();

    } catch (error) {

        console.error(error);

        alert("No fue posible cancelar la cita.");
    }
};

/*=============================================
FECHA ACTUAL
=============================================*/

function mostrarFechaActualCitas() {

    const elemento =
        document.getElementById("textoFechaActual");

    if (!elemento) {
        return;
    }

    const fechaActual = new Intl.DateTimeFormat(
        "es-CL",
        {
            weekday: "long",
            day: "2-digit",
            month: "long",
            year: "numeric"
        }
    ).format(new Date());

    elemento.textContent =
        fechaActual.charAt(0).toUpperCase() +
        fechaActual.slice(1);
}


/*=============================================
PESTAÑA PRÓXIMAS
=============================================*/

async function cargarCitasProximas() {

    activarPestanaCita("btnProximas");

    cambiarTituloTablaCitas(
        "Próximas citas",
        "Reservas programadas desde mañana hasta el final del mes."
    );

    mostrarCargaTablaCitas();

    try {

        const response = await fetch(
            "./php/obtenerCitasProximas.php"
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            cargarTablaCitas([]);

            return;
        }

        cargarTablaCitas(resultado.datos);

    } catch (error) {

        console.error(error);

        alert("No fue posible cargar las próximas citas.");

        cargarTablaCitas([]);
    }
}


/*=============================================
PESTAÑA HISTORIAL
=============================================*/

async function cargarHistorialCitas() {

    activarPestanaCita("btnHistorial");

    cambiarTituloTablaCitas(
        "Historial de citas",
        "Citas anteriores, finalizadas y canceladas."
    );

    mostrarCargaTablaCitas();

    try {

        const response = await fetch(
            "./php/obtenerHistorialCitas.php"
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            cargarTablaCitas([]);

            return;
        }

        cargarTablaCitas(resultado.datos);

    } catch (error) {

        console.error(error);

        alert("No fue posible cargar el historial.");

        cargarTablaCitas([]);
    }
}


/*=============================================
BUSCAR POR RANGO
=============================================*/

async function buscarCitasPorRango() {

    const fechaInicio =
        document.getElementById("fechaInicio").value;

    const fechaFin =
        document.getElementById("fechaFin").value;

    if (!fechaInicio || !fechaFin) {

        alert("Seleccione la fecha inicial y final.");

        return;
    }

    if (fechaInicio > fechaFin) {

        alert(
            "La fecha inicial no puede ser mayor que la fecha final."
        );

        return;
    }

    cambiarTituloTablaCitas(
        "Citas por calendario",
        `Resultados desde ${
            formatearFechaCitaTabla(fechaInicio)
        } hasta ${
            formatearFechaCitaTabla(fechaFin)
        }.`
    );

    mostrarCargaTablaCitas();

    try {

        const response = await fetch(
            "./php/obtenerCitasRango.php" +
            `?inicio=${encodeURIComponent(fechaInicio)}` +
            `&fin=${encodeURIComponent(fechaFin)}`
        );

        const resultado = await response.json();

        if (manejarSesionExpirada(resultado)) {
            return;
        }

        if (!resultado.ok) {

            alert(resultado.mensaje);

            cargarTablaCitas([]);

            return;
        }

        cargarTablaCitas(resultado.datos);

    } catch (error) {

        console.error(error);

        alert("No fue posible consultar el calendario.");

        cargarTablaCitas([]);
    }
}


function mostrarCargaTablaCitas() {

    const tabla =
        document.getElementById("table_citas");

    const sinCitas =
        document.getElementById("sinCitasAdmin");

    if (tabla) {
        tabla.style.display = "none";
    }

    if (sinCitas) {

        sinCitas.style.display = "block";

        sinCitas.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            <h3>Cargando citas</h3>
            <p>Espere un momento...</p>
        `;
    }

    actualizarTotalResultadosCitas(0);
}


/*=============================================
BUSCADOR LOCAL
=============================================*/

function filtrarCitasAdmin() {

    const texto =
        this.value.trim().toLowerCase();

    const filas =
        document.querySelectorAll("#tbody_cita tr");

    let visibles = 0;

    filas.forEach(fila => {

        const contenido =
            fila.textContent.toLowerCase();

        const mostrar =
            contenido.includes(texto);

        fila.style.display =
            mostrar ? "" : "none";

        if (mostrar) {
            visibles++;
        }
    });

    actualizarTotalResultadosCitas(visibles);
}


/*=============================================
MOSTRAR PANEL CALENDARIO
=============================================*/

function mostrarCalendario() {

    activarPestanaCita("btnCalendario");

    const panel =
        document.getElementById("panelCalendario");

    if (!panel) {
        return;
    }

    panel.classList.toggle(
        "panelCalendarioVisible"
    );

    cambiarTituloTablaCitas(
        "Calendario de citas",
        "Seleccione un rango de fechas para consultar las reservas."
    );
}


/*=============================================
ACTIVAR PESTAÑA
=============================================*/

function activarPestanaCita(idBoton) {

    document
        .querySelectorAll(".btnPestanaCita")
        .forEach(boton => {

            boton.classList.remove(
                "pestanaCitaActiva"
            );
        });

    const botonActivo =
        document.getElementById(idBoton);

    if (botonActivo) {

        botonActivo.classList.add(
            "pestanaCitaActiva"
        );
    }

    if (idBoton !== "btnCalendario") {

        const panel =
            document.getElementById("panelCalendario");

        if (panel) {
            panel.classList.remove(
                "panelCalendarioVisible"
            );
        }
    }
}


/*=============================================
CAMBIAR TÍTULO DE LA TABLA
=============================================*/

function cambiarTituloTablaCitas(
    titulo,
    descripcion
) {

    const elementoTitulo =
        document.getElementById("tituloTablaCitas");

    const elementoDescripcion =
        document.getElementById(
            "descripcionTablaCitas"
        );

    if (elementoTitulo) {
        elementoTitulo.textContent = titulo;
    }

    if (elementoDescripcion) {
        elementoDescripcion.textContent =
            descripcion;
    }
}


/*=============================================
TOTAL DE RESULTADOS
=============================================*/

function actualizarTotalResultadosCitas(total) {

    const elemento =
        document.getElementById(
            "totalResultadosCitas"
        );

    if (!elemento) {
        return;
    }

    elemento.textContent =
        total === 1
            ? "1 resultado"
            : `${total} resultados`;
}



window.abrirOT = function(idCita) {

    sessionStorage.setItem("origenOT", "citas");

    window.location.href = "adminOrdenTrabajo.html?id=" + encodeURIComponent(idCita);
};


function filtrarCitasPorTarjeta() {

    const estado = this.dataset.estado;

    activarPestanaCita("btnHoy");

    activarTarjetaCita(estado);

    if (estado === "Todas") {

        cambiarTituloTablaCitas("Citas de hoy", "Todas las reservas programadas para la fecha actual."
        );

        cargarTablaCitas(citasHoyAdmin);

        return;
    }

    const citasFiltradas =
        citasHoyAdmin.filter(
            cita => cita.estado === estado
        );

    cambiarTituloTablaCitas(
        `${estado}`,
        `Citas de hoy con estado ${estado.toLowerCase()}.`
    );

    cargarTablaCitas(citasFiltradas);
}


function activarTarjetaCita(estado) {

    document
        .querySelectorAll(".tarjetaCitaFiltro")
        .forEach(tarjeta => {

            tarjeta.classList.remove(
                "tarjetaCitaSeleccionada"
            );

            if (tarjeta.dataset.estado === estado) {

                tarjeta.classList.add(
                    "tarjetaCitaSeleccionada"
                );
            }
        });
}


/*=============================================
BUSCAR EN LA TABLA DE CITAS
=============================================*/

function filtrarCitasAdmin(event) {

    const input = event.target;

    const textoBusqueda =
        normalizarTextoCita(input.value);

    const tabla =
        document.getElementById("table_citas");

    const tbody =
        document.getElementById("tbody_cita");

    const sinCitas =
        document.getElementById("sinCitasAdmin");

    if (!tabla || !tbody || !sinCitas) {
        return;
    }

    const filas =
        tbody.querySelectorAll("tr");

    let resultadosVisibles = 0;

    filas.forEach(fila => {

        const contenidoFila =
            normalizarTextoCita(fila.textContent);

        const coincide =
            contenidoFila.includes(textoBusqueda);

        fila.style.display =
            coincide ? "" : "none";

        if (coincide) {
            resultadosVisibles++;
        }
    });

    actualizarTotalResultadosCitas(
        resultadosVisibles
    );

    if (
        resultadosVisibles === 0 &&
        textoBusqueda !== ""
    ) {

        tabla.style.display = "none";

        sinCitas.style.display = "block";

        sinCitas.innerHTML = `
            <i class="fa-solid fa-magnifying-glass"></i>

            <h3>No se encontraron resultados</h3>

            <p>
                No existen citas que coincidan con
                "${escaparHTMLCita(input.value.trim())}".
            </p>
        `;

    } else if (filas.length > 0) {

        tabla.style.display = "table";
        sinCitas.style.display = "none";

    } else {

        tabla.style.display = "none";
        sinCitas.style.display = "block";

        sinCitas.innerHTML = `
            <i class="fa-regular fa-calendar-xmark"></i>

            <h3>No hay citas para mostrar</h3>

            <p>
                No se encontraron reservas en esta sección.
            </p>
        `;
    }
}


/*=============================================
NORMALIZAR TEXTO PARA BÚSQUEDA
=============================================*/

function normalizarTextoCita(texto) {

    return String(texto ?? "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();
}


/*=============================================
EVITAR HTML EN EL MENSAJE
=============================================*/

function escaparHTMLCita(texto) {

    return String(texto ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}