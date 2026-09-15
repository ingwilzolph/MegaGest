
function acceso(usuario,buton){
    if(usuario !== "administrador"){
      buton.disabled = true;
    }
}

/* =====================================================
   RESPUESTA DE PERMISO DENEGADO
===================================================== */

function manejarPermisoDenegado(response) {

    if (!response) {
        return false;
    }

    if (response.status !== 403) {
        return false;
    }

    alert(
        "Su usuario no tiene permiso para acceder a esta sección."
    );

    return true;
}

window.manejarPermisoDenegado =
    manejarPermisoDenegado;