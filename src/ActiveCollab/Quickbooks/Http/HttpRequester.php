<?php

namespace ActiveCollab\Quickbooks\Http;


use ActiveCollab\Quickbooks\Exception\ApiRequestException;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

/**
 * Class HttpRequester
 *
 * @package ActiveCollab\Quickbooks\Http
 */
class HttpRequester implements HttpRequesterInterface
{
    /**
     * @var Client $client
     */
    private $client;

    function __construct(Client $client)
    {
        $this->client = $client;
    }


    /**
     * Make a request.
     *
     * @param string $method
     * @param string $url
     * @param array  $payload
     * @param array  $headers
     *
     * @throws ApiRequestException
     *
     * @return array
     */
    public function request($method, $url, array $payload = null, array $headers)
    {
        $request = $this->client->createRequest($method, $url);

        if($payload) {
            $request->setBody(json_encode($payload), 'application/json');
        }

        $request->addHeader("Accept", 'application/json');

        $request->removeHeader('Content-Type');
        $request->addHeader("Content-Type", 'application/json');


        if ($headers) {
            foreach ($headers as $name => $value) {
                $request->addHeader($name, $value);
            }
        }

        try {
            $response =  $request->send();
        } catch (RequestException $e) {
            throw new ApiRequestException($e->getMessage(),$e->getCode(),$e->getPrevious());
        }

        $jsonResponse = $response->getBody(true);

        return json_decode($jsonResponse,true);
    }
}