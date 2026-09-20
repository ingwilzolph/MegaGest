import Carrito from "./carrito.js?v=4";

const carrito = new Carrito();
const elementos = {
    menu: document.getElementById("menuInicio"),
    botonMenu: document.getElementById("botonMenuInicio"),
    servicios: document.getElementById("serviciosInicio"),
    productos: document.getElementById("productosInicio"),
    volverArriba: document.getElementById("volverArribaInicio")
};

document.getElementById("anioInicio").textContent = new Date().getFullYear();
elementos.botonMenu?.addEventListener("click", () => {
    const abierto = elementos.menu.classList.toggle("abierto");
    elementos.botonMenu.setAttribute("aria-expanded", String(abierto));
});
elementos.menu?.querySelectorAll("a").forEach(enlace => enlace.addEventListener("click", () => elementos.menu.classList.remove("abierto")));

const observador = "IntersectionObserver" in window ? new IntersectionObserver(entradas => {
    entradas.forEach(entrada => {
        if (entrada.isIntersecting) {
            entrada.target.classList.add("visible");
            observador.unobserve(entrada.target);
        }
    });
}, {threshold: .12}) : null;
document.querySelectorAll("[data-revelar]").forEach(elemento => observador ? observador.observe(elemento) : elemento.classList.add("visible"));

window.addEventListener("scroll", () => elementos.volverArriba?.classList.toggle("visible", window.scrollY > 650), {passive: true});
elementos.volverArriba?.addEventListener("click", () => window.scrollTo({top: 0, behavior: "smooth"}));

function escapar(valor) {
    return String(valor ?? "").replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
}
function moneda(valor) {
    return new Intl.NumberFormat("es-CL", {style: "currency", currency: "CLP", maximumFractionDigits: 0}).format(Number(valor) || 0);
}
function rutaServicio(servicio) {
    const id = Number(servicio.id_servicio);
    const archivo = String(servicio.imagen || servicio.thumb || "").trim();
    return archivo ? (archivo.includes("/") ? archivo : `images/servicios/${archivo}`) : `images/servicios/${id}.webp`;
}

async function cargarServiciosInicio() {
    try {
        const respuesta = await fetch("php/obtenerServicios.php", {cache: "no-store"});
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible consultar los servicios.");
        const servicios = (Array.isArray(resultado.datos) ? resultado.datos : []).slice(0, 4);
        if (!servicios.length) {
            elementos.servicios.innerHTML = '<div class="estadoCargaInicio">No hay servicios publicados por el momento.</div>';
            return;
        }
        elementos.servicios.innerHTML = servicios.map(servicio => `
            <a class="tarjetaServicioInicio" href="services.html?servicio=${Number(servicio.id_servicio)}#formCita">
                <img src="${escapar(rutaServicio(servicio))}" alt="${escapar(servicio.nombre)}" loading="lazy" onerror="this.onerror=null;this.src='images/cabesaRepuestos.webp'">
                <div class="tarjetaServicioContenido">
                    <small>Servicio automotriz</small>
                    <h3>${escapar(servicio.nombre)}</h3>
                    <p>${escapar(servicio.descripcion || "Atención especializada para su vehículo.")}</p>
                    <b>Reservar atención →</b>
                </div>
            </a>
        `).join("");
    } catch (error) {
        console.error("Error cargando servicios:", error);
        elementos.servicios.innerHTML = '<div class="estadoCargaInicio">No fue posible cargar los servicios.</div>';
    }
}

async function cargarProductosInicio() {
    try {
        const respuesta = await fetch("php/obtenerTableProductos.php", {cache: "no-store"});
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || "No fue posible consultar los productos.");
        const todos = Array.isArray(resultado.datos) ? resultado.datos : [];
        const preferidos = todos.filter(producto => Number(producto.en_oferta) === 1 || Number(producto.destacado) === 1);
        const productos = (preferidos.length ? preferidos : todos).slice(0, 4);
        if (!productos.length) {
            elementos.productos.innerHTML = '<div class="estadoCargaInicio">No hay productos publicados por el momento.</div>';
            return;
        }
        elementos.productos.innerHTML = productos.map(producto => {
            const oferta = Number(producto.en_oferta) === 1;
            const disponible = Number(producto.cantidad) > 0;
            return `
                <article class="tarjetaProductoInicio">
                    ${oferta ? '<span class="insigniaProductoInicio">Oferta</span>' : (Number(producto.destacado) ? '<span class="insigniaProductoInicio">Destacado</span>' : "")}
                    <a class="imagenProductoInicio" href="repuestos.html"><img src="images/productos/${Number(producto.id_producto)}.webp" alt="${escapar(producto.nombre)}" loading="lazy" onerror="this.onerror=null;this.src='images/productos/no-image.webp'"></a>
                    <div class="contenidoProductoInicio">
                        <small>${escapar(producto.marca || producto.categoria || "Repuesto")}</small>
                        <h3>${escapar(producto.nombre)}</h3>
                        <div class="preciosProductoInicio"><strong>${moneda(producto.precio)}</strong>${oferta ? `<del>${moneda(producto.precio_normal)}</del>` : ""}</div>
                        <button class="botonAgregarInicio" type="button" data-producto="${Number(producto.id_producto)}" ${disponible ? "" : "disabled"}>${disponible ? "Agregar al carrito" : "Sin stock"}</button>
                    </div>
                </article>`;
        }).join("");
        elementos.productos.querySelectorAll("[data-producto]").forEach(boton => {
            boton.addEventListener("click", () => {
                const producto = productos.find(item => Number(item.id_producto) === Number(boton.dataset.producto));
                if (!producto) return;
                const resultadoAgregar = carrito.agregarProducto({
                    id_producto: Number(producto.id_producto),
                    nombre: producto.nombre,
                    marca: producto.marca,
                    precio: Number(producto.precio),
                    cantidad: Number(producto.cantidad)
                });
                if (resultadoAgregar?.ok === false) {
                    alert(resultadoAgregar.mensaje);
                    return;
                }
                window.dispatchEvent(new CustomEvent("alianzapro:carrito"));
                const textoOriginal = boton.textContent;
                boton.textContent = "Agregado ✓";
                setTimeout(() => boton.textContent = textoOriginal, 1200);
            });
        });
    } catch (error) {
        console.error("Error cargando productos:", error);
        elementos.productos.innerHTML = '<div class="estadoCargaInicio">No fue posible cargar los productos.</div>';
    }
}

cargarServiciosInicio();
cargarProductosInicio();
