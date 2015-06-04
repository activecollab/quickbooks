<?php

namespace ActiveCollab\Quickbooks;


use ActiveCollab\Quickbooks\OAuth\LeagueOAuth;
use ActiveCollab\Quickbooks\Http\HttpRequester;
use Guzzle\Http\Client;


/**
 * Class Quickbooks
 *
 * @package ActiveCollab\QUickbooksWrapper
 */
class Quickbooks
{
    /**
     * Get Authenticator
     *
     * @param  array $config
     * @return Authenticator
     */
    public static function getAuthenticator(array $config)
    {
        $oauthClient = new LeagueOAuth([
            'identifier'   => $config['consumerKey'],
            'secret'       => $config['consumerKeySecret'],
            'callback_uri' => $config['callbackUrl']
        ]);


        return new Authenticator($oauthClient,$config['consumerKey'], $config['consumerKeySecret'], $config['callbackUrl']);
    }

    /**
     * Get DataService
     *
     * @param array $config
     *
     * @return DataService
     */
    public static function getDataService(array $config)
    {
        $oauthClient = new LeagueOAuth([
            'identifier'   => $config['consumerKey'],
            'secret'       => $config['consumerKeySecret'],
            'callback_uri' => $config['callbackUrl']
        ]);

        $httpClient  = new HttpRequester(
            new Client()
        );

        return new DataService($oauthClient,$httpClient,$config['accessToken'],$config['accessTokenSecret'],$config['realmId']);
    }
}