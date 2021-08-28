<?php
declare(strict_types=1);

namespace RedisApp\Middleware;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            if(!verifyToken($_POST['token'])){
                return textResponse("Bad Request. CSRF Token Mismatch");
            }
        }

        return $handler->handle($request);
    }
}