// =====================================
// FOTOGRAFÍAS OT
// =====================================

const inputFotos = document.getElementById("inputFotos");

if (inputFotos) {
    inputFotos.addEventListener("change", subirFotosOT);
}

async function subirFotosOT() {

    if (this.files.length === 0)
        return;

    const formData = new FormData();

    formData.append("id_ot", idOT);

    for (const foto of this.files) {
        formData.append("fotos[]", foto);
    }

    try {

        const response = await fetch("./php/subirFotosOT.php", {
            method: "POST",
            body: formData
        });

        const datos = await response.json();

        if (datos.ok) {

            cargarFotosOT();

        } else {

            alert(datos.mensaje);

        }

    } catch (error) {

        alert(error);

    }

    this.value = "";

}

async function cargarFotosOT(){

    try {

        const response = await fetch(
            "./php/listarFotosOT.php?id_ot=" + idOT
        );

        const fotos = await response.json();

        const galeria = document.getElementById("galeriaFotos");

        galeria.innerHTML = "";


        fotos.forEach(foto => {

            const card = document.createElement("div");
            card.className = "fotoOT";


            const img = document.createElement("img");

            img.src = foto.nombreArchivo;

            img.onclick = ()=>{

                abrirFoto(foto.nombreArchivo);

            };

            img.loading = "lazy";


            card.appendChild(img);


            const btnEliminar = document.createElement("button");

            btnEliminar.className = "btnEliminarFoto";

            btnEliminar.innerHTML =
                '<i class="fa-solid fa-trash"></i>';


            btnEliminar.onclick = () => {

                eliminarFotoOT(foto.id_foto);

            };


            card.appendChild(btnEliminar);

            galeria.appendChild(card);

        });


    } catch(error){

        alert("Error cargando fotos:", error);

    }

}

async function eliminarFotoOT(idFoto){


    if(!confirm("¿Eliminar esta fotografía?")){
        return;
    }


    const formData = new FormData();

    formData.append(
        "id_foto",
        idFoto
    );


    try{


        const response = await fetch(
            "./php/eliminarFotoOT.php",
            {
                method:"POST",
                body:formData
            }
        );


        const resultado = await response.json();


        if(resultado.ok){

            cargarFotosOT();

        }else{

            alert(resultado.mensaje);

        }


    }catch(error){

        alert(error);

    }

}

function abrirFoto(ruta){

    const visor =
        document.getElementById("visorFoto");

    const imagen =
        document.getElementById("imagenGrande");


    imagen.src = ruta;

    visor.style.display = "flex";

}



document.getElementById("cerrarFoto")
.addEventListener("click",()=>{

    document.getElementById("visorFoto")
    .style.display="none";

});