const parametros = new URLSearchParams(window.location.search);

const idCita = parametros.get("id");

let idOT = null;

let totalServiciosOT = 0;

let totalProductosOT = 0;

let totalManoObraOT = 0;

let cantServiciosOT = 0;

let cantProductosOT = 0;

let cantManoObraOT = 0;

let estadoActualOT = "";

/*=============================================
CARGAR ORDEN DE TRABAJO
=============================================*/

async function cargarOrdenTrabajo(){

    try{

        const response = await fetch(
            "./php/obtenerOrdenTrabajo.php?id=" + idCita
        );

        const resultado = await response.json();

        if(!resultado.ok){

            alert(resultado.mensaje);
            return;

        }

        const ot = resultado.datos;

        idOT = ot.id_ot;

        estadoActualOT = ot.estado;

        // Header
        document.getElementById("numeroOT").textContent = ot.numeroOT;

        // Estado
        const estado = document.getElementById("estadoOT");

        estado.textContent = ot.estado;

        estado.classList.remove(
            "estadoAbierta",
            "estadoProceso",
            "estadoTerminada",
            "estadoFacturada"
        );

        switch(ot.estado){

            case "Abierta":
                estado.classList.add("estadoAbierta");
                break;

            case "En proceso":
                estado.classList.add("estadoProceso");
                break;

            case "Terminada":
                estado.classList.add("estadoTerminada");
                break;

            case "Facturada":
                estado.classList.add("estadoFacturada");
                break;

        }

        // Cliente
        document.getElementById("numeroReserva").value = ot.numeroReserva;
        document.getElementById("nombreCliente").value = ot.nombre;
        document.getElementById("telefono").value = ot.telefono;
        document.getElementById("correo").value = ot.correo;

        // Vehículo
        document.getElementById("vehiculo").value = ot.vehiculo;
        document.getElementById("patente").value = ot.patente;

        // Recepción
        document.getElementById("kilometraje").value = ot.kilometraje ?? "";
        document.getElementById("combustible").value = ot.combustible ?? "";
        document.getElementById("observacionesRecepcion").value = ot.observacionesRecepcion ?? "";
        document.getElementById("observacionesEntrega").value = ot.observacionesEntrega ?? "";

        // Cargar datos relacionados
        await cargarFotosOT();
        await cargarServiciosRealizados();
        await cargarProductosOT();
        await cargarManoObraOT();

        // Bloquear si ya está terminada
        if (ot.estado === "Terminada" || ot.estado === "Facturada") {
            bloquearOrdenTerminada();
        }

        configurarBotonFacturacionOT(ot.estado);

    }catch(error){

        console.error(error);
        alert("Error al cargar la Orden de Trabajo.");

    }

}

/*=============================================
ABRIR DIALOG SERVICIOS
=============================================*/

async function abrirDialogServicio(){

    configurarCostoServicioOT();

    document.getElementById("dialogServicioOT").showModal();

    cargarServiciosOT();

}

/*=============================================
CARGAR SERVICIOS
=============================================*/

async function cargarServiciosOT(){

    try{

        const response = await fetch("./php/obtenerServiciosOT.php");

        const resultado = await response.json();

        if(!resultado.ok){

            alert(resultado.mensaje);
            return;

        }

        const select =
            document.getElementById("selectServicioOT");

        select.innerHTML =
            "<option value=''>Seleccione un servicio</option>";

        resultado.datos.filter(servicio => servicio.nombre.trim().toLowerCase() !== "otro servicio").forEach(servicio => {

            select.innerHTML += `
                <option
                    value="${servicio.id_servicio}"
                    data-precio="${servicio.precio_min}">

                    ${servicio.nombre}

                </option>
            `;
        });

    }catch(error){

        console.error(error);

        alert("No fue posible cargar los servicios.");
    }

}

/*=============================================
GUARDAR SERVICIO (SE HARÁ DESPUÉS)
=============================================*/

