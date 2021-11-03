<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\ElbRequest;
use Psr\Http\Message\ServerRequestInterface;

final class ElbRequestTest extends AbstractRequestTestCase
{
    const REQUEST_HEADERS = [
        'accept' => [ 'text/html,application/xhtml+xml' ],
        'accept-language' => [ 'en-US,en;q=0.8' ],
        'content-type' => [ 'text/plain' ],
        'cookie' => [ 'cookies' ],
        'host' => [ 'lambda-846800462-us-east-2.elb.amazonaws.com' ],
        'user-agent' => [ 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6)' ],
        'x-amzn-trace-id' => [ 'Root=1-5bdb40ca-556d8b0c50dc66f0511bf520' ],
        'x-forwarded-for' => [ '72.21.198.66' ],
        'x-forwarded-port' => [ '443' ],
        'x-forwarded-proto' => [ 'https' ]
    ];

    const REQUEST_METHOD = 'POST';
    const REQUEST_SCHEME = 'https';
    const REQUEST_HOST = 'lambda-846800462-us-east-2.elb.amazonaws.com';
    const REQUEST_PATH = '/hello/world';
    const REQUEST_QUERY = 'foo=bar&multivalueName=value';
    const REQUEST_BODY = '{"foo":"bar"}';

    protected function getRequest(): ServerRequestInterface
    {
        return new ElbRequest($this->getEvent(), json_decode($this->getEventBody(), true));
    }

    protected function getEventBody()
    {
        return file_get_contents(__DIR__ . '/Fixture/ElbRequest.json');
    }
}
