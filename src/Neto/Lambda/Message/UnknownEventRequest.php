<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use Psr\Http\Message\StreamInterface;
use function GuzzleHttp\Psr7\stream_for;

final class UnknownEventRequest extends EventRequest
{
    /**
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return stream_for(json_encode($this->getEventPayload()));
    }

    public static function getEventSource(): string
    {
        return 'aws:unknown';
    }
}
