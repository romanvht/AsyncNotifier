<?php

namespace App\Services;

use React\Promise\Promise;
use Psr\Log\LoggerInterface;

class JobService {
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process($job): Promise
    {
        return new Promise(function ($resolve, $reject) use ($job) {
            $this->logger->info('Processing job', $job);
            
            switch ($job['type']) {
                case 'email':
                    $notification = new \App\Notifications\EmailNotification(
                        $job['data']['to'],
                        $job['data']['subject'],
                        $job['data']['body']
                    );
                    break;
                case 'telegram':
                    $notification = new \App\Notifications\TelegramNotification(
                        $job['data']['chat_id'],
                        $job['data']['message']
                    );
                    break;
                default:
                    $this->logger->error('Unknown job type', $job);
                    $reject('Unknown job type');
                    return;
            }
            
            try {
                $result = $notification->send();
                
                if ($result) {
                    $this->logger->info('Notification sent successfully', ['type' => $job['type']]);
                    $resolve(true);
                } else {
                    $this->logger->error('Failed to send notification', ['type' => $job['type']]);
                    $reject('Failed to send notification');
                }
            } catch (\Exception $e) {
                $this->logger->error('Exception while sending notification', [
                    'type' => $job['type'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $reject('Exception while sending notification: ' . $e->getMessage());
            }
        });
    }
}
