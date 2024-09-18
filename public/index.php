<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Queue\Queue;
use App\Services\JobService;
use App\Services\RedisService;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$logger = new Logger('api');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/api.log', Logger::DEBUG));

$redis = new RedisService();
$queue = new Queue($redis);
$jobService = new JobService($logger);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['type']) && isset($data['data'])) {
        $notificationClass = $jobService->getNotificationClass($data['type']);

        if ($notificationClass === null) {
            $logger->error('Unknown notification type', ['type' => $data['type']]);
            http_response_code(400);
            echo json_encode(['error' => 'Unknown notification type']);
            return;
        }

        $notification = new $notificationClass($data['data']);

        if (!$notification->validate()) {
            $logger->error('Invalid notification data', ['type' => $data['type'], 'data' => $data['data']]);
            http_response_code(400);
            echo json_encode(['error' => 'Invalid notification data']);
            return;
        }

        $queue->push([
            'type' => $data['type'],
            'data' => $data['data']
        ]);
        
        $logger->info('Job queued', ['type' => $data['type']]);
        echo json_encode(['status' => 'queued']);
    } else {
        $logger->error('Invalid request format', ['data' => $data]);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request format']);
    }
} else {
    $logger->warning('Method not allowed', ['method' => $_SERVER['REQUEST_METHOD']]);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
