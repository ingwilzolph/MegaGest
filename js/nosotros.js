"use strict";

document.addEventListener("DOMContentLoaded", () => {
    inicializarMenuNosotros();
    inicializarReveladoNosotros();
    inicializarVolverArribaNosotros();
    actualizarAnioNosotros();
    cargarEstadisticasNosotros();
});

function inicializarMenuNosotros() {
    const boton = document.getElementById("botonMenuInicio");
    const menu = document.getElementById("menuInicio");

    if (!boton || !menu) return;

    const cerrarMenu = () => {
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
        enlace.addEventListener("click", cerrarMenu);
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 850) cerrarMenu();
    });
}

function inicializarReveladoNosotros() {
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
    }, {
        threshold: 0.12,
        rootMargin: "0px 0px -35px"
    });

    elementos.forEach((elemento) => observador.observe(elemento));
}

function inicializarVolverArribaNosotros() {
    const boton = document.getElementById("volverArribaInicio");
    if (!boton) return;

    const actualizar = () => {
        boton.classList.toggle("visible", window.scrollY > 500);
    };

    window.addEventListener("scroll", actualizar, {passive: true});
    boton.addEventListener("click", () => {
        window.scrollTo({top: 0, behavior: "smooth"});
    });
    actualizar();
}

function actualizarAnioNosotros() {
    const anio = document.getElementById("anioInicio");
    if (anio) anio.textContent = new Date().getFullYear();
}

async function solicitarJsonNosotros(url) {
    const respuesta = await fetch(url, {cache: "no-store"});
    const texto = await respuesta.text();

    if (!respuesta.ok) {
        throw new Error(`HTTP ${respuesta.status} al consultar ${url}`);
    }

    if (!texto.trim()) {
        throw new Error(`Respuesta vacía desde ${url}`);
    }

    let resultado;
    try {
        resultado = JSON.parse(texto);
    } catch (error) {
        throw new Error(`La respuesta de ${url} no es JSON válido`);
    }

    if (!resultado.ok) {
        throw new Error(resultado.mensaje || `No fue posible consultar ${url}`);
    }

    return Array.isArray(resultado.datos) ? resultado.datos : [];
}

function asignarEstadisticaNosotros(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = Number(valor).toLocaleString("es-CL");
}

async function cargarEstadisticasNosotros() {
    const consultaServicios = solicitarJsonNosotros("php/obtenerServicios.php")
        .then((servicios) => {
            asignarEstadisticaNosotros("cantidadServiciosNosotros", servicios.length);
        })
        .catch((error) => {
            console.warn("No se cargaron las estadísticas de servicios:", error);
            asignarEstadisticaNosotros("cantidadServiciosNosotros", 0);
        });

    const consultaProductos = solicitarJsonNosotros("php/obtenerTableProductos.php")
        .then((productos) => {
            const disponibles = productos.filter((producto) => Number(producto.cantidad) > 0);
            const unidades = disponibles.reduce(
                (total, producto) => total + Math.max(0, Number(producto.cantidad) || 0),
                0
            );

            asignarEstadisticaNosotros("cantidadProductosNosotros", disponibles.length);
            asignarEstadisticaNosotros("unidadesNosotros", unidades);
        })
        .catch((error) => {
            console.warn("No se cargaron las estadísticas de productos:", error);
            asignarEstadisticaNosotros("cantidadProductosNosotros", 0);
            asignarEstadisticaNosotros("unidadesNosotros", 0);
        });

    await Promise.allSettled([consultaServicios, consultaProductos]);
}
