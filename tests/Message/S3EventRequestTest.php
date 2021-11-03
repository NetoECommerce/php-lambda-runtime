<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\S3EventRequest;
use Psr\Http\Message\ServerRequestInterface;

class S3EventRequestTest extends AbstractRequestTestCase
{
    const REQUEST_BODY = '{"bucket":{"name":"example-bucket"},"object":{"key":"test\/key"}}';

    protected function getRequest(): ServerRequestInterface
    {
        $eventPayload = json_decode($this->getEventBody(), true);
        $record = current($eventPayload['Records']);
        return new S3EventRequest($this->getEvent(), $record);
    }

    protected function getEventBody()
    {
        return file_get_contents(__DIR__ . '/Fixture/S3Events.json');
    }
}
