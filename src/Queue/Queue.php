<?php

namespace App\Queue;

use App\Services\RedisService;

class Queue
{
    private $redis;
    private $queueName = 'notifications';

    public function __construct(RedisService $redis)
    {
        $this->redis = $redis;
    }

    public function push(array $notification)
    {
        $this->redis->push($this->queueName, $notification);
    }

    public function pop(): ?array
    {
        return $this->redis->pop($this->queueName);
    }
}
