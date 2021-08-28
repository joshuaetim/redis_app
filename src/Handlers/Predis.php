<?php
declare(strict_types=1);

namespace RedisApp\Handlers;

use Predis\Client;
use Laminas\Diactoros\Response;

class Predis extends Client
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(null, ['prefix' => 'pred:']);
    }

    
}