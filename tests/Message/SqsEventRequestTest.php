<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\SqsEventRequest;
use Psr\Http\Message\ServerRequestInterface;

class SqsEventRequestTest extends AbstractRequestTestCase
{
    const REQUEST_BODY = 'Hello from SQS!';

    protected function getRequest(): ServerRequestInterface
    {
        $eventPayload = json_decode($this->getEventBody(), true);
        $record = current($eventPayload['Records']);
        return new SqsEventRequest($this->getEvent(), $record);
    }

    protected function getEventBody()
    {
        return file_get_contents(__DIR__ . '/Fixture/SqsEvents.json');
    }

}
