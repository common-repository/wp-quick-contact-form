<?php
/*
Plugin Name: WP Quick Contact Form
Plugin URI: http://www.icograma.com/wp-quick-contact-form/
Description: Allows you to place a simple contact form. Edit a page or post content in HTML mode, placing the shortcode [quickform mailto="mymail@example.com"] where you want the form to appear.
Version: 1.01
Author: Icograma
Author URI: http://www.icograma.com/
*/


/* Email address where you want to receive the messages */
$mailto = "mail@example.com";


//load scripts
//method from http://www.prelovac.com/vladimir/best-practice-for-adding-javascript-code-to-wordpress-plugin

add_action('wp_print_scripts', 'WPQuickContactForm_ScriptsAction');

function WPQuickContactForm_ScriptsAction()
{
 $WPQuickContactForm_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) );
 
 if (!is_admin())
	{
	  wp_enqueue_script('jquery');
	  wp_enqueue_script('wp_QuickContactForm_script', $WPQuickContactForm_plugin_url.'/scripts/jquery.form-validation-and-hints.js', array('jquery'),'0.202');
	}

}


//load styles
add_action('wp_print_styles', 'add_my_stylesheet');

function add_my_stylesheet() {
		$myStyleUrl = WP_PLUGIN_URL . '/wp-quick-contact-form/css/form-validation-and-hints.css';
		$myStyleFile = WP_PLUGIN_DIR . '/wp-quick-contact-form/css/form-validation-and-hints.css';
		if ( file_exists($myStyleFile) ) {
				wp_register_style('myStyleSheets', $myStyleUrl);
				wp_enqueue_style( 'myStyleSheets');
		}
}




$lang = "en";

//contact form localized strings
$loc = array(
"en" => array (
	"title" => "contact us",
	"form-h1" => "Send us your message",
	"form-from" => "From:",
	"form-to" => "To:",
	"form-from-example" => "mail@example.com",
	"form-from-error" => "Please type in a valid e-mail address.",
	"form-sendcc" => "Send to this address a copy of the message",
	"form-message" => "Message:",
	"form-message-error" => "Please type in the message to be sent.",
	"form-submit" => "Send Message &raquo;",
	"thanks-h1" => "Thank you for contacting us",
	"thanks-h2" => "Your message has been sent.",
	"thanks-atext" => 'Send another message',
	),
"es" => array (
	"title" => "contacto",
	"form-h1" => "Enviar un mensaje",
	"form-from" => "De:",
	"form-to" => "Para:",
	"form-from-example" => "mail@example.com",
	"form-from-error" => "Por favor, ingrese una direcci&oacute;n de mail v&aacute;lida.",
	"form-sendcc" => "Enviar a esta direcci&oacute;n copia del mensaje",
	"form-message" => "Mensaje:",
	"form-message-error" => "Por favor, escriba un mensaje a enviar.",
	"form-submit" => "Enviar Mensaje &raquo;",
	"thanks-h1" => "&iexcl;Gracias!",
	"thanks-h2" => "El mensaje ha sido enviado.",
	"thanks-atext" => 'Enviar otro mensaje',
	)
);

function echoPHPvalidationError($field){
	global $errors,$lang;
	if( isset($errors[$lang][$field]) ){
		echo('<p class="error">' . $errors[$lang][$field] . '</p>');
	};
};


function icg_term_description( $term = 0, $taxonomy = 'post_tag' ) {
	if ( !$term && ( is_tax() || is_tag() || is_category() ) ) {
		global $wp_query;
		$term = $wp_query->get_queried_object();
		$taxonomy = $term->taxonomy;
		$term = $term->term_id;
	}
	return get_term_field( 'description', $term, $taxonomy );
}









function echoContactForm( $atts ){

global $lang, $loc, $mailto; 

$title = $loc[$lang]['title'];
//$to = get_option('home');
$to = $mailto;

	extract(shortcode_atts(array(  
		"mailto" => $to
//		"to" => get_option('home'),
	), $atts));

echo('<div id="formContacto">');

$mode = "form";
$errors = array( "en" => array(), "es" => array() );

if ( $mailto == 'mail@example.com' ) {
	$mailto = '';
	}

if ( isset($_POST['sendmsg']) && isset($_POST['email']) ) {
	//tal vez estemos en modo email.. 
	$mode = "email";
	$email = $_POST['email'] ;


	//vamos a verificar cabeceras, etc.

	//from http://www.markussipila.info/pub/emailvalidator.php:
	// define a regular expression for "normal" addresses
	$email_regexp_normal = "^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})$";
	// define a regular expression for "strange looking" but syntactically valid addresses
	$email_regexp_validButRare = "^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$";

	if (eregi('@example.com', $email)) {
		$mode = "form";
		$errors['en']['email'] = $loc['en']['form-from-error'];
		$errors['es']['email'] = $loc['es']['form-from-error'];
	}else if (eregi($email_regexp_normal, $email)){
		//echo("The address $email is valid and looks normal.");
	}else if( eregi($email_regexp_validButRare, $email) && $email != $_POST['sendmsg'] ) {
		$mode = "form";
		$errors['en']['email'] = 'This address looks a bit strange. If you think it is correct, press &quot;Send&quot; again.';
		$errors['es']['email'] = 'Esta direcci&oacute;n parece un poco extra&ntilde;a. Si es correcta, haga clic nuevamente en &quot;Enviar&quot;.';
		//echo("The address $email looks a bit strange but it is syntactically valid. You might want to check it for typos.");
	}else{
		$mode = "form";
		$errors['en']['email'] = 'This address is not valid.';
		$errors['es']['email'] = 'Esta direcci&oacute;n no es v&aacute;lida.';
	  //echo("The address $email is not valid.");
	}

	//prevent mail headers injection // from http://www.securephpwiki.com/index.php/Email_Injection :
	$email = urldecode($email);
	if(eregi("\r",$email) || eregi("\n",$email)){
		//die("Activity has been logged.");
		$mode = "form";
		$errors['en']['email'] = 'This address is not valid, and <em>looks like a header injection attempt</em>.<br />Activity has been logged.';
		$errors['es']['email'] = 'Esta direcci&oacute;n no es v&aacute;lida, y <em>parece un intento de inyeccion de cabeceras</em>.<br />La actividad ha sido registrado.';
	}
	
	
	if( !isset($_POST['mensaje']) || $_POST['mensaje']=="" ){
		$mode = "form";
		$errors['en']['mensaje'] = $loc['en']['form-message-error'];
		$errors['es']['mensaje'] = $loc['es']['form-message-error'];
	}
}



if($mode=="form"){ //show form

echo('<form method="post" action="" onsubmit="return checkForm(this);"><input type="hidden" name="sendmsg" value="');

if (isset($_POST['email'])){
	echo( $_POST['email'] );
	}else{
	echo('@');
	};

echo('" />');

echo('<table border="0" cellpadding="0" cellspacing="0"> ');

echo('<tfoot><tr><th></th><td><p><input type="submit" name="ok" value="'. ($loc[$lang]['form-submit'] ). '" /></p></td></tr></tfoot>');


echo('<tbody><tr class="required"><th><p><label for="email">'. ($loc[$lang]['form-from'] ).'</label></p></th><td>');
echoPHPvalidationError('email');

echo('<p><input class="text verifyMail" type="text" name="email" id="email" size="60" value="');

					if( isset($_POST['email']) ){
						echo( $_POST['email'] );
					} elseif (isset($_GET['from'])){
						echo( $_GET['from'] );
					}

echo('" title="');

					if( !isset($_POST['email']) && !isset($_GET['from']) ) {
						echo("*" . $loc[$lang]['form-from-example'] );
					}

echo('" /></p><p class="iferror">');
echo($loc[$lang]['form-from-error'] );
echo('</p></td></tr><tr><td></td><td><p><input type="checkbox" name="cc" id="cc" value="1" checked="checked" /> <label for="cc">');

echo($loc[$lang]['form-sendcc'] ); 
echo('</label></p></td></tr><tr class="required"><th><p><label for="mensaje" class="lh">');

echo($loc[$lang]['form-message'] ); 

echo('</label></p></th><td>');
echoPHPvalidationError('mensaje');

echo('<p><textarea id="mensaje" name="mensaje" rows="16" cols="60">');

					if( isset($_POST['mensaje']) ){
						echo( $_POST['mensaje'] );
					}

echo('</textarea></p><p class="iferror">');

echo($loc[$lang]['form-message-error'] ); 
echo('</p></td></tr></tbody>');


echo('</table></form><div style="clear:both;"></div>');

}else{ //process & send mail

$email = $_POST['email'] ;
//$email = $_POST['email'] ; //ya esta en el IF del mainbody

$docc = $_POST['cc'] ;
$mensaje = $_POST['mensaje'] ;
$http_referrer = getenv( "HTTP_REFERER" );


if($lang == "es"){
$subject = "Mensaje a ". $to;
}else{
$subject = "Feedback to ". $to;
}


if (get_magic_quotes_gpc()) {
	$mensaje = stripslashes( $mensaje );
}

if($lang == "en"){
$messageproper =
	"Message generated from ".$http_referrer."\n\n".
	"From: ".$email."\n".
	"------------------------- MESSAGE -------------------------\n\n" .
	$mensaje .
	"\n\n------------------------------------------------------------\n" ;
}else{
$messageproper =
	"Mensaje enviado desde ".$http_referrer."\n\n".
	"De: ".$email."\n".
	"------------------------- MENSAJE -------------------------\n\n" .
	$mensaje .
	"\n\n------------------------------------------------------------\n" ;
	}

// SEND MESSAGE

echo("<!-- From: <$email>\nReply-To: <$email> -->");


$headers = 'From: '. $email . "\r\n" .
   'Reply-To: '. $email. "\r\n" .
   'X-Mailer: PHP/' . phpversion();

ini_set ("sendmail_from", $email ); //Not ideal but it works, avoid error: 'Warning: mail() [function.mail]: "sendmail_from" not set in php.ini or custom "From:" header missing'

echo("<!-- ");

$mailsentOK = mail($to, $subject, $messageproper, $headers);//\nReply-To: $email"); 
if( ( $docc==1||$docc=="1" ) && $mailsentOK ){
	$mailsentOK = mail($email, $subject, $messageproper, $headers);//\nReply-To: $email"); 
}

echo(" -->");

if( $mailsentOK ){
echo('<h2>');
echo( $loc[$lang]['thanks-h1'] );
echo('</h2><p>');
// echo( $loc[$lang]['thanks-h2'] . ' <em><a href="./?from='.$email.'">&laquo; ' . $loc[$lang]['thanks-atext'] . '</a></em>' );
echo( $loc[$lang]['thanks-h2'] );
echo('</p><div class="showmsg"><table border="0" cellpadding="0" cellspacing="0" width="100%"><thead><tr><th><p>');
echo($loc[$lang]['form-from'] ); 
echo('</p></th><td width="100%"><p>');
echo($email); 
echo('</p></td></tr><tr><th><p>');
echo($loc[$lang]['form-to'] ); 
echo('</p></th><td><p>'.$to.'</p></td></tr></thead><tbody>');
echo('<tr class="required"><th><p>');
echo($loc[$lang]['form-message'] );
echo('</p></th><td><pre>');
echo( nl2br($mensaje) ); 
echo('</pre></td></tr></tbody></table></div><!-- /showmsg -->');
}else{
	echo('<h2 class="error">Ha ocurrido un error!</h2><p>El mensaje no pudo ser enviado... <a href="./" onclick="history.go(-1);return false;">Volver a intentar</a></p>');
}

}; 
echo('</div><!-- /formContacto -->');
};

//end echoContactForm()

add_shortcode('quickform', 'echoContactForm');

?>