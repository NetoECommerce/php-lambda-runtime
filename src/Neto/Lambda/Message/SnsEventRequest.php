<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use Psr\Http\Message\StreamInterface;
use function GuzzleHttp\Psr7\stream_for;

final class SnsEventRequest extends EventRequest
{
    public function getBody(): StreamInterface
    {
        return stream_for($this->eventPayload['Sns']['Message']);
    }

    public static function getEventSource(): string
    {
        return 'aws:sns';
    }
}
