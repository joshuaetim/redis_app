<?php
declare(strict_types=1);

namespace RedisApp\Controllers;

use Predis\Client;
use Laminas\Diactoros\Response;
use RedisApp\Handlers\PostHandler;
use RedisApp\Handlers\ViewHandler;
use RedisApp\Handlers\RequestHandler;
use Psr\Http\Message\ServerRequestInterface;

class PageController 
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(null, ['prefix' => 'pred:']);
    }
    
    public function home(ServerRequestInterface $request) : Response
    {
        $data = ['title' => 'Home'];

        if($check = authCheck()){
            $data['auth'] = $check;
        }

        return view('home.html', $data);
    }

    public function displayProfile(ServerRequestInterface $request): Response
    {
        $auth = authCheck();

        $username = RequestHandler::getLastParam($request);

        if($auth['username'] == $username) return redirect('/profile');

        $id = $this->client->hget("users", $username);

        $userDetails = $this->client->hgetall("user:$id");

        $user = [
            'fullname' => $userDetails['fullname'],
            'email' => $userDetails['email'],
            'username' => $userDetails['username'],
            'id' => $id,
            'bio' => $userDetails['bio'],
        ];

        $posts = PostHandler::getUserPosts($username);

        // return jsonResponse($user);

        return view('user/profile.html', ['title' => $username.' - Profile', 'user' => $user, 'posts' => $posts, 'auth' => $auth]);
    }
}