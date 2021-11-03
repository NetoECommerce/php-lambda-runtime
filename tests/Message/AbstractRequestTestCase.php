<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractRequestTestCase extends TestCase
{
    const RUNTIME_HEADERS = [
        'Lambda-Runtime-Aws-Request-Id' => [ '8476a536-e9f4-11e8-9739-2dfe598c3fcd' ],
        'Lambda-Runtime-Deadline-Ms' => [ '1542409706888' ],
        'Lambda-Runtime-Invoked-Function-Arn' => [ 'arn:aws:lambda:us-east-2:123456789012:function:custom-runtime' ],
        'Lambda-Runtime-Trace-Id' => [ 'Root=1-5bef4de7-ad49b0e87f6ef6c87fc2e700;Parent=9a9197af755a6419' ]
    ];

    const REQUEST_HEADERS = [];
    const REQUEST_METHOD = 'GET';
    const REQUEST_SCHEME = '';
    const REQUEST_HOST = '';
    const REQUEST_PATH = '';
    const REQUEST_QUERY = '';
    const REQUEST_BODY = '';

    abstract protected function getRequest(): ServerRequestInterface;

    abstract protected function getEventBody();

    protected function getEvent()
    {
        return new Response(
            200,
            static::RUNTIME_HEADERS,
            $this->getEventBody()
        );
    }

    public function testHeadersArePopulated()
    {
        $request = $this->getRequest();
        $expectedHeaders = array_merge(static::RUNTIME_HEADERS, static::REQUEST_HEADERS);

        foreach ($expectedHeaders as $header => $values) {
            $this->assertTrue($request->hasHeader($header), "Header $header has not been set");
            $this->assertEquals($values, $request->getHeader($header));
        }
    }

    public function testHttpMethodIsSet()
    {
        $request = $this->getRequest();
        $this->assertEquals(static::REQUEST_METHOD, $request->getMethod());
    }

    public function testBodyIsPopulated()
    {
        $request = $this->getRequest();
        $this->assertEquals(static::REQUEST_BODY, (string) $request->getBody());
    }

    public function testUriSchemeIsCorrect()
    {
        $uri = $this->getRequest()->getUri();
        $this->assertEquals(static::REQUEST_SCHEME, $uri->getScheme());
    }

    public function testUriHostIsCorrect()
    {
        $uri = $this->getRequest()->getUri();
        $this->assertEquals(static::REQUEST_HOST, $uri->getHost());
    }

    public function testUriPathIsCorrect()
    {
        $uri = $this->getRequest()->getUri();
        $this->assertEquals(static::REQUEST_PATH, $uri->getPath());
    }

    public function testUriQueryIsCorrect()
    {
        $uri = $this->getRequest()->getUri();
        $this->assertEquals(static::REQUEST_QUERY, $uri->getQuery());
    }
}
