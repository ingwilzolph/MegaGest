"use strict";

/* =====================================================
   ELEMENTOS PRINCIPALES
===================================================== */

const enlaceRecuperarPassword =
    document.getElementById(
        "nuevaContrasena"
    );

const contenedorRecuperacion =
    document.querySelector(
        ".smart-forms"
    );

/* =====================================================
   ESTADO
===================================================== */

const recuperacionAdmin = {
    correo: "",
    token: "",
    segundosReenvio: 0,
    intervaloReenvio: null,
    procesando: false
};

/* =====================================================
   INICIALIZACIÓN
===================================================== */

if (
    enlaceRecuperarPassword &&
    contenedorRecuperacion
) {

    enlaceRecuperarPassword.addEventListener(
        "click",
        iniciarRecuperacionAdmin
    );
}

/* =====================================================
   INICIAR RECUPERACIÓN
===================================================== */

async function iniciarRecuperacionAdmin(evento) {

    evento.preventDefault();

    if (recuperacionAdmin.procesando) {
        return;
    }

    const campoCorreo =
        document.getElementById("correo");

    const correo =
        campoCorreo?.value.trim().toLowerCase() ||
        "";

    if (!validarCorreoAdmin(correo)) {

        mostrarMensajeLogin(
            "Ingrese un correo electrónico válido.",
            false
        );

        campoCorreo?.focus();

        return;
    }

    recuperacionAdmin.correo = correo;

    bloquearLoginAdmin(true);

    try {

        recuperacionAdmin.procesando = true;

        const resultado =
            await realizarPeticionAdmin(
                "./php/enviarCodigo.php",
                {
                    correo
                }
            );

        if (!resultado.ok) {

            mostrarMensajeLogin(
                resultado.mensaje ||
                "No fue posible enviar el código.",
                false
            );

            return;
        }

        mostrarFormularioCodigoAdmin();

    } catch (error) {

        console.error(
            "Error enviando código:",
            error
        );

        mostrarMensajeLogin(
            "No fue posible comunicarse con el servidor.",
            false
        );

    } finally {

        recuperacionAdmin.procesando = false;

        bloquearLoginAdmin(false);
    }
}

/* =====================================================
   FORMULARIO DEL CÓDIGO
===================================================== */

