<?php declare(strict_types=1);

namespace Neto\Lambda\Middleware;

use Neto\Lambda\Message\ErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class MiddlewareDispatcher
 *
 * A request handler that manages a queue of middleware, executing them in the
 * order they were added. At any time a middleware can return a response to
 * end execution or continue on to the next middleware by calling the handle()
 * method.
 *
 * @package Neto\Lambda\Handler
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private $queue = [];

    /**
     * Adds a middleware object to the end of the queue
     *
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function push(MiddlewareInterface $middleware)
    {
        $this->queue[] = $middleware;
        return $this;
    }

    /**
     * Injects a middleware object at the start of the queue
     *
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function unshift(MiddlewareInterface $middleware)
    {
        array_unshift($this->queue, $middleware);
        return $this;
    }

    /**
     * Returns the first middleware and removes it from the queue
     *
     * @return MiddlewareInterface
     */
    protected function shift(): MiddlewareInterface
    {
        return array_shift($this->queue);
    }

    /**
     * Iterates over the middleware queue until the request is handled and a response is returned
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!count($this->queue)) {
            $json = [
                'success' => false,
                'exception' => 'RequestNotHandled',
                'message' => 'No middleware is available to handle the request.'
            ];
            return new ErrorResponse(500, [], json_encode($json));
        }

        return $this->shift()->process($request, $this);
    }
}
