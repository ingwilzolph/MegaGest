<?php 

	/*if (!isset($_SESSION)) session_start(); 
	if(!$_POST) exit;
	
	include dirname(__FILE__).'/settings/settings.php';
	include dirname(__FILE__).'/functions/emailValidation.php';
	
	
	/* Current Date Year
	------------------------------- */		
	//$currYear = date("Y");		
	
/*	---------------------------------------------------------------------------
	: Register all form field variables here
	--------------------------------------------------------------------------- */
	/*$sendername = strip_tags(trim($_POST["sendername"]));	
	$emailaddress = strip_tags(trim($_POST["emailaddress"]));
	$sendersubject = strip_tags(trim($_POST["sendersubject"]));
	$sendermessage = strip_tags(trim($_POST["sendermessage"]));
    $captcha = strtoupper(strip_tags(trim($_POST["captcha"])));
	
/*	----------------------------------------------------------------------
	: Prepare form field variables for CSV export
	----------------------------------------------------------------------- */	
	/*if($generateCSV == true){
		$csvFile = $csvFileName;	
		$csvData = array(
			"$sendername",
			"$emailaddress",
			"$sendersubject"
		);
	}

/*	-------------------------------------------------------------------------
	: Prepare serverside validation 
	------------------------------------------------------------------------- */ 
	/*$errors = array();
	 //validate name
	if(isset($_POST["sendername"])){
	 
			if (!$sendername) {
				$errors[] = "Debe introducir un nombre.";
			} elseif(strlen($sendername) < 2)  {
				$errors[] = "El nombre debe tener al menos 2 caracteres.";
			}
	 
	}
	//validate email address
	if(isset($_POST["emailaddress"])){
		if (!$emailaddress) {
			$errors[] = "Debe introducir un correo.";
		} else if (!validEmail($emailaddress)) {
			$errors[] = "Debe ingresar un correo electronico válido.";
		}
	}
	
	//validate subject
	if(isset($_POST["sendersubject"])){
			if (!$sendersubject) {
				$errors[] = "Debe ingresar un asunto.";
			} elseif(strlen($sendersubject) < 4)  {
				$errors[] = "el asunto debe tener al menos 4 caracteres.";
			}
	}
	
	//validate message / comment
	if(isset($_POST["sendermessage"])){
		if (strlen($sendermessage) < 10) {
			if (!$sendermessage) {
				$errors[] = "Debe ingresar un mensaje.";
			} else {
				$errors[] = "El mensaje debe tener al menos 10 caracteres.";
			}
		}
	}
	
	// validate security captcha 
	if(isset($_POST["captcha"])){
		if (!$captcha) {
			$errors[] = "Debe introduzcar el codigo captcha";
		} else if ($captcha != $_SESSION['gfm_captcha']) {
			$errors[] = "El codigo captcha es incorrecto";
		}
	}
	
	if ($errors) {
		//Output errors in a list
		$errortext = "";
		foreach ($errors as $error) {
			$errortext .= '<li>'. $error . "</li>";
		}
	
		echo '<div class="alert notification alert-error">The following errors occured:<br><ul>'. $errortext .'</ul></div>';
	
	} else{
	
		include dirname(__FILE__).'/phpmailer/PHPMailerAutoload.php';
		include dirname(__FILE__).'/templates/smartmessage.php';
			
		$mail = new PHPMailer();
		$mail->IsHTML(true);
		$mail->setFrom($emailaddress,$sendername);
		$mail->CharSet = "UTF-8";
		$mail->Encoding = "base64";
		$mail->Timeout = 200;
		$mail->ContentType = "text/html";
		$mail->addAddress($receiver_email, $receiver_name);
		$mail->Subject = $receiver_subject;
		$mail->Body = $message;
		$mail->AltBody = "Use an HTML compatible email client";
				
		// For multiple email recepients from the form 
		// Simply change recepients from false to true
		// Then enter the recipients email addresses
		// echo $message;
		$recipients = false;
		if($recipients == true){
			$recipients = array(
				"address@example.com" => "Recipient Name",
				"soporte@alianzapro.cl" => "Recipient Name"
			);
			
			foreach($recipients as $email => $name){
				$mail->AddBCC($email, $name);
			}	
		}
		
		if($mail->Send()) {
			/*	-----------------------------------------------------------------
				: Generate the CSV file and post values if its true
				----------------------------------------------------------------- */		
				/*if($generateCSV == true){	
					if (file_exists($csvFile)) {
						$csvFileData = fopen($csvFile, 'a');
						fputcsv($csvFileData, $csvData );
					} else {
						$csvFileData = fopen($csvFile, 'a'); 
						$headerRowFields = array(
							"Guest Name",
							"Email Address",
							"Subject"									
						);
						fputcsv($csvFileData,$headerRowFields);
						fputcsv($csvFileData, $csvData );
					}
					fclose($csvFileData);
				}	
				
			/*	---------------------------------------------------------------------
				: Send the auto responder message if its true
				--------------------------------------------------------------------- */
				/*if($autoResponder == true){
				
					include dirname(__FILE__).'/templates/autoresponder.php';
					
					$automail = new PHPMailer();
					$automail->setFrom($receiver_email,$receiver_name);
					$automail->isHTML(true);                                 
					$automail->CharSet = "UTF-8";
					$automail->Encoding = "base64";
					$automail->Timeout = 200;
					$automail->ContentType = "text/html";
					$automail->AddAddress($emailaddress, $sendername);
					$automail->Subject = "Thank you for contacting us";
					$automail->Body = $automessage;
					$automail->AltBody = "Use an HTML compatible email client";
					$automail->Send();	 
				}
				
				if($redirectForm == true){
					echo '<script>setTimeout(function () { window.location.replace("'.$redirectForm_url.'") }, 8000); </script>';
				}
							
			  	echo '<div class="alert notification alert-success">Message has been sent successfully!</div>';
				} 
				else {
				  echo '<div class="alert notification alert-error">Message not sent - server error occured!</div>';	
				}
	}*/

	if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require '../vendor/autoload.php';
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

