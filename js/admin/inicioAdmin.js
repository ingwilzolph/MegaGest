(function () {
    "use strict";

    let relojInicioAdmin = null;
    let cargandoInicioAdmin = false;

    const monedaInicioAdmin = new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        maximumFractionDigits: 0
    });

    const fechaCompletaInicioAdmin = new Intl.DateTimeFormat("es-CL", {
        weekday: "long",
        day: "2-digit",
        month: "long",
        year: "numeric"
    });

    const fechaCortaInicioAdmin = new Intl.DateTimeFormat("es-CL", {
        day: "2-digit",
        month: "short",
        hour: "2-digit",
        minute: "2-digit"
    });

    window.inicializarInicioAdmin = inicializarInicioAdmin;
    window.cargarInicioAdmin = cargarInicioAdmin;

    function inicializarInicioAdmin() {
        const pagina = document.getElementById("inicioAdmin");

        if (!pagina) {
            return;
        }

        if (pagina.dataset.inicializado === "true") {
            cargarInicioAdmin();
            return;
        }

        pagina.dataset.inicializado = "true";

        const botonActualizar = document.getElementById("btnActualizarInicio");

        if (botonActualizar) {
            botonActualizar.addEventListener("click", cargarInicioAdmin);
        }

        actualizarRelojInicioAdmin();

        if (relojInicioAdmin !== null) {
            window.clearInterval(relojInicioAdmin);
        }

        relojInicioAdmin = window.setInterval(() => {
            if (!document.getElementById("inicioAdmin")) {
                window.clearInterval(relojInicioAdmin);
                relojInicioAdmin = null;
                return;
            }

            actualizarRelojInicioAdmin();
        }, 30000);

        cargarInicioAdmin();
    }

    async function cargarInicioAdmin() {
        if (cargandoInicioAdmin || !document.getElementById("inicioAdmin")) {
            return;
        }

        cargandoInicioAdmin = true;
        cambiarEstadoBotonInicioAdmin(true);
        ocultarMensajeInicioAdmin();

        try {
            const respuesta = await fetch("./php/obtenerInicio.php", {
                method: "GET",
                cache: "no-store",
                headers: {
                    Accept: "application/json"
                }
            });

            const texto = await respuesta.text();

            if (!texto.trim()) {
                throw new Error("El servidor devolvió una respuesta vacía.");
            }

            let resultado;

            try {
                resultado = JSON.parse(texto);
            } catch (error) {
                console.error("Respuesta no JSON de Inicio:", texto);
                throw new Error("La respuesta del servidor no es válida.");
            }

            if (resultado.sesionExpirada) {
                cerrarSesionInicioAdmin();
                return;
            }

            if (!respuesta.ok || !resultado.ok) {
                throw new Error(
                    resultado.mensaje || "No fue posible cargar la información de Inicio."
                );
            }

            mostrarInicioAdmin(resultado);

        } catch (error) {
            console.error("Error cargando Inicio:", error);
            mostrarErrorInicioAdmin(
                error.message || "No fue posible cargar la información de Inicio."
            );

        } finally {
            cargandoInicioAdmin = false;
            cambiarEstadoBotonInicioAdmin(false);
        }
    }

    function mostrarInicioAdmin(resultado) {
        const usuario = resultado.usuario || {};
        const permisos = resultado.permisos || {};
        const tarjetas = resultado.tarjetas || {};

        mostrarIdentidadInicioAdmin(usuario);
        aplicarPermisosInicioAdmin(permisos, tarjetas);
        mostrarTarjetasInicioAdmin(tarjetas);
        mostrarGraficoInicioAdmin(resultado.grafico || [], permisos);
        mostrarAgendaInicioAdmin(resultado.agenda || []);
        mostrarOrdenesInicioAdmin(resultado.ordenes || []);
        mostrarAlertasInicioAdmin(resultado.alertas || []);
        mostrarActividadInicioAdmin(resultado.actividad || []);
    }

    function mostrarIdentidadInicioAdmin(usuario) {
        const ahora = new Date();
        const hora = ahora.getHours();
        let saludo = "Buenas noches";

        if (hora >= 5 && hora < 12) {
            saludo = "Buenos días";
        } else if (hora >= 12 && hora < 20) {
            saludo = "Buenas tardes";
        }

        asignarTextoInicioAdmin("saludoInicio", saludo);
        asignarTextoInicioAdmin("nombreUsuarioInicio", primerNombreInicioAdmin(usuario.nombre));
        asignarTextoInicioAdmin("rolUsuarioInicio", capitalizarInicioAdmin(usuario.rol));

        const mensajes = {
            administrador: "Este es el resumen general y financiero de AlianzaPro para hoy.",
            cajero: "Aquí tienes los cobros, pedidos y operaciones que requieren atención.",
            vendedor: "Revisa tus ventas, pedidos, inventario y comisiones del día.",
            mecanico: "Estas son tus citas y órdenes de trabajo asignadas para hoy."
        };

        asignarTextoInicioAdmin(
            "mensajeInicio",
            mensajes[usuario.rol] || "Este es el resumen operativo de AlianzaPro para hoy."
        );

        actualizarRelojInicioAdmin();
    }

    function aplicarPermisosInicioAdmin(permisos, tarjetas) {
        document.querySelectorAll("#inicioAdmin [data-tarjeta]").forEach(tarjeta => {
            const clave = tarjeta.dataset.tarjeta;
            tarjeta.classList.toggle(
                "oculto",
                tarjetas[clave] === null || typeof tarjetas[clave] === "undefined"
            );
        });

        const panelGrafico = document.getElementById("panelGraficoInicio");

        if (panelGrafico) {
            panelGrafico.classList.toggle("oculto", !permisos.ver_ventas);
        }
    }

    function mostrarTarjetasInicioAdmin(datos) {
        asignarTextoInicioAdmin("inicioVentasHoy", monedaInicioAdmin.format(numeroInicioAdmin(datos.ventas_hoy)));
        asignarTextoInicioAdmin("inicioGananciaHoy", monedaInicioAdmin.format(numeroInicioAdmin(datos.ganancia_hoy)));
        asignarTextoInicioAdmin("inicioCostoHoy", "Costos: " + monedaInicioAdmin.format(numeroInicioAdmin(datos.costos_hoy)));
        asignarTextoInicioAdmin("inicioCitasHoy", numeroInicioAdmin(datos.citas_hoy));
        asignarTextoInicioAdmin("inicioOrdenesActivas", numeroInicioAdmin(datos.ordenes_activas));
        asignarTextoInicioAdmin("inicioPendientesCobro", numeroInicioAdmin(datos.pendientes_cobro));
        asignarTextoInicioAdmin("inicioPedidosPendientes", numeroInicioAdmin(datos.pedidos_pendientes));
        asignarTextoInicioAdmin("inicioStockCritico", numeroInicioAdmin(datos.stock_critico));
        asignarTextoInicioAdmin("inicioComisionesPropias", monedaInicioAdmin.format(numeroInicioAdmin(datos.comisiones_propias)));
    }

    function mostrarGraficoInicioAdmin(datos, permisos) {
        const contenedor = document.getElementById("graficoInicio");

        if (!contenedor || !permisos.ver_ventas) {
            return;
        }

        limpiarElementoInicioAdmin(contenedor);

        if (!datos.length) {
            contenedor.appendChild(crearVacioInicioAdmin("📉", "Sin movimientos", "No hay ingresos registrados en el período."));
            asignarTextoInicioAdmin("totalGraficoInicio", monedaInicioAdmin.format(0));
            return;
        }

        const maximo = Math.max(
            1,
            ...datos.flatMap(dia => [numeroInicioAdmin(dia.ventas), numeroInicioAdmin(dia.taller)])
        );
        const total = datos.reduce((suma, dia) => suma + numeroInicioAdmin(dia.total), 0);

        asignarTextoInicioAdmin("totalGraficoInicio", monedaInicioAdmin.format(total));

        const barras = crearElementoInicioAdmin("div", "grafico-barras");
        const etiquetas = crearElementoInicioAdmin("div", "grafico-etiquetas");

        datos.forEach(dia => {
            const grupo = crearElementoInicioAdmin("div", "grafico-dia");
            const barraVentas = crearBarraInicioAdmin("ventas", dia.ventas, maximo, dia.fecha);
            const barraTaller = crearBarraInicioAdmin("taller", dia.taller, maximo, dia.fecha);

            grupo.append(barraVentas, barraTaller);
            barras.appendChild(grupo);

            const etiqueta = document.createElement("span");
            etiqueta.textContent = nombreDiaInicioAdmin(dia.fecha);
            etiquetas.appendChild(etiqueta);
        });

        contenedor.append(barras, etiquetas);
    }

    function crearBarraInicioAdmin(tipo, valorOriginal, maximo, fecha) {
        const valor = numeroInicioAdmin(valorOriginal);
        const barra = crearElementoInicioAdmin("div", "grafico-columna " + tipo);
        const porcentaje = valor > 0 ? Math.max(4, (valor / maximo) * 100) : 1.5;

        barra.style.height = porcentaje + "%";
        barra.title = capitalizarInicioAdmin(tipo) + " · " +
            formatearFechaSimpleInicioAdmin(fecha) + " · " +
            monedaInicioAdmin.format(valor);

        return barra;
    }

    function mostrarAgendaInicioAdmin(datos) {
        const contenedor = document.getElementById("listaAgendaInicio");

        if (!contenedor) {
            return;
        }

        limpiarElementoInicioAdmin(contenedor);

        if (!datos.length) {
            contenedor.appendChild(crearVacioInicioAdmin("📅", "Agenda despejada", "No hay citas pendientes para hoy."));
            return;
        }

        datos.forEach(cita => {
            const item = crearElementoInicioAdmin("article", "agenda-item");
            const hora = crearElementoInicioAdmin("time", "agenda-hora", cita.hora || "—");
            const datosCita = crearElementoInicioAdmin("div", "agenda-datos");
            const cliente = crearElementoInicioAdmin("strong", "", cita.nombre || "Cliente");
            const vehiculo = crearElementoInicioAdmin(
                "span",
                "",
                [cita.vehiculo, cita.patente].filter(Boolean).join(" · ") || "Vehículo sin información"
            );
            const servicio = crearElementoInicioAdmin("small", "", cita.servicio || "Servicio sin especificar");

            datosCita.append(cliente, vehiculo, servicio);

            const boton = crearElementoInicioAdmin("button", "btn-agenda-abrir", "→");
            boton.type = "button";
            boton.title = cita.id_ot ? "Abrir orden de trabajo" : "Abrir cita";
            boton.setAttribute("aria-label", boton.title);
            boton.addEventListener("click", () => abrirCitaInicioAdmin(cita));

            item.append(hora, datosCita, boton);
            contenedor.appendChild(item);
        });
    }

    function mostrarOrdenesInicioAdmin(datos) {
        const cuerpo = document.getElementById("tablaOrdenesInicio");

        if (!cuerpo) {
            return;
        }

        limpiarElementoInicioAdmin(cuerpo);

        if (!datos.length) {
            const fila = document.createElement("tr");
            const celda = document.createElement("td");
            celda.colSpan = 5;
            celda.appendChild(crearVacioInicioAdmin("✅", "Todo al día", "No hay órdenes que requieran atención.", true));
            fila.appendChild(celda);
            cuerpo.appendChild(fila);
            return;
        }

        datos.forEach(orden => {
            const fila = document.createElement("tr");
            const celdaNumero = crearCeldaInicioAdmin(orden.numeroOT || "OT-" + orden.id_ot, "orden-numero");
            const celdaCliente = document.createElement("td");
            celdaCliente.className = "orden-cliente";
            celdaCliente.append(
                crearElementoInicioAdmin("strong", "", orden.cliente || "Cliente"),
                crearElementoInicioAdmin(
                    "small",
                    "",
                    [orden.vehiculo, orden.patente].filter(Boolean).join(" · ") || "Vehículo sin información"
                )
            );

            const celdaResponsable = crearCeldaInicioAdmin(orden.mecanico || "Sin asignar");
            const celdaEstado = document.createElement("td");
            const estado = capitalizarInicioAdmin(orden.estado || "Sin estado");
            celdaEstado.appendChild(
                crearElementoInicioAdmin(
                    "span",
                    "estado-inicio estado-" + claseEstadoInicioAdmin(orden.estado),
                    estado
                )
            );

            const celdaAccion = document.createElement("td");
            const boton = crearElementoInicioAdmin("button", "btn-orden-abrir", "→");
            boton.type = "button";
            boton.title = "Abrir orden de trabajo";
            boton.setAttribute("aria-label", "Abrir " + (orden.numeroOT || "orden"));
            boton.addEventListener("click", () => abrirOrdenInicioAdmin(orden));
            celdaAccion.appendChild(boton);

            fila.append(celdaNumero, celdaCliente, celdaResponsable, celdaEstado, celdaAccion);
            cuerpo.appendChild(fila);
        });
    }

    function mostrarAlertasInicioAdmin(datos) {
        const contenedor = document.getElementById("listaAlertasInicio");

        asignarTextoInicioAdmin("contadorAlertasInicio", datos.length);

        if (!contenedor) {
            return;
        }

        limpiarElementoInicioAdmin(contenedor);

        if (!datos.length) {
            contenedor.appendChild(crearVacioInicioAdmin("✅", "Sin alertas pendientes", "La operación se encuentra al día."));
            return;
        }

        const iconos = {
            cobro: "🧾",
            cancelacion: "⚠️",
            agotado: "📦",
            stock: "📉",
            cita: "📅"
        };

        datos.forEach(alerta => {
            const item = crearElementoInicioAdmin(
                "article",
                "alerta-item alerta-" + (alerta.prioridad || "normal")
            );
            item.tabIndex = 0;
            item.setAttribute("role", "button");

            const icono = crearElementoInicioAdmin("div", "alerta-icono", iconos[alerta.tipo] || "🔔");
            const texto = crearElementoInicioAdmin("div", "alerta-texto");
            texto.append(
                crearElementoInicioAdmin("strong", "", alerta.titulo || "Alerta"),
                crearElementoInicioAdmin("span", "", alerta.detalle || "Requiere revisión.")
            );
            const cantidad = crearElementoInicioAdmin("span", "alerta-cantidad", numeroInicioAdmin(alerta.cantidad));

            const abrir = () => navegarInicioAdmin(alerta.destino);
            item.addEventListener("click", abrir);
            item.addEventListener("keydown", evento => {
                if (evento.key === "Enter" || evento.key === " ") {
                    evento.preventDefault();
                    abrir();
                }
            });

            item.append(icono, texto, cantidad);
            contenedor.appendChild(item);
        });
    }

    function mostrarActividadInicioAdmin(datos) {
        const panel = document.getElementById("panelActividadInicio");
        const contenedor = document.getElementById("listaActividadInicio");

        if (!panel || !contenedor) {
            return;
        }

        panel.classList.toggle("oculto", !datos.length);

        if (!datos.length) {
            limpiarElementoInicioAdmin(contenedor);
            return;
        }

        limpiarElementoInicioAdmin(contenedor);

        datos.forEach(movimiento => {
            const item = crearElementoInicioAdmin("article", "actividad-item " + (movimiento.tipo || ""));
            const icono = crearElementoInicioAdmin(
                "div",
                "actividad-icono",
                movimiento.tipo === "taller" ? "🔧" : "🛒"
            );
            const datosMovimiento = crearElementoInicioAdmin("div", "actividad-datos");
            datosMovimiento.append(
                crearElementoInicioAdmin("strong", "", movimiento.titulo || "Movimiento"),
                crearElementoInicioAdmin("span", "", movimiento.detalle || ""),
                crearElementoInicioAdmin("small", "", monedaInicioAdmin.format(numeroInicioAdmin(movimiento.monto)))
            );

            item.title = formatearFechaHoraInicioAdmin(movimiento.fecha);
            item.append(icono, datosMovimiento);
            contenedor.appendChild(item);
        });
    }

    function abrirCitaInicioAdmin(cita) {
        if (cita.id_ot) {
            abrirOrdenInicioAdmin({ id_ot: cita.id_ot, id_cita: cita.id_cita });
            return;
        }

        navegarInicioAdmin("citas");
    }

    function abrirOrdenInicioAdmin(orden) {

        sessionStorage.setItem("origenOT", "inicio");

        if (typeof window.abrirOT === "function" && orden.id_cita) {
            
            window.location.href = "adminOrdenTrabajo.html?id=" + encodeURIComponent(orden.id_cita);

            return;
        }

        const identificador = orden.id_cita || orden.id_ot;

        if (identificador) {
            window.location.href = "adminOrdenTrabajo.html?id=" + encodeURIComponent(identificador);
        }
    }

    function navegarInicioAdmin(destino) {
        if (!destino) {
            return;
        }

        if (typeof window.cargar === "function") {
            window.cargar(destino);
        }
    }

    function actualizarRelojInicioAdmin() {
        const ahora = new Date();
        const fecha = fechaCompletaInicioAdmin.format(ahora);
        const hora = ahora.toLocaleTimeString("es-CL", {
            hour: "2-digit",
            minute: "2-digit"
        });

        asignarTextoInicioAdmin("fechaInicio", capitalizarInicioAdmin(fecha));
        asignarTextoInicioAdmin("horaInicio", hora);
    }

    function cambiarEstadoBotonInicioAdmin(cargando) {
        const boton = document.getElementById("btnActualizarInicio");

        if (!boton) {
            return;
        }

        boton.disabled = cargando;
        boton.classList.toggle("cargando", cargando);
    }

    function mostrarErrorInicioAdmin(mensaje) {
        const elemento = document.getElementById("mensajeSistemaInicio");

        if (!elemento) {
            return;
        }

        elemento.textContent = mensaje;
        elemento.classList.remove("oculto");
        elemento.classList.add("error");
    }

    function ocultarMensajeInicioAdmin() {
        const elemento = document.getElementById("mensajeSistemaInicio");

        if (!elemento) {
            return;
        }

        elemento.textContent = "";
        elemento.classList.add("oculto");
        elemento.classList.remove("error");
    }

    function cerrarSesionInicioAdmin() {
        if (typeof window.cerrarSesion === "function") {
            window.cerrarSesion();
            return;
        }

        window.location.href = "page-login.html";
    }

    function crearVacioInicioAdmin(icono, titulo, mensaje, compacto = false) {
        const vacio = crearElementoInicioAdmin(
            "div",
            "inicio-vacio" + (compacto ? " compacto" : "")
        );
        vacio.append(
            crearElementoInicioAdmin("span", "inicio-vacio-icono", icono),
            crearElementoInicioAdmin("strong", "", titulo),
            crearElementoInicioAdmin("span", "", mensaje)
        );
        return vacio;
    }

    function crearElementoInicioAdmin(etiqueta, clase = "", texto = null) {
        const elemento = document.createElement(etiqueta);

        if (clase) {
            elemento.className = clase;
        }

        if (texto !== null && typeof texto !== "undefined") {
            elemento.textContent = String(texto);
        }

        return elemento;
    }

    function crearCeldaInicioAdmin(texto, clase = "") {
        return crearElementoInicioAdmin("td", clase, texto);
    }

    function limpiarElementoInicioAdmin(elemento) {
        while (elemento.firstChild) {
            elemento.removeChild(elemento.firstChild);
        }
    }

    function asignarTextoInicioAdmin(id, texto) {
        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.textContent = String(texto ?? "—");
        }
    }

    function numeroInicioAdmin(valor) {
        const numero = Number(valor);
        return Number.isFinite(numero) ? numero : 0;
    }

    function capitalizarInicioAdmin(texto) {
        const valor = String(texto || "").trim();
        return valor ? valor.charAt(0).toUpperCase() + valor.slice(1) : "—";
    }

    function primerNombreInicioAdmin(nombreCompleto) {
        const nombre = String(nombreCompleto || "usuario").trim();
        return nombre.split(/\s+/)[0] || "usuario";
    }

    function claseEstadoInicioAdmin(estado) {
        return String(estado || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim()
            .replace(/\s+/g, "-") || "sin-estado";
    }

    function fechaLocalInicioAdmin(fecha) {
        if (!fecha) {
            return null;
        }

        const valor = String(fecha).replace(" ", "T");
        const objeto = new Date(valor);
        return Number.isNaN(objeto.getTime()) ? null : objeto;
    }

    function nombreDiaInicioAdmin(fecha) {
        const objeto = fechaLocalInicioAdmin(fecha + " 12:00:00");

        if (!objeto) {
            return "—";
        }

        return new Intl.DateTimeFormat("es-CL", { weekday: "short" })
            .format(objeto)
            .replace(".", "");
    }

    function formatearFechaSimpleInicioAdmin(fecha) {
        const objeto = fechaLocalInicioAdmin(fecha + " 12:00:00");

        if (!objeto) {
            return "Fecha no disponible";
        }

        return objeto.toLocaleDateString("es-CL", {
            day: "2-digit",
            month: "long"
        });
    }

    function formatearFechaHoraInicioAdmin(fecha) {
        const objeto = fechaLocalInicioAdmin(fecha);
        return objeto ? fechaCortaInicioAdmin.format(objeto) : "Fecha no disponible";
    }

    /* Funciona cuando el archivo se carga después del fragmento HTML. */
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
            if (document.getElementById("inicioAdmin")) {
                inicializarInicioAdmin();
            }
        }, { once: true });
    } else if (document.getElementById("inicioAdmin")) {
        inicializarInicioAdmin();
    }
})();
