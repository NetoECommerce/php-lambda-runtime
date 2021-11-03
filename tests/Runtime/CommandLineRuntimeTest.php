<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Runtime;

use Neto\Lambda\Runtime\CommandLineRuntime;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

final class CommandLineRuntimeTest extends TestCase
{
    /** @var CommandLineRuntime */
    private $runtime;

    /** @var RequestHandlerInterface */
    private $requestHandler;

    /** @var ResponseInterface */
    private $response;

    /** @var resource */
    private $outputHandler;

    public function setUp(): void
    {
        putenv('_PAYLOAD=foo');
        $this->response = Phake::mock(ResponseInterface::class);
        Phake::when($this->response)->getHeaders()->thenReturn([]);

        $this->requestHandler = Phake::mock(RequestHandlerInterface::class);
        Phake::when($this->requestHandler)->handle(Phake::anyParameters())->thenReturn($this->response);
        $this->outputHandler = fopen('php://temp', 'w+');

        $this->runtime = new CommandLineRuntime($this->requestHandler, $this->outputHandler);
    }

    public function testRequestIdIsIncludedInRequestHeaders()
    {
        $this->runtime->run();
        Phake::verify($this->requestHandler)->handle(Phake::capture($request));
        $this->assertArrayHasKey('lambda-runtime-aws-request-id', $request->getHeaders());
    }

    public function testRequestIncludesPayload()
    {
        $this->runtime->run();
        Phake::verify($this->requestHandler)->handle(Phake::capture($request));
        $this->assertEquals('foo', (string) $request->getBody());
    }

    public function testResponseBodyIsOutput()
    {
        Phake::when($this->response)->getBody()->thenReturn('foobar');
        $this->runtime->run();

        rewind($this->outputHandler);
        $output = stream_get_contents($this->outputHandler);

        $this->assertStringContainsString('foobar', $output);
    }

    public function testStatusCodeIsOutput()
    {
        Phake::when($this->response)->getStatusCode()->thenReturn(429);
        $this->runtime->run();

        rewind($this->outputHandler);
        $output = stream_get_contents($this->outputHandler);

        $this->assertStringContainsString('429', $output);
    }

    public function testHeadersAreOutput()
    {
        Phake::when($this->response)->getHeaders()->thenReturn([
            'foo' => [ 'bar', 'baz' ],
            'foobar' => [ 'baz' ]
        ]);
        $this->runtime->run();

        rewind($this->outputHandler);
        $output = stream_get_contents($this->outputHandler);

        $this->assertStringContainsString('foo: bar', $output);
        $this->assertStringContainsString('foo: baz', $output);
        $this->assertStringContainsString('foobar: baz', $output);
    }
}