try {

    /*====================
    VALIDAR CAPTCHA
    ====================*/

    if (!isset($_SESSION['gfm_captcha']) || strtoupper(trim($_POST['captcha'])) !== strtoupper($_SESSION['gfm_captcha'])) {
        echo json_encode(['ok' => false,'mensaje' => 'CAPTCHA incorrecto' ]);
        exit;
    }

    /*====================
    OBTENER DATOS
    ====================*/

    $nombre = trim($_POST['sendername']);
    $correo = trim($_POST['emailaddress']);
	$asunto = trim($_POST['sendersubject']);
    $mensaje = trim($_POST['sendermessage']);


    /*====================
    CORREO A ALIANZAPRO
    ====================*/
    $mailEmpresa = new PHPMailer(true);
    
    try {

        $mailEmpresa->isSMTP();

        $mailEmpresa->Timeout = 30;

        $mailEmpresa->Host = SMTP_HOST;

        $mailEmpresa->SMTPAuth = true;

        $mailEmpresa->Username = SMTP_USER;

        $mailEmpresa->Password = SMTP_PASS;

        $mailEmpresa->SMTPSecure = SMTP_SECURE;

        $mailEmpresa->Port = SMTP_PORT;

        $mailEmpresa->CharSet = 'UTF-8';

        $mailEmpresa->setFrom(
            SMTP_USER,
            'AlianzaPro'
        );

        $mailEmpresa->addAddress(
            SMTP_USER
        );

        $mailEmpresa->addReplyTo(
            $correo,
            $nombre
        );

        $mailEmpresa->isHTML(true);

        $mailEmpresa->Subject = $asunto;

        $mailEmpresa->Body = $mensaje;

        $mailEmpresa->send();

    } catch (MailException $e) {

    throw new \Exception(
        'Correo empresa: ' . $mailEmpresa->ErrorInfo
    );

}

    /*====================
    CORREO CLIENTE + PDF
    ====================*/

     $mailCliente = new PHPMailer(true);

    try {
        $mailCliente->isSMTP();

        $mailCliente->Timeout = 30;

        $mailCliente->Host = SMTP_HOST;

        $mailCliente->SMTPAuth = true;

        $mailCliente->Username = SMTP_USER;

        $mailCliente->Password = SMTP_PASS;

        $mailCliente->SMTPSecure = SMTP_SECURE;

        $mailCliente->Port = SMTP_PORT;

        $mailCliente->CharSet = 'UTF-8';

        $mailCliente->setFrom(
            SMTP_USER,
            'AlianzaPro'
        );

        $mailCliente->addAddress(
            $correo,
            $nombre
        );

        $mailCliente->isHTML(true);

        $mailCliente->Subject = 'Confirmación de mensaje';

        $mailCliente->Body = "

                            <p>Hola <strong>{$nombre}</strong>,</p>

                            <p>Gracias de haber escribirnos!</p>
                            <br>

                            <p>Su sugesíon cuenta mucho para nosotros.</p>
                            ";

        $mailCliente->send();

    } catch (MailException $e) {throw new \Exception('Correo cliente: '.$mailCliente->ErrorInfo);}

    echo json_encode(['ok' => true,'mensaje' => 'Mensaje enviado correctamente']);

} catch (\Exception $e) {echo json_encode(['ok' => false,'mensaje' => $e->getMessage()]);}


?>