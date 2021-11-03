<?php declare(strict_types=1);

namespace Neto\Lambda\Middleware;

use Invoker\Exception\InvocationException;
use Invoker\Exception\NotCallableException;
use Invoker\Invoker;
use Invoker\InvokerInterface;
use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Neto\Container\ContainerAwareInterface;
use Neto\Container\ContainerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Simple controller router.
 *
 * Uses the lambda handler name to route a request to a controller. If neither
 * the controller class or action method exist, it passes the request on to the
 * next middleware.
 *
 * Example: if the handler name is helloworld.get, the router will attempt to
 * load the \App\Controller\HelloworldController class and execute the method
 * getAction().
 *
 * @package Neto\Lambda\Middleware
 */
class Router implements MiddlewareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var string */
    private $handler;

    /** @var string */
    private $namespace;

    /** @var string */
    private $controller;

    /** @var string */
    private $action;

    /** @var InvokerInterface */
    private $invoker;

    /** @var mixed */
    private $controllerInstance;

    /**
     * Router constructor.
     *
     * Handler is expected in the format of "controller.action"
     *
     * @param string $handlerName The handler name defined in your lambda function
     * @param string $namespace   The namespace to prepend to the controller class
     *
     * @throws \RuntimeException if $handlerName is not in the correct format
     */
    public function __construct($handlerName, $namespace = '\\App\\Controller\\')
    {
        $this->handler = $handlerName;
        $this->namespace = $namespace;

        if (strpos($handlerName, '.') === false) {
            throw new \RuntimeException('Handler is expected in the format: controller.action');
        }

        list($controller, $action) = explode('.', $this->handler);

        $this->controller = ucfirst($controller) . 'Controller';
        $this->action = $action . 'Action';
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getControllerClass()
    {
        return $this->namespace . $this->controller;
    }

    /**
     * Instantiate the invoker.
     *
     * This is done as late as possible so we can inject our container in the constructor.
     *
     * @return InvokerInterface
     */
    public function getInvoker(): InvokerInterface
    {
        if (!$this->invoker) {
            $this->invoker = new Invoker(null, $this->container);
            if ($this->container) {
                $this->invoker->getParameterResolver()->prependResolver(
                    new ParameterNameContainerResolver($this->container)
                );
                $this->invoker->getParameterResolver()->prependResolver(
                    new TypeHintContainerResolver($this->container)
                );
            }
        }

        return $this->invoker;
    }

    /**
     * Attempt to instantiate our controller class.
     *
     * @return mixed
     */
    public function getControllerInstance()
    {
        $class = $this->getControllerClass();

        // we only need to instantiate it once
        if ($this->controllerInstance) {
            return $this->controllerInstance;
        }

        // check the container for the controller class definition
        if ($this->container && $this->container->has($class)) {
            return $this->controllerInstance = $this->container->get($class);
        }

        // check if controller class and action method exist
        if (!class_exists($class)) {
            return null;
        }

        return $this->controllerInstance = new $class();
    }

    /**
     * Attempt to route the request to a controller using the lambda handler name
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @throws InvocationException
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $invoker = $this->getInvoker();
        $controller = $this->getControllerInstance();

        try {
            $response = $invoker->call([
                $controller,
                $this->getAction()
            ], [
                'request' => $request,
                'handler' => $handler
            ]);
        } catch (NotCallableException $e) {
            // move to the next middleware if the action doesn't exist
            return $handler->handle($request);
        }

        // an action can return null/false to pass the request on to the next middleware
        if (!$response) {
            return $handler->handle($request);
        }

        return $response;
    }
}
