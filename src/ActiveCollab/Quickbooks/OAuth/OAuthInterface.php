<?php

namespace ActiveCollab\Quickbooks\OAuth;


/**
 * Interface OAuthInterface
 *
 * @package ActiveCollab\Quickbooks\OAuth
 */
interface OAuthInterface
{
    /**
     * Get request token.
     *
     * @return array
     */
    public function getRequestTokens();

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
    public function getAccessTokens($requestToken, $requestTokenSecret, $oauthToken, $oauthVerifier);

    /**
     * Generate authorization header for the request.
     *
     * @param string $method
     * @param string $url
     * @param string $accessToken
     * @param string $accessTokenSecret
     *
     * @return string
     */
    public function generateAuthorizationHeader($method, $url, $accessToken, $accessTokenSecret);

    /**
     * Generate authorization url.
     *
     * @param  string $requestToken
     * @return string
     */
    public function getAuthorizationUrl($requestToken);

}