export default class Carrito {

    constructor() {

        this.carrito = this.cargarCarrito();

        this.actualizarContador();
    }


    /* ==================================================
       CARGAR Y NORMALIZAR
    ================================================== */

    cargarCarrito() {

        try {

            const guardado =
                JSON.parse(
                    localStorage.getItem("carrito")
                );

            if (!Array.isArray(guardado)) {
                return [];
            }

            return guardado
                .map(item => {

                    const stock =
                        Math.max(
                            0,
                            Number(item.cantidad) || 0
                        );

                    const cantidadCliente =
                        Math.max(
                            1,
                            Number(item.cant) || 1
                        );

                    return {
                        id_producto:
                            Number(item.id_producto),

                        nombre:
                            String(item.nombre || ""),

                        marca:
                            String(item.marca || ""),

                        categoria:
                            String(item.categoria || ""),

                        precio:
                            Math.max(
                                0,
                                Number(item.precio) || 0
                            ),

                        cantidad: stock,

                        cant:
                            Math.min(
                                cantidadCliente,
                                stock
                            )
                    };
                })
                .filter(item =>
                    item.id_producto > 0 &&
                    item.cantidad > 0 &&
                    item.cant > 0
                );

        } catch (error) {

            console.error(
                "No fue posible leer el carrito:",
                error
            );

            localStorage.removeItem("carrito");

            return [];
        }
    }


    /* ==================================================
       GUARDAR
    ================================================== */

    guardarCarrito() {

        localStorage.setItem(
            "carrito",
            JSON.stringify(this.carrito)
        );

        this.actualizarContador();
    }


    /* ==================================================
       AGREGAR PRODUCTO
    ================================================== */

    agregarProducto(producto) {

        const idProducto =
            Number(producto.id_producto);

        const stock =
            Math.max(
                0,
                Number(producto.cantidad) || 0
            );

        if (!idProducto) {

            return {
                ok: false,
                mensaje:
                    "El producto seleccionado no es válido."
            };
        }

        if (stock <= 0) {

            return {
                ok: false,
                mensaje:
                    "Este producto está agotado."
            };
        }

        const existente =
            this.carrito.find(item =>
                Number(item.id_producto) ===
                idProducto
            );

        if (existente) {

            if (existente.cant >= stock) {

                return {
                    ok: false,
                    mensaje:
                        `Solo existen ${stock} unidades disponibles.`
                };
            }

            existente.cant += 1;
            existente.cantidad = stock;
            existente.precio =
                Number(producto.precio) || 0;

        } else {

            this.carrito.push({

                id_producto: idProducto,

                nombre:
                    String(producto.nombre || ""),

                marca:
                    String(producto.marca || ""),

                categoria:
                    String(producto.categoria || ""),

                precio:
                    Math.max(
                        0,
                        Number(producto.precio) || 0
                    ),

                cantidad: stock,

                cant: 1
            });
        }

        this.guardarCarrito();

        return {
            ok: true,
            mensaje:
                "Producto agregado al carrito."
        };
    }


    /* ==================================================
       ACTUALIZAR CANTIDAD
    ================================================== */

    actualizarCantidad(
        idProducto,
        nuevaCantidad
    ) {

        const id =
            Number(idProducto);

        const cantidad =
            Number(nuevaCantidad);

        const item =
            this.carrito.find(producto =>
                Number(producto.id_producto) === id
            );

        if (!item) {

            return {
                ok: false,
                mensaje:
                    "El producto no está en el carrito."
            };
        }

        if (!Number.isInteger(cantidad)) {

            return {
                ok: false,
                mensaje:
                    "La cantidad no es válida."
            };
        }

        if (cantidad <= 0) {

            this.eliminarProducto(id);

            return {
                ok: true,
                mensaje:
                    "Producto eliminado del carrito."
            };
        }

        if (cantidad > item.cantidad) {

            return {
                ok: false,
                mensaje:
                    `Solo existen ${item.cantidad} unidades disponibles.`
            };
        }

        item.cant = cantidad;

        this.guardarCarrito();

        return {
            ok: true,
            mensaje:
                "Cantidad actualizada."
        };
    }


    /* ==================================================
       ELIMINAR PRODUCTO
    ================================================== */

    eliminarProducto(idProducto) {

        const id = Number(idProducto);

        const indice =
            this.carrito.findIndex(item =>
                Number(item.id_producto) === id
            );

        if (indice === -1) {
            return false;
        }

        this.carrito.splice(indice, 1);

        this.guardarCarrito();

        return true;
    }


    /* ==================================================
       VACIAR
    ================================================== */

    vaciarCarrito() {

        this.carrito = [];

        localStorage.removeItem("carrito");

        this.actualizarContador();
    }


    /* ==================================================
       CÁLCULOS
    ================================================== */

    calcularTotal() {

        return this.carrito.reduce(
            (total, item) =>
                total +
                (
                    Number(item.precio) *
                    Number(item.cant)
                ),
            0
        );
    }


    calcularSubTotal() {

        return this.calcularTotal();
    }


    calcularNeto() {

        return Math.round(
            this.calcularTotal() / 1.19
        );
    }


    calcularIVA() {

        return (
            this.calcularTotal() -
            this.calcularNeto()
        );
    }


    obtenerCantidadTotal() {

        return this.carrito.reduce(
            (total, item) =>
                total + Number(item.cant),
            0
        );
    }


    /* ==================================================
       CONTADOR
    ================================================== */

    actualizarContador() {

        const contador =
            document.getElementById(
                "contadorCarrito"
            );

        if (contador) {

            contador.textContent =
                this.obtenerCantidadTotal();
        }
    }


    /* ==================================================
       OBTENER CARRITO
    ================================================== */

    obtenerCarrito() {

        return this.carrito;
    }


    estaVacio() {

        return this.carrito.length === 0;
    }
}