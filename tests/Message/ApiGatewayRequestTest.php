<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\ApiGatewayRequest;
use Psr\Http\Message\ServerRequestInterface;

class ApiGatewayRequestTest extends AbstractRequestTestCase
{
    const REQUEST_HEADERS = [
        'Accept' => [ '*/*' ],
        'Accept-Encoding' => [ 'gzip, deflate' ],
        'cache-control' => [ 'no-cache' ],
        'CloudFront-Forwarded-Proto' => [ 'https' ],
        'CloudFront-Is-Desktop-Viewer' => [ 'true' ],
        'CloudFront-Is-Mobile-Viewer' => [ 'false' ],
        'CloudFront-Is-SmartTV-Viewer' => [ 'false' ],
        'CloudFront-Is-Tablet-Viewer' => [ 'false' ],
        'CloudFront-Viewer-Country' => [ 'US' ],
        'Content-Type' => [ 'application/json' ],
        'headerName' => [ 'headerValue' ],
        'Host' => [ 'gy415nuibc.execute-api.us-east-1.amazonaws.com' ],
        'User-Agent' => [ 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6)' ],
        'Via' => [ '1.1 d98420743a69852491bbdea73f7680bd.cloudfront.net (CloudFront)' ],
        'X-Amz-Cf-Id' => [ 'pn-PWIJc6thYnZm5P0NMgOUglL1DYtl0gdeJky8tqsg8iS_sgsKD1A==' ],
        'X-Forwarded-For' => [ '54.240.196.186, 54.182.214.83' ],
        'X-Forwarded-Port' => [ '443' ],
        'X-Forwarded-Proto' => [ 'https' ],
    ];
    const REQUEST_METHOD = 'POST';
    const REQUEST_SCHEME = 'https';
    const REQUEST_HOST = 'gy415nuibc.execute-api.us-east-1.amazonaws.com';
    const REQUEST_PATH = '/hello/world';
    const REQUEST_QUERY = 'foo=bar&multivalueName=value';
    const REQUEST_BODY = '{"foo":"bar"}';

    protected function getRequest(): ServerRequestInterface
    {
        return new ApiGatewayRequest($this->getEvent(), json_decode($this->getEventBody(), true));
    }

    protected function getEventBody()
    {
        return file_get_contents(__DIR__ . '/Fixture/ApiGatewayRequest.json');
    }
}
