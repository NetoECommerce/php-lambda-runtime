<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\UriFactory;
use PHPUnit\Framework\TestCase;

class UriFactoryTest extends TestCase
{
    public function testCreatingUriFromParts()
    {
        $uri = UriFactory::createUriFromParts(
            'https',
            'lambda-846800462-us-east-2.elb.amazonaws.com',
            666,
            '/hello/world',
            'foo=bar&bar=baz'
        );

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('lambda-846800462-us-east-2.elb.amazonaws.com', $uri->getHost());
        $this->assertEquals(666, $uri->getPort());
        $this->assertEquals('/hello/world', $uri->getPath());
        $this->assertEquals('foo=bar&bar=baz', $uri->getQuery());
        $this->assertEquals(
            'https://lambda-846800462-us-east-2.elb.amazonaws.com:666/hello/world?foo=bar&bar=baz',
            (string) $uri
        );
    }

    public function testCreatingDefaultUriFromParts()
    {
        $uri = UriFactory::createUriFromParts();

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('localhost', $uri->getHost());
        $this->assertEquals('/', $uri->getPath());
        $this->assertEquals('', $uri->getQuery());
        $this->assertEquals('https://localhost/', (string) $uri);
    }
}
