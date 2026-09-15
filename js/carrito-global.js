import Carrito from "./carrito.js?v=4";

const carrito = new Carrito();

const contenido =
    document.getElementById("mi-elemento");

const footer =
    document.getElementById("footerCarrito");

const titulo =
    document.getElementById("titleCarrito");

const btnVaciar =
    document.getElementById("btnVaciarCarrito");

const panelCarrito =
    document.getElementById("carritoLateralInicio");

const fondoCarrito =
    document.getElementById("fondoCarritoInicio");

const btnAbrirCarrito =
    document.getElementById("abrirCarritoInicio");

const btnCerrarCarrito =
    document.getElementById("cerrarCarritoInicio");


inicializarCarritoGlobal();

window.addEventListener(
    "alianzapro:carrito",
    function () {
        carrito.carrito = carrito.cargarCarrito();
        actualizarCarritoGlobal();
    }
);


function inicializarCarritoGlobal() {

    actualizarCarritoGlobal();

    btnAbrirCarrito?.addEventListener("click", () => {
        cambiarEstadoCarrito(true);
    });

    btnCerrarCarrito?.addEventListener("click", () => {
        cambiarEstadoCarrito(false);
    });

    fondoCarrito?.addEventListener("click", () => {
        cambiarEstadoCarrito(false);
    });

    document.addEventListener("keydown", evento => {
        if (evento.key === "Escape") {
            cambiarEstadoCarrito(false);
        }
    });

    if (btnVaciar) {

        btnVaciar.addEventListener(
            "click",
            function () {

                if (carrito.estaVacio()) {
                    return;
                }

                const confirmado = confirm(
                    "¿Desea vaciar completamente el carrito?"
                );

                if (!confirmado) {
                    return;
                }

                carrito.vaciarCarrito();

                actualizarCarritoGlobal();
            }
        );
    }
}


function cambiarEstadoCarrito(abierto) {

    if (!panelCarrito) {
        return;
    }

    panelCarrito.classList.toggle("abierto", abierto);
    panelCarrito.setAttribute("aria-hidden", String(!abierto));

    if (fondoCarrito) {
        fondoCarrito.hidden = !abierto;
    }

    document.body.classList.toggle("carritoAbierto", abierto);
}


function actualizarCarritoGlobal() {

    carrito.actualizarContador();

    const cantidad =
        carrito.obtenerCantidadTotal();

    if (titulo) {

        titulo.textContent =
            `Carrito (${cantidad})`;
    }

    if (!contenido || !footer) {
        return;
    }

    contenido.innerHTML = "";

    if (carrito.estaVacio()) {

        contenido.innerHTML = `

            <div class="carritoVacio">

                <div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>

                <h3>Su carrito está vacío</h3>

                <p>
                    Visite nuestro catálogo y agregue
                    los productos que necesita.
                </p>

                <a
                    href="./listing-row.html"
                    class="btnIrProductosCarrito">

                    Ver productos

                </a>

            </div>
        `;

        footer.innerHTML = "";

        return;
    }

    carrito
        .obtenerCarrito()
        .forEach(item => {

            contenido.appendChild(
                crearItemCarritoGlobal(item)
            );
        });

    const neto =
        carrito.calcularNeto();

    const iva =
        carrito.calcularIVA();

    const total =
        carrito.calcularTotal();

    footer.innerHTML = `

        <div class="filaResumenCarrito">

            <span>Neto</span>

            <strong>
                ${formatearPrecioCarrito(neto)}
            </strong>

        </div>

        <div class="filaResumenCarrito">

            <span>IVA incluido (19%)</span>

            <strong>
                ${formatearPrecioCarrito(iva)}
            </strong>

        </div>

        <div class="totalResumenCarrito">

            <span>Total</span>

            <strong>
                ${formatearPrecioCarrito(total)}
            </strong>

        </div>

        <button
            type="button"
            id="btnContinuarCompraGlobal"
            class="btn-comprar">

            <span>Continuar compra</span>

            <span
                class="flechaComprar"
                aria-hidden="true">
                →
            </span>

        </button>
    `;

    document
        .getElementById(
            "btnContinuarCompraGlobal"
        )
        .addEventListener("click", () => {

            window.location.href =
                "./checkOut.html";
        });
}


function crearItemCarritoGlobal(item) {

    const elemento =
        document.createElement("article");

    elemento.className = "itemCarrito";

    elemento.innerHTML = `

        <div class="imagenItemCarrito">

            <img
                src="./images/productos/${
                    Number(item.id_producto)
                }.webp"
                alt="${escaparHTMLCarrito(item.nombre)}">

        </div>

        <div class="informacionItemCarrito">

            <strong>
                ${escaparHTMLCarrito(item.nombre)}
            </strong>

            <small>
                ${escaparHTMLCarrito(
                    item.marca || "Sin marca"
                )}
            </small>

            <div class="controlesItemCarrito">

                <div class="cantidadItemCarrito">

                    <button
                        type="button"
                        class="btnRestarItem"
                        title="Disminuir cantidad"
                        aria-label="Disminuir cantidad">

                        <span aria-hidden="true">−</span>

                    </button>

                    <span class="numeroCantidadItem">
                        ${Number(item.cant)}
                    </span>

                    <button
                        type="button"
                        class="btnSumarItem"
                        title="Aumentar cantidad"
                        aria-label="Aumentar cantidad">

                        <span aria-hidden="true">+</span>

                    </button>

                </div>

                <strong class="precioItemCarrito">

                    ${formatearPrecioCarrito(
                        Number(item.precio) *
                        Number(item.cant)
                    )}

                </strong>

            </div>

        </div>

        <button
            type="button"
            class="btnEliminarItemCarrito"
            title="Eliminar producto">

            <i class="fa-solid fa-trash-can"></i>

        </button>
    `;

    elemento
        .querySelector(".btnRestarItem")
        .addEventListener("click", () => {

            carrito.actualizarCantidad(
                item.id_producto,
                Number(item.cant) - 1
            );

            actualizarCarritoGlobal();
        });

    elemento
        .querySelector(".btnSumarItem")
        .addEventListener("click", () => {

            const resultado =
                carrito.actualizarCantidad(
                    item.id_producto,
                    Number(item.cant) + 1
                );

            if (!resultado.ok) {
                alert(resultado.mensaje);
            }

            actualizarCarritoGlobal();
        });

    elemento
        .querySelector(
            ".btnEliminarItemCarrito"
        )
        .addEventListener("click", () => {

            carrito.eliminarProducto(
                item.id_producto
            );

            actualizarCarritoGlobal();
        });

    const imagen =
        elemento.querySelector("img");

    imagen.onerror = function () {

        this.onerror = null;

        this.src =
            "./images/productos/no-image.webp";
    };

    return elemento;
}


function formatearPrecioCarrito(valor) {

    return new Intl.NumberFormat(
        "es-CL",
        {
            style: "currency",
            currency: "CLP",
            minimumFractionDigits: 0
        }
    ).format(Number(valor) || 0);
}


function escaparHTMLCarrito(valor) {

    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}
