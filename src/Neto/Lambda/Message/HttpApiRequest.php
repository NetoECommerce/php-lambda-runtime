<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class HttpApiRequest extends ServerRequest implements EventSourceInterface
{
    /** @var string */
    private $version = '2.0';

    /**
     * ApiGatewayRequest constructor.
     * @param ResponseInterface $event
     * @param array $eventPayload
     */
    public function __construct(ResponseInterface $event, array $eventPayload)
    {
        $headers = array_change_key_case($eventPayload['headers'] ?? []);
        $headers = array_merge($event->getHeaders(), $headers);
        $method = $eventPayload['requestContext']['http']['method'] ?? 'GET';
        $body = $eventPayload['body'] ?? '';

        if ($eventPayload['isBase64Encoded'] && $body) {
            $body = base64_decode($body);
        }

        $uri = UriFactory::createUriFromParts(
            $headers['x-forwarded-proto'] ?? 'https',
            $eventPayload['requestContext']['domainName'] ?? 'localhost',
            $headers['x-forwarded-port'] ?? 443,
            $eventPayload['requestContext']['http']['path'] ?? '/',
            $eventPayload['rawQueryString'] ?? ''
        );

        $this->setVersion($eventPayload['version']);

        parent::__construct($method, $uri, $headers, $body);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return HttpApiRequest
     */
    public function setVersion($version): HttpApiRequest
    {
        $this->version = $version;
        return $this;
    }

    public static function hasEventSource(array $eventPayload): bool
    {
        // HttpGateway request will contain request context and version elements
        return isset($eventPayload['requestcontext'], $eventPayload['version']);
    }

    public static function getEventSource(): string
    {
        return 'aws:apigateway2';
    }
}