function mostrarFormularioCodigoAdmin() {

    detenerContadorReenvio();

    contenedorRecuperacion.innerHTML = `

        <div
            class="smart-container wrap-2 recuperacionAdmin">

            <div class="frm-row">

                <button
                    type="button"
                    id="btnVolverLoginAdmin"
                    class="volverRecuperacionAdmin"
                    title="Volver al inicio de sesión">

                    <i class="fa fa-arrow-left"></i>

                </button>

                <div class="spacer-b30">

                    <div class="tagline">

                        <span>
                            Verificar identidad
                        </span>

                    </div>

                </div>

                <div class="encabezadoRecuperacionAdmin">

                    <div class="iconoRecuperacionAdmin">

                        <i class="fa fa-envelope"></i>

                    </div>

                    <h3>
                        Revise su correo
                    </h3>

                    <p>
                        Ingrese el código de 6 dígitos
                        enviado a:
                    </p>

                    <strong id="correoRecuperacionAdmin"></strong>

                </div>

                <form
                    id="formCodigoAdmin"
                    autocomplete="off">

                    <div
                        id="camposCodigoAdmin"
                        class="codigoVerificacionAdmin">

                        <input
                            type="text"
                            class="codigoAdmin"
                            inputmode="numeric"
                            maxlength="1"
                            aria-label="Primer dígito"
                            required>

                        <input
                            type="text"
                            class="codigoAdmin"
                            inputmode="numeric"
                            maxlength="1"
                            aria-label="Segundo dígito"
                            required>

                        <input
                            type="text"
                            class="codigoAdmin"
                            inputmode="numeric"
                            maxlength="1"
                            aria-label="Tercer dígito"
                            required>

                        <input
                            type="text"
                            class="codigoAdmin"
                            inputmode="numeric"
                            maxlength="1"
                            aria-label="Cuarto dígito"
                            required>

                        <input
                            type="text"
                            class="codigoAdmin"
                            inputmode="numeric"
                            maxlength="1"
                            aria-label="Quinto dígito"
                            required>

                        <input
                            type="text"
                            class="codigoAdmin"
                            inputmode="numeric"
                            maxlength="1"
                            aria-label="Sexto dígito"
                            required>

                    </div>

                    <div
                        id="mensajeRecuperacionAdmin"
                        class="mensajeRecuperacionAdmin"
                        role="status"
                        aria-live="polite">
                    </div>

                    <button
                        type="submit"
                        id="btnVerificarCodigoAdmin"
                        class="iniciarSesion btnRecuperacionAdmin">

                        <i class="fa fa-shield"></i>

                        Verificar código

                    </button>

                </form>

                <div class="reenviarCodigoAdmin">

                    <span>
                        ¿No recibió el código?
                    </span>

                    <button
                        type="button"
                        id="btnReenviarCodigoAdmin">

                        Reenviar código

                    </button>

                    <small id="contadorReenvioAdmin"></small>

                </div>

                <p class="avisoSeguridadAdmin">

                    <i class="fa fa-clock-o"></i>

                    El código expira en 10 minutos y
                    permite un máximo de 5 intentos.

                </p>

            </div>

        </div>
    `;

    const correoMostrado =
        document.getElementById(
            "correoRecuperacionAdmin"
        );

    if (correoMostrado) {
        correoMostrado.textContent =
            recuperacionAdmin.correo;
    }

    document
        .getElementById("btnVolverLoginAdmin")
        ?.addEventListener(
            "click",
            volverAlLoginAdmin
        );

    document
        .getElementById("formCodigoAdmin")
        ?.addEventListener(
            "submit",
            verificarCodigoAdmin
        );

    document
        .getElementById("btnReenviarCodigoAdmin")
        ?.addEventListener(
            "click",
            reenviarCodigoAdmin
        );

    configurarCamposCodigoAdmin();

    iniciarContadorReenvio(60);

    const primerCampo =
        document.querySelector(
            ".codigoAdmin"
        );

    primerCampo?.focus();
}

/* =====================================================
   CAMPOS DEL CÓDIGO
===================================================== */

function configurarCamposCodigoAdmin() {

    const campos = [
        ...document.querySelectorAll(
            ".codigoAdmin"
        )
    ];

    campos.forEach((campo, indice) => {

        campo.addEventListener(
            "input",
            function () {

                this.value = this.value
                    .replace(/\D/g, "")
                    .slice(0, 1);

                if (
                    this.value &&
                    indice < campos.length - 1
                ) {
                    campos[indice + 1].focus();
                }
            }
        );

        campo.addEventListener(
            "keydown",
            function (evento) {

                if (
                    evento.key === "Backspace" &&
                    this.value === "" &&
                    indice > 0
                ) {
                    campos[indice - 1].focus();
                }

                if (
                    evento.key === "ArrowLeft" &&
                    indice > 0
                ) {
                    campos[indice - 1].focus();
                }

                if (
                    evento.key === "ArrowRight" &&
                    indice < campos.length - 1
                ) {
                    campos[indice + 1].focus();
                }
            }
        );

        campo.addEventListener(
            "paste",
            pegarCodigoAdmin
        );
    });
}

function pegarCodigoAdmin(evento) {

    evento.preventDefault();

    const codigoPegado =
        evento.clipboardData
            .getData("text")
            .replace(/\D/g, "")
            .slice(0, 6);

    if (codigoPegado.length === 0) {
        return;
    }

    const campos =
        document.querySelectorAll(
            ".codigoAdmin"
        );

    campos.forEach((campo, indice) => {

        campo.value =
            codigoPegado[indice] || "";
    });

    const siguienteIndice =
        Math.min(
            codigoPegado.length,
            campos.length
        ) - 1;

    campos[siguienteIndice]?.focus();
}

