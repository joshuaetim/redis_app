<?php
declare(strict_types=1);

use League\Route\Http\Exception\NotFoundException;


require_once '../vendor/autoload.php';

define('BASEPATH', dirname(__DIR__));

session_start();

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

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals();

$router = new League\Route\Router;

$router->get('/', [RedisApp\Controllers\PageController::class, 'home']);

$router->get('/u/{username}', [RedisApp\Controllers\PageController::class, 'displayProfile']);

$router->get('/posts', [RedisApp\Controllers\PostController::class, 'index']);

$router->get('/posts/{id}', [RedisApp\Controllers\PostController::class, 'single']);

$router->get('/redis', [RedisApp\Controllers\RedisController::class, 'test'])
        ->middleware(new \RedisApp\Middleware\AuthMiddleware);


$router->group('', function(\League\Route\RouteGroup $route)
{
    $route->get('/register', [RedisApp\Controllers\Auth\RegisterController::class, 'create']);

    $route->get('/login', [RedisApp\Controllers\Auth\LoginController::class, 'create']);
        
})->middleware(new \RedisApp\Middleware\GuestMiddleware);


$router->post('/register', [RedisApp\Controllers\Auth\RegisterController::class, 'store']);
    
$router->post('/login', [RedisApp\Controllers\Auth\LoginController::class, 'store']);


// $router->group('', function(\League\Route\RouteGroup $route)
// {
//     $route->post('/register', [RedisApp\Controllers\Auth\RegisterController::class, 'store']);
    
//     $route->post('/login', [RedisApp\Controllers\Auth\LoginController::class, 'store']);
    
//     $route->post('/logout', [RedisApp\Controllers\Auth\LogoutController::class, 'logout'])
//             ->middleware(new \RedisApp\Middleware\AuthMiddleware);

// })->middleware(new \RedisApp\Middleware\CsrfMiddleware);


$router->group('', function(\League\Route\RouteGroup $route)
{
    $route->get('/profile', [RedisApp\Controllers\UserController::class, 'profile']);

    $route->get('/create-post', [RedisApp\Controllers\PostController::class, 'create']);

    $route->post('/add-post', [RedisApp\Controllers\PostController::class, 'store']);

    $route->get('/posts/{id}/edit', [RedisApp\Controllers\PostController::class, 'edit']);

    $route->post('/updateProfile', [RedisApp\Controllers\UserController::class, 'updateProfile']);

    $route->post('/logout', [RedisApp\Controllers\Auth\LogoutController::class, 'logout']);

})->middleware(new \RedisApp\Middleware\AuthMiddleware);


$router->get('/test', function(){
    $auth = authCheck();

    $data = ['title' => $auth['username'].' profile', 'auth' => $auth, 'posts' => []];

    // return jsonResponse($data);

    return view('profile.html', $data);
});

$router->middleware(new \RedisApp\Middleware\CsrfMiddleware);

try{
    $response = $router->dispatch($request);

    $emitter = new Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
    $emitter->emit($response);
} catch(NotFoundException $exception) {
    header('Location: /'); // todo: make a 404 page
}

// echo PageController::home();