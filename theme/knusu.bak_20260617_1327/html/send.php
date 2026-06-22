<?php
/**
 * This is email script based on PHPMailer library. Please congigure this SMTP details (below) to make the contact form working.
 */


/* ========================================================================== */
/* SMTP Email Settings: Please change this variables                          */
/* Make sure you done Gmail Account (or your server email account details)    */
/* changes as described here: https://mailtrap.io/blog/phpmailer-gmail/       */

$from_email				= "YOUREMAIL@gmail.com";
$from_email_password	= "emailPassword";
$from_email_name		= "Your Name";
$to_email				= "RECEIVER_EMAIL@gmail.com";
$to_email_name			= "Receiver Name";
$email_subject			= 'Contact Form Data';

/* ========================================================================== */


/* ========================================================================== */
/* Form fields you want to receive in email                                   */
/* This is array so just write the name set in the form                       */

// All form fields
$field_list = array(
	'name',
	'email',
	'number',
	'subject',
	'message',
);

// required fields in the form
$required_fields = array(
	'name',
	'email',
	'number',
	'subject',
	'message',
);

// to verify the valid email address
$email_fields = array(
	'email'
);

/* ========================================================================== */


/* Do not modify after this line */


//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/SMTP.php';



function isValidEmail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL) 
		&& preg_match('/@.+\./', $email);
}

function field_name($key) {
	$return = '';
	if( !empty($key) ){
		$return = str_replace('_', ' ', $key);
		$return = str_replace('-', ' ', $return);
		$return = ucwords($return);
	}
	return $return;

}

function save_mail($mail) {
	//You can change 'Sent Mail' to any other folder or tag
	$path = '{imap.gmail.com:993/imap/ssl}[Gmail]/Sent Mail';

	//Tell your server to open an IMAP connection using the same username and password as you used for SMTP
	$imapStream = imap_open($path, $mail->Username, $mail->Password);

	$result = imap_append($imapStream, $path, $mail->getSentMIMEMessage());
	imap_close($imapStream);

	return $result;
}

$email_status = false;
$email_status_message = '';

if( !empty($_POST) ){

	// check if all required fields are not empty
	$error_message = '';

	foreach( $_POST as $key=>$value ){
		if( !empty($key) && in_array( $key, $required_fields ) ){
			$field_name = field_name($key);
			if( empty($value) ){
				$error_message .= '<li>The "'.$field_name.'" Field is required. Please fill it and submit again</li>' ;
			} else if( in_array( $key, $email_fields ) && isValidEmail($value) == false ){
				$error_message .= '<li>The "'.$field_name.'" email is not valid email</li>' ;
			}
		}
	} // foreach



	
	if( !empty($error_message) ){

		// Required fields are empty
		echo '<div class="alert alert-danger" role="alert">Please fill required fields: <br> <ul>' . $error_message . '</ul> <br> </div>';
		die();

	} else {

		// all required files are valid.. continue to send email
		$email_body = '<h2>Contact Form Data</h2><table style="border: 1px solid #b5b5b5; padding: 5px;">';
		foreach( $_POST as $key=>$value ){
			if( in_array( $key, $field_list ) ){
				$field_name = field_name($key);
				$email_body .= '<tr>
					<td style="border: 1px solid #b5b5b5; padding: 5px;"><strong>'.$field_name.'</strong> </td>
					<td style="border: 1px solid #b5b5b5; padding: 5px;">: '.$value.'</td>
				</tr>';
				
			}
		} // foreach
		$email_body .= '</table>';




		/**** Trying to send email ****/

		//Create a new PHPMailer instance
		$mail = new PHPMailer();
		//Tell PHPMailer to use SMTP
		$mail->isSMTP();
		//Enable SMTP debugging
		//SMTP::DEBUG_OFF = off (for production use)
		//SMTP::DEBUG_CLIENT = client messages
		//SMTP::DEBUG_SERVER = client and server messages
		//$mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->SMTPDebug = SMTP::DEBUG_OFF;

		//Set the hostname of the mail server
		$mail->Host = 'smtp.gmail.com';
		//Set the SMTP port number - likely to be 25, 465 or 587
		$mail->Port = 587;
		//We don't need to set this as it's the default value
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "tls";
		$mail->Username   = $from_email;
		$mail->Password   = $from_email_password; // Please see here: https://mailtrap.io/blog/phpmailer-gmail/
		//Set who the message is to be sent from
		$mail->setFrom( $from_email, $from_email_name);
		//Set an alternative reply-to address
		$mail->addReplyTo($from_email, $from_email_name);
		//Set who the message is to be sent to
		$mail->addAddress( $to_email, $to_email_name);
		//Set the subject line
		$mail->Subject = $email_subject;
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML( $email_body );
		//Replace the plain text body with one created manually
		//$mail->AltBody = 'This is a plain-text message body';

		if ( $mail->send() ) {
			echo '<div class="alert alert-success" role="alert">Thank for filling the form. <br> Our team will contact you soon !!! </div>';
			
			// save your message in the 'Sent Mail' folder.
			// save_mail($mail);
			
		} else {
			echo '<div class="alert alert-danger" role="alert">Error.. caannot send email: <br> ' . $mail->ErrorInfo . ' </div>';
			
		}

	}



} else {

	die('<p>Please go to Contact page and fill the contact form.</p>');

}



