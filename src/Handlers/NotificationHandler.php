<?php

namespace App\Handlers;

use App\Queue\Queue;
use Monolog\Logger;
use React\EventLoop\LoopInterface;

class NotificationHandler {
    private $queue;
    private $logger;
    private $loop;

    public function __construct(Queue $queue, Logger $logger, LoopInterface $loop)
    {
        $this->queue = $queue;
        $this->logger = $logger;
        $this->loop = $loop;
    }

    public function handle()
    {
        $this->loop->addPeriodicTimer(1, function () {
            if (!$this->queue->isEmpty()) {
                $notification = $this->queue->pop();
                $result = $notification->send();

                if ($result) {
                    $this->logger->info('Notification sent successfully');
                } else {
                    $this->logger->error('Failed to send notification');
                }
            }
        });
    }
}
