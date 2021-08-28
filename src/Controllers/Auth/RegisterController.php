<?php
declare(strict_types=1);

namespace RedisApp\Controllers\Auth;

use Predis\Client;
use Laminas\Diactoros\Response;
use RedisApp\Handlers\ViewHandler;
use Psr\Http\Message\ServerRequestInterface;

class RegisterController
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(null, ['prefix' => 'pred:']);
    }

    public function create(ServerRequestInterface $request): Response
    {
        return view('auth/register.html', ['title' => 'Register New User']);
    }

    public function store(ServerRequestInterface $request): Response
    {
        
        $requestBody = $request->getParsedBody();

        $username = $requestBody['username'];
        $email = $requestBody['email'];
        $fullname = $requestBody['fullname'];
        $password = $requestBody['password'];
        $password_confirm = $requestBody['password_confirm'];

        $_SESSION['flash']['input'] = ['username' => $username, 'email' => $email, 'fullname' => $fullname];
        
        if(strlen(trim($password)) < 8){
            $_SESSION['flash']['error'] = 'Password length cannot be less than 8';
            return redirect('/register');
        }

        if(strlen(trim($username)) < 4 || strlen(trim($fullname)) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $_SESSION['flash']['error'] = 'Please fill in the required fields correctly';
            return redirect('/register');
        }

        if($password != $password_confirm){
            $_SESSION['flash']['error'] = 'The passwords do not match';
            return redirect('/register');
        }


        if($this->client->hget('users', $username)){
            // return textResponse('bad user');
            $_SESSION['flash']['error'] = "The username is already selected";
            return redirect('/register');
        }

        // check email exists
        if($this->client->hget('emails', $email)){
            // return textResponse('bad user');
            $_SESSION['flash']['error'] = "This email is in use. <a href='/login'>Login</a>?";
            return redirect('/register');
        }

        // create user
        $password = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->client->incr('next_user_id');
        $authSecret = randomVal();
        $this->client->hset("users", $username, $userId);
        $this->client->hset("emails", $email, $userId);
        $this->client->hset("user:$userId", 
                        "username", $username,
                        "fullname", $fullname,
                        "email", $email,
                        "password", $password,
                        "auth", $authSecret
                    );
        $this->client->hset("auths", $authSecret, $userId);

        // log user entry
        $this->client->zadd("users_by_time", time(), $username);

        // set cookie for auth
        setcookie("auth", $authSecret, time()+(60*60));

        return redirect('/');


        return new Response\TextResponse($requestBody['username']);
    }
}