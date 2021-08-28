<?php
declare(strict_types=1);

namespace RedisApp\Controllers;

use Predis\Client;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;

class RedisController
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function test(ServerRequestInterface $request): Response
    {
        // $this->client->set('pred:name', 'The Mighty');

        $response = new Response\TextResponse($this->client->get('pred:name'));
        return $response;
    }
}