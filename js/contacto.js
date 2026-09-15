"use strict";

document.addEventListener("DOMContentLoaded", () => {
    inicializarMenuContacto();
    inicializarReveladoContacto();
    inicializarVolverArribaContacto();
    inicializarFormularioContacto();
    inicializarMapaContacto();
    const anio = document.getElementById("anioInicio");
    if (anio) anio.textContent = new Date().getFullYear();
});

function inicializarMenuContacto() {
    const boton = document.getElementById("botonMenuInicio");
    const menu = document.getElementById("menuInicio");
    if (!boton || !menu) return;
    const cerrar = () => {menu.classList.remove("abierto");boton.classList.remove("activo");boton.setAttribute("aria-expanded", "false");};
    boton.addEventListener("click", () => {const abierto = menu.classList.toggle("abierto");boton.classList.toggle("activo", abierto);boton.setAttribute("aria-expanded", String(abierto));});
    menu.querySelectorAll("a").forEach((enlace) => enlace.addEventListener("click", cerrar));
    window.addEventListener("resize", () => {if (window.innerWidth > 850) cerrar();});
}

function inicializarReveladoContacto() {
    const elementos = document.querySelectorAll("[data-revelar]");
    if (!("IntersectionObserver" in window)) {elementos.forEach((elemento) => elemento.classList.add("visible"));return;}
    const observador = new IntersectionObserver((entradas, observer) => entradas.forEach((entrada) => {if (!entrada.isIntersecting) return;entrada.target.classList.add("visible");observer.unobserve(entrada.target);}), {threshold:.12,rootMargin:"0px 0px -35px"});
    elementos.forEach((elemento) => observador.observe(elemento));
}

function inicializarVolverArribaContacto() {
    const boton = document.getElementById("volverArribaInicio");
    if (!boton) return;
    const actualizar = () => boton.classList.toggle("visible", window.scrollY > 500);
    window.addEventListener("scroll", actualizar, {passive:true});
    boton.addEventListener("click", () => window.scrollTo({top:0,behavior:"smooth"}));
    actualizar();
}

function inicializarFormularioContacto() {
    const formulario = document.getElementById("formContacto");
    const mensaje = document.getElementById("mensajeFormularioContacto");
    const botonEnviar = document.getElementById("enviarFormularioContacto");
    const textoBoton = botonEnviar?.querySelector("span");
    const campoMensaje = document.getElementById("sendermessage");
    const contador = document.getElementById("contadorMensajeContacto");
    const actualizarCaptcha = document.getElementById("actualizarCaptchaContacto");
    if (!formulario) return;

    const mostrarMensaje = (texto, correcto = false) => {
        mensaje.textContent = texto;
        mensaje.className = `mensajeFormularioContacto visible ${correcto ? "correcto" : "error"}`;
    };

    const refrescarCaptcha = () => {
        const imagen = document.getElementById("imagenCaptchaContacto");
        if (imagen) imagen.src = `smart-form/contact/php/captcha/captcha.php?t=${Date.now()}`;
        const campo = document.getElementById("captcha");
        if (campo) campo.value = "";
    };

    campoMensaje?.addEventListener("input", () => {if (contador) contador.textContent = String(campoMensaje.value.length);});
    actualizarCaptcha?.addEventListener("click", refrescarCaptcha);
    formulario.addEventListener("reset", () => {setTimeout(() => {if (contador) contador.textContent = "0";mensaje.className = "mensajeFormularioContacto";mensaje.textContent = "";refrescarCaptcha();}, 0);});

    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        mensaje.className = "mensajeFormularioContacto";
        if (!formulario.checkValidity()) {formulario.reportValidity();mostrarMensaje("Revise los campos obligatorios antes de enviar.");return;}

        botonEnviar.disabled = true;
        if (textoBoton) textoBoton.textContent = "Enviando…";
        try {
            const respuesta = await fetch(formulario.action, {method:"POST",body:new FormData(formulario),headers:{"X-Requested-With":"XMLHttpRequest"}});
            const texto = await respuesta.text();
            if (!texto.trim()) throw new Error("El servidor devolvió una respuesta vacía.");
            let resultado;
            try {resultado = JSON.parse(texto);} catch (_) {throw new Error("El servidor no devolvió una respuesta válida.");}
            if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible enviar el mensaje.");
            formulario.reset();
            setTimeout(() => mostrarMensaje(resultado.mensaje || "Mensaje enviado correctamente.", true), 0);
        } catch (error) {
            mostrarMensaje(error.message || "No fue posible enviar el mensaje.");
            refrescarCaptcha();
            console.error("Error enviando formulario de contacto:", error);
        } finally {
            botonEnviar.disabled = false;
            if (textoBoton) textoBoton.textContent = "Enviar mensaje";
        }
    });
}

function inicializarMapaContacto() {
    const contenedor = document.getElementById("mapaContacto");
    if (!contenedor || typeof window.L === "undefined") return;
    const posicion = [-33.28230838622429, -70.87951755816192];
    const mapa = window.L.map(contenedor, {scrollWheelZoom:false}).setView(posicion, 16);
    window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.webp", {attribution:"&copy; OpenStreetMap contributors",maxZoom:19}).addTo(mapa);
    const icono = window.L.divIcon({className:"marcadorLogoContacto",html:'<img src="images/logoAlianzaPro.webp" alt="">',iconSize:[58,58],iconAnchor:[29,58],popupAnchor:[0,-55]});
    window.L.marker(posicion, {icon:icono}).addTo(mapa).bindPopup('<div class="popupContacto"><strong>AlianzaPro SPA</strong><br>Baquedano 687, Lampa<br>Su auto, nuestro compromiso.</div>').openPopup();
}
