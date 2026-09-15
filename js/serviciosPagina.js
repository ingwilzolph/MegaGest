const elementosServicios = {
    menu: document.getElementById("menuInicio"),
    botonMenu: document.getElementById("botonMenuInicio"),
    volverArriba: document.getElementById("volverArribaInicio"),
    contador: document.getElementById("contadorServicios"),
    miniaturas: document.getElementById("miniaturasServicios"),
    imagen: document.getElementById("imagenPrincipal"),
    titulo: document.getElementById("titleService"),
    descripcion: document.getElementById("descriptionService"),
    precio: document.getElementById("priceService"),
    estado: document.getElementById("estadoServicioPrincipal"),
    selector: document.getElementById("selectServicio"),
    reservar: document.getElementById("reservarServicioSeleccionado")
};

let serviciosPublicados = [];
let servicioSeleccionado = null;

document.getElementById("anioInicio").textContent = new Date().getFullYear();

elementosServicios.botonMenu?.addEventListener("click", () => {
    const abierto = elementosServicios.menu.classList.toggle("abierto");
    elementosServicios.botonMenu.setAttribute("aria-expanded", String(abierto));
});

elementosServicios.menu?.querySelectorAll("a").forEach(enlace => {
    enlace.addEventListener("click", () => elementosServicios.menu.classList.remove("abierto"));
});

window.addEventListener("scroll", () => {
    elementosServicios.volverArriba?.classList.toggle("visible", window.scrollY > 650);
}, {passive: true});

elementosServicios.volverArriba?.addEventListener("click", () => {
    window.scrollTo({top: 0, behavior: "smooth"});
});

function escapar(valor) {
    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function moneda(valor) {
    return new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        maximumFractionDigits: 0
    }).format(Number(valor) || 0);
}

function rutasImagen(servicio) {
    const id = Number(servicio.id_servicio);
    const archivo = String(servicio.imagen || servicio.thumb || "").trim();
    const rutas = [];

    if (archivo) {
        rutas.push(archivo.includes("/") ? archivo : `images/servicios/${archivo}`);
    }

    rutas.push(
        `images/servicios/${id}.webp`,
        `images/servicios/${id}.webp`,
        `images/servicios/${id}.jpg`,
        `images/thumbs/${id}.webp`,
        "images/cabesaRepuestos.webp"
    );

    return [...new Set(rutas)];
}

function aplicarImagenConRespaldo(imagen, servicio) {
    const rutas = rutasImagen(servicio);
    let indice = 0;

    imagen.onerror = () => {
        indice += 1;
        if (indice < rutas.length) imagen.src = rutas[indice];
        else imagen.onerror = null;
    };

    imagen.src = rutas[indice];
}

function seleccionarServicio(servicio, desplazar = false) {
    if (!servicio) return;

    servicioSeleccionado = servicio;
    elementosServicios.titulo.textContent = servicio.nombre || "Servicio automotriz";
    elementosServicios.descripcion.textContent =
        servicio.descripcion || "Atención especializada para su vehículo.";
    elementosServicios.precio.textContent = Number(servicio.precio_min) > 0
        ? moneda(servicio.precio_min)
        : "Consultar";
    elementosServicios.estado.textContent = servicio.categoria || "Servicio automotriz";
    elementosServicios.imagen.alt = servicio.nombre || "Servicio AlianzaPro";
    aplicarImagenConRespaldo(elementosServicios.imagen, servicio);

    if (elementosServicios.selector) {
        elementosServicios.selector.value = servicio.nombre || "";
    }

    elementosServicios.miniaturas.querySelectorAll(".miniaturaServicio").forEach(boton => {
        const activo = Number(boton.dataset.idServicio) === Number(servicio.id_servicio);
        boton.classList.toggle("activa", activo);
        boton.setAttribute("aria-pressed", String(activo));
        if (activo) boton.scrollIntoView({behavior: "smooth", block: "nearest", inline: "center"});
    });

    if (desplazar) {
        document.getElementById("visorServicios")?.scrollIntoView({behavior: "smooth", block: "start"});
    }
}

