<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Application;

use Neto\Lambda\Application\AppFactory;
use Neto\Lambda\Runtime\CommandLineRuntime;
use Neto\Lambda\Runtime\LambdaRuntime;
use Neto\Lambda\Runtime\ServerRuntime;
use Phake;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AppFactoryTest extends TestCase
{
    public function runtimeProvider()
    {
        // params: expected class, context
        return [
            'Lambda context' => [ LambdaRuntime::class, '' ],
            'Server context' => [ ServerRuntime::class, 'cli-server' ],
            'CLI context'    => [ CommandLineRuntime::class, 'cli' ]
        ];
    }

    /**
     * @param $expectedClass string
     * @param $context string
     * @dataProvider runtimeProvider
     */
    public function testAppCreation($expectedClass, $context)
    {
        $app = AppFactory::create($context);

        $this->assertInstanceOf($expectedClass, $app->getRuntime());
    }

    public function testParametersAreSetCorrectly()
    {
        $handler = Phake::mock(RequestHandlerInterface::class);
        $container = Phake::mock(ContainerInterface::class);
        $app = AppFactory::create('', $handler, $container);

        $this->assertSame($handler, $app->getDispatcher());
        $this->assertSame($container, $app->getContainer());
    }
}
