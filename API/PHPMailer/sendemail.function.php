<?php
require_once( 'PHPMailerAutoload.php' );

function SendEmail( $from_address, $to_name, $to_address, $cc_recipients, $bcc_recipients, $replyto_name, $replyto_address, $subject, $html_body, $attachment_count = 0, $aryAttachments = null ){
	//dbpc(" -> Sending email to [" . $to_name . "]");
	
	$SMTPInfo = array();
	$SMTPInfo['contact@willsabol.com']['pw'] = "zE9!rEK&sw~&";
	$SMTPInfo['contact@willsabol.com']['name'] = "HuntRecipes Contact";
	
	//Create a new PHPMailer instance
	$mail = new PHPMailer;
	//Tell PHPMailer to use SMTP
	if ( strlen($SMTPInfo[$from_address]['pw']) > 0 ) {
		$mail->isSMTP( );
	} else {
		$mail->isSendMail( );
		$mail->Encoding = 'base64';
	}
	//Enable SMTP debugging
	// 0 = off (for production use)
	// 1 = client messages
	// 2 = client and server messages
	$mail->SMTPDebug = 0;
	//Ask for HTML-friendly debug output
	$mail->Debugoutput = 'html';
	//Set the hostname of the mail server
	$mail->Host = "secureus4.sgcpanel.com";
	//Set the SMTP port number - likely to be 25, 465 or 587
	$mail->Port = 465;
	//Whether to use SMTP authentication
	$mail->SMTPAuth = ( strlen($SMTPInfo[$from_address]['pw']) > 0 );
	$mail->SMTPSecure = 'ssl';
	//Username to use for SMTP authentication
	$mail->Username = $from_address;
	//Password to use for SMTP authentication
	$mail->Password = @$SMTPInfo[$from_address]['pw'];
	//Set reply-to address
	if ( $replyto_address != "" ) {
		$mail->addReplyTo($replyto_address, $replyto_name);
	}
	//Set who the message is to be sent to
	$mail->addAddress($to_address, $to_name);
	//loop cc's
	for ( $cci = 0; $cci < count($cc_recipients); $cci++ ) {
		if ( isset($cc_recipients[$cci]['address']) && isset($cc_recipients[$cci]['name']) ) {
			$mail->addCC($cc_recipients[$cci]['address'], $cc_recipients[$cci]['name']);
		} elseif ( isset($cc_recipients[$cci]['address']) ) {
			$mail->addCC($cc_recipients[$cci]['address'], '');
		}
	}
	//loopb cc's
	for ( $bcci = 0; $bcci < count($bcc_recipients); $bcci++ ) {
		if ( isset($bcc_recipients[$bcci]['address']) && isset($bcc_recipients[$bcci]['name']) ) {
			$mail->addBCC($bcc_recipients[$bcci]['address'], $bcc_recipients[$bcci]['name']);
		} elseif ( isset($bcc_recipients[$bcci]['address']) ) {
			$mail->addBCC($bcc_recipients[$bcci]['address'], '');
		}
	}
	//Set the subject line
	$mail->Subject = $subject;
	// html body
	$mail->IsHTML(true); 
	$mail->msgHTML($html_body);
	//Set who the message is to be sent from
	$mail->setFrom($from_address, $SMTPInfo[$from_address]['name']);
	
	
	//Attach an image file
	if( $attachment_count > 0 ) {
		$attachment_counter = 0;
		while( $attachment_counter < $attachment_count ) {
			$mail->addAttachment( $aryAttachments[$attachment_counter]);
			$attachment_counter++;
		}
	}
	
	//send the message, check for errors
	if (!$mail->send()) {
		echo "\nMailer Error: " . $mail->ErrorInfo;
		return false;
	} else {
		//echo "\nMessage sent!";
		return true;
	}
	

}


?>
