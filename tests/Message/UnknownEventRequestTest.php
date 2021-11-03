<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use Neto\Lambda\Message\UnknownEventRequest;
use Psr\Http\Message\ServerRequestInterface;

class UnknownEventRequestTest extends AbstractRequestTestCase
{
    const REQUEST_BODY = '{"foo":"bar"}';

    protected function getRequest(): ServerRequestInterface
    {
        return new UnknownEventRequest($this->getEvent(), json_decode($this->getEventBody(), true));
    }

    protected function getEventBody()
    {
        return static::REQUEST_BODY;
    }
}
