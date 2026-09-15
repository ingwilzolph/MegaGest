"use strict";

(function () {

    let cierreSesionEnProceso = false;
    let comprobacionEnProceso = false;
    let intervaloSesion = null;

    /* =====================================================
       MANEJAR SESIÓN EXPIRADA
    ===================================================== */

    function manejarSesionExpirada(response) {

        if (!response) {
            return false;
        }

        const sesionExpirada =
    response.status === 401 ||
            (
                response.redirected &&
                response.url.includes(
                    "page-login"
                )
            );

        if (!sesionExpirada) {
            return false;
        }

        cerrarSesionAdministrativa();

        return true;
    }

    /* =====================================================
       CERRAR SESIÓN
    ===================================================== */

    function cerrarSesionAdministrativa() {

        if (cierreSesionEnProceso) {
            return;
        }

        cierreSesionEnProceso = true;

        if (intervaloSesion) {

            clearInterval(
                intervaloSesion
            );

            intervaloSesion = null;
        }

        localStorage.removeItem("usuario");
        localStorage.removeItem("rol");
        localStorage.removeItem("id_usuario");

        alert(
            "Su sesión ha expirado. Inicie sesión nuevamente."
        );

        window.location.replace(
            "./page-login.html"
        );
    }

    /* =====================================================
       COMPROBAR SESIÓN
    ===================================================== */

    async function comprobarSesionAdministrativa() {

        if (
            cierreSesionEnProceso ||
            comprobacionEnProceso
        ) {
            return;
        }

        comprobacionEnProceso = true;

        try {

            const response = await fetch(
                "./php/verificarSesionAjax.php",
                {
                    method: "GET",
                    credentials: "same-origin",
                    cache: "no-store"
                }
            );

            manejarSesionExpirada(response);

        } catch (error) {

            console.warn(
                "No fue posible comprobar temporalmente la sesión."
            );

        } finally {

            comprobacionEnProceso = false;
        }
    }

    /* =====================================================
       INICIAR
    ===================================================== */

    function iniciarControlSesion() {

        comprobarSesionAdministrativa();

        intervaloSesion = setInterval(
            comprobarSesionAdministrativa,
            60000
        );
    }

    /* =====================================================
    MANEJAR PERMISO DENEGADO
    ===================================================== */

    function manejarPermisoDenegado(response) {

        if (!response) {
            return false;
        }

        if (response.status !== 403) {
            return false;
        }

        alert(
            "Su usuario no tiene permiso para realizar esta operación."
        );

        return true;
    }

    /*
     * Disponible para todos los módulos.
     */

    window.manejarSesionExpirada =
        manejarSesionExpirada;

    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            iniciarControlSesion,
            {
                once: true
            }
        );

    } else {

        iniciarControlSesion();
    }

})();