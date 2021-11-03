<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Middleware;

use PHPUnit\Framework\TestCase;
use Neto\Lambda\Middleware\CallableMiddleware;
use Phake;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

final class CallableMiddlewareTest extends TestCase
{
    /** @var ServerRequestInterface */
    private $request;

    /** @var RequestHandlerInterface */
    private $handler;

    public function setUp(): void
    {
        $this->request = Phake::mock(ServerRequestInterface::class);
        $this->handler = Phake::mock(RequestHandlerInterface::class);
    }

    public function testCallableReturnsResponseObject()
    {
        $middleware = new CallableMiddleware(function () {
            return 'foo';
        });
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('foo', (string) $response->getBody());
    }

    public function testCallableReturnsResponseJson()
    {
        $middleware = new CallableMiddleware(function () {
            return ['test' => 'blah'];
        });
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode(['test' => 'blah']), (string) $response->getBody());
    }

    public function testCallableWithNoParameters()
    {
        $middleware = new CallableMiddleware(function () {
            return 'foo';
        });
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testCallableWithOneParameter()
    {
        $middleware = new CallableMiddleware(function ($request) {
            $this->assertEquals($this->request, $request);
        });
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testCallableWithAllParameters()
    {
        $middleware = new CallableMiddleware(function ($request, $handler) {
            $this->assertEquals($this->request, $request);
            $this->assertEquals($this->handler, $handler);
        });
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testCallableWithAutoWiring()
    {
        $container = Phake::mock(ContainerInterface::class);
        Phake::when($container)->has(ContainerInterface::class)->thenReturn(true);
        Phake::when($container)->get(ContainerInterface::class)->thenReturn($container);
        Phake::when($container)->has('foo')->thenReturn(true);
        Phake::when($container)->get('foo')->thenReturn('bar');

        $middleware = new CallableMiddleware(function (ContainerInterface $container, $foo) {
            $this->assertInstanceOf(ContainerInterface::class, $container);
            $this->assertEquals('bar', $foo);
        });
        $middleware->setContainer($container);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testReturningResponseObjectFromCallable()
    {
        $expectedResponse = Phake::mock(ResponseInterface::class);
        $middleware = new CallableMiddleware(function () use ($expectedResponse) {
            return $expectedResponse;
        });
        $actualResponse = $middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $actualResponse);
    }
}
