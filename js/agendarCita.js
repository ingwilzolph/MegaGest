
const fecha = document.getElementById("fecha");

const hora = document.getElementById("hora");

const hoy = new Date();

const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

fecha.min = primerDia.toISOString().split("T")[0];
fecha.max = ultimoDia.toISOString().split("T")[0];

fecha.addEventListener("change", cargarHoras);

async function cargarHoras(){

    hora.innerHTML = "<option value=''>Cargando horarios...</option>";

    try{

        const peticion = await fetch(
            "./php/obtenerHorasDisponibles.php?fecha=" + fecha.value
        );

        const horarios = await peticion.json();

        hora.innerHTML = "";

        if(horarios.length === 0){

            hora.innerHTML =
                "<option value=''>No hay horarios disponibles</option>";

            return;
        }

        hora.innerHTML =
            "<option value=''>Seleccione una hora</option>";

        horarios.forEach(h => {

            const option = document.createElement("option");

            option.value = h;
            option.textContent = h;

            hora.appendChild(option);

        });

    }
    catch(error){

        hora.innerHTML =
            "<option value=''>Error al cargar horarios</option>";

    }

}

async function iniciarCalendario(){

    const respuesta = await fetch("./php/obtenerDiasCompletos.php");

    const diasCompletos = await respuesta.json();

    flatpickr("#fecha",{

        locale:"es",

        dateFormat:"Y-m-d",

        minDate:"today",

        maxDate:new Date(new Date().getFullYear(), new Date().getMonth()+1,0),

        disable:[
            function(date){

                // Domingos

                return date.getDay()===0;

            },

            ...diasCompletos

        ],

        disableMobile:true,

        onChange(){

            cargarHoras();

        }

    });

}

iniciarCalendario();


const formulario = document.getElementById("formCita");

formulario.addEventListener("submit", enviarCita);

const respuesta = document.getElementById("respuestaCita");

async function enviarCita(e){

    e.preventDefault();

    const boton = document.getElementById("btnReservarCita");

    const loader = document.getElementById("loader");

    try{

        boton.style.display = "none";
        respuesta.style.display = "none";
        loader.style.display = "block";

        const datos = new FormData(formulario);
        
        const peticion = await fetch(
            "./php/agendarCita.php",
            {
                method:"POST",
                body:datos
            }
        );

        const resultado = await peticion.json();

        if(resultado.ok){
            
            const p = document.createElement("p");
            p.style.color = "green";
            p.style.fontSize = "12px";
            p.style.textAlign = "center";
            p.textContent = resultado.mensaje+" \u2713";
             
            respuesta.innerHTML = "";
            respuesta.appendChild(p);
        
            formulario.reset();
            recargarCaptcha();

        }
        else{
            const p = document.createElement("p");
            p.style.color = "red";
            p.style.fontSize = "12px";
            p.style.textAlign = "center";
            p.textContent = resultado.mensaje;
             
            respuesta.innerHTML = "";
            respuesta.appendChild(p);

            recargarCaptcha();
        }

    }
    catch(error){
         const p = document.createElement("p");
            p.style.color = "red";
            p.style.fontSize = "12px";
            p.style.textAlign = "center";
            p.textContent = error;
             
            respuesta.innerHTML = "";
            respuesta.appendChild(p);
    }
    finally{

        loader.style.display = "none";
        respuesta.style.display = "block";
        boton.style.display = "block";

    }


}

function recargarCaptcha(){

    const captcha = document.getElementById("captchax");

    captcha.src = "smart-form/contact/php/captcha/captcha.php?"+new Date().getTime();
}

formulario.querySelectorAll("input, select, textarea").forEach(elemento => {

    elemento.addEventListener("focus", () => {

        respuesta.innerHTML = "";

    });

});