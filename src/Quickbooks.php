<?php

namespace ActiveCollab\Quickbooks;

use League\OAuth1\Client\Server\User;
use League\OAuth1\Client\Server\Server;
use Guzzle\Http\Exception\BadResponseException;
use ActiveCollab\Quickbooks\Data\ConnectionResponse;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\CredentialsInterface;

class Quickbooks extends Server
{
    /**
     * Return temporary credentials url.
     * 
     * @return string
     */
    public function urlTemporaryCredentials()
    {
        return 'https://oauth.intuit.com/oauth/v1/get_request_token';
    }

    /**
     * Return authorization url.
     * 
     * @return string
     */
    public function urlAuthorization()
    {
        return 'https://appcenter.intuit.com/Connect/Begin';
    }

    /**
     * Return token credentials url.
     * 
     * @return string
     */
    public function urlTokenCredentials()
    {
        return 'https://oauth.intuit.com/oauth/v1/get_access_token';
    }

    /**
     * Return user details url.
     * 
     * @return string
     */
    public function urlUserDetails()
    {
        return 'https://appcenter.intuit.com/api/v1/user/current';
    }

    /**
     * Return connection url.
     * 
     * @return string
     */
    public function urlConnection()
    {
        return 'https://appcenter.intuit.com/api/v1/connection';
    }

    /**
     * Return user details.
     * 
     * @param  array            $data
     * @param  TokenCredentials $tokenCredentials
     * @return User
     */
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

    /**
     * Return user uid.
     * 
     * @param array            $data
     * @param TokenCredentials $tokenCredentials
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * Return user email.
     * 
     * @param  array            $data
     * @param  TokenCredentials $tokenCredentials
     * @return string
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return (string) $data['User']['EmailAddress'];
    }

    /**
     * Retrun user screen name.
     * 
     * @param array            $data
     * @param TokenCredentials $tokenCredentials
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * Return headers.
     * 
     * @param  CredentialsInterface $credentials
     * @param  string               $method
     * @param  string               $url
     * @param  array                $bodyParameters
     * @return array
     */
    public function getHeaders(CredentialsInterface $credentials, $method, $url, array $bodyParameters = [])
    {
        $headers = parent::getHeaders($credentials, $method, $url, $bodyParameters);

        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';

        return $headers;
    }

    /**
     * Reconnect and return new access tokens.
     * 
     * @param  tokenCredentials     $tokenCredentials
     * @return ConnectionResponse
     */
    public function reconnect(TokenCredentials $tokenCredentials)
    {
        return $this->sendConnectionRequest($tokenCredentials, 'reconnect');
    }

    /**
     * Disconnect from quickbooks.
     *
     * @param  TokenCredentials     $tokenCredentials
     * @return ConnectionResponse
     */
    public function disconnect(TokenCredentials $tokenCredentials)
    {
        return $this->sendConnectionRequest($tokenCredentials, 'disconnect');
    }

    /**
     * Send connection request.
     * 
     * @param  TokenCredentials     $tokenCredentials
     * @param  string               $action
     * @return ConnectionResponse
     * @throws CredentialsException
     */
    public function sendConnectionRequest(TokenCredentials $tokenCredentials, $action)
    {
        $client = $this->createHttpClient();
        $uri = $this->urlConnection() . '/' . $action;
        $headers = $this->getHeaders($tokenCredentials, 'GET', $uri);

        try {
            $response = $client->get($uri, $headers)->send()->json();
        } catch (BadResponseException $e) {
            return $this->handleTokenCredentialsBadResponse($e);
        }

        return new ConnectionResponse($response);
    }
}
