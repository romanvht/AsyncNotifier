<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Queue\Queue;
use App\Queue\QueueProcessor;
use App\Services\RedisService;
use App\Services\JobService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Dotenv\Dotenv;
use React\EventLoop\Factory;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$logger = new Logger('worker');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/worker.log', Logger::DEBUG));

$redis = new RedisService();
$queue = new Queue($redis);

$logger->info('Worker started');

$loop = Factory::create();
$job = new JobService($logger);

$queueProcessor = new QueueProcessor($queue, $job, $loop, $logger);
$queueProcessor->checkQueue();

$loop->run();
