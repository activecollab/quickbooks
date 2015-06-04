<?php

namespace ActiveCollab\Quickbooks;

use ActiveCollab\Quickbooks\OAuth\OAuthInterface;

class Authenticator
{
    /**
     * @var OAuthInterface
     */
    private $client;

    function __construct(OAuthInterface $client)
    {
        $this->client = $client;
    }


    /**
     * Get request token.
     *
     * @return string
     */
    public function getRequestTokens()
    {
        return $this->client->getRequestTokens();
    }

    /**
     * Get url for login.
     *
     * @param  string $requestToken
     * @return string
     */
    public function getLoginUrl($requestToken)
    {
        return $this->client->getAuthorizationUrl($requestToken);
    }

    /**
     * Get access token from API.
     *
     * @param string $oauthTokenStored
     * @param string $oauthVerifierStored
     * @param string $oauthToken
     * @param string $oauthVerifier
     * @return string
     */
    public function getAccessTokens($oauthTokenStored, $oauthVerifierStored, $oauthToken, $oauthVerifier)
    {
        return $this->client->getAccessTokens($oauthTokenStored, $oauthVerifierStored, $oauthToken, $oauthVerifier);
    }
}