function obtenerCodigoAdmin() {

    return [
        ...document.querySelectorAll(
            ".codigoAdmin"
        )
    ]
        .map(campo => campo.value)
        .join("");
}

function limpiarCodigoAdmin() {

    const campos =
        document.querySelectorAll(
            ".codigoAdmin"
        );

    campos.forEach(campo => {
        campo.value = "";
        campo.disabled = false;
    });

    campos[0]?.focus();
}

/* =====================================================
   VERIFICAR CÓDIGO
===================================================== */

async function verificarCodigoAdmin(evento) {

    evento.preventDefault();

    if (recuperacionAdmin.procesando) {
        return;
    }

    const codigo =
        obtenerCodigoAdmin();

    if (!/^\d{6}$/.test(codigo)) {

        mostrarMensajeRecuperacionAdmin(
            "Ingrese los 6 dígitos del código.",
            false
        );

        return;
    }

    bloquearFormularioCodigoAdmin(true);

    try {

        recuperacionAdmin.procesando = true;

        limpiarMensajeRecuperacionAdmin();

        const resultado =
            await realizarPeticionAdmin(
                "./php/verificarCodigo.php",
                {
                    correo:
                        recuperacionAdmin.correo,

                    codigo
                }
            );

        if (!resultado.ok) {

            mostrarMensajeRecuperacionAdmin(
                resultado.mensaje ||
                "El código no es válido.",
                false
            );

            limpiarCodigoAdmin();

            return;
        }

        if (
            !resultado.token_recuperacion ||
            !/^[a-f0-9]{64}$/i.test(
                resultado.token_recuperacion
            )
        ) {

            mostrarMensajeRecuperacionAdmin(
                "El servidor no entregó una autorización válida.",
                false
            );

            return;
        }

        /*
         * El token se mantiene solamente en memoria.
         * No se guarda en localStorage.
         */

        recuperacionAdmin.token =
            resultado.token_recuperacion;

        detenerContadorReenvio();

        mostrarFormularioNuevaPasswordAdmin();

    } catch (error) {

        console.error(
            "Error verificando código:",
            error
        );

        mostrarMensajeRecuperacionAdmin(
            "No fue posible verificar el código.",
            false
        );

    } finally {

        recuperacionAdmin.procesando = false;

        bloquearFormularioCodigoAdmin(false);
    }
}

/* =====================================================
   REENVIAR CÓDIGO
===================================================== */

async function reenviarCodigoAdmin() {

    if (
        recuperacionAdmin.procesando ||
        recuperacionAdmin.segundosReenvio > 0
    ) {
        return;
    }

    const boton =
        document.getElementById(
            "btnReenviarCodigoAdmin"
        );

    try {

        recuperacionAdmin.procesando = true;

        if (boton) {
            boton.disabled = true;
            boton.textContent = "Enviando...";
        }

        limpiarMensajeRecuperacionAdmin();

        const resultado =
            await realizarPeticionAdmin(
                "./php/enviarCodigo.php",
                {
                    correo:
                        recuperacionAdmin.correo
                }
            );

        if (!resultado.ok) {

            mostrarMensajeRecuperacionAdmin(
                resultado.mensaje ||
                "No fue posible reenviar el código.",
                false
            );

            return;
        }

        limpiarCodigoAdmin();

        mostrarMensajeRecuperacionAdmin(
            resultado.mensaje ||
            "Si la cuenta existe, se envió un código nuevo.",
            true
        );

        iniciarContadorReenvio(60);

    } catch (error) {

        console.error(
            "Error reenviando código:",
            error
        );

        mostrarMensajeRecuperacionAdmin(
            "No fue posible reenviar el código.",
            false
        );

    } finally {

        recuperacionAdmin.procesando = false;

        actualizarBotonReenvioAdmin();
    }
}

/* =====================================================
   FORMULARIO NUEVA CONTRASEÑA
===================================================== */

