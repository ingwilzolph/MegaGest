"use strict";

document.addEventListener("DOMContentLoaded", () => {
    inicializarMenuLoginAdmin();
    inicializarFormularioLoginAdmin();
    inicializarPasswordLoginAdmin();
    inicializarReveladoLoginAdmin();

    const anio = document.getElementById("anioInicio");
    if (anio) anio.textContent = new Date().getFullYear();
});

function inicializarMenuLoginAdmin() {
    const boton = document.getElementById("botonMenuInicio");
    const menu = document.getElementById("menuInicio");

    if (!boton || !menu) return;

    const cerrar = () => {
        menu.classList.remove("abierto");
        boton.classList.remove("activo");
        boton.setAttribute("aria-expanded", "false");
    };

    boton.addEventListener("click", () => {
        const abierto = menu.classList.toggle("abierto");
        boton.classList.toggle("activo", abierto);
        boton.setAttribute("aria-expanded", String(abierto));
    });

    menu.querySelectorAll("a").forEach((enlace) => {
        enlace.addEventListener("click", cerrar);
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 850) cerrar();
    });
}

function inicializarFormularioLoginAdmin() {
    const formulario = document.getElementById("form-login");
    const correo = document.getElementById("correo");
    const recordar = document.getElementById("remember");

    if (!formulario) return;

    const correoGuardado = localStorage.getItem("correo_admin_recordado") || "";

    if (correo && correoGuardado) {
        correo.value = correoGuardado;
        if (recordar) recordar.checked = true;
    }

    formulario.addEventListener("submit", iniciarSesionAdmin);

    const parametros = new URLSearchParams(window.location.search);
    if (parametros.get("sesion") === "expirada") {
        mostrarRespuestaLoginAdmin(
            "La sesión expiró por inactividad. Ingrese nuevamente.",
            false
        );
    }
}

async function iniciarSesionAdmin(evento) {
    evento.preventDefault();

    const formulario = evento.currentTarget;
    const boton = document.getElementById("botonIniciarSesion");
    const textoBoton = boton?.querySelector("span");
    const correo = document.getElementById("correo");
    const recordar = document.getElementById("remember");

    if (!formulario.checkValidity()) {
        formulario.reportValidity();
        mostrarRespuestaLoginAdmin("Complete el correo y la contraseña.", false);
        return;
    }

    if (boton) boton.disabled = true;
    if (textoBoton) textoBoton.textContent = "Verificando…";
    limpiarRespuestaLoginAdmin();

    try {
        const respuesta = await fetch(formulario.action, {
            method: "POST",
            body: new FormData(formulario),
            headers: {"X-Requested-With": "XMLHttpRequest"}
        });

        const texto = await respuesta.text();
        if (!texto.trim()) throw new Error("El servidor devolvió una respuesta vacía.");

        let resultado;
        try {
            resultado = JSON.parse(texto);
        } catch (_) {
            throw new Error("El servidor no devolvió una respuesta válida.");
        }

        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.mensaje || "No fue posible iniciar sesión.");
        }

        const usuario = resultado.usuario || {};
        const rol = String(usuario.rol || "").toLowerCase();

        localStorage.setItem("usuario", `${resultado.mensaje || "Bienvenido"}, ${rol}`);
        localStorage.setItem("rol", rol);
        localStorage.setItem("id_usuario", String(usuario.id_usuario || ""));

        if (recordar?.checked && correo) {
            localStorage.setItem("correo_admin_recordado", correo.value.trim().toLowerCase());
        } else {
            localStorage.removeItem("correo_admin_recordado");
        }

        mostrarRespuestaLoginAdmin("Acceso correcto. Abriendo MegaGest…", true);
        window.setTimeout(() => {
            window.location.href = "admin.php";
        }, 450);

    } catch (error) {
        mostrarRespuestaLoginAdmin(error.message || "No fue posible iniciar sesión.", false);
        const password = document.getElementById("password");
        if (password) {
            password.value = "";
            password.focus();
        }

    } finally {
        if (boton) boton.disabled = false;
        if (textoBoton) textoBoton.textContent = "Ingresar a MegaGest";
    }
}

function inicializarPasswordLoginAdmin() {
    const boton = document.getElementById("mostrarPasswordLogin");
    const campo = document.getElementById("password");

    if (!boton || !campo) return;

    boton.addEventListener("click", () => {
        const mostrar = campo.type === "password";
        campo.type = mostrar ? "text" : "password";
        boton.setAttribute("aria-label", mostrar ? "Ocultar contraseña" : "Mostrar contraseña");
        boton.innerHTML = `<i class="fa-regular ${mostrar ? "fa-eye-slash" : "fa-eye"}"></i>`;
    });
}

function mostrarRespuestaLoginAdmin(mensaje, correcto) {
    const contenedor = document.querySelector(".respuesta");
    if (!contenedor) return;

    contenedor.textContent = mensaje;
    contenedor.className = `respuesta visible ${correcto ? "correcto" : "error"}`;
}

function limpiarRespuestaLoginAdmin() {
    const contenedor = document.querySelector(".respuesta");
    if (!contenedor) return;

    contenedor.textContent = "";
    contenedor.className = "respuesta";
}

function inicializarReveladoLoginAdmin() {
    const elementos = document.querySelectorAll("[data-revelar]");

    if (!("IntersectionObserver" in window)) {
        elementos.forEach((elemento) => elemento.classList.add("visible"));
        return;
    }

    const observador = new IntersectionObserver((entradas, observer) => {
        entradas.forEach((entrada) => {
            if (!entrada.isIntersecting) return;
            entrada.target.classList.add("visible");
            observer.unobserve(entrada.target);
        });
    }, {threshold: .1});

    elementos.forEach((elemento) => observador.observe(elemento));
}
