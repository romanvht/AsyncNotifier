<?php

namespace App\Notifications;

use App\Notifications\Interface\NotificationInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class TelegramNotification implements NotificationInterface {
    private $chatId;
    private $message;
    private $logger;

    public function __construct(string $chatId, string $message)
    {
        $this->chatId = $chatId;
        $this->message = $message;

        $this->initLogger();
    }

    private function initLogger()
    {
        $this->logger = new Logger('telegram_errors');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/telegram_errors.log', Logger::DEBUG));
    }

    public function send(): bool
    {
        $client = new Client();

        try {
            $response = $client->post('https://api.telegram.org/bot' . $_ENV['TELEGRAM_BOT_TOKEN'] . '/sendMessage', [
                'form_params' => [
                    'chat_id' => $this->chatId,
                    'text' => $this->message,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode === 200 && isset($body['ok']) && $body['ok'] === true) {
                return true;
            } else {
                $this->logger->error('Failed to send Telegram message', [
                    'chat_id' => $this->chatId,
                    'status_code' => $statusCode,
                    'response' => $body
                ]);
                return false;
            }
        } catch (GuzzleException $e) {
            $this->logger->error('Exception while sending Telegram message', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}