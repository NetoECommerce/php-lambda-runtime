<?php declare(strict_types=1);

namespace Neto\Lambda\Runtime;

use GuzzleHttp\ClientInterface;
use Neto\Lambda\Message\InvocationRequestFactory;
use Neto\Lambda\Message\InvocationResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class LambdaRuntime
 *
 * Strategy used when running an application in the actual Lambda environment
 *
 * @package Neto\Lambda
 */
class LambdaRuntime implements RuntimeInterface
{
    /** @var RuntimeClient */
    private $runtimeClient;

    /** @var ServerRequestInterface[] */
    private $requestBuffer = [];

    /** @var RequestHandlerInterface */
    private $requestHandler;

    /**
     * Runtime constructor.
     *
     * @param ClientInterface $client HTTP client for interacting with AWS invocation API
     * @param RequestHandlerInterface $requestHandler
     */
    public function __construct(RequestHandlerInterface $requestHandler, ClientInterface $client)
    {
        $this->requestHandler = $requestHandler;
        $baseUri = 'http://' . getenv('AWS_LAMBDA_RUNTIME_API');
        $this->runtimeClient = new RuntimeClient($client, $baseUri);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        while (true) {
            $this->poll();
        }
    }

    /**
     * Polls the Lambda invocation endpoint to fetch events
     *
     * Once an event is received it is transformed into an array of PSR-7 compliant requests, the request handler is
     * invoked and the response sent back to the Lambda invocation API.
     *
     * @return void
     */
    public function poll()
    {
        // if buffer is empty, fetch the next event
        if (count($this->requestBuffer) === 0) {
            $event = $this->runtimeClient->getNextInvocation();

            if (null === $event) {
                return;
            }

            $this->requestBuffer = InvocationRequestFactory::createRequestsFromInvocation($event);
        }

        $this->handle(array_shift($this->requestBuffer));
    }

    /**
     * Sends a single request to the application to be processed and then handles the response
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    private function handle(ServerRequestInterface $request)
    {
        // clone the handler to ensure state is reset for each request
        $handler = clone $this->requestHandler;

        // let the application handle the request and get the response
        $response = $handler->handle($request);

        if ($this->isErrorResponse($request, $response)) {
            $this->runtimeClient->sendInvocationError((string) $response->getBody(), 'InvocationError');

            // clear the request buffer - we don't want to continue processing a batched invocation if one fails
            $this->requestBuffer = [];

            return;
        }

        // handle all requests in buffer before sending response
        if (count($this->requestBuffer) > 0) {
            return;
        }

        $this->runtimeClient->sendInvocationResponse(
            InvocationResponseFactory::createRequestFromApplicationResponse($request, $response)
        );
    }

    /**
     * Determines if we should use the invocation response or invocation error API endpoint
     *
     * HTTP-based requests always use the invocation response endpoint so we can notify the client of any errors using
     * application logic (ie: status code, response body, etc).
     *
     * Event-based requests (eg. SNS, SQS, etc) however are a simple pass/fail and the body of the successful response
     * is discarded, so we treat a non-2xx status code as a fail and pass the body (if set) to the invocation error
     * endpoint so the error can be dealt with by a retry, DLQ or forwarded to another lambda.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    private function isErrorResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $invocationSource = $request->getHeaderLine('x-invocation-source');

        // HTTP-based responses should always be treated as "successful"
        // ELB or API Gateway responses should not use the invocation error endpoint unless a fatal error occurs
        if (in_array($invocationSource, [ 'aws:elb', 'aws:apigateway', 'aws:apigateway2' ])) {
            return false;
        }

        // For event-based requests we treat a non-2xx response as a failure
        return $response->getStatusCode() < 200 || $response->getStatusCode() >= 300;
    }
}
