<?php declare(strict_types=1);

namespace Neto\Lambda\Runtime;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CommandLineRuntime
 *
 * This strategy is used when invoking from the command line.
 *
 * @package Neto\Lambda\Runtime
 */
class CommandLineRuntime implements RuntimeInterface
{
    /** @var RequestHandlerInterface */
    private $requestHandler;

    /** @var resource */
    private $outputHandler;

    /** @var array */
    private $headers;

    /** @var string */
    private $body;

    /**
     * CommandLineRuntime constructor.
     *
     * @param RequestHandlerInterface $requestHandler
     * @param resource $outputHandler
     */
    public function __construct(RequestHandlerInterface $requestHandler, $outputHandler = null)
    {
        $this->requestHandler = $requestHandler;
        $this->outputHandler = $outputHandler ?? STDOUT;
        $this->body = \getenv('_PAYLOAD') ?? '{}';
        $this->headers = [
            'lambda-runtime-aws-request-id' => \uniqid()
        ];
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $request = new ServerRequest('GET', '', $this->headers, $this->body);
        $this->send($this->requestHandler->handle($request));
    }

    /**
     * Outputs the application response to the output handler in a human-readable format
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function send(ResponseInterface $response)
    {
        fwrite($this->outputHandler, 'Status code ' . $response->getStatusCode() . PHP_EOL . PHP_EOL);

        fwrite($this->outputHandler, 'Headers' . PHP_EOL);
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                fwrite($this->outputHandler, "$name: $value" . PHP_EOL);
            }
        }

        fwrite($this->outputHandler, PHP_EOL . 'Response body' . PHP_EOL);
        fwrite($this->outputHandler, $response->getBody() . PHP_EOL);
    }
}
