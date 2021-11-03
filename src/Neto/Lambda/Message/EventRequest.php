<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

abstract class EventRequest extends ServerRequest implements EventSourceInterface
{
    /**
     * @inheritDoc
     */
    abstract public static function getEventSource(): string;

    /** @var array */
    protected $eventPayload;

    /**
     * EventRequest constructor.
     * @param ResponseInterface $event
     * @param array $eventPayload
     */
    public function __construct(ResponseInterface $event, array $eventPayload)
    {
        $this->setEventPayload($eventPayload);
        parent::__construct('GET', new Uri(), $event->getHeaders());
    }

    /**
     * @return array
     */
    public function getEventPayload(): array
    {
        return $this->eventPayload;
    }

    /**
     * @param array $eventPayload
     * @return EventRequest
     */
    public function setEventPayload(array $eventPayload): EventRequest
    {
        $this->eventPayload = $eventPayload;
        return $this;
    }

    public static function hasEventSource(array $eventPayload): bool
    {
        if (isset($eventPayload['records'])) {
            $firstRecord = current($eventPayload['records']);

            $source = $firstRecord['eventSource'] ?? $firstRecord['EventSource'];

            return $source === static::getEventSource();
        }
        return false;
    }
}
