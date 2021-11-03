<?php
namespace Neto\Lambda\Middleware\Fixture;

use Phake;
use Psr\Http\Message\ResponseInterface;

class FooController
{
    public function barAction()
    {
        return Phake::mock(ResponseInterface::class);
    }

    public function nullAction()
    {
        return null;
    }
}
