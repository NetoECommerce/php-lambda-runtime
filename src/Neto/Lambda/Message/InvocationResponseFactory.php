<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class InvocationResponseFactory
 * @package Neto\Lambda\Message
 */
class InvocationResponseFactory
{
    /**
     * Converts a response from the application into a request to send to the Lambda invocation response API
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ServerRequestInterface
     */
    public static function createRequestFromApplicationResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ServerRequestInterface {
        $invocationSource = $request->getHeaderLine('x-invocation-source');
        $headers = $response->getHeaders();
        $body = (string) $response->getBody();

        foreach ($headers as &$values) {
            $values = implode(", ", $values);
        }

        if ($invocationSource === 'aws:elb') {
            $body = json_encode([
                'statusCode'        => $response->getStatusCode(),
                'statusDescription' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
                'headers'           => $headers ?: [],
                'isBase64Encoded'   => false,
                'body'              => (string) $response->getBody(),
            ]);
        } elseif (in_array($invocationSource, [ 'aws:apigateway', 'aws:apigateway2' ])) {
            $body = json_encode([
                'statusCode'        => $response->getStatusCode(),
                'headers'           => $headers ?: [],
                'multiValueHeaders' => $response->getHeaders(),
                'isBase64Encoded'   => false,
                'body'              => (string) $response->getBody(),
            ]);
        }

        return new ServerRequest(
            'POST',
            '',
            [],
            $body
        );
    }
}
