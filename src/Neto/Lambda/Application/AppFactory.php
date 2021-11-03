<?php declare(strict_types=1);

namespace Neto\Lambda\Application;

use GuzzleHttp\Client;
use Neto\Container\SimpleContainer;
use Neto\Lambda\Middleware\MiddlewareDispatcher;
use Neto\Lambda\Runtime\CommandLineRuntime;
use Neto\Lambda\Runtime\LambdaRuntime;
use Neto\Lambda\Runtime\ServerRuntime;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class AppFactory
 * @package Neto\Lambda\Application
 */
final class AppFactory
{
    /**
     * Creates an instance of App, taking invocation environment into account.
     *
     * Different Runtimes are required if the function is being invoked from
     * the command-line, built-in web server or the actual Lambda environment.
     *
     * @param null|string $context
     * @param null|RequestHandlerInterface $handler
     * @param null|ContainerInterface $container
     * @return App
     */
    public static function create(
        ?string $context = null,
        ?RequestHandlerInterface $handler = null,
        ?ContainerInterface $container = null
    ): App {
        $context = $context ?? \getenv('_CONTEXT');
        $handler = $handler ?? new MiddlewareDispatcher();
        $container = $container ?? new SimpleContainer();

        switch ($context) {
            // check if we're invoking from the command line
            case 'cli':
                $runtime = new CommandLineRuntime($handler);
                break;

            // or if we're using PHPs built-in web server
            case 'cli-server':
                $runtime = new ServerRuntime($handler);
                break;

            // otherwise assume we're actually running this in AWS Lambda environment
            default:
                $httpClient = new Client();
                $runtime = new LambdaRuntime($handler, $httpClient);
                break;
        }

        return new App($runtime, $handler, $container);
    }
}
