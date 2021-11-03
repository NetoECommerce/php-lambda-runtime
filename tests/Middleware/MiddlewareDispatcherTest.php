<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Middleware;

use Neto\Lambda\Middleware\MiddlewareDispatcher;
use Neto\Lambda\Message\ErrorResponse;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareDispatcherTest extends TestCase
{
    /** @var MiddlewareDispatcher */
    private $dispatcher;

    /** @var ServerRequestInterface|\Phake_IMock */
    private $request;

    /** @var ResponseInterface|\Phake_IMock */
    private $response;

    /** @var MiddlewareInterface|\Phake_IMock */
    private $middleware;

    public function setUp(): void
    {
        $this->dispatcher = new MiddlewareDispatcher();
        $this->request = Phake::mock(ServerRequestInterface::class);
        $this->response = Phake::mock(ResponseInterface::class);
        $this->middleware = Phake::mock(MiddlewareInterface::class);
    }

    public function testCanPushMiddleware()
    {
        Phake::when($this->middleware)->process($this->request, $this->dispatcher)->thenReturn($this->response);

        $this->dispatcher->push($this->middleware);
        $this->assertSame($this->response, $this->dispatcher->handle($this->request));
    }

    public function testCanUnshiftMiddleware()
    {
        $middleware2 = Phake::mock(MiddlewareInterface::class);
        $response2 = Phake::mock(ResponseInterface::class);
        Phake::when($this->middleware)->process($this->request, $this->dispatcher)->thenReturn($this->response);
        Phake::when($middleware2)->process($this->request, $this->dispatcher)->thenReturn($response2);

        $this->dispatcher->push($this->middleware);
        $this->dispatcher->unshift($middleware2);

        $this->assertSame($response2, $this->dispatcher->handle($this->request));
    }

    public function testErrorResponseWhenQueueIsEmpty()
    {
        $response = $this->dispatcher->handle($this->request);

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