function mostrarFormularioNuevaPasswordAdmin() {

    contenedorRecuperacion.innerHTML = `

        <div
            class="smart-container wrap-2 recuperacionAdmin">

            <div class="frm-row">

                <button
                    type="button"
                    id="btnVolverCodigoAdmin"
                    class="volverRecuperacionAdmin"
                    title="Volver">

                    <i class="fa fa-arrow-left"></i>

                </button>

                <div class="spacer-b30">

                    <div class="tagline">

                        <span>
                            Nueva contraseña
                        </span>

                    </div>

                </div>

                <div class="encabezadoRecuperacionAdmin">

                    <div class="iconoRecuperacionAdmin">

                        <i class="fa fa-lock"></i>

                    </div>

                    <h3>
                        Proteja su cuenta
                    </h3>

                    <p>
                        Cree una contraseña nueva
                        para su cuenta administrativa.
                    </p>

                </div>

                <form
                    id="formNuevaPasswordAdmin"
                    autocomplete="off">

                    <div class="section">

                        <label class="field prepend-icon">

                            <input
                                type="password"
                                name="password"
                                id="passwordNuevaAdmin"
                                class="gui-input"
                                minlength="8"
                                maxlength="64"
                                placeholder="Nueva contraseña"
                                autocomplete="new-password"
                                required>

                            <span class="field-icon">
                                <i class="fa fa-lock"></i>
                            </span>

                            <button
                                type="button"
                                class="mostrarPasswordAdmin"
                                data-campo="passwordNuevaAdmin"
                                aria-label="Mostrar contraseña">

                                <i class="fa fa-eye"></i>

                            </button>

                        </label>

                    </div>

                    <div class="section">

                        <label class="field prepend-icon">

                            <input
                                type="password"
                                name="confirmarPassword"
                                id="confirmarPasswordAdmin"
                                class="gui-input"
                                minlength="8"
                                maxlength="64"
                                placeholder="Confirmar contraseña"
                                autocomplete="new-password"
                                required>

                            <span class="field-icon">
                                <i class="fa fa-lock"></i>
                            </span>

                            <button
                                type="button"
                                class="mostrarPasswordAdmin"
                                data-campo="confirmarPasswordAdmin"
                                aria-label="Mostrar contraseña">

                                <i class="fa fa-eye"></i>

                            </button>

                        </label>

                    </div>

                    <div class="requisitosPasswordAdmin">

                        <strong>
                            La contraseña debe contener:
                        </strong>

                        <ul>
                            <li id="reglaLargoAdmin">
                                Entre 8 y 64 caracteres
                            </li>

                            <li id="reglaMayusculaAdmin">
                                Una letra mayúscula
                            </li>

                            <li id="reglaMinusculaAdmin">
                                Una letra minúscula
                            </li>

                            <li id="reglaNumeroAdmin">
                                Un número
                            </li>

                            <li id="reglaEspecialAdmin">
                                Un carácter especial
                            </li>

                            <li id="reglaCoincidenciaAdmin">
                                Las contraseñas deben coincidir
                            </li>
                        </ul>

                    </div>

                    <div
                        id="mensajeRecuperacionAdmin"
                        class="mensajeRecuperacionAdmin"
                        role="status"
                        aria-live="polite">
                    </div>

                    <button
                        type="submit"
                        id="btnCambiarPasswordAdmin"
                        class="iniciarSesion btnRecuperacionAdmin">

                        <i class="fa fa-key"></i>

                        Cambiar contraseña

                    </button>

                </form>

            </div>

        </div>
    `;

    document
        .getElementById("btnVolverCodigoAdmin")
        ?.addEventListener(
            "click",
            function () {

                recuperacionAdmin.token = "";

                mostrarFormularioCodigoAdmin();
            }
        );

    document
        .getElementById(
            "formNuevaPasswordAdmin"
        )
        ?.addEventListener(
            "submit",
            cambiarPasswordAdmin
        );

    document
        .querySelectorAll(
            ".mostrarPasswordAdmin"
        )
        .forEach(boton => {

            boton.addEventListener(
                "click",
                alternarPasswordAdmin
            );
        });

    const password =
        document.getElementById(
            "passwordNuevaAdmin"
        );

    const confirmacion =
        document.getElementById(
            "confirmarPasswordAdmin"
        );

    password?.addEventListener(
        "input",
        revisarReglasPasswordAdmin
    );

    confirmacion?.addEventListener(
        "input",
        revisarReglasPasswordAdmin
    );

    password?.focus();
}

