<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ApiGatewayRequest extends ServerRequest implements EventSourceInterface
{
    /**
     * ApiGatewayRequest constructor.
     * @param ResponseInterface $event
     * @param array $eventPayload
     */
    public function __construct(ResponseInterface $event, array $eventPayload)
    {
        $headers = array_change_key_case($eventPayload['headers'] ?? []);
        $headers = array_merge($event->getHeaders(), $headers);
        $method = $eventPayload['httpMethod'] ?? 'GET';
        $body = $eventPayload['body'] ?? '';

        if ($eventPayload['isBase64Encoded'] && $body) {
            $body = base64_decode($body);
        }

        $uri = UriFactory::createUriFromParts(
            $headers['x-forwarded-proto'] ?? 'https',
            $headers['host'] ?? 'localhost',
            $headers['x-forwarded-port'] ?? 443,
            $eventPayload['path'] ?? '/',
            http_build_query($eventPayload['queryStringParameters'] ?? [])
        );

        parent::__construct($method, $uri, $headers, $body);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return '1.0';
    }

    public static function hasEventSource(array $eventPayload): bool
    {
        // HttpGateway request will contain request context and version is 1.0
        return (isset($eventPayload['requestcontext']) &&
            isset($eventPayload['version']) &&
            $eventPayload['version'] === '1.0' );
    }

    public static function getEventSource(): string
    {
        return 'aws:apigateway';
    }
}
