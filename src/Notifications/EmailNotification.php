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

    public function __construct(array $data)
    {
        $this->to = isset($data['to']) ? $data['to'] : null;
        $this->subject = isset($data['subject']) ? $data['subject'] : null;
        $this->body = isset($data['body']) ? $data['body'] : null;

        $this->initLogger();
    }

    private function initLogger()
    {
        $this->logger = new Logger('email_errors');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/email_errors.log', Logger::DEBUG));
    }

    public function validate(): bool
    {
        return filter_var($this->to, FILTER_VALIDATE_EMAIL) !== false && !empty($this->subject) && !empty($this->body);
    }    

    public function send(): bool
    {
        if (!$this->validate()) {
            $this->logger->warning('Attempt to send Email with invalid data');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->Port = $_ENV['MAIL_PORT'];

            if (!empty($_ENV['MAIL_ENCRYPTION'])) {
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            }

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($this->to);

            $mail->isHTML(true);
            $mail->Subject = $this->subject;
            $mail->Body = $this->body;

            $result = $mail->send();
            
            if (!$result) {
                $this->logger->error('Failed to send email', [
                    'to' => $this->to, 
                    'error' => $mail->ErrorInfo
                ]);

                return false;
            }

            return true;
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
