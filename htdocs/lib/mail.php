<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if(!defined('_COCOTS_INITIALIZED')) {
  return;
}

require_once(COCOTS_VENDOR_DIR . 'autoload.php');

function getMailer() {
  $mail = new PHPMailer(true);
  $mail->CharSet = 'UTF-8';
  $mail->isSMTP();
  $mail->Host = COCOTS_MAIL_SMTP_HOST;
  $mail->SMTPAuth = COCOTS_MAIL_SMTP_AUTH;
  if (COCOTS_MAIL_SMTP_AUTH) {
    $mail->Username = COCOTS_MAIL_SMTP_AUTH_USER;
    $mail->Password = COCOTS_MAIL_SMTP_AUTH_PASS;
  }
  if (COCOTS_MAIL_SMTP_SECURE) {
    if (COCOTS_MAIL_SMTP_SECURE === 'ssl') {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else if (COCOTS_MAIL_SMTP_SECURE === 'tls') {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
      throw new Exception('Unknown SMTP SECURE constant: ' . COCOTS_MAIL_SMTP_SECURE);
    }
  }
  $mail->Port = COCOTS_MAIL_SMTP_PORT;
  if (COCOTS_MAIL_SMTP_DEBUG) {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'error_log';
  }

  $mail->setFrom(COCOTS_MAIL_FROM);
  $mail->isHTML(false);
  return $mail;
}
