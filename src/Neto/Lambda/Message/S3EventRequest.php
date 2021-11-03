<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use Psr\Http\Message\StreamInterface;
use function GuzzleHttp\Psr7\stream_for;

final class S3EventRequest extends EventRequest
{
    public function getBody(): StreamInterface
    {
        return stream_for(json_encode($this->eventPayload['s3']));
    }

    public static function getEventSource(): string
    {
        return 'aws:s3';
    }
}
