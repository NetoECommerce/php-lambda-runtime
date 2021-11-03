<?php declare(strict_types=1);

namespace Neto\Lambda\Middleware;

use GuzzleHttp\Psr7\Response;
use Invoker\Exception\InvocationException;
use Invoker\Invoker;
use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Neto\Container\ContainerAwareInterface;
use Neto\Container\ContainerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CallableMiddleware
 *
 * This is a wrapper for anonymous functions, allowing us to treat a callable
 * as a PSR-15 compliant middleware. The callable can optionally include two
 * parameters, $request and $handler, and can return either a Response object
 * or a string that will be returned as the response body.
 *
 * @package Neto\Lambda\Middleware
 */
class CallableMiddleware implements MiddlewareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var callable */
    private $callable;

    /**
     * CallableMiddleware constructor.
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @throws InvocationException
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $invoker = new Invoker(null, $this->container);
        if ($this->container) {
            $invoker->getParameterResolver()->prependResolver(new ParameterNameContainerResolver($this->container));
            $invoker->getParameterResolver()->prependResolver(new TypeHintContainerResolver($this->container));
        }

        $return = $invoker->call($this->callable, [
            'request' => $request,
            'handler' => $handler
        ]);

        // if the callable returns a PSR-7 response, just return that
        if ($return instanceof ResponseInterface) {
            return $return;
        }

        // To be a little more API-friendly, convert array responses to JSON
        if (is_array($return)) {
            $return = json_encode($return);
        }

        // otherwise, assume the callable is returning a string to write to the response body
        $response = new Response(200);
        $response->getBody()->write((string) $return);

        return $response;
    }
}
