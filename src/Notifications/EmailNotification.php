<?php

namespace App\Notifications;

use App\Notifications\Interface\NotificationInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EmailNotification implements NotificationInterface {
    private $to;
    private $subject;
    private $body;
    private $logger;

    public function __construct(string $to, string $subject, string $body)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;

        $this->initLogger();
    }

    private function initLogger()
    {
        $this->logger = new Logger('email_errors');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/email_errors.log', Logger::DEBUG));
    }

    public function send(): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port = $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($this->to);

            $mail->isHTML(true);
            $mail->Subject = $this->subject;
            $mail->Body = $this->body;

            $result = $mail->send();
            
            if (!$result) {
                $this->logger->error('Failed to send email', ['to' => $this->to, 'error' => $mail->ErrorInfo]);
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Exception while sending email', [
                'to' => $this->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
