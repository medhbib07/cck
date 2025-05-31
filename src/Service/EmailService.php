<?php
namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private const SMTP_HOST = 'smtp.gmail.com';
    private const SMTP_PORT = 587;
    private const SMTP_USER = 'contactservicecck@gmail.com';
    private const SMTP_PASSWORD = 'wgce jksc mokx ehki';
    private const FROM_NAME = 'CCK Support';

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $messageContent,
        string $altBody = ''
    ): bool {
        $mail = new PHPMailer(true);

        try {
            // SMTP config
            $mail->isSMTP();
            $mail->Host = self::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = self::SMTP_USER;
            $mail->Password = self::SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = self::SMTP_PORT;

            $mail->setFrom(self::SMTP_USER, self::FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->buildHtmlTemplate($toName, $subject, $messageContent);
            $mail->AltBody = $altBody ?: strip_tags($messageContent);

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log('Email sending failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

private function buildHtmlTemplate(string $userName, string $subject, string $content): string
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$subject}</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    body {
      font-family: 'Poppins', Arial, sans-serif;
      background-color: #f8f9fa;
      color: #4a4a4a;
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }
    
    .email-container {
      max-width: 600px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .header {
      background-color: #25354A;
      padding: 30px 20px;
      text-align: center;
    }
    
    .logo {
      color: #ffffff;
      font-size: 24px;
      font-weight: 700;
      text-decoration: none;
      display: inline-block;
    }
    
    .content-wrapper {
      padding: 30px;
    }
    
    h1, h2, h3 {
      color: #25354A;
    }
    
    .message-box {
      background-color: #E8ECE9;
      border-left: 4px solid #6CC1D5;
      padding: 15px;
      margin: 20px 0;
      border-radius: 0 4px 4px 0;
    }
    
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    
    .info-table td {
      padding: 10px;
      border-bottom: 1px solid #E8ECE9;
    }
    
    .info-table tr:last-child td {
      border-bottom: none;
    }
    
    .info-label {
      color: #5C79A5;
      font-weight: 600;
      width: 30%;
    }
    
    .footer {
      background-color: #25354A;
      color: white;
      padding: 20px;
      text-align: center;
      font-size: 14px;
    }
    
    .footer a {
      color: #6CC1D5;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <div class="logo">CCK - Plateforme Universitaire</div>
    </div>
    
    <div class="content-wrapper">
      <h2>{$subject}</h2>
      
      <div class="message-box">
        {$content}
      </div>
      
      <p>Ce message a été envoyé via le formulaire de contact de la plateforme CCK.</p>
      
      <p><strong>Ne répondez pas directement à cet email.</strong> Pour répondre à l'étudiant, utilisez l'adresse email fournie dans le message.</p>
    </div>
    
    <div class="footer">
      © 2023 CCK - Ministère de l'Enseignement Supérieur et de la Recherche Scientifique<br>
      <a href="https://cck.tn">cck.tn</a> | <a href="mailto:contact@cck.tn">contact@cck.tn</a>
    </div>
  </div>
</body>
</html>
HTML;
}
}
