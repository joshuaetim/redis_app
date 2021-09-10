# RedisApp

RedisApp is an attempt to use Redis as a primary database instead of a caching system. The application built with this feature is a blog with a score/ranking functionality. Redis data types like Hash, Set, Sorted Set, and Lists are used to store different categories of data used by the application.

## Installation

You can download or clone this repository. To run the application on your system, run the following command:

```bash
composer install
php -S localhost:8000 -t public/
```

Please note that you should have PHP and Composer installed on your local system.

## Basic Structure

The App structure uses the [dotenv](https://github.com/vlucas/phpdotenv) package to handle environment configs, [Laminas\Diactoros](https://docs.laminas.dev/laminas-diactoros/) for PSR implementations, [League\Route](https://route.thephpleague.com/) for routing, and [Laminas\HttpHandlerRunner](https://docs.laminas.dev/laminas-httphandlerrunner/).

```php
// index.php

$dotenv = Dotenv\Dotenv::createImmutable(BASEPATH);
$dotenv->load();

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals();

$router = new League\Route\Router;

$router->get('/', [RedisApp\Controllers\PageController::class, 'home']);

try{
    $response = $router->dispatch($request);

    $emitter = new Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
    $emitter->emit($response);
} catch(NotFoundException $exception) {
    header('Location: /'); // or: make a 404 page
}
```

For templating, the [Twig engine](https://twig.symfony.com/) was used. [Predis](https://github.com/predis/predis), the Redis client for PHP was used to access Redis through the application. 

```php
//...
class PageController 
{
    protected $client;

    public function __construct()
    {
        $this->client = redis();
    }
    //...

    // check a user's profile
    public function displayProfile(ServerRequestInterface $request, array $args): Response
    {
        $auth = authCheck();

        $username = $args['username'];

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
```

```php
//...
function redis()
{
    $parameters = null;

    if($_ENV['APP_ENV'] == "production"){
        $parameters = array(
            'host' => parse_url($_ENV['REDISCLOUD_URL'], PHP_URL_HOST),
            'port' => parse_url($_ENV['REDISCLOUD_URL'], PHP_URL_PORT),
            'password' => parse_url($_ENV['REDISCLOUD_URL'], PHP_URL_PASS),
        );
    }

    return new \Predis\Client($parameters, ['prefix' => 'pred:']);
}
```

**Please note the prefix used "pred:", to ensure all keys produced by the application are unique.**

## Online Access

This App is Hosted online at [https://joshredisapp.herokuapp.com/](https://joshredisapp.herokuapp.com/).

## Discussion

This was a demonstration of Redis as a primary database. The discussion is on my [Medium](https://joshua-etim.medium.com/) profile. Having used this application and tested it for persistence, I believe Redis can efficiently replace a conventional database. Although one bottleneck is the size available being limited to a part of your RAM, the tradeoff is justified in cases where speed is important. 

Sidenote: A blog, I believe, isn't the most popular justification for using Redis as a primary database. This is for testing purposes and should not be used in production environment.

## Feedback
Feedback and further advice would be appreciated. Please direct them to etimjoshua4@gmail.com

Thanks in advance!