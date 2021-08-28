<?php
declare(strict_types=1);

namespace RedisApp\Handlers;

use Laminas\Diactoros\Response;

class ViewHandler
{
    public static function twig(string $location, array $viewData): Response
    {
        $loader = new \Twig\Loader\FilesystemLoader(BASEPATH.'/views');
        $twig = new \Twig\Environment($loader);

        // csrf token function
        $csrfFunction = new \Twig\TwigFunction('csrf_token', function(){
            if(empty($_SESSION['token'])){
                $_SESSION['token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['token'];
        });
        $twig->addFunction($csrfFunction);

        $content = $twig->render($location, $viewData);
        $response = new Response\HtmlResponse($content);

        return $response;
    }
}