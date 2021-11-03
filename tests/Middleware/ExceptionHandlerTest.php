<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Middleware;

use Exception;
use Neto\Lambda\Message\ErrorResponse;
use Neto\Lambda\Middleware\ExceptionHandler;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ExceptionHandlerTest extends TestCase
{
    /** @var ExceptionHandler */
    private $middleware;

    /** @var resource */
    private $outputHandler;

    /** @var ServerRequestInterface|\Phake_IMock */
    private $request;

    /** @var ResponseInterface|\Phake_IMock */
    private $response;

    /** @var RequestHandlerInterface|\Phake_IMock */
    private $handler;

    public function setUp(): void
    {
        $this->outputHandler = fopen('php://memory', 'r+');
        $this->middleware = new ExceptionHandler(false, $this->outputHandler);
        $this->request = Phake::mock(ServerRequestInterface::class);
        $this->response = Phake::mock(ResponseInterface::class);
        $this->handler = Phake::mock(RequestHandlerInterface::class);
    }

    public function testSuccessfulRequest()
    {
        Phake::when($this->handler)->handle($this->request)->thenReturn($this->response);
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $response);
    }

    public function testErrorResponseWhenExceptionIsThrown()
    {
        Phake::when($this->handler)->handle($this->request)->thenThrow(new Exception('foo', 503));
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertJson((string) $response->getBody());

        $json = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('errorType', $json);
        $this->assertArrayHasKey('errorMessage', $json);
    }

    public function testStacktraceInErrorResponse()
    {
        $this->middleware = new ExceptionHandler(true, $this->outputHandler);
        Phake::when($this->handler)->handle($this->request)->thenThrow(new Exception('foo', 503));
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertJson((string) $response->getBody());
        $json = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('stacktrace', $json);
    }

    public function testOutOfBoundsStatusCode()
    {
        Phake::when($this->handler)->handle($this->request)->thenThrow(new Exception('foo', 399));
        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertEquals(500, $response->getStatusCode());

        Phake::when($this->handler)->handle($this->request)->thenThrow(new Exception('foo', 600));
        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
