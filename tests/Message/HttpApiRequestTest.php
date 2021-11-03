<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\HttpApiRequest;
use Psr\Http\Message\ServerRequestInterface;

final class HttpApiRequestTest extends AbstractRequestTestCase
{
    const REQUEST_HEADERS = [
        'accept' => [ 'application/json' ],
        'accept-encoding' => [ 'gzip, deflate, br' ],
        'accept-language' => [ 'en-US,en;q=0.9' ],
        'content-length' => [ '0' ],
        'host' => [ 'r3pmxmplak.execute-api.us-east-2.amazonaws.com' ],
        'sec-fetch-dest' => [ 'document' ],
        'sec-fetch-mode' => [ 'navigate' ],
        'sec-fetch-site' => [ 'cross-site' ],
        'sec-fetch-user' => [ '?1' ],
        'upgrade-insecure-requests' => [ '1' ],
        'user-agent' => [ 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6)' ],
        'x-amzn-trace-id' => [ 'Root=1-5e6722a7-cc56xmpl46db7ae02d4da47e' ],
        'x-forwarded-for' => [ '205.255.255.176' ],
        'x-forwarded-port' => [ '443' ],
        'x-forwarded-proto' => [ 'https' ],
    ];
    const REQUEST_METHOD = 'POST';
    const REQUEST_SCHEME = 'https';
    const REQUEST_HOST = 'r3pmxmplak.execute-api.us-east-2.amazonaws.com';
    const REQUEST_PATH = '/hello/world';
    const REQUEST_QUERY = 'parameter1=value1&parameter1=value2&parameter2=value';
    const REQUEST_BODY = '{"foo":"bar"}';

    protected function getRequest(): ServerRequestInterface
    {
        return new HttpApiRequest($this->getEvent(), json_decode($this->getEventBody(), true));
    }

    protected function getEventBody()
    {
        return file_get_contents(__DIR__ . '/Fixture/HttpApiRequest.json');
    }
}
