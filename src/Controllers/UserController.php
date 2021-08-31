<?php declare(strict_types=1);

namespace RedisApp\Controllers;

use Predis\Client;
use Laminas\Diactoros\Response;
use RedisApp\Handlers\PostHandler;
use RedisApp\Handlers\RequestHandler;
use Psr\Http\Message\ServerRequestInterface;

class UserController
{
    protected $client;

    public function __construct()
    {
        $this->client = redis();
    }

    public function profile(ServerRequestInterface $request): Response
    {
        $auth = authCheck();

        $data = ['title' => $auth['username'].' Profile', 'auth' => $auth];

        $posts = PostHandler::getUserPosts($auth['username']);

        $data['posts'] = $posts;

        return view('profile.html', $data);
    }

    public function updateProfile(ServerRequestInterface $request): Response
    {
        $requestBody = $request->getParsedBody();

        $fullname = $requestBody['fullname'];
        $email = $requestBody['email'];
        $bio = $requestBody['bio'];

        $password = $requestBody['password'];
        $password_confirm = $requestBody['password_confirm'];


        if(!empty($password)){
            if($password !== $password_confirm){
                $_SESSION['flash']['error'] = 'The passwords do not match';
                return redirect('/profile');
            }
            if(strlen($password) < 8){
                $_SESSION['flash']['error'] = 'Password must be 8 or more characters';
                return redirect('/profile');
            }
            $password = password_hash($password, PASSWORD_DEFAULT);
        }
        else{
            $password = null;
        }

        
        $userDetails = authCheck();
        
        if(strlen(trim($fullname)) < 4 || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $_SESSION['flash']['error'] = 'Please fill in the required fields correctly';
            return redirect('/profile');
        }

        if(strlen($bio) < 4 || strlen($bio) > 150){
            $_SESSION['flash']['error'] = 'Bio must be between 4 - 150 characters';
            return redirect('/profile');
        }

        // update profile

        // check email exists
        $existEmail = $this->client->hget('emails', $email);
        if($existEmail && $email != $userDetails['email']){
            // return textResponse('bad user');
            $_SESSION['flash']['error'] = "Email $email is in use.";
            return redirect('/profile');
        }

        // delete old email
        $this->client->hdel("emails", $userDetails['email']);

        // update details
        $password = $password ?? $this->client->hget("user:{$userDetails['id']}", "password");
        
        $this->client->hset("user:{$userDetails['id']}", 
                        "email", $email,
                        "fullname", $fullname,
                        "password", $password,
                        "bio", $bio
                        // "password", password_hash('password', PASSWORD_DEFAULT)
                    );

        // add new email to list
        $this->client->hset("emails", $email, $userDetails['id']);

        $_SESSION['flash']['success'] = "Profile Updated Successfully";

        return redirect('/profile');

    }
}