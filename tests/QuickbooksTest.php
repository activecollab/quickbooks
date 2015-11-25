<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\Quickbooks;
use League\OAuth1\Client\Credentials\ClientCredentials;

class QuickbooksTest extends TestCase
{
    /**
     * @var Quickbooks
     */
    protected $server;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $clientCredentials = new ClientCredentials();
        $clientCredentials->setIdentifier('consumer-key');
        $clientCredentials->setSecret('consumer-key-secret');

        $this->server = new Quickbooks($clientCredentials);
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        $this->server = null;

        parent::tearDown();
    }

    /**
     * Test methods which return urls
     */
    public function testUrls()
    {
        $this->assertEquals('https://oauth.intuit.com/oauth/v1/get_request_token', $this->server->urlTemporaryCredentials());
        $this->assertEquals('https://appcenter.intuit.com/Connect/Begin', $this->server->urlAuthorization());
        $this->assertEquals('https://oauth.intuit.com/oauth/v1/get_access_token', $this->server->urlTokenCredentials());
        $this->assertEquals('https://appcenter.intuit.com/api/v1/user/current', $this->server->urlUserDetails());
    }

    /**
     * Test getting user details
     */
    public function testGettingUserDetails()
    {
        // mock server
        $mockServer = $this->getMockBuilder('ActiveCollab\Quickbooks\Quickbooks')
                       ->setConstructorArgs([ $this->getMockClientCredentials(), null ])
                       ->setMethods([ 'createHttpClient', 'getHeaders', 'urlUserDetails' ])
                       ->getMock();

        // mock token credentials
        $mockTokenCredentials = $this->getMock('League\OAuth1\Client\Credentials\TokenCredentials', [ 'getIdentifier', 'getSecret' ]);
        $mockTokenCredentials->expects($this->any())
                             ->method('getIdentifier')
                             ->will($this->returnValue('tokencredentialsidentifier'));
        $mockTokenCredentials->expects($this->any())
                             ->method('getSecret')
                             ->will($this->returnValue('tokencredentialssecret'));

        $mockServer->expects($this->once())
                   ->method('urlUserDetails')
                   ->will($this->returnValue('http://www.example.com/user'));
        $mockServer->expects($this->once())
                   ->method('getHeaders')
                   ->will($this->returnValue(null));
        $mockServer->expects($this->once())
                   ->method('createHttpClient')
                   ->will($this->returnValue($mockHttpClient = $this->getMock('stdClass', [ 'get' ])));

        $mockHttpClient->expects($this->once())
                       ->method('get')
                       ->with('http://www.example.com/user', null)
                       ->will($this->returnValue($mockRequest = $this->getMock('stdClass', [ 'send' ])));

        $mockRequest->expects($this->once())
                    ->method('send')
                    ->will($this->returnValue($mockResponse = $this->getMock('stdClass', [ 'json' ])));

        $data = [ 
            'User' => [ 
                'FirstName'     => 'John',
                'LastName'      => 'Doe',
                'EmailAddress'  => 'john.doe@activecollab.com',
                'IsVerified'    => 'true'
            ]
        ];

        $mockResponse->expects($this->once())
                     ->method('json')
                     ->will($this->returnValue($data));

        $user = $mockServer->getUserDetails($mockTokenCredentials);
        $this->assertInstanceOf('League\OAuth1\Client\Server\User', $user);
        $this->assertEquals('John', $user->firstName);
        $this->assertEquals('Doe', $user->lastName);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals(null, $mockServer->getUserUid($mockTokenCredentials));
        $this->assertEquals('john.doe@activecollab.com', $mockServer->getUserEmail($mockTokenCredentials));
        $this->assertEquals(null, $mockServer->getUserScreenName($mockTokenCredentials));
    }

    /**
     * Return mocked client credentials
     * 
     * @return array
     */
    protected function getMockClientCredentials()
    {
        return [
            'identifier' => 'myidentifier',
            'secret' => 'mysecret',
            'callback_uri' => 'http://app.dev/',
        ];
    }
}
