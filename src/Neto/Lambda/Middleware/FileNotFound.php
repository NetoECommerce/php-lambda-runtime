<?php declare(strict_types=1);

namespace Neto\Lambda\Middleware;

use Neto\Lambda\Message\ErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class FileNotFound
 * @package Neto\Lambda\Middleware
 */
class FileNotFound implements MiddlewareInterface
{
    /**
     * Add this to the end of a middleware queue for a 404 fallback
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $json = [
            'success' => false,
            'message' => 'Resource not found'
        ];
        return new ErrorResponse(404, [], json_encode($json));
    }
}
