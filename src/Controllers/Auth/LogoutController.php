<?php
declare(strict_types=1);

namespace RedisApp\Controllers\Auth;

use Predis\Client;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;

class LogoutController
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(null, ['prefix' => 'pred:']);
    }

    public function logout(ServerRequestInterface $request): Response
    {
        $userData = authCheck();

        $userId = $userData['id'];
        $oldAuth = $this->client->hget("user:$userId", "auth");
        $newAuth = randomVal();

        $this->client->hset("user:$userId", "auth", $newAuth);
        $this->client->hset("auths", $newAuth, $userId);
        $this->client->hdel("auths", $oldAuth);

        // remove cookie
        setcookie("auth", "data", 1);

        return redirect('/');
    }
}