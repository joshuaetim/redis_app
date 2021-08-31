<?php
declare(strict_types=1);

namespace RedisApp\Controllers\Auth;

use Predis\Client;
use Laminas\Diactoros\Response;
use RedisApp\Handlers\ViewHandler;
use Psr\Http\Message\ServerRequestInterface;

class LoginController
{
    protected $client;

    public function __construct()
    {
        $this->client = redis();
    }

    public function create(ServerRequestInterface $request): Response
    {
        if(authCheck()) return redirect("/home");

        return view('auth/login.html', ['title' => 'Login to Account']);
    }

    public function store(ServerRequestInterface $request): Response
    {
        $requestBody = $request->getParsedBody();

        $username = $requestBody['username'];
        // $email = $requestBody['email'];
        $password = $requestBody['password'];
        // $password_confirm = $requestBody['password_confirm'];

        $_SESSION['flash']['input'] = ['username' => $username];

        if(empty($username)){
            return errorRedirect("Username cannot be empty", '/login');
        }

        if(empty($password) || strlen($password) < 8){
            return errorRedirect("Password must be 8 or more characters", '/login');
        }

        // start authentication
        if($userId = $this->client->hget("users", $username)){
            if(password_verify($password, $this->client->hget("user:$userId", "password"))){
                // ok. set up cookie
                $authSecret = $this->client->hget("user:$userId", "auth");
                setcookie("auth", $authSecret, time()+(3600));

                return redirect('/');
            }
        }

        return errorRedirect("Invalid login details. Please try again", '/login');

        // return redirect('/');
    }
}