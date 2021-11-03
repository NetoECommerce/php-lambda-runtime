<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

/**
 * Interface EventSourceInterface
 * @package Neto\Lambda\Message
 */
interface EventSourceInterface
{
    /**
     * Returns true if the request implementation can parse the given event
     *
     * @param array $eventPayload JSON-decoded body of the invocation request
     * @return bool
     */
    public static function hasEventSource(array $eventPayload): bool;

    /**
     * Type of event, eg aws:elb, aws:apigateway, aws:sns, etc.
     *
     * @return string
     */
    public static function getEventSource(): string;
}
