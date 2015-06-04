<?php

namespace ActiveCollab\Quickbooks;

use ActiveCollab\Quickbooks\Http\HttpRequesterInterface;
use ActiveCollab\Quickbooks\OAuth\OAuthInterface;

/**
 * Class DataService
 *
 * @package ActiveCollab\Quickbooks
 */
class DataService
{
    /**
     * @var OAuthInterface $oauth
     */
    private $oauth;

    /**
     * @var HttpRequesterInterface $requester
     */
    private $requester;

    /**
     * @var int $realmId
     */
    private $realmId;

    /**
     * @var string $accessToken
     */
    private $accessToken;

    /**
     * @var string $accessTokenSecret
     */
    private $accessTokenSecret;

    function __construct(OAuthInterface $oauth, HttpRequesterInterface $requester, $accessToken, $accessTokenSecret, $realmId)
    {
        $this->oauth             = $oauth;
        $this->requester         = $requester;
        $this->realmId           = $realmId;
        $this->accessToken       = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
    }

    /**
     * Get all entities.
     *
     * @param  string $entityName
     * @return array
     */
    public function all($entityName)
    {
        return $this->query('select * from '.$entityName);
    }

    /**
     * Get entity by id.
     *
     * @param  string $entityName
     * @param  int    $id
     *
     * @return array
     */
    public function get($entityName, $id)
    {
        return $this->makeCall('GET',$this->buildUrl('/'.strtolower($entityName).'/'.$id));
    }

    /**
     * Create new entity.
     *
     * @param  string $entityName
     * @param  array  $payload
     *
     * @return array
     */
    public function create($entityName, $payload)
    {
        return $this->makeCall('POST', $this->buildUrl('/'.strtolower($entityName)), $payload);
    }

    /**
     * Query API
     *
     * @param  string $query
     * @return array
     */
    public function query($query)
    {
        return $this->makeCall('GET',$this->buildUrl('/query?query='.$query))['QueryResponse'];
    }

    /**
     * Build url for querying API
     *
     * @param  string $config
     * @return string
     */
    private function buildUrl($config)
    {
        return Config::URL_API.'/v'.Config::API_VERSION.'/company/'.$this->realmId.$config;
    }

    /**
     * Make API call.
     *
     * @param string     $method
     * @param string     $url
     * @param null|array $payload
     *
     * @return array
     */
    private function makeCall($method,$url, $payload = [])
    {
        return $this->requester->request($method, $url, $payload, [
            'Authorization' => $this->oauth->generateAuthorizationHeader($method, $url, $this->accessToken, $this->accessTokenSecret)
        ]);
    }
}