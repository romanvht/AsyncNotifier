<?php

namespace App\Queue;

use App\Services\JobService;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class QueueProcessor {
    private $queue;
    private $job;
    private $loop;
    private $logger;

    public function __construct($queue, JobService $job, LoopInterface $loop, LoggerInterface $logger)
    {
        $this->queue = $queue;
        $this->job = $job;
        $this->loop = $loop;
        $this->logger = $logger;
    }

    public function checkQueue()
    {
        $job = $this->queue->pop();
        
        if ($job) {
            $this->logger->info('Job found in queue', ['type' => $job['type']]);
            $this->job->process($job)->then(
                function () {
                    $this->logger->debug('Job processed successfully');
                    $this->loop->futureTick([$this, 'checkQueue']);
                },
                function ($error) {
                    $this->logger->error('Error processing job', ['error' => $error]);
                    $this->loop->futureTick([$this, 'checkQueue']);
                }
            );
        } else {
            $this->loop->addTimer(1, [$this, 'checkQueue']);
        }
    }
}
