<?php
declare(strict_types=1);

use League\Route\Http\Exception\NotFoundException;

require_once '../vendor/autoload.php';

define('BASEPATH', dirname(__DIR__));

$dotenv = Dotenv\Dotenv::createImmutable(BASEPATH);
$dotenv->load();

session_start();

require_once '../src/helpers.php';

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

    $route->post('/like-post', [RedisApp\Controllers\PostController::class, 'like']);

    $route->get('/posts/{id}/edit', [RedisApp\Controllers\PostController::class, 'edit']);

    $route->post('/update-post', [RedisApp\Controllers\PostController::class, 'update']);

    $route->post('/delete-post', [RedisApp\Controllers\PostController::class, 'delete']);

    $route->post('/updateProfile', [RedisApp\Controllers\UserController::class, 'updateProfile']);

    $route->post('/logout', [RedisApp\Controllers\Auth\LogoutController::class, 'logout']);

})->middleware(new \RedisApp\Middleware\AuthMiddleware);


$router->get('/test', function(){
    return textResponse($_ENV['APP_ENV']);
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