async function guardarServicioOT(){

    const select = document.getElementById("selectServicioOT");

    const descripcion = select.options[select.selectedIndex]?.text.trim() || "";

    const cantidad = Number(document.getElementById("cantidadServicioOT").value);

    const precio = Number(document.getElementById("precioServicioOT").value);

    const esAdministrador = obtenerRolOT() === "administrador";

    const costo = esAdministrador
        ? Number(document.getElementById("costoServicioOT").value)
        : 0;

    if (!select.value) {
        alert("Seleccione un servicio.");
        return;
    }

    if (!Number.isInteger(cantidad) || cantidad <= 0) {
        alert("Ingrese una cantidad válida.");
        return;
    }

    if (!Number.isInteger(precio) || precio <= 0) {
        alert("Ingrese un precio válido.");
        return;
    }

    if (esAdministrador && (!Number.isInteger(costo) || costo < 0)) {
        alert("Ingrese un costo interno válido.");
        return;
    }

    const formData = new FormData();

    formData.append("id_ot", idOT);
    formData.append("descripcion", descripcion);
    formData.append("cantidad", cantidad);
    formData.append("precio", precio);

    if (esAdministrador) {
        formData.append("costo_unitario", costo);
    }

    const response = await fetch(
        "./php/guardarServicioOT.php",
        {
            method:"POST",
            body:formData
        }
    );


    const resultado = await response.json();

    if(resultado.ok){

        document.getElementById("dialogServicioOT").close();

        document.getElementById("selectServicioOT").value = "";
        document.getElementById("cantidadServicioOT").value = "1";
        document.getElementById("precioServicioOT").value = "";
        document.getElementById("costoServicioOT").value = "";

        await cargarServiciosRealizados();

    }else{

        alert(resultado.mensaje);

    }

}

function obtenerRolOT(){

    return String(localStorage.getItem("rol") || "")
        .trim()
        .toLowerCase();

}

function configurarCostoServicioOT(){

    const esAdministrador = obtenerRolOT() === "administrador";
    const campo = document.getElementById("campoCostoServicioOT");
    const input = document.getElementById("costoServicioOT");

    if (!campo || !input) {
        return;
    }

    campo.hidden = !esAdministrador;
    input.disabled = !esAdministrador;

    if (!esAdministrador) {
        input.value = "";
    }

}

/*=============================================
INICIALIZACIÓN
=============================================*/

document.addEventListener("DOMContentLoaded",()=>{

    configurarCostoServicioOT();

    cargarOrdenTrabajo();

    document.getElementById("selectServicioOT").addEventListener("change",function(){

        const opcion = this.options[this.selectedIndex];

        document.getElementById("precioServicioOT").value = opcion.dataset.precio || "";

    });

});


async function cargarServiciosRealizados(){

    const response = await fetch("./php/listarServiciosOT.php?id_ot=" + idOT);

    const servicios = await response.json();

    if (!Array.isArray(servicios)) {
        alert(servicios.mensaje || "No fue posible cargar los servicios.");
        return;
    }

    const tbody = document.getElementById("tbodyServiciosOT");

    tbody.innerHTML="";

    let totalGeneral = 0;
    let cant = 0;

    servicios.forEach(servicio=>{

        totalGeneral += Number(servicio.total);
        cant += Number(servicio.cantidad);

        tbody.innerHTML += `
            <tr>

                <td>${servicio.descripcion}</td>

                <td>${servicio.nombre} ${servicio.apellido}</td>

                <td>${servicio.cantidad}</td>

                <td>$${Number(servicio.precio).toLocaleString()}</td>

                <td>

                    ${servicio.puede_eliminar ? `
                        <button class="btnEliminarServicio" onclick="eliminarServicioOT(${servicio.id_servicio_ot})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ` : ""}

                </td>

            </tr>
        `;

    });

    document.getElementById("totalServicios").textContent ="$" + totalGeneral.toLocaleString();

    totalServiciosOT = totalGeneral;
    cantServiciosOT = cant;

    actualizarTotalOT();
    

}

async function eliminarServicioOT(idServicioOT){

    if(!confirm("¿Eliminar este servicio?")){
        return;
    }

    const formData = new FormData();

    formData.append("id_servicio_ot", idServicioOT);

    const response = await fetch("./php/eliminarServicioOT.php",
        {
            method:"POST",
            body:formData
        }
    );

    const resultado = await response.json();

    if(resultado.ok){

        cargarServiciosRealizados();
        actualizarTotalOT();

    }else{

        alert(resultado.mensaje);

    }

}

// =====================================
// BUSCADOR DE PRODUCTOS OT
// =====================================

let productoSeleccionadoOT = null;


document.addEventListener("DOMContentLoaded",()=>{

    const input = document.getElementById("buscarProductoOT");

    if(input){
        input.addEventListener("input",buscarProductosOT);
    }
});


async function buscarProductosOT(){

    const texto = this.value.trim();

    const resultados = document.getElementById("resultadoProductosOT");

    if(texto.length < 2){
        resultados.innerHTML = "";
        return;

    }

    try{

        const response = await fetch("./php/buscarProductosOT.php?buscar=" + encodeURIComponent(texto));

        const productos = await response.json();

        resultados.innerHTML = "";

        if(productos.length === 0){

            resultados.innerHTML = `

                <div class="resultadoProducto">

                    No se encontraron productos

                </div>

            `;

            return;

        }




        productos.forEach(producto=>{

            const div = document.createElement("div");

            div.className = "resultadoProducto";

            div.innerHTML = `

                <strong>
                    ${producto.nombre}
                </strong>

                <small>

                    Marca: ${producto.marca}
                    |
                    Stock: ${producto.cantidad}
                    |
                    $${Number(producto.precio).toLocaleString()}

                </small>

            `;

            div.onclick = ()=>{

                seleccionarProductoOT(producto);

            };

            resultados.appendChild(div);

        });



    }catch(error){


        console.error(
            "Error buscando productos:",
            error
        );

    }

}


function seleccionarProductoOT(producto){


    productoSeleccionadoOT = producto;

    document.getElementById("nombreProductoOT").value = producto.nombre;

    document.getElementById("stockProductoOT").value = producto.cantidad;

    document.getElementById("precioProductoOT").value = producto.precio;

    document.getElementById("resultadoProductosOT").innerHTML = "";

    document.getElementById("buscarProductoOT").value = producto.nombre;

}

function abrirDialogProducto(){

    document.getElementById("dialogProductoOT").showModal();

    // Limpiar campos al abrir

    document.getElementById("buscarProductoOT").value = "";

    document.getElementById("nombreProductoOT").value = "";

    document.getElementById("stockProductoOT").value = "";

    document.getElementById("cantidadProductoOT").value = 1;

    document.getElementById("precioProductoOT").value = "";


    document.getElementById("resultadoProductosOT").innerHTML = "";


    productoSeleccionadoOT = null;

}

async function guardarProductoOT(){

    if(!productoSeleccionadoOT){

        alert("Seleccione un producto primero");

        return;

    }


    const cantidad = Number(document.getElementById("cantidadProductoOT").value);

    const stock = Number(productoSeleccionadoOT.cantidad);

    if(cantidad <= 0){

        alert("Cantidad inválida");

        return;

    }

    if(cantidad > stock){

        alert("Stock insuficiente. Disponible: " + stock);

        return;

    }

    const datos = new FormData();

    datos.append("id_ot", idOT);
    datos.append("id_producto", productoSeleccionadoOT.id_producto);
    datos.append("descripcion", productoSeleccionadoOT.nombre);
    datos.append("cantidad", cantidad);
    datos.append("precio", productoSeleccionadoOT.precio);


    try{

        const response = await fetch("./php/guardarProductoOT.php",

            {
                method:"POST",
                body:datos
            }

        );

        const resultado = await response.json();

        if(resultado.ok){

            alert(resultado.mensaje);

            document.getElementById("dialogProductoOT").close();

            // Limpiar producto seleccionado
            productoSeleccionadoOT = null;

            // Recargar productos de la OT
            cargarProductosOT();

        }else{

            alert(resultado.mensaje);

        }


    }catch(error){

        console.error(error);

        alert("Error al guardar producto");

    }

}

async function cargarProductosOT(){

    const response = await fetch("./php/listarProductosOT.php?id_ot=" + idOT);

    const productos = await response.json();

    if (!Array.isArray(productos)) {
        alert(productos.mensaje || "No fue posible cargar los productos.");
        return;
    }

    const tbody = document.getElementById("tbodyProductosOT");

    tbody.innerHTML="";

    let totalGeneral = 0;
    let cant = 0;

    productos.forEach(producto=>{

        totalGeneral += Number(producto.total);
        cant += Number(producto.cantidad);

        tbody.innerHTML += `

            <tr>

                <td>
                    ${producto.descripcion}
                </td>

                <td>
                    ${producto.nombre} ${producto.apellido}
                </td>

                <td>
                    ${producto.cantidad}
                </td>

                <td>
                    $${Number(producto.precio).toLocaleString()}
                </td>

                <td>
                    ${producto.puede_eliminar ? `
                        <button class="btnEliminarServicio" onclick="eliminarProductoOT(${producto.id_producto_ot}, ${producto.cantidad})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ` : ""}
                </td>

            </tr>
        `;
    });


    document.getElementById("totalProductos").textContent = "$" + totalGeneral.toLocaleString();

    totalProductosOT = totalGeneral;
    cantProductosOT = cant;

    actualizarTotalOT();

}

async function eliminarProductoOT(idProductoOT, cantidad){


    if(!confirm("¿Eliminar producto de la OT?")){

        return;

    }

    const datos = new FormData();

    datos.append("id_producto_ot", idProductoOT);

    try{

        const response = await fetch("./php/eliminarProductoOT.php",

            {

                method:"POST",

                body:datos

            }

        );

        const resultado = await response.json();

        if(resultado.ok){

            cargarProductosOT();
            cantProductosOT = Number(cantProductosOT) - Number(cantidad);
            actualizarTotalOT();

        }else{

            alert(resultado.mensaje);

        }

    }catch(error){

        console.error(error);

    }

}


// =====================================
// MANO DE OBRA OT
// =====================================


function abrirDialogManoObra(){

    configurarCostoManoObra();

    document.getElementById("dialogManoObraOT").showModal();

    document.getElementById("descripcionManoObra").value = "";

    document.getElementById("cantidadManoObra").value = 1;

    document.getElementById("precioManoObra").value = "";

    document.getElementById("costoManoObra").value = "";

}

async function guardarManoObra(){

    const descripcion = document.getElementById("descripcionManoObra").value.trim();

    const cantidad = Number(document.getElementById("cantidadManoObra").value);

    const precio = Number(document.getElementById("precioManoObra").value);

    const costoInput = document.getElementById("costoManoObra");

    const costoInterno = costoInput.value.trim() !== ""
        ? Number(costoInput.value)
        : null;

    if(!descripcion){

        alert("Ingrese descripción");

        return;

    }

    if(precio <= 0){

        alert("Ingrese un precio válido");

        return;

    }

    if (
        costoInterno !== null &&
        (!Number.isInteger(costoInterno) || costoInterno < 0)
    ) {
        alert("Ingrese un costo interno válido");
        return;
    }

    const datos = new FormData();


    datos.append("id_ot", idOT);

    datos.append("descripcion", descripcion);

    datos.append("cantidad", cantidad);

    datos.append("precio", precio);

    if (costoInterno !== null) {
        datos.append("costo_unitario", costoInterno);
    }

    try{

        const response = await fetch("./php/guardarManoObra.php",

            {
                method:"POST",

                body:datos

            }

        );

        const resultado = await response.json();

        if(resultado.ok){

            document.getElementById("dialogManoObraOT").close();

            cargarManoObraOT();

        }else{

            alert(resultado.mensaje);

        }

    }catch(error){

        console.error(error);

    }

}

function configurarCostoManoObra(){

    const esAdministrador = obtenerRolOT() === "administrador";
    const campo = document.getElementById("campoCostoManoObra");
    const input = document.getElementById("costoManoObra");

    if (!campo || !input) {
        return;
    }

    campo.hidden = !esAdministrador;
    input.disabled = !esAdministrador;

    if (!esAdministrador) {
        input.value = "";
    }

}


async function cargarManoObraOT(){

    const response = await fetch("./php/listarManoObraOT.php?id_ot=" + idOT);

    const datos = await response.json();

    if (!Array.isArray(datos)) {
        alert(datos.mensaje || "No fue posible cargar la mano de obra.");
        return;
    }

    const tbody = document.getElementById("tbodyManoObraOT");

    tbody.innerHTML="";

    let total = 0;
    let cant = 0;

    datos.forEach(item=>{

        total += Number(item.total);
        cant += Number(item.cantidad);


        tbody.innerHTML += `

        <tr>

            <td>
                ${item.descripcion}
            </td>

            <td>
               ${item.nombre} ${item.apellido}
            </td>

            <td>
                ${item.cantidad}
            </td>

            <td>

            $${Number(item.precio).toLocaleString()}

            </td>

            <td>

            ${item.puede_eliminar ? `
                <button
                    class="btnEliminarServicio"
                    onclick="eliminarManoObra(${item.id_mano_obra})">
                    <i class="fa-solid fa-trash"></i>
                </button>
            ` : ""}

            </td>

        </tr>

        `;

    });


    document.getElementById("totalManoObra").textContent = "$" + total.toLocaleString();

totalManoObraOT = total;
cantManoObraOT = cant;

actualizarTotalOT();

}

async function eliminarManoObra(id){

    if(!confirm("¿Eliminar esta mano de obra?")){

        return;

    }


    const datos = new FormData();


    datos.append(
        "id_mano_obra",
        id
    );

    try{

        const response = await fetch(

            "./php/eliminarManoObra.php",

            {
                method:"POST",
                body:datos
            }

        );


        const resultado =
            await response.json();


        if(resultado.ok){


            cargarManoObraOT();


        }else{


            alert(resultado.mensaje);

        }


    }catch(error){

        console.error(error);

    }

}

function actualizarTotalOT(){

    const total = totalServiciosOT + totalProductosOT + totalManoObraOT;
    
    if(cantServiciosOT !=0){document.getElementById("serviciosCantidad").textContent = "Total servicios (" + cantServiciosOT.toLocaleString() + ") :";}
    else{document.getElementById("serviciosCantidad").textContent = "Total Servicios:";}
    document.getElementById("resumenServicios").textContent = "$" + totalServiciosOT.toLocaleString();

    if(cantProductosOT !=0){document.getElementById("productosCantidad").textContent = "Total productos (" + cantProductosOT.toLocaleString( )+ ") :";}
    else{document.getElementById("productosCantidad").textContent = "Total Productos:";}
    document.getElementById("resumenProductos").textContent = "$" + totalProductosOT.toLocaleString();
    
    if(cantManoObraOT !=0){document.getElementById("manoObraCantidad").textContent = "Total Mano de Obra (" + cantManoObraOT.toLocaleString() + ") :";}
    else{document.getElementById("manoObraCantidad").textContent = "Total Mano de Obra:";}
    document.getElementById("resumenManoObra").textContent = "$" + totalManoObraOT.toLocaleString();

    document.getElementById("totalGeneralOT").textContent = "$" + total.toLocaleString();

}

function abrirDialogFinalizar(){

    document.getElementById("dialogFinalizarOT").showModal();

}

function configurarBotonFacturacionOT(estadoOT){

    const boton = document.getElementById("btnFacturarOT");

    if (!boton) {
        return;
    }

    const rol = obtenerRolOT();
    const autorizado = ["administrador", "cajero"].includes(rol);
    const puedeFacturar = estadoOT === "Terminada" && autorizado;

    boton.hidden = !puedeFacturar;
    boton.disabled = !puedeFacturar;
    boton.classList.toggle("disabled", !puedeFacturar);

    boton.style.setProperty(
        "opacity",
        puedeFacturar ? "1" : "0.5",
        "important"
    );

    boton.style.setProperty(
        "cursor",
        puedeFacturar ? "pointer" : "not-allowed",
        "important"
    );

    boton.style.setProperty(
        "pointer-events",
        puedeFacturar ? "auto" : "none",
        "important"
    );

    if (puedeFacturar) {
        boton.removeAttribute("aria-disabled");
    } else {
        boton.setAttribute("aria-disabled", "true");
    }

}

function abrirDialogCobroOT(){

    if (estadoActualOT !== "Terminada") {
        alert("La orden debe estar terminada antes de cobrarla.");
        return;
    }

    if (!["administrador", "cajero"].includes(obtenerRolOT())) {
        alert("Solo el administrador o cajero puede cobrar esta orden.");
        return;
    }

    const total = totalServiciosOT + totalProductosOT + totalManoObraOT;

    if (total <= 0) {
        alert("La orden no contiene conceptos cobrables.");
        return;
    }

    document.getElementById("cobroServiciosOT").textContent =
        "$" + totalServiciosOT.toLocaleString();

    document.getElementById("cobroProductosOT").textContent =
        "$" + totalProductosOT.toLocaleString();

    document.getElementById("cobroManoObraOT").textContent =
        "$" + totalManoObraOT.toLocaleString();

    document.getElementById("cobroTotalOT").textContent =
        "$" + total.toLocaleString();

    document.getElementById("metodoPagoOT").disabled = false;
    document.getElementById("observacionesCobroOT").disabled = false;
    document.getElementById("btnConfirmarCobroOT").disabled = false;

    document.getElementById("metodoPagoOT").value = "";
    document.getElementById("observacionesCobroOT").value = "";

    document.getElementById("dialogCobroOT").showModal();

}

async function cobrarYFacturarOT(){

    const metodoPago = document.getElementById("metodoPagoOT").value;
    const observaciones = document.getElementById("observacionesCobroOT").value.trim();
    const boton = document.getElementById("btnConfirmarCobroOT");

    if (!metodoPago) {
        alert("Seleccione un método de pago.");
        return;
    }

    if (!confirm("¿Confirma que recibió el pago de esta orden?")) {
        return;
    }

    const datos = new FormData();
    datos.append("id_ot", idOT);
    datos.append("metodo_pago", metodoPago);
    datos.append("observaciones", observaciones);

    boton.disabled = true;

    try {

        const response = await fetch(
            "./php/facturarOrdenTrabajo.php",
            {
                method: "POST",
                body: datos
            }
        );

        const resultado = await response.json();

        if (!resultado.ok) {
            throw new Error(
                resultado.mensaje ||
                "No fue posible cobrar y facturar la orden."
            );
        }

        document.getElementById("dialogCobroOT").close();

        alert(resultado.mensaje);

        estadoActualOT = "Facturada";

        await cargarOrdenTrabajo();

    } catch (error) {

        console.error(error);
        alert(error.message || "No fue posible cobrar y facturar la orden.");

    } finally {

        boton.disabled = false;

    }

}

async function finalizarOT(){

    if(!confirm("¿Desea finalizar esta Orden de Trabajo?")){

        return;

    }

    const datos = new FormData();

    datos.append("id_ot", idOT);

    datos.append("observacionesEntrega", document.getElementById("observacionesEntrega").value);


    try{

        const response = await fetch(

            "./php/finalizarOrdenTrabajo.php",

            {
                method:"POST",
                body:datos
            }

        );

        const resultado = await response.json();

        if(resultado.ok){

            alert(resultado.mensaje);

            document.getElementById("dialogFinalizarOT").close();

            cargarOrdenTrabajo();

            bloquearOrdenTerminada();

        }else{

            alert(resultado.mensaje);

        }

    }catch(error){

        console.error(error);

    }

}

async function guardarOrdenTrabajo(){

    const datos = new FormData();

    datos.append("id_ot", idOT);

    datos.append("id_usuario", localStorage.getItem("id_usuario"));

    datos.append("kilometraje", document.getElementById("kilometraje").value);

    datos.append("combustible", document.getElementById("combustible").value);

    datos.append("observacionesRecepcion", document.getElementById("observacionesRecepcion").value);

    try{

        const response = await fetch(

            "./php/guardarOrdenTrabajo.php",

            {

                method:"POST",

                body:datos

            }

        );

        const resultado = await response.json();

        if(resultado.ok){

            alert("Orden guardada correctamente.");

        }else{

            alert(resultado.mensaje);

        }

    }catch(error){

        console.error(error);

    }

}

let ordenTerminada = false;

function bloquearOrdenTerminada() {

    ordenTerminada = true;

    // El bloqueo se aplica elemento por elemento. No se agrega una clase
    // global al body porque también bloquearía el botón de cobro.
    document.body.classList.remove("ordenTerminada");

    // Deshabilitar todos los campos
    document.querySelectorAll("input, textarea, select").forEach(control => {
        if (!control.closest("#dialogCobroOT")) {
            control.disabled = true;
        }
    });

    // Deshabilitar todos los botones reales
    document.querySelectorAll("button").forEach(boton => {

        if (
            boton.id !== "btnFacturarOT" &&
            boton.id !== "btnVolverOT" &&
            !boton.closest("#dialogCobroOT")
        ) {
            boton.disabled = true;
            boton.style.opacity = "0.5";
            boton.style.cursor = "not-allowed";
        }

    });

    // Bloquear botones o enlaces de acciones
    document.querySelectorAll(`
        .btnAgregar,
        .btnGuardar:not(#btnFacturarOT),
        .btnFinalizar,
        .btnEliminarServicio,
        .btnEliminarProducto,
        .btnEliminarFoto
    `).forEach(elemento => {

        elemento.disabled = true;
        elemento.setAttribute("aria-disabled", "true");
        elemento.style.opacity = "0.5";
        elemento.style.cursor = "not-allowed";
        elemento.style.pointerEvents = "none";
    });

    // Bloquear específicamente la subida de fotografías
    const inputFotos = document.getElementById("inputFotos");

    if (inputFotos) {
        inputFotos.disabled = true;
    }

    const btnVolverOT = document.getElementById("btnVolverOT");

    if (btnVolverOT) {
        btnVolverOT.disabled = false;
        btnVolverOT.style.opacity = "1";
        btnVolverOT.style.cursor = "pointer";
        btnVolverOT.style.pointerEvents = "auto";
        btnVolverOT.removeAttribute("aria-disabled");
    }

    // La OT terminada queda bloqueada, pero administrador y cajero
    // deben poder abrir el cobro y convertirla en Facturada.
    configurarBotonFacturacionOT(estadoActualOT);

}


document.addEventListener("click", function (event) {

    if (!ordenTerminada) {
        return;
    }

    const accionBloqueada = event.target.closest(`
        .btnAgregar,
        .btnGuardar:not(#btnFacturarOT),
        .btnFinalizar,
        .btnEliminarServicio,
        .btnEliminarProducto,
        .btnEliminarFoto
    `);

    if (accionBloqueada) {
        event.preventDefault();
        event.stopPropagation();

        alert("La orden de trabajo está terminada y no se puede modificar.");
    }

}, true);




function volverDesdeOT() {

    const origen = sessionStorage.getItem("origenOT") || "inicio";

    sessionStorage.setItem("seccionVolverAdmin", origen);

    window.location.href = "admin.php";
}