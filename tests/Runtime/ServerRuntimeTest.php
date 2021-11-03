<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Runtime;

use GuzzleHttp\Psr7\Response;
use Neto\Lambda\Runtime\ServerRuntime;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class ServerRuntimeTest extends TestCase
{
    /** @var ServerRuntime */
    private $runtime;

    /** @var RequestHandlerInterface|\Phake_IMock */
    private $requestHandler;

    public function setUp(): void
    {
        $response = new Response(200, [], 'foo');
        $this->requestHandler = Phake::mock(RequestHandlerInterface::class);
        Phake::when($this->requestHandler)->handle(Phake::anyParameters())->thenReturn($response);

        $this->runtime = new ServerRuntime($this->requestHandler);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRequestIdIsIncludedInRequestHeaders()
    {
        $this->runtime->run();
        Phake::verify($this->requestHandler)->handle(Phake::capture($request));
        $this->assertArrayHasKey('lambda-runtime-aws-request-id', $request->getHeaders());
    }

    /**
     * @runInSeparateProcess
     */
    public function testResponseBodyIsOutput()
    {
        $this->expectOutputString('foo');
        $this->runtime->run();
    }
}