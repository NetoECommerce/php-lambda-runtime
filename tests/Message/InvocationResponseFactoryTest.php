<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Neto\Lambda\Message\InvocationResponseFactory;
use PHPUnit\Framework\TestCase;

class InvocationResponseFactoryTest extends TestCase
{
    public function testCreatingInvocationResponseFromElbRequest()
    {
        $request = new ServerRequest('POST', '', [ 'x-invocation-source' => 'aws:elb' ]);
        $response = new Response(201, [ 'foo' => [ 'bar', 'baz' ] ], '{"foo":"bar"}');
        $request = InvocationResponseFactory::createRequestFromApplicationResponse($request, $response);
        $parsedBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(201, $parsedBody['statusCode']);
        $this->assertEquals('201 Created', $parsedBody['statusDescription']);
        $this->assertEquals([ 'foo' => 'bar, baz' ], $parsedBody['headers']);
        $this->assertEquals('{"foo":"bar"}', $parsedBody['body']);
        $this->assertFalse($parsedBody['isBase64Encoded']);
    }

    public function testCreatingInvocationResponseWithNoHeaders()
    {
        $request = new ServerRequest('POST', '', [ 'x-invocation-source' => 'aws:elb' ]);
        $response = new Response(200, [], '');
        $request = InvocationResponseFactory::createRequestFromApplicationResponse($request, $response);
        $parsedBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals([], $parsedBody['headers']);

        $request = new ServerRequest('POST', '', [ 'x-invocation-source' => 'aws:apigateway' ]);
        $request = InvocationResponseFactory::createRequestFromApplicationResponse($request, $response);
        $parsedBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals([], $parsedBody['headers']);

        $request = new ServerRequest('POST', '', [ 'x-invocation-source' => 'aws:apigateway2' ]);
        $request = InvocationResponseFactory::createRequestFromApplicationResponse($request, $response);
        $parsedBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals([], $parsedBody['headers']);
    }

    public function testCreatingInvocationResponseFromApiGatewayRequest()
    {
        $request = new ServerRequest('POST', '', [ 'x-invocation-source' => 'aws:apigateway' ]);
        $response = new Response(201, [ 'foo' => [ 'bar', 'baz' ] ], '{"foo":"bar"}');
        $request = InvocationResponseFactory::createRequestFromApplicationResponse($request, $response);
        $parsedBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(201, $parsedBody['statusCode']);
        $this->assertEquals([ 'foo' => 'bar, baz' ], $parsedBody['headers']);
        $this->assertEquals([ 'foo' => [ 'bar', 'baz' ] ], $parsedBody['multiValueHeaders']);
        $this->assertEquals('{"foo":"bar"}', $parsedBody['body']);
        $this->assertFalse($parsedBody['isBase64Encoded']);
        $this->assertArrayNotHasKey('statusDescription', $parsedBody);
    }

    public function testCreatingInvocationResponseFromHttpApiRequest()
    {
        $request = new ServerRequest('POST', '', [ 'x-invocation-source' => 'aws:apigateway2' ]);
        $response = new Response(200, [ 'foo' => [ 'bar', 'baz' ] ], '{"foo":"bar"}');
        $request = InvocationResponseFactory::createRequestFromApplicationResponse($request, $response);
        $parsedBody = json_decode((string) $request->getBody(), true);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(200, $parsedBody['statusCode']);
        $this->assertEquals([ 'foo' => 'bar, baz' ], $parsedBody['headers']);
        $this->assertEquals([ 'foo' => [ 'bar', 'baz' ] ], $parsedBody['multiValueHeaders']);
        $this->assertEquals('{"foo":"bar"}', $parsedBody['body']);
        $this->assertFalse($parsedBody['isBase64Encoded']);
        $this->assertArrayNotHasKey('statusDescription', $parsedBody);
    }

    public function testCreatingInvocationResponseFromEventRequest()
    {
        $request = new ServerRequest('POST', '', [ 'x-invocation-source' => 'aws:sns' ]);
        $response = new Response(200, [], 'event message');
        $request = InvocationResponseFactory::createRequestFromApplicationResponse($request, $response);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('event message', (string) $request->getBody());
    }
}
