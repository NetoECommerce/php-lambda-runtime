<?php declare(strict_types=1);

namespace Neto\Lambda\Application;

use Neto\Container\ContainerAwareInterface;
use Neto\Container\ContainerAwareTrait;
use Neto\Lambda\Middleware\CallableMiddleware;
use Neto\Lambda\Runtime\RuntimeInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class App
 * @package Neto\Lambda\Application
 */
class App implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var RuntimeInterface */
    private $runtime;

    /** @var RequestHandlerInterface */
    private $dispatcher;

    /**
     * App constructor.
     *
     * @param RuntimeInterface $runtime
     * @param RequestHandlerInterface $dispatcher
     * @param ContainerInterface $container
     */
    public function __construct(
        RuntimeInterface $runtime,
        RequestHandlerInterface $dispatcher,
        ContainerInterface $container
    ) {
        $this->runtime = $runtime;
        $this->dispatcher = $dispatcher;
        $this->container = $container;
    }

    /**
     * @return RuntimeInterface
     */
    public function getRuntime(): RuntimeInterface
    {
        return $this->runtime;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getDispatcher(): RequestHandlerInterface
    {
        return $this->dispatcher;
    }


    /**
     * Starts the runtime.
     *
     * @return void
     */
    public function run()
    {
        $this->runtime->run();
    }

    /**
     * Adds a PSR-15 compliant middleware to the queue dispatcher
     *
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        if ($middleware instanceof ContainerAwareInterface) {
            $middleware->setContainer($this->container);
        }

        $this->dispatcher->push($middleware);
        return $this;
    }

    /**
     * Adds a callable/anon function to the middleware queue.
     *
     * This allows us to write a simple anonymous function to handle a request
     * instead of writing and instantiating a middleware class. The function
     * can optionally include two parameters, $request and $handler, and can
     * return either a Response object or a simple string that will be returned
     * in the response body.
     *
     * @param $callable
     * @return $this
     */
    public function addCallable(callable $callable)
    {
        $middleware = new CallableMiddleware($callable);
        $middleware->setContainer($this->container);
        $this->dispatcher->push($middleware);
        return $this;
    }
}