/* =====================================================
   VALIDAR CONTRASEÑA
===================================================== */

function validarPasswordAdmin(
    password,
    confirmacion
) {

    if (
        password.length < 8 ||
        password.length > 64
    ) {
        return {
            ok: false,
            mensaje:
                "La contraseña debe tener entre 8 y 64 caracteres."
        };
    }

    if (!/[A-Z]/.test(password)) {
        return {
            ok: false,
            mensaje:
                "La contraseña debe contener una letra mayúscula."
        };
    }

    if (!/[a-z]/.test(password)) {
        return {
            ok: false,
            mensaje:
                "La contraseña debe contener una letra minúscula."
        };
    }

    if (!/[0-9]/.test(password)) {
        return {
            ok: false,
            mensaje:
                "La contraseña debe contener un número."
        };
    }

    if (!/[^A-Za-z0-9]/.test(password)) {
        return {
            ok: false,
            mensaje:
                "La contraseña debe contener un carácter especial."
        };
    }

    if (password !== confirmacion) {
        return {
            ok: false,
            mensaje:
                "Las contraseñas no coinciden."
        };
    }

    return {
        ok: true,
        mensaje: ""
    };
}

function revisarReglasPasswordAdmin() {

    const password =
        document.getElementById(
            "passwordNuevaAdmin"
        )?.value || "";

    const confirmacion =
        document.getElementById(
            "confirmarPasswordAdmin"
        )?.value || "";

    actualizarReglaPassword(
        "reglaLargoAdmin",
        password.length >= 8 &&
        password.length <= 64
    );

    actualizarReglaPassword(
        "reglaMayusculaAdmin",
        /[A-Z]/.test(password)
    );

    actualizarReglaPassword(
        "reglaMinusculaAdmin",
        /[a-z]/.test(password)
    );

    actualizarReglaPassword(
        "reglaNumeroAdmin",
        /[0-9]/.test(password)
    );

    actualizarReglaPassword(
        "reglaEspecialAdmin",
        /[^A-Za-z0-9]/.test(password)
    );

    actualizarReglaPassword(
        "reglaCoincidenciaAdmin",
        confirmacion !== "" &&
        password === confirmacion
    );
}

function actualizarReglaPassword(
    idElemento,
    cumplida
) {

    const elemento =
        document.getElementById(
            idElemento
        );

    if (!elemento) {
        return;
    }

    elemento.classList.toggle(
        "cumplida",
        cumplida
    );
}

/* =====================================================
   CAMBIAR CONTRASEÑA
===================================================== */

async function cambiarPasswordAdmin(evento) {

    evento.preventDefault();

    if (recuperacionAdmin.procesando) {
        return;
    }

    const password =
        document.getElementById(
            "passwordNuevaAdmin"
        )?.value || "";

    const confirmarPassword =
        document.getElementById(
            "confirmarPasswordAdmin"
        )?.value || "";

    const validacion =
        validarPasswordAdmin(
            password,
            confirmarPassword
        );

    if (!validacion.ok) {

        mostrarMensajeRecuperacionAdmin(
            validacion.mensaje,
            false
        );

        return;
    }

    if (!recuperacionAdmin.token) {

        mostrarMensajeRecuperacionAdmin(
            "La autorización ha expirado. Solicite un código nuevo.",
            false
        );

        return;
    }

    bloquearFormularioPasswordAdmin(true);

    try {

        recuperacionAdmin.procesando = true;

        limpiarMensajeRecuperacionAdmin();

        const resultado =
            await realizarPeticionAdmin(
                "./php/cambiarPassword.php",
                {
                    correo:
                        recuperacionAdmin.correo,

                    token_recuperacion:
                        recuperacionAdmin.token,

                    password,

                    confirmarPassword
                }
            );

        if (!resultado.ok) {

            mostrarMensajeRecuperacionAdmin(
                resultado.mensaje ||
                "No fue posible cambiar la contraseña.",
                false
            );

            return;
        }

        /*
         * Eliminamos el token inmediatamente
         * después de utilizarlo.
         */

        recuperacionAdmin.token = "";

        mostrarResultadoRecuperacionAdmin(
            resultado.mensaje
        );

    } catch (error) {

        console.error(
            "Error cambiando contraseña:",
            error
        );

        mostrarMensajeRecuperacionAdmin(
            "No fue posible comunicarse con el servidor.",
            false
        );

    } finally {

        recuperacionAdmin.procesando = false;

        bloquearFormularioPasswordAdmin(false);
    }
}

