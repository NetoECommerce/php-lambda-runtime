<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\KinesisEventRequest;
use Psr\Http\Message\ServerRequestInterface;

class KinesisEventRequestTest extends AbstractRequestTestCase
{
    const REQUEST_BODY = 'Hello, this is a test.';

    protected function getRequest(): ServerRequestInterface
    {
        $eventPayload = json_decode($this->getEventBody(), true);
        $record = current($eventPayload['Records']);
        return new KinesisEventRequest($this->getEvent(), $record);
    }

    protected function getEventBody()
    {
        return file_get_contents(__DIR__ . '/Fixture/KinesisEvents.json');
    }
}
