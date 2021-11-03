<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\SnsEventRequest;
use Psr\Http\Message\ServerRequestInterface;

class SnsEventRequestTest extends AbstractRequestTestCase
{
    const REQUEST_BODY = 'example message';

    protected function getRequest(): ServerRequestInterface
    {
        $eventPayload = json_decode($this->getEventBody(), true);
        $record = current($eventPayload['Records']);
        return new SnsEventRequest($this->getEvent(), $record);
    }

    protected function getEventBody()
    {
        return file_get_contents(__DIR__ . '/Fixture/SnsEvents.json');
    }
}