function renderizarServicios(servicios) {
    elementosServicios.selector.innerHTML = '<option value="">Seleccione un servicio</option>';
    elementosServicios.miniaturas.innerHTML = "";

    servicios.forEach(servicio => {
        const opcion = document.createElement("option");
        opcion.value = servicio.nombre;
        opcion.textContent = servicio.nombre;
        elementosServicios.selector.appendChild(opcion);

        const boton = document.createElement("button");
        boton.type = "button";
        boton.className = "miniaturaServicio";
        boton.dataset.idServicio = servicio.id_servicio;
        boton.setAttribute("aria-pressed", "false");
        boton.setAttribute("aria-label", `Mostrar ${servicio.nombre}`);
        boton.innerHTML = `
            <span class="imagenMiniaturaServicio"><img alt="${escapar(servicio.nombre)}" loading="lazy"></span>
            <span class="nombreMiniaturaServicio">${escapar(servicio.nombre)}</span>
        `;
        aplicarImagenConRespaldo(boton.querySelector("img"), servicio);
        boton.addEventListener("click", () => seleccionarServicio(servicio));
        elementosServicios.miniaturas.appendChild(boton);
    });
}

function servicioInicial(servicios) {
    const parametro = Number(new URLSearchParams(window.location.search).get("servicio"));
    if (parametro) {
        const encontrado = servicios.find(servicio => Number(servicio.id_servicio) === parametro);
        if (encontrado) return encontrado;
    }

    try {
        const guardado = JSON.parse(sessionStorage.getItem("servicio") || "null");
        if (guardado?.id_servicio) {
            const encontrado = servicios.find(servicio =>
                Number(servicio.id_servicio) === Number(guardado.id_servicio)
            );
            if (encontrado) return encontrado;
        }
    } catch (error) {
        console.warn("No fue posible leer el servicio guardado.", error);
    }

    return servicios[0];
}

async function cargarServicios() {
    try {
        const respuesta = await fetch("php/obtenerServicios.php", {cache: "no-store"});
        const texto = await respuesta.text();
        const resultado = texto ? JSON.parse(texto) : null;

        if (!respuesta.ok || !resultado?.ok) {
            throw new Error(resultado?.mensaje || `HTTP ${respuesta.status}`);
        }

        serviciosPublicados = Array.isArray(resultado.datos) ? resultado.datos : [];
        elementosServicios.contador.textContent = `${serviciosPublicados.length} servicio${serviciosPublicados.length === 1 ? "" : "s"} disponible${serviciosPublicados.length === 1 ? "" : "s"}`;

        if (!serviciosPublicados.length) {
            elementosServicios.miniaturas.innerHTML = '<div class="cargandoMiniaturas">No hay servicios publicados en este momento.</div>';
            elementosServicios.selector.innerHTML = '<option value="">No hay servicios disponibles</option>';
            elementosServicios.titulo.textContent = "Sin servicios publicados";
            elementosServicios.descripcion.textContent = "Puede contactarnos para consultar las atenciones disponibles.";
            elementosServicios.reservar.disabled = true;
            return;
        }

        renderizarServicios(serviciosPublicados);
        seleccionarServicio(servicioInicial(serviciosPublicados));
    } catch (error) {
        console.error("Error cargando servicios:", error);
        elementosServicios.contador.textContent = "Servicios no disponibles";
        elementosServicios.miniaturas.innerHTML = '<div class="cargandoMiniaturas">No fue posible cargar los servicios. Inténtelo nuevamente.</div>';
        elementosServicios.selector.innerHTML = '<option value="">Error al cargar servicios</option>';
        elementosServicios.titulo.textContent = "No fue posible cargar los servicios";
        elementosServicios.descripcion.textContent = error.message;
        elementosServicios.reservar.disabled = true;
    }
}

elementosServicios.selector?.addEventListener("change", () => {
    const servicio = serviciosPublicados.find(item => item.nombre === elementosServicios.selector.value);
    if (servicio) seleccionarServicio(servicio, true);
});

elementosServicios.reservar?.addEventListener("click", () => {
    if (!servicioSeleccionado) return;
    elementosServicios.selector.value = servicioSeleccionado.nombre;
    document.getElementById("formCita")?.scrollIntoView({behavior: "smooth", block: "start"});
    setTimeout(() => elementosServicios.selector.focus(), 500);
});

cargarServicios();
