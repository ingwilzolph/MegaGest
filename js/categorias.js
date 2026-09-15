/* =====================================================
   CATEGORÍAS DE LA TIENDA PÚBLICA
===================================================== */

const contenidoCategoria =
    document.getElementById("contenidoCategoria");

let categoriaTiendaActiva =
    localStorage.getItem("guardarProductos") || "";


async function cargarCategoriasTienda() {

    if (!contenidoCategoria) {
        return;
    }

    contenidoCategoria.innerHTML = `
        <div class="cargandoCategoriasTienda">
            <i class="fa-solid fa-spinner fa-spin"></i>
            Cargando categorías...
        </div>
    `;

    try {

        const response = await fetch(
            `./php/obtenerCategorias.php?v=${Date.now()}`,
            {
                cache: "no-store"
            }
        );

        const resultado = await response.json();

        if (!resultado.ok) {

            contenidoCategoria.innerHTML = `
                <p class="errorCategoriasTienda">
                    ${escaparHTMLCategoria(resultado.mensaje)}
                </p>
            `;

            return;
        }

        const categorias = Array.isArray(resultado.datos)
            ? resultado.datos
            : [];

        contenidoCategoria.innerHTML = "";

        categorias.forEach(categoria => {

            const nombre =
                String(
                    categoria.categoria ||
                    categoria.nombre ||
                    ""
                ).trim();

            if (!nombre) {
                return;
            }

            const boton =
                document.createElement("button");

            boton.type = "button";

            boton.className =
                "btnCategoriaTienda";

            boton.dataset.categoria =
                nombre.toLowerCase();

            boton.innerHTML = `
                <span class="iconoCategoriaTienda">
                    ${obtenerIconoCategoria(nombre)}
                </span>

                <span>
                    ${escaparHTMLCategoria(nombre)}
                </span>
            `;

            boton.classList.toggle(
                "activa",
                nombre.toLowerCase() ===
                categoriaTiendaActiva.toLowerCase()
            );

            boton.addEventListener(
                "click",
                function () {

                    categoriaTiendaActiva =
                        this.dataset.categoria;

                    localStorage.setItem(
                        "guardarProductos",
                        categoriaTiendaActiva
                    );

                    document
                        .querySelectorAll(
                            ".btnCategoriaTienda"
                        )
                        .forEach(elemento => {

                            elemento.classList.remove(
                                "activa"
                            );
                        });

                    this.classList.add("activa");

                    document.dispatchEvent(
                        new CustomEvent(
                            "categoriaTiendaSeleccionada",
                            {
                                detail: {
                                    categoria:
                                        categoriaTiendaActiva
                                }
                            }
                        )
                    );
                }
            );

            contenidoCategoria.appendChild(
                boton
            );
        });

    } catch (error) {

        console.error(
            "Error cargando categorías:",
            error
        );

        contenidoCategoria.innerHTML = `
            <p class="errorCategoriasTienda">
                No fue posible cargar las categorías.
            </p>
        `;
    }
}


function obtenerIconoCategoria(nombre) {

    const categoria =
        String(nombre).toLowerCase();

    const iconos = {

        accesorios:
            '<i class="fa-solid fa-toolbox"></i>',

        cables:
            '<i class="fa-solid fa-plug"></i>',

        carroceria:
            '<i class="fa-solid fa-car-side"></i>',

        escape:
            '<i class="fa-solid fa-wind"></i>',

        filtros:
            '<i class="fa-solid fa-filter"></i>',

        frenos:
            '<i class="fa-solid fa-compact-disc"></i>',

        iluminacion:
            '<i class="fa-solid fa-lightbulb"></i>',

        juntas:
            '<i class="fa-solid fa-ring"></i>',

        mangueras:
            '<i class="fa-solid fa-arrows-left-right"></i>',

        motor:
            '<i class="fa-solid fa-gears"></i>',

        neumaticos:
            '<i class="fa-solid fa-circle-dot"></i>',

        refrigeracion:
            '<i class="fa-solid fa-temperature-low"></i>',

        retenes:
            '<i class="fa-solid fa-circle-notch"></i>',

        rodamientos:
            '<i class="fa-solid fa-bullseye"></i>',

        suspension:
            '<i class="fa-solid fa-arrows-up-down"></i>',

        transmision:
            '<i class="fa-solid fa-gauge-high"></i>'
    };

    return iconos[categoria] ||
        '<i class="fa-solid fa-box"></i>';
}


function escaparHTMLCategoria(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


cargarCategoriasTienda();