/* =====================================================
   RESULTADO CORRECTO
===================================================== */

function mostrarResultadoRecuperacionAdmin(
    mensaje
) {

    contenedorRecuperacion.innerHTML = `

        <div
            class="smart-container wrap-2 recuperacionAdmin">

            <div class="resultadoRecuperacionAdmin">

                <div class="iconoExitoRecuperacionAdmin">

                    <i class="fa fa-check"></i>

                </div>

                <h3>
                    Contraseña actualizada
                </h3>

                <p id="textoResultadoRecuperacionAdmin"></p>

                <button
                    type="button"
                    id="btnIrLoginAdmin"
                    class="iniciarSesion btnRecuperacionAdmin">

                    <i class="fa fa-sign-in"></i>

                    Iniciar sesión

                </button>

            </div>

        </div>
    `;

    const texto =
        document.getElementById(
            "textoResultadoRecuperacionAdmin"
        );

    if (texto) {
        texto.textContent =
            mensaje ||
            "La contraseña fue actualizada correctamente.";
    }

    document
        .getElementById("btnIrLoginAdmin")
        ?.addEventListener(
            "click",
            volverAlLoginAdmin
        );
}

/* =====================================================
   MOSTRAR U OCULTAR CONTRASEÑA
===================================================== */

function alternarPasswordAdmin(evento) {

    const boton =
        evento.currentTarget;

    const idCampo =
        boton.dataset.campo;

    const campo =
        document.getElementById(idCampo);

    if (!campo) {
        return;
    }

    const mostrar =
        campo.type === "password";

    campo.type =
        mostrar ? "text" : "password";

    boton.innerHTML = mostrar
        ? '<i class="fa fa-eye-slash"></i>'
        : '<i class="fa fa-eye"></i>';

    boton.setAttribute(
        "aria-label",
        mostrar
            ? "Ocultar contraseña"
            : "Mostrar contraseña"
    );
}

/* =====================================================
   BLOQUEOS
===================================================== */

function bloquearLoginAdmin(bloquear) {

    const boton =
        document.querySelector(
            ".iniciarSesion"
        );

    const loader =
        document.getElementById("loader");

    if (boton) {
        boton.disabled = bloquear;
    }

    if (loader) {
        loader.style.display =
            bloquear ? "block" : "none";
    }
}

function bloquearFormularioCodigoAdmin(
    bloquear
) {

    document
        .querySelectorAll(".codigoAdmin")
        .forEach(campo => {
            campo.disabled = bloquear;
        });

    const boton =
        document.getElementById(
            "btnVerificarCodigoAdmin"
        );

    if (!boton) {
        return;
    }

    boton.disabled = bloquear;

    boton.innerHTML = bloquear
        ? `
            <i class="fa fa-spinner fa-spin"></i>
            Verificando...
          `
        : `
            <i class="fa fa-shield"></i>
            Verificar código
          `;
}

function bloquearFormularioPasswordAdmin(
    bloquear
) {

    const formulario =
        document.getElementById(
            "formNuevaPasswordAdmin"
        );

    formulario
        ?.querySelectorAll(
            "input, button"
        )
        .forEach(elemento => {
            elemento.disabled = bloquear;
        });

    const boton =
        document.getElementById(
            "btnCambiarPasswordAdmin"
        );

    if (!boton) {
        return;
    }

    boton.innerHTML = bloquear
        ? `
            <i class="fa fa-spinner fa-spin"></i>
            Actualizando...
          `
        : `
            <i class="fa fa-key"></i>
            Cambiar contraseña
          `;
}

/* =====================================================
   CONTADOR DE REENVÍO
===================================================== */

function iniciarContadorReenvio(segundos) {

    detenerContadorReenvio();

    recuperacionAdmin.segundosReenvio =
        segundos;

    actualizarBotonReenvioAdmin();

    recuperacionAdmin.intervaloReenvio =
        window.setInterval(
            function () {

                recuperacionAdmin
                    .segundosReenvio--;

                if (
                    recuperacionAdmin
                        .segundosReenvio <= 0
                ) {
                    detenerContadorReenvio();
                }

                actualizarBotonReenvioAdmin();

            },
            1000
        );
}

function detenerContadorReenvio() {

    if (
        recuperacionAdmin.intervaloReenvio
    ) {
        clearInterval(
            recuperacionAdmin.intervaloReenvio
        );
    }

    recuperacionAdmin.intervaloReenvio =
        null;

    recuperacionAdmin.segundosReenvio =
        0;
}

function actualizarBotonReenvioAdmin() {

    const boton =
        document.getElementById(
            "btnReenviarCodigoAdmin"
        );

    const contador =
        document.getElementById(
            "contadorReenvioAdmin"
        );

    if (!boton || !contador) {
        return;
    }

    const esperando =
        recuperacionAdmin.segundosReenvio > 0;

    boton.disabled =
        esperando ||
        recuperacionAdmin.procesando;

    boton.textContent =
        "Reenviar código";

    contador.textContent = esperando
        ? `Disponible en ${recuperacionAdmin.segundosReenvio}s`
        : "";
}

/* =====================================================
   MENSAJES
===================================================== */

function mostrarMensajeRecuperacionAdmin(
    mensaje,
    correcto
) {

    const elemento =
        document.getElementById(
            "mensajeRecuperacionAdmin"
        );

    if (!elemento) {
        return;
    }

    elemento.className = correcto
        ? "mensajeRecuperacionAdmin correcto"
        : "mensajeRecuperacionAdmin error";

    elemento.textContent = mensaje;
}

function limpiarMensajeRecuperacionAdmin() {

    const elemento =
        document.getElementById(
            "mensajeRecuperacionAdmin"
        );

    if (!elemento) {
        return;
    }

    elemento.className =
        "mensajeRecuperacionAdmin";

    elemento.textContent = "";
}

function mostrarMensajeLogin(
    mensaje,
    correcto
) {

    const elemento =
        document.querySelector(
            ".respuesta"
        );

    if (!elemento) {

        alert(mensaje);

        return;
    }

    elemento.className = correcto
        ? "respuesta correcto"
        : "respuesta error";

    elemento.textContent = mensaje;
}

/* =====================================================
   PETICIONES
===================================================== */

async function realizarPeticionAdmin(
    url,
    datos
) {

    const respuesta = await fetch(
        url,
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify(datos),

            cache: "no-store"
        }
    );

    const texto =
        await respuesta.text();

    let resultado;

    try {

        resultado = JSON.parse(texto);

    } catch (error) {

        console.error(
            "Respuesta inválida del servidor:",
            texto
        );

        throw new Error(
            "El servidor devolvió una respuesta inválida."
        );
    }

    if (
        !respuesta.ok &&
        typeof resultado.ok === "undefined"
    ) {
        resultado.ok = false;
    }

    return resultado;
}

/* =====================================================
   AUXILIARES
===================================================== */

function validarCorreoAdmin(correo) {

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        .test(correo);
}

function volverAlLoginAdmin() {

    detenerContadorReenvio();

    recuperacionAdmin.correo = "";
    recuperacionAdmin.token = "";
    recuperacionAdmin.procesando = false;

    window.location.href =
        "page-login.html";
}