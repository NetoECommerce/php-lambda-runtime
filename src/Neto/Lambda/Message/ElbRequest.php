<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ElbRequest extends ServerRequest implements EventSourceInterface
{
    /**
     * ElbRequest constructor.
     * @param ResponseInterface $event
     * @param array $eventPayload
     */
    public function __construct(ResponseInterface $event, array $eventPayload)
    {
        $body = $eventPayload['isBase64Encoded'] ? base64_decode($eventPayload['body']) : $eventPayload['body'];
        $method = $eventPayload['httpMethod'];
        $headers = array_change_key_case($eventPayload['headers'] ?? []);
        $headers = array_merge($event->getHeaders(), $headers);
        $path = $eventPayload['path'] ?? '/';

        $uri = UriFactory::createUriFromParts(
            $headers['x-forwarded-proto'] ?? 'https',
            $headers['host'] ?? 'localhost',
            $headers['port'] ?? 443,
            $path,
            http_build_query($eventPayload['queryStringParameters'])
        );

        parent::__construct($method, $uri, $headers, $body);
    }

    public static function hasEventSource($eventPayload): bool
    {
        return isset($eventPayload['requestcontext']['elb']);
    }

    public static function getEventSource(): string
    {
        return 'aws:elb';
    }
}
