<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Runtime;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Neto\Lambda\Runtime\LambdaRuntime;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function GuzzleHttp\Psr7\stream_for;

final class LambdaRuntimeTest extends TestCase
{
    const REQUEST_HEADERS = [
        'lambda-runtime-aws-request-id' => [ 'abcd1234' ]
    ];
    const INVOCATION_RESPONSE_URL = 'http://127.0.0.1:9001/2018-06-01/runtime/invocation/abcd1234/response';
    const INVOCATION_ERROR_URL = 'http://127.0.0.1:9001/2018-06-01/runtime/invocation/abcd1234/error';

    /** @var RequestHandlerInterface|\Phake_IMock */
    private $handler;

    /** @var ResponseInterface */
    private $lambdaApiResponse;

    /** @var ResponseInterface */
    private $applicationResponse;

    /** @var array */
    private $requestHistory;

    public function setUp(): void
    {
        putenv('AWS_LAMBDA_RUNTIME_API=127.0.0.1:9001');

        $this->requestHistory = [];
        $this->handler = Phake::mock(RequestHandlerInterface::class);
        $this->lambdaApiResponse = new Response(200, self::REQUEST_HEADERS, stream_for('{}'));
        $this->applicationResponse = new Response(200, [], 'body content');

        Phake::when($this->handler)->handle(Phake::anyParameters())->thenReturn($this->applicationResponse);
    }

    /**
     * @param ResponseInterface[] $responses
     * @return LambdaRuntime
     */
    private function getRuntimeInstance(array $responses)
    {
        $mock = new MockHandler($responses);
        $history = Middleware::history($this->requestHistory);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);

        return new LambdaRuntime($this->handler, $httpClient);
    }

    public function testSuccessfulPollForElbRequest()
    {
        $runtime = $this->getRuntimeInstance([ $this->lambdaApiResponse, new Response() ]);

        $runtime->poll();

        // we expect 2 requests, one to get the event and one successful response
        $this->assertCount(2, $this->requestHistory);

        $request = $this->requestHistory[1]['request'];

        // assert the invocation response API is called, indicating a successful poll
        $this->assertEquals(self::INVOCATION_RESPONSE_URL, (string) $request->getUri());
    }

    public function testSuccessfulPollForSnsEvents()
    {
        $runtime = $this->getRuntimeInstance([
            $this->lambdaApiResponse->withBody(stream_for(fopen(__DIR__ . '/../Message/Fixture/SnsEvents.json', 'r'))),
            new Response()
        ]);

        $runtime->poll();
        $runtime->poll();

        // verify the invocation response endpoint is only called once
        $this->assertCount(2, $this->requestHistory);
        $request = $this->requestHistory[1]['request'];

        // assert the invocation response API is called, indicating a successful poll
        $this->assertEquals(self::INVOCATION_RESPONSE_URL, (string) $request->getUri());
    }

    public function testFailedEventInBatch()
    {
        Phake::when($this->handler)->handle(Phake::anyParameters())
            ->thenReturn($this->applicationResponse)
            ->thenReturn(new Response(500, [], 'Error message'));

        $runtime = $this->getRuntimeInstance([
            $this->lambdaApiResponse->withBody(stream_for(fopen(__DIR__ . '/../Message/Fixture/SnsEvents.json', 'r'))),
            new Response()
        ]);

        $runtime->poll();
        $runtime->poll();

        $this->assertCount(2, $this->requestHistory);
        $request = $this->requestHistory[1]['request'];

        // verify invocation error endpoint is called
        $this->assertEquals(self::INVOCATION_ERROR_URL, (string) $request->getUri());
        $this->assertJsonStringEqualsJsonString(
            '{"errorMessage":"Error message","errorType":"InvocationError"}',
            (string) $request->getBody()
        );
    }

    public function testPollReturnsEarlyOnNonEvent()
    {
        $runtime = $this->getRuntimeInstance([ new Response(), new Response() ]);

        $runtime->poll();

        Phake::verifyNoInteraction($this->handler);
    }
}
