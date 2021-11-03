<?php declare(strict_types=1);

namespace Neto\Lambda\Runtime;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ServerRuntime
 *
 * Strategy for processing requests and outputting responses when run from
 * PHPs built-in web server.
 *
 * @package Neto\Lambda\Runtime
 */
class ServerRuntime implements RuntimeInterface
{
    /** @var RequestHandlerInterface */
    private $requestHandler;

    /**
     * ServerRuntime constructor.
     * @param RequestHandlerInterface $requestHandler
     */
    public function __construct(RequestHandlerInterface $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function run()
    {
        $request = ServerRequest::fromGlobals();
        $request = $request->withAddedHeader('lambda-runtime-aws-request-id', \uniqid());
        $this->send($this->requestHandler->handle($request));
    }

    /**
     * @param ResponseInterface $response
     * @return void
     */
    public function send(ResponseInterface $response)
    {
        $status = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($status, true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        echo (string) $response->getBody();
    }
}
