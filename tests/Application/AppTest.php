<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Application;

use Neto\Container\ContainerAwareInterface;
use Neto\Lambda\Application\App;
use Neto\Lambda\Middleware\CallableMiddleware;
use Neto\Lambda\Middleware\MiddlewareDispatcher;
use Neto\Lambda\Runtime\RuntimeInterface;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

final class AppTest extends TestCase
{
    /** @var App */
    private $app;

    /** @var MiddlewareDispatcher|\Phake_IMock */
    private $handler;

    /** @var ContainerInterface|\Phake_IMock */
    private $container;

    /** @var RuntimeInterface|\Phake_IMock */
    private $runtime;

    public function setUp(): void
    {
        $this->runtime = Phake::mock(RuntimeInterface::class);
        $this->handler = Phake::mock(MiddlewareDispatcher::class);
        $this->container = Phake::mock(ContainerInterface::class);
        $this->app = new App($this->runtime, $this->handler, $this->container);
    }

    public function testRuntimeIsRun()
    {
        $this->app->run();

        Phake::verify($this->runtime)->run();
    }

    public function testAddingMiddlewareToDispatcher()
    {
        $middleware = Phake::mock(MiddlewareInterface::class);
        $this->app->addMiddleware($middleware);

        Phake::verify($this->handler)->push($middleware);
    }

    public function testAddingContainerAwareMiddlewareSetsContainer()
    {
        $middleware = Phake::mock([MiddlewareInterface::class, ContainerAwareInterface::class]);
        Phake::when($middleware)->getContainer()->thenReturn($this->container);
        $this->app->addMiddleware($middleware);

        Phake::verify($this->handler)->push($middleware);
        Phake::verify($middleware)->setContainer($this->container);
    }

    public function testAddingCallableMiddlewareToDispatcher()
    {
        $this->app->addCallable(function () {
            return 'foo';
        });

        Phake::verify($this->handler)->push(Phake::capture($middleware));
        $this->assertInstanceOf(CallableMiddleware::class, $middleware);
        $this->assertSame($this->container, $middleware->getContainer());
    }
}
