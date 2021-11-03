<?php declare(strict_types=1);

namespace Neto\Lambda\Runtime;

/**
 * A Runtime class handles the specifics of different runtime environments.
 *
 * This is essential in allowing a lambda-based function to be run in a local environment, either from the command-line
 * or from a web server.
 *
 * @package Neto\Lambda\Runtime
 */
interface RuntimeInterface
{
    /**
     * Starts the processing of a request or requests through our request handler/middleware dispatcher.
     *
     * @return void
     */
    public function run();
}
