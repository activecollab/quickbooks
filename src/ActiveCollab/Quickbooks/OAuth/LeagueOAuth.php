<?php

namespace ActiveCollab\Quickbooks\OAuth;


use ActiveCollab\Quickbooks\Config;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\User;

class LeagueOAuth extends Server implements OAuthInterface
{

    /**
     * Get request token.
     *
     * @return string
     */
    public function getRequestTokens()
    {
        $tempCredentials = $this->getTemporaryCredentials();

        return [
            'requestToken'       => $tempCredentials->getIdentifier(),
            'requestTokenSecret' => $tempCredentials->getSecret()
        ];
    }

    /**
     * Get access token from API.
     *
     * @param string $requestToken
     * @param string $requestTokenSecret
     * @param string $oauthToken
     * @param string $oauthVerifier
     *
     * @return array
     */
    public function getAccessTokens($requestToken, $requestTokenSecret, $oauthToken, $oauthVerifier)
    {
        $tempCredentials = new TemporaryCredentials();
        $tempCredentials->setIdentifier($requestToken);
        $tempCredentials->setSecret($requestTokenSecret);

        $tokenCredentials = $this->getTokenCredentials($tempCredentials, $oauthToken, $oauthVerifier);

        return [
            'accessToken'       => $tokenCredentials->getIdentifier(),
            'accessTokenSecret' => $tokenCredentials->getSecret()
        ];
    }

    /**
     * Generate authorization header for the request.
     *
     * @param string     $method
     * @param string     $url
     * @param string     $accessToken
     * @param string     $accessTokenSecret
     * @param null|array $payload
     *
     * @return string
     */
    public function generateAuthorizationHeader($method, $url, $accessToken, $accessTokenSecret, $payload = [])
    {
        $clientCredentials = new TokenCredentials();
        $clientCredentials->setIdentifier($accessToken);
        $clientCredentials->setSecret($accessTokenSecret);

        return $this->protocolHeader($method, $url, $clientCredentials,$payload);
    }


    /**
     * Get the URL for retrieving temporary credentials.
     *
     * @return string
     */
    public function urlTemporaryCredentials()
    {
        return Config::URL_REQUEST_TOKEN;
    }

    /**
     * Get the URL for redirecting the resource owner to authorize the client.
     *
     * @return string
     */
    public function urlAuthorization()
    {
        return Config::URL_AUTHORIZATION;
    }

    /**
     * Get the URL retrieving token credentials.
     *
     * @return string
     */
    public function urlTokenCredentials()
    {
        return Config::URL_ACCESS_TOKEN;
    }

    /**
     * Get the URL for retrieving user details.
     *
     * @return string
     */
    public function urlUserDetails()
    {
        return;
    }

    /**
     * Take the decoded data from the user details URL and convert
     * it to a User object.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return User
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * Take the decoded data from the user details URL and extract
     * the user's UID.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return string|int
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * Take the decoded data from the user details URL and extract
     * the user's email.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return string
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * Take the decoded data from the user details URL and extract
     * the user's screen name.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return string
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return;
    }
}