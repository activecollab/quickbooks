<?php

class QuickbooksTest extends TestCase
{
    public function testCreateDataService()
    {
        $dataService = \ActiveCollab\Quickbooks\Quickbooks::getDataService([
            'consumerKey'       => 'consumer-key',
            'consumerKeySecret' => 'consumer-key-secret',
            'accessToken'       => 'access-token',
            'accessTokenSecret' => 'access-token-secret',
            'callbackUrl'       => 'callback-url',
            'realmId'           => 123456789,
        ]);

        $this->assertInstanceOf('\ActiveCollab\Quickbooks\DataService', $dataService);
    }

    public function testCreateAuthenticator()
    {
        $authenticator = \ActiveCollab\Quickbooks\Quickbooks::getAuthenticator([
            'consumerKey'       => 'consumer-key',
            'consumerKeySecret' => 'consumer-key-secret',
            'callbackUrl'       => 'callback-url'
        ]);

        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Authenticator', $authenticator);
    }
}