<?php

namespace ActiveCollab\Quickbooks;

use ActiveCollab\Quickbooks\Quickbooks;
use ActiveCollab\Quickbooks\Data\Entity;
use Guzzle\Service\Client as GuzzleClient;
use ActiveCollab\Quickbooks\Data\QueryResponse;
use Guzzle\Http\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\ClientCredentials;
use DateTime;

class DataService
{
    const API_VERSION = 3;

    /**
     * @var string
     */
    protected $consumer_key, $consumer_key_secret, $access_token, $access_token_secret, $realmId;

    /**
     * @var string|null
     */
    protected $user_agent = null;

    /**
     * @var string
     */
    protected $entity = '';

    /**
     * Construct data service
     * 
     * @param string $consumer_key
     * @param string $consumer_key_secret
     * @param string $access_token
     * @param string $access_token_secret
     * @param string $realmId
     */
    public function __construct($consumer_key, $consumer_key_secret, $access_token, $access_token_secret, $realmId)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_key_secret = $consumer_key_secret;
        $this->access_token = $access_token;
        $this->access_token_secret = $access_token_secret;
        $this->realmId = $realmId;
    } 

    /**
     * Return api url
     * 
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://quickbooks.api.intuit.com/v'.self::API_VERSION;
    }

    /**
     * Return http client
     * 
     * @return GuzzleClient
     */
    public function createHttpClient()
    {
        return new GuzzleClient();
    }

    /**
     * Return oauth server
     * 
     * @return Quickbooks
     */
    public function createServer()
    {
        $client_credentials = new ClientCredentials();
        $client_credentials->setIdentifier($this->consumer_key);
        $client_credentials->setSecret($this->consumer_key_secret);

        return new Quickbooks($client_credentials);
    }

    /**
     * Return token credentials
     * 
     * @return TokenCredentials
     */
    public function getTokenCredentials()
    {
        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier($this->access_token);
        $tokenCredentials->setSecret($this->access_token_secret);

        return $tokenCredentials;
    }

    /**
     * Set user agent
     * 
     * @param string|null $user_agent
     */
    public function setUserAgent($user_agent = null)
    {
        $this->user_agent = $user_agent;

        return $this;
    }

    /**
     * Return user agent
     * 
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set entity
     * 
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Return entity url
     * 
     * @return string
     */
    public function getRequestUrl($slug)
    {
        return $this->getApiUrl() . '/company/' . $this->realmId .  '/' . strtolower($slug);
    }

    /**
     * Send create request
     * 
     * @param  array            $payload
     * @return Entity
     */
    public function create(array $payload)
    {
        $response = $this->request('POST', $this->getRequestUrl($this->entity), $payload);

        return new Entity($response[$this->entity]);
    }

    /**
     * Send read request
     * 
     * @param  int              $id
     * @return Entity
     */
    public function read($id)
    {
        $uri = $this->getRequestUrl($this->entity) . '/' . $id;

        $response = $this->request('GET', $uri);

        return new Entity($response[$this->entity]);
    }

    /**
     * Send update request
     * 
     * @param  array            $payload
     * @return Entity
     */
    public function update(array $payload)
    {
        $uri = $this->getRequestUrl($this->entity) . '?operation=update';

        $response = $this->request('POST', $uri, $payload);

        return new Entity($response[$this->entity]);
    }

    /**
     * Send delete request
     * 
     * @param  array            $payload
     * @return null
     */
    public function delete(array $payload)
    {
        $uri = $this->getRequestUrl($this->entity) . '?operation=delete';

        $this->request('POST', $uri, $payload);

        return null;
    }

    /**
     * Send query request
     * 
     * @param  string|null      $query
     * @return QueryResponse
     */
    public function query($query = null)
    {
        if ($query === null) {
            $query = "select * from {$this->entity}";
        }

        $uri = $this->getRequestUrl('query') . '?query=' . urlencode($query);

        $response = $this->request('GET', $uri);

        return new QueryResponse($response['QueryResponse']);
    }

    /**
     * Send CDC request
     * 
     * @param  array        $entities
     * @param  DateTime     $changed_since
     * @return array
     */
    public function cdc(array $entities, DateTime $changed_since)
    {
        $entities_value = urlencode(implode(',', $entities));
        $changed_since_value = urlencode(date_format($changed_since, DateTime::ATOM));
        $uri = $this->getRequestUrl('cdc') . '?entities=' . $entities_value . '&changedSince=' . $changed_since_value;

        $response = $this->request('GET', $uri);

        if (!isset($response['CDCResponse']) || !isset($response['CDCResponse'][0]['QueryResponse'])) {
            throw new \Exception("Invalid CDC response.");
        }

        $query_response = $response['CDCResponse'][0]['QueryResponse'];
        $result = [];
        foreach ($query_response as $values) {
            foreach ($values as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = [];
                }
                $result[$key][] = new Entity($value);
            }
        }

        return $result;
    }

    /**
     * Return headers for request
     * 
     * @param  string           $method
     * @param  string           $uri
     * @return array
     */
    public function getHeaders($method, $uri) 
    {
        $server = $this->createServer();

        $headers = $server->getHeaders($this->getTokenCredentials(), $method, $uri);

        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';

        if (!empty($this->user_agent)) {
            $headers['User-Agent'] = $this->user_agent;
        }

        return $headers;
    }

    /**
     * Request
     * 
     * @param  string $method
     * @param  string $uri
     * @param  string|array      $body
     * @return array
     * @throws \Exception
     */
    public function request($method, $uri, array $body = null)
    {   
        $client = $this->createHttpClient();

        $headers = $this->getHeaders($method, $uri);

        if ($body !== null) {
            $body = json_encode($body);
        }

        try {
            return $client->createRequest($method, $uri, $headers, $body)->send()->json();
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when sending request."
            );
        }
    }

}