<?php

// helper functions

function textResponse(string $text)
{
    return new \Laminas\Diactoros\Response\TextResponse($text);
}

function jsonResponse($data)
{
    return new \Laminas\Diactoros\Response\JsonResponse($data);
}

function redirect(string $url)
{
    return new \Laminas\Diactoros\Response\RedirectResponse($url);
}

function view(string $location, array $data)
{
    if(!empty($_SESSION['flash'])){
        $data['flash'] = $_SESSION['flash'];
        $_SESSION['flash'] = array();
    }

    return \RedisApp\Handlers\ViewHandler::twig($location, $data);
}

function randomVal(int $length = 16)
{
    $x = "";
    for($i = 0; $i < $length; $i++){
        $x .= dechex(random_int(0, 255));
    }
    $x = substr($x, 0, $length);

    return $x;
}

function authCheck()
{
    $auth = new \RedisApp\Handlers\AuthHandler;
    return $auth->check();
}

function errorRedirect(string $message, string $url)
{
    $_SESSION['flash']['error'] = $message;
    return redirect($url);
}

function verifyToken($token)
{
    if(!empty($token)){
        if(hash_equals($_SESSION['token'], $token)){
            return true;
        }
    }
    return false;
}