<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Middleware;

require_once __DIR__ . '/Fixture/FooController.php';

use Neto\Lambda\Middleware\Fixture\FooController;
use PHPUnit\Framework\TestCase;
use Neto\Lambda\Middleware\Router;
use Phake;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class RouterTest extends TestCase
{
    /** @var Router */
    private $router;

    /** @var ServerRequestInterface */
    private $request;

    /** @var RequestHandlerInterface */
    private $handler;

    public function setUp(): void
    {
        $this->router = new Router('foo.bar', '\\Neto\\Lambda\\Middleware\\Fixture\\');
        $this->request = Phake::mock(ServerRequestInterface::class);
        $this->handler = Phake::mock(RequestHandlerInterface::class);
        Phake::when($this->handler)->handle($this->request)->thenReturn(
            Phake::mock(ResponseInterface::class)
        );
    }

    public function testConstructorSetsController()
    {
        $this->assertEquals('FooController', $this->router->getController());
    }

    public function testConstructorSetsAction()
    {
        $this->assertEquals('barAction', $this->router->getAction());
    }

    public function testControllerClassIncludesNamespace()
    {
        $this->assertEquals(
            '\\Neto\\Lambda\\Middleware\\Fixture\\FooController',
            $this->router->getControllerClass()
        );
    }

    public function testConstructorThrowsExceptionOnInvalidHandler()
    {
        $this->expectException(RuntimeException::class);
        new Router('foobar');
    }

    public function testInstantiatingController()
    {
        $controller = $this->router->getControllerInstance();
        $this->assertInstanceOf(\Neto\Lambda\Middleware\Fixture\FooController::class, $controller);
    }

    public function testInstantiatingControllerFromContainer()
    {
        $className = $this->router->getControllerClass();
        $container = Phake::mock(ContainerInterface::class);
        $controller = Phake::mock(FooController::class);
        Phake::when($container)->has($className)->thenReturn(true);
        Phake::when($container)->get($className)->thenReturn($controller);
        $this->router->setContainer($container);

        $this->assertSame($controller, $this->router->getControllerInstance());
        Phake::verify($container, Phake::times(1))->get($className);
    }

    public function testInstantiatingNonExistentController()
    {
        $router = new Router('baz.bar');
        $controller = $router->getControllerInstance();
        $this->assertNull($controller);
    }

    public function testProcessingNonExistentController()
    {
        $router = new Router('baz.bar');
        $router->process($this->request, $this->handler);
        Phake::verify($this->handler)->handle($this->request);
    }

    public function testProcessingNonExistentAction()
    {
        $router = new Router('foo.baz', '\\Neto\\Lambda\\Middleware\\Fixture\\');
        $router->process($this->request, $this->handler);
        Phake::verify($this->handler)->handle($this->request);
    }

    public function testProcessingActionWithNullResponse()
    {
        $router = new Router('foo.null', '\\Neto\\Lambda\\Middleware\\Fixture\\');
        $router->process($this->request, $this->handler);
        Phake::verify($this->handler)->handle($this->request);
    }

    public function testSuccessfulAction()
    {
        $response = $this->router->process($this->request, $this->handler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}