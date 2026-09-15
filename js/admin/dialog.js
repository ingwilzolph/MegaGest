function confirmar(titulo, mensaje) {

    return new Promise((resolve) => {

        const dialog = document.getElementById("modalConfirmacion");
        const tituloDialog = document.getElementById("headDialog");
        const mensajeDialog = document.getElementById("bodyDialog");
        const btnSi = document.getElementById("btnConfirmacion");

        tituloDialog.textContent = titulo;
        mensajeDialog.textContent = mensaje;

        dialog.showModal();

        btnSi.onclick = () => {
            dialog.close();
            resolve(true);
        };

        dialog.onclose = () => {
            resolve(false);
        };

    });

}

function mensajeModal(titulo, mensaje) {

        const dialog = document.getElementById("modalText");
        const tituloDialog = document.getElementById("headDialog1");
        const mensajeDialog = document.getElementById("bodyDialog1");

        tituloDialog.textContent = titulo;
        mensajeDialog.textContent = mensaje;

        dialog.showModal();

}