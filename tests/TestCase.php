<?php

namespace ActiveCollab\Quickbooks\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use \PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class TestCase extends BaseTestCase
{
    /**
     * Get fixture for test mocking
     *
     * @param  string $name
     * @param  bool   $asArray
     *
     * @return array|string
     */
    public function getFixture($name, $asArray = true)
    {
        $fixture = file_get_contents(__DIR__.'/fixtures/'.$name.'.json');

        return $asArray ? json_decode($fixture,true) : $fixture;
    }

    public function assertArray($data)
    {
        $this->assertTrue(is_array($data), 'Not array');
    }


    protected function createMockGuzzleClient(array $responses = []): Client
    {
        $mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mockHandler);

        return new Client(['handler' => $handlerStack]);
    }

    protected function createJsonResponse(array $data, int $statusCode = 200): Response
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );
    }

    protected function createErrorResponse(int $statusCode, string $method, string $url, string $message = ''): RequestException
    {
        return new BadResponseException(
            $message,
            new Request($method, $url),
            new Response($statusCode)
        );
    }

}
