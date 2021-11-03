<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class InvocationRequestFactory
 * @package Neto\Lambda\Message
 */
class InvocationRequestFactory
{
    private static $supportedRequests = [
        ApiGatewayRequest::class,
        ElbRequest::class,
        HttpApiRequest::class,
        KinesisEventRequest::class,
        SesEventRequest::class,
        SnsEventRequest::class,
        SqsEventRequest::class,
        S3EventRequest::class,
        UnknownEventRequest::class
    ];

    /**
     * Determines the source of the invocation using the event payload
     *
     * @param array $eventPayload Payload from lambda invocation API
     * @return string
     */
    public static function getInvocationSource(array $eventPayload): string
    {
        // convert keys to lower case, AWS doesn't standardise the naming at all...
        // Records vs records, EventSource vs eventSource, etc
        $eventPayload = array_change_key_case($eventPayload);

        foreach (static::$supportedRequests as $requestClass) {
            if ($requestClass::hasEventSource($eventPayload)) {
                return $requestClass::getEventSource();
            }
        }

        return 'aws:unknown';
    }

    /**
     * Takes the response from the Lambda runtime API and returns an array of PSR-7 Requests to process
     *
     * @param ResponseInterface $event
     * @return ServerRequestInterface[]
     */
    public static function createRequestsFromInvocation(ResponseInterface $event): array
    {
        // deserialise event json to an associative array
        $eventPayload = json_decode((string) $event->getBody(), true);
        $eventSource = self::getInvocationSource($eventPayload);
        $requests = [];

        foreach (static::$supportedRequests as $requestClass) {
            if ($eventSource === $requestClass::getEventSource()) {
                $requests = self::mapEventsToRequestClass($event, $eventPayload, $requestClass);
                break;
            }
        }

        // add the event source as a header to each request and return
        return array_map(function (ServerRequestInterface $request) use ($eventSource) {
            return $request->withAddedHeader('x-invocation-source', $eventSource);
        }, $requests);
    }

    /**
     * Creates an array of request objects from the event payload
     *
     * @param ResponseInterface $event
     * @param array $eventPayload
     * @param string $class
     * @return ServerRequestInterface[]
     */
    private static function mapEventsToRequestClass($event, $eventPayload, $class): array
    {
        $records = $eventPayload['records'] ?? ($eventPayload['Records'] ?? null);
        if (null === $records) {
            // ELB/APIG events are only a single record
            $records = [ $eventPayload ];
        }

        return array_map(function ($record) use ($event, $class): ServerRequestInterface {
            return new $class($event, $record);
        }, $records);
    }
}
