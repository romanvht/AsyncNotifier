<?php

namespace App\Services;

use Predis\Client;

class RedisService {
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => $_ENV['REDIS_HOST'],
            'port'   => $_ENV['REDIS_PORT'],
            'password' => $_ENV['REDIS_PASSWORD'] ?: null,
        ]);
    }

    public function push($queue, $data)
    {
        $this->client->rpush($queue, json_encode($data));
    }

    public function pop($queue)
    {
        $data = $this->client->lpop($queue);
        return $data ? json_decode($data, true) : null;
    }
}
