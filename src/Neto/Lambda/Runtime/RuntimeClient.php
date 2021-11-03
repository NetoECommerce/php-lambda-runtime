<?php declare(strict_types=1);

namespace Neto\Lambda\Runtime;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RuntimeClient
 *
 * Handles communication between the runtime library and the AWS Lambda invocation API
 *
 * @package Neto\Lambda\Runtime
 */
class RuntimeClient
{
    /** @var ClientInterface */
    private $httpClient;

    /** @var string */
    private $baseUri;

    /** @var string */
    private $requestId;

    /**
     * RuntimeClient constructor.
     * @param ClientInterface $httpClient
     * @param string $baseUri
     */
    public function __construct(ClientInterface $httpClient, $baseUri)
    {
        $this->httpClient = $httpClient;
        $this->baseUri = $baseUri;
    }

    /**
     * Fetches the next event from the Lambda invocation API
     *
     * Returns null if no event is fetched, otherwise an unmodified Response object containing the next event to handle
     *
     * @return ResponseInterface|null
     * @throws GuzzleException
     */
    public function getNextInvocation()
    {
        $this->requestId = null;

        try {
            // check runtime API for a new event
            $event = $this->httpClient->request('GET', '/2018-06-01/runtime/invocation/next', [
                'base_uri' => $this->baseUri
            ]);
        } catch (GuzzleException $e) {
            $this->sendInitialisationError($e->getMessage(), 'InvocationError');
            return null;
        }

        // ensure we have a request id from the runtime api
        if (!$event->hasHeader('lambda-runtime-aws-request-id')) {
            $this->sendInitialisationError('Event data is absent.', 'MissingEventData');
            return null;
        }

        // store the request id for later, we'll need it to respond
        $this->requestId = $event->getHeader('lambda-runtime-aws-request-id')[0];

        return $event;
    }

    /**
     * Sends a successful response back to the Lambda Runtime API
     *
     * @param ServerRequestInterface $request
     * @return void
     * @throws GuzzleException
     */
    public function sendInvocationResponse(ServerRequestInterface $request)
    {
        $uri = new Uri($this->baseUri . '/2018-06-01/runtime/invocation/' . $this->requestId . '/response');

        $this->httpClient->send($request->withUri($uri));
    }

    /**
     * Sends an application error to the Lambda Runtime API
     *
     * @param string $errorMessage
     * @param string $errorType
     * @return void
     * @throws GuzzleException
     */
    public function sendInvocationError($errorMessage, $errorType)
    {
        $this->httpClient->request(
            'POST',
            '/2018-06-01/runtime/invocation/' . $this->requestId . '/error',
            [
                'json' => [
                    'errorMessage' => $errorMessage,
                    'errorType'    => $errorType
                ],
                'base_uri' => $this->baseUri
            ]
        );
    }

    /**
     * Sends an initialisation error to the Lambda Runtime API
     *
     * @param string $errorMessage
     * @param string $errorType
     * @return void
     * @throws GuzzleException
     */
    public function sendInitialisationError($errorMessage, $errorType)
    {
        $this->httpClient->request(
            'POST',
            '/2018-06-01/runtime/init/error',
            [
                'json' => [
                    'errorMessage' => $errorMessage,
                    'errorType'    => $errorType
                ],
                'base_uri' => $this->baseUri
            ]
        );
    }
}
