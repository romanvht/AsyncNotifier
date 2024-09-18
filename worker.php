<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Queue\Queue;
use App\Services\RedisService;
use App\Notifications\EmailNotification;
use App\Notifications\TelegramNotification;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Dotenv\Dotenv;
use React\EventLoop\Factory;
use React\Promise\Promise;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$logger = new Logger('worker');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/worker.log', Logger::DEBUG));

$redis = new RedisService();
$queue = new Queue($redis);

$logger->info('Worker started');

$loop = Factory::create();

$processJob = function ($job) use ($logger) {
    return new Promise(function ($resolve, $reject) use ($job, $logger) {
        $logger->info('Processing job', $job);
        
        switch ($job['type']) {
            case 'email':
                $notification = new EmailNotification(
                    $job['data']['to'],
                    $job['data']['subject'],
                    $job['data']['body']
                );
                break;
            case 'telegram':
                $notification = new TelegramNotification(
                    $job['data']['chat_id'],
                    $job['data']['message']
                );
                break;
            default:
                $logger->error('Unknown job type', $job);
                $reject('Unknown job type');
                return;
        }
        
        try {
            $result = $notification->send();
            
            if ($result) {
                $logger->info('Notification sent successfully', ['type' => $job['type']]);
                $resolve(true);
            } else {
                $logger->error('Failed to send notification', ['type' => $job['type']]);
                $reject('Failed to send notification');
            }
        } catch (\Exception $e) {
            $logger->error('Exception while sending notification', [
                'type' => $job['type'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $reject('Exception while sending notification: ' . $e->getMessage());
        }
    });
};

$checkQueue = null;
$checkQueue = function () use ($queue, $processJob, $loop, $logger, &$checkQueue) {
    $job = $queue->pop();
    
    if ($job) {
        $logger->info('Job found in queue', ['type' => $job['type']]);
        $processJob($job)->then(
            function () use ($loop, $checkQueue, $logger) {
                $logger->debug('Job processed successfully');
                $loop->futureTick($checkQueue);
            },
            function ($error) use ($loop, $checkQueue, $logger) {
                $logger->error('Error processing job', ['error' => $error]);
                $loop->futureTick($checkQueue);
            }
        );
    } else {
        $loop->addTimer(1, $checkQueue);
    }
};

$loop->futureTick($checkQueue);

$loop->run();
