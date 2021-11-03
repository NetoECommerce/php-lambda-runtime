<?php declare(strict_types=1);

namespace Neto\Lambda\Middleware;

use Neto\Lambda\Message\ErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ExceptionHandler
 *
 * This middleware, when added at the start of a queue, allows us to catch
 * exceptions further up the queue and return a standardised ErrorResponse.
 *
 * This also allows us to throw exceptions in our middleware and not worry
 * about the creation and structure of a suitable error response.
 *
 * @package Neto\Lambda\Middleware
 */
class ExceptionHandler implements MiddlewareInterface
{
    /** @var bool */
    private $logStackTrace;

    /** @var resource */
    private $outputHandler;

    /**
     * ExceptionHandler constructor.
     * @param bool $logStackTrace Adds stacktrace to logs when an exception is caught
     * @param resource $outputHandler
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct($logStackTrace = false, $outputHandler = null)
    {
        $this->logStackTrace = $logStackTrace;
        $this->outputHandler = $outputHandler ?? fopen('php://stdout', 'w');
    }

    /**
     * Wraps subsequent middleware in a try/catch block and returns a standard
     * ErrorResponse if an exception is thrown
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Exception $e) {
            $status = (int) $e->getCode();
            $jsonBody = [
                'success' => false,
                'errorType' => get_class($e),
                'errorMessage' => $e->getMessage()
            ];

            fwrite($this->outputHandler, 'Caught exception ' . get_class($e) . ': ');
            fwrite($this->outputHandler, $e->getMessage() . PHP_EOL);

            if ($this->logStackTrace) {
                fwrite($this->outputHandler, $e->getTraceAsString() . PHP_EOL);
                $jsonBody['stacktrace'] = $e->getTrace();
            }

            if ($status < 400 || $status >= 600) {
                $status = 500;
            }

            return new ErrorResponse($status, ['content-type'=>'application/json'], json_encode($jsonBody));
        }
    }
}
