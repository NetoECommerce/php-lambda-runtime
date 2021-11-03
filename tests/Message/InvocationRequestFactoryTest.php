<?php declare(strict_types=1);

namespace Neto\Lambda\Test\Message;

use GuzzleHttp\Psr7\Response;
use Neto\Lambda\Message\ApiGatewayRequest;
use Neto\Lambda\Message\ElbRequest;
use Neto\Lambda\Message\HttpApiRequest;
use Neto\Lambda\Message\InvocationRequestFactory;
use Neto\Lambda\Message\KinesisEventRequest;
use Neto\Lambda\Message\S3EventRequest;
use Neto\Lambda\Message\SesEventRequest;
use Neto\Lambda\Message\SnsEventRequest;
use Neto\Lambda\Message\SqsEventRequest;
use Neto\Lambda\Message\UnknownEventRequest;
use PHPUnit\Framework\TestCase;

class InvocationRequestFactoryTest extends TestCase
{
    public function eventSourceDataProvider()
    {
        return [
            'Unknown event' => [
                '{}',
                'aws:unknown',
                1,
                UnknownEventRequest::class,
            ],
            'ELB' => [
                file_get_contents(__DIR__ . '/Fixture/ElbRequest.json'),
                'aws:elb',
                1,
                ElbRequest::class,
            ],
            'SNS' => [
                file_get_contents(__DIR__ . '/Fixture/SnsEvents.json'),
                'aws:sns',
                2,
                SnsEventRequest::class,
            ],
            'SQS' => [
                file_get_contents(__DIR__ . '/Fixture/SqsEvents.json'),
                'aws:sqs',
                2,
                SqsEventRequest::class,
            ],
            'S3' => [
                file_get_contents(__DIR__ . '/Fixture/S3Events.json'),
                'aws:s3',
                1,
                S3EventRequest::class,
            ],
            'API Gateway' => [
                file_get_contents(__DIR__ . '/Fixture/ApiGatewayRequest.json'),
                'aws:apigateway',
                1,
                ApiGatewayRequest::class,
            ],
            'API Gateway v2' => [
                file_get_contents(__DIR__ . '/Fixture/HttpApiRequest.json'),
                'aws:apigateway2',
                1,
                HttpApiRequest::class,
            ],
            'Kinesis' => [
                file_get_contents(__DIR__ . '/Fixture/KinesisEvents.json'),
                'aws:kinesis',
                2,
                KinesisEventRequest::class,
            ],
            'SES' => [
                file_get_contents(__DIR__ . '/Fixture/SesEvents.json'),
                'aws:ses',
                1,
                SesEventRequest::class,
            ]
        ];
    }

    /**
     * @dataProvider eventSourceDataProvider
     * @param string $eventBody
     * @param string $expectedSource
     */
    public function testGetInvocationSource($eventBody, $expectedSource)
    {
        $eventSource = InvocationRequestFactory::getInvocationSource(json_decode($eventBody, true));
        $this->assertEquals($expectedSource, $eventSource);
    }

    /**
     * @dataProvider eventSourceDataProvider
     * @param string $eventBody
     * @param string $expectedSource
     * @param integer $expectedRecords
     * @param string $expectedClass
     */
    public function testCreatingRequestFromInvocation($eventBody, $expectedSource, $expectedRecords, $expectedClass)
    {
        $event = new Response(200, [], $eventBody);
        $event = $event->withAddedHeader('x-test-invocation-header', 'foobarbaz');
        $requests = InvocationRequestFactory::createRequestsFromInvocation($event);
        $firstRequest = current($requests);

        // assert correct concrete class is used
        $this->assertInstanceOf($expectedClass, $firstRequest);

        // assert number of events in the payload
        $this->assertCount($expectedRecords, $requests);

        // assert the invocation source header is injected
        $this->assertTrue($firstRequest->hasHeader('x-invocation-source'));
        $this->assertEquals($expectedSource, $firstRequest->getHeaderLine('x-invocation-source'));

        // assert runtime header is propagated to request
        $this->assertTrue($firstRequest->hasHeader('x-test-invocation-header'));
        $this->assertEquals('foobarbaz', $firstRequest->getHeaderLine('x-test-invocation-header'));
    }
}
