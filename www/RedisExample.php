<?php

namespace App;

use Predis\Client;

class RedisExample
{
    private $client;

    public function __construct()
    {
        $this->client = new Client('tcp://redis:6379');
    }

    public function setValue($key, $value)
    {
        $this->client->set($key, $value);
    }

    public function getValue($key)
    {
        return $this->client->get($key);
    }

    public function increment($key)
    {
        return $this->client->incr($key);
    }

    public function delete($key)
    {
        return $this->client->del($key);
    }

    public function exists($key)
    {
        return $this->client->exists($key);
    }
}