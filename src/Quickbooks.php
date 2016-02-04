<?php

namespace ActiveCollab\Quickbooks;

use Guzzle\Http\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\User;

class Quickbooks extends Server
{
    public function urlTemporaryCredentials()
    {
        return 'https://oauth.intuit.com/oauth/v1/get_request_token';
    }

    public function urlAuthorization()
    {
        return 'https://appcenter.intuit.com/Connect/Begin';
    }

    public function urlTokenCredentials()
    {
        return 'https://oauth.intuit.com/oauth/v1/get_access_token';
    }

    public function urlUserDetails()
    {
        return 'https://appcenter.intuit.com/api/v1/user/current';
    }

    public function urlConnection()
    {
        return 'https://appcenter.intuit.com/api/v1/connection';
    }

    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();

        $user->firstName = (string) $data['User']['FirstName'];
        $user->lastName = (string) $data['User']['LastName'];
        $user->name = $user->firstName . ' ' . $user->lastName;
        $user->email = (string) $data['User']['EmailAddress'];

        $verified = filter_var((string) $data['User']['IsVerified'], FILTER_VALIDATE_BOOLEAN);

        $user->extra = compact('verified');

        return $user;
    }

    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return (string) $data['User']['EmailAddress'];
    }

    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * Reconnect and return new access tokens.
     * 
     * @param  TokenCredentials     $tokenCredentials
     * @return TokenCredentials
     * @throws CredentialsException
     */
    public function reconnect(TokenCredentials $tokenCredentials)
    {
        $uri = $this->urlConnection() . '/reconnect';

        $client = $this->createHttpClient();

        $headers = $this->getHeaders($tokenCredentials, 'GET', $uri);

        try {
            $response = $client->get($uri, $headers)->send();
        } catch (BadResponseException $e) {
            return $this->handleTokenCredentialsBadResponse($e);
        }

        return $this->createTokenCredentials($response->getBody());
    }

    /**
     * Disconnect from quickbooks
     *
     * @param  TokenCredentials     $tokenCredentials
     * @return bool
     */
    public function disconnect(TokenCredentials $tokenCredentials)
    {
        $uri = $this->urlConnection() . '/disconnect';

        $client = $this->createHttpClient();

        $headers = $this->getHeaders($tokenCredentials, 'GET', $uri);

        try {
            $response = $client->get($uri, $headers)->send();
        } catch (BadResponseException $e) {
            throw new \Exception("Disconnection failed");
        }

        return true;
    }
}
