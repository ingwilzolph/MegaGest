	/*jQuery(document).ready(function($){

				function reloadCaptcha(){ $("#captchax").attr("src","./smart-form/contact/php/captcha/captcha.php?r=" + Math.random()); }
				$('.captcode').click(function(e){
					e.preventDefault();
					reloadCaptcha();
				});
				
				function swapButton(){
					var txtswap = $(".form-footer button[type='submit']");
					if (txtswap.text() == txtswap.data("btntext-sending")) {
						txtswap.text(txtswap.data("btntext-original"));
					} else {
						txtswap.data("btntext-original", txtswap.text());
						txtswap.text(txtswap.data("btntext-sending"));
					}
				}
			   
				$( "#smart-form" ).validate({
				
						/* @validation states + elements 
						------------------------------------------- */
						/*errorClass: "state-error",
						validClass: "state-success",
						errorElement: "em",
						onkeyup: false,
						onclick: false,
						
						/* @validation rules 
						------------------------------------------ */
						/*rules: {
								sendername: {
										required: true,
										minlength: 2
								},		
								emailaddress: {
										required: true,
										email: true
								},
								sendersubject: {
										required: true,
										minlength: 4
								},								
								sendermessage: {
										required: true,
										minlength: 10
								},
								captcha:{
									required:true,
									remote:'./smart-form/contact/php/captcha/process.php'
								}
						},
						messages:{
								sendername: {
										required: 'Debe ingresar su nombre',
										minlength: 'El nombre debe tener al menos 2 cararacteres'
								},				
								emailaddress: {
										required: 'Debe ingresar el correo',
										email: 'Introduzca un correo valido'
								},
								sendersubject: {
										required: 'El asunto es importante',
										minlength: 'El asunto de debe ser al menos 4 caracteres'
								},														
								sendermessage: {
										required: 'Ups Olvidaste su mensaje',
										minlength: 'El mensaje debe ser al menos 10 caracteres'
								},															
								captcha:{
										required: 'Debe introduzcar el codigo de captcha',
										remote:'El codigo de captcha es incorrecto'
								}
						},

						/* @validation highlighting + error placement  
						---------------------------------------------------- */
						/*highlight: function(element, errorClass, validClass) {
								$(element).closest('.field').addClass(errorClass).removeClass(validClass);
						},
						unhighlight: function(element, errorClass, validClass) {
								$(element).closest('.field').removeClass(errorClass).addClass(validClass);
						},
						errorPlacement: function(error, element) {
						   if (element.is(":radio") || element.is(":checkbox")) {
									element.closest('.option-group').after(error);
						   } else {
									error.insertAfter(element.parent());
						   }
						},
						
						/* @ajax form submition 
						---------------------------------------------------- */						
						/*submitHandler:function(form) {
							$(form).ajaxSubmit({
								    target:'.result',			   
									beforeSubmit:function(){ 
											swapButton();
											$('.form-footer').addClass('progress');
									},
									error:function(){
											swapButton();
											$('.form-footer').removeClass('progress');
									},
									 success:function(){
										 	swapButton();
											$('.form-footer').removeClass('progress');
											$('.alert-success').show().delay(7000).fadeOut();
											$('.field').removeClass("state-error, state-success");
											if( $('.alert-error').length == 0){
												$('#smart-form').resetForm();
												reloadCaptcha();
											}
									 }
							  });
						}
						
				});		
		
	});	*/
	
const formulario = document.getElementById("smart-form");

formulario.addEventListener("submit", enviarReclamo);

const respuesta = document.getElementById("result");

async function enviarReclamo(e){

    e.preventDefault();

    const boton1 = document.getElementById("btnEnviarReclamo");
	const boton2 = document.getElementById("reset");
    const loader = document.getElementById("loader");

    try{
		boton1.style.display = "none";
        boton2.style.display = "none";
        respuesta.style.display = "none";
        loader.style.display = "block";

        const datos = new FormData(formulario);
        
        const peticion = await fetch("./php/smartprocess.php",
            {
                method:"POST",
                body:datos
            }
        );

        const resultado = await peticion.json();

        console.log(resultado);

        if(resultado.ok){

            const p = document.createElement("p");
            p.style.color = "green";
            p.style.fontSize = "12px";
            p.style.textAlign = "center";
            p.textContent = resultado.mensaje+" \u2713";
             
            respuesta.innerHTML = "";
            respuesta.appendChild(p);
            
           const campos = formulario.querySelectorAll("input, textarea");
           campos.forEach(campo => {
           campo.value = "";
            });
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
        boton1.style.display = "inline-block";
		boton2.style.display = "inline-block";

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
    