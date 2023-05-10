<?php

ini_set( 'display_errors', 1 );
error_reporting( E_ALL );

require_once '../vendor/autoload.php';

// require_once __DIR__  '../../vendor/autoload.php';

// Create a new PHPMailer instance
$mail = new PHPMailer\PHPMailer\PHPMailer();

// Set the SMTP server details
$mail->isSMTP();
$mail->Host = 'sandbox.smtp.mailtrap.io';
$mail->SMTPDebug = 2;
$mail->SMTPAuth = true;
$mail->Username = '';
$mail->Password = '';
$mail->SMTPSecure = 'tls';
$mail->Port = 2525;

// Set the email message details
$mail->setFrom('parvezhossain724@gmail.com', 'Lazyengineer');
$mail->addAddress('phossain.sct@gmail.com');
$mail->Subject = 'Test email from PHPMailer';
$mail->Body = 'This is a test email from PHPMailer.';

// Send the email
if ($mail->send()) {
    echo 'Email sent successfully.';
} else {
    echo 'Error sending email: ' . $mail->ErrorInfo;
}
