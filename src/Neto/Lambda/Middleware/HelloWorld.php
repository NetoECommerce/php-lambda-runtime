<?php declare(strict_types=1);

namespace Neto\Lambda\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class HelloWorld
 *
 * A dummy middleware used for testing.
 *
 * @package Neto\Lambda\Middleware
 */
class HelloWorld implements MiddlewareInterface
{
    /**
     * Returns a "hello world" response.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new Response(
            200,
            [ 'hello' => 'world' ],
            json_encode([
                'success' => true,
                'message' => 'Hello world!'
            ])
        );
    }
}
