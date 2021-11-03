<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Runtime;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Neto\Lambda\Runtime\RuntimeClient;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RuntimeClientTest extends TestCase
{
    /** @var string */
    private $baseUri = 'http://127.0.0.1:9001';

    /** @var array */
    private $requestHistory;

    public function setUp(): void
    {
        $this->requestHistory = [];
    }

    /**
     * @param ResponseInterface[] $responses
     * @return RuntimeClient
     */
    public function getRuntimeClientInstance(array $responses)
    {
        $mock = new MockHandler($responses);
        $history = Middleware::history($this->requestHistory);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);

        return new RuntimeClient($httpClient, $this->baseUri);
    }

    public function testFetchingNextEventFromRuntimeApi()
    {
        $expectedUri = "{$this->baseUri}/2018-06-01/runtime/invocation/next";
        $expectedEvent = new Response(200, [ 'lambda-runtime-aws-request-id' => uniqid() ], '{}');
        $runtimeClient = $this->getRuntimeClientInstance([ $expectedEvent ]);

        $actualEvent = $runtimeClient->getNextInvocation();

        $this->assertCount(1, $this->requestHistory);
        $this->assertSame($expectedEvent, $actualEvent, 'Client should return unmodified API response');

        $request = $this->requestHistory[0]['request'];

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals($expectedUri, (string) $request->getUri());
    }

    public function testFetchingEmptyEventFromRuntimeApi()
    {
        $expectedUri = "{$this->baseUri}/2018-06-01/runtime/init/error";
        $runtimeClient = $this->getRuntimeClientInstance([
            new Response(), // next invocation response
            new Response() // init error response
        ]);

        $result = $runtimeClient->getNextInvocation();

        $this->assertNull($result, 'Client should return null if invocation is invalid');

        // verify init error endpoint is called
        $this->assertCount(2, $this->requestHistory);
        $request = $this->requestHistory[1]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($expectedUri, (string) $request->getUri());
    }

    public function testHandlingHttpClientException()
    {
        $expectedUri = "{$this->baseUri}/2018-06-01/runtime/init/error";
        $runtimeClient = $this->getRuntimeClientInstance([
            new ServerException('foo', Phake::mock(RequestInterface::class)),
            new Response() // init error response
        ]);

        $result = $runtimeClient->getNextInvocation();

        $this->assertNull($result, 'Client should return null if fetching invocation fails');

        // verify init error endpoint is called
        $this->assertCount(2, $this->requestHistory);
        $request = $this->requestHistory[1]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($expectedUri, (string) $request->getUri());
    }

    public function testSendingAResponse()
    {
        $requestId = uniqid('', true);
        $expectedUri = "{$this->baseUri}/2018-06-01/runtime/invocation/$requestId/response";
        $runtimeClient = $this->getRuntimeClientInstance([
            new Response(200, [ 'lambda-runtime-aws-request-id' => $requestId ]),
            new Response()
        ]);

        $runtimeClient->getNextInvocation();
        $runtimeClient->sendInvocationResponse(new ServerRequest('POST', ''));

        $this->assertCount(2, $this->requestHistory);

        // verify endpoint URI is set correctly
        $request = $this->requestHistory[1]['request'];
        $this->assertEquals($expectedUri, (string) $request->getUri());
    }

    public function testSendingInvocationError()
    {
        $requestId = uniqid('', true);
        $expectedUri = "{$this->baseUri}/2018-06-01/runtime/invocation/$requestId/error";
        $runtimeClient = $this->getRuntimeClientInstance([
            new Response(200, [ 'lambda-runtime-aws-request-id' => $requestId ]),
            new Response()
        ]);

        $runtimeClient->getNextInvocation();
        $runtimeClient->sendInvocationError('Not found', 'NotFoundException');

        $this->assertCount(2, $this->requestHistory);

        $request = $this->requestHistory[1]['request'];
        $requestBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals($expectedUri, (string) $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertArrayHasKey('errorMessage', $requestBody);
        $this->assertArrayHasKey('errorType', $requestBody);
        $this->assertEquals('Not found', $requestBody['errorMessage']);
        $this->assertEquals('NotFoundException', $requestBody['errorType']);
    }

    public function testSendingInitialisationError()
    {
        $expectedUri = "{$this->baseUri}/2018-06-01/runtime/init/error";
        $runtimeClient = $this->getRuntimeClientInstance([ new Response() ]);
        $runtimeClient->sendInitialisationError('Foo', 'BarException');

        $this->assertCount(1, $this->requestHistory);

        $request = $this->requestHistory[0]['request'];
        $requestBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals($expectedUri, (string) $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertArrayHasKey('errorMessage', $requestBody);
        $this->assertArrayHasKey('errorType', $requestBody);
        $this->assertEquals('Foo', $requestBody['errorMessage']);
        $this->assertEquals('BarException', $requestBody['errorType']);
    }
}
