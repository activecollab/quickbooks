<?php

namespace ActiveCollab\Quickbooks\Tests;

use Guzzle\Http\Message\Response;
use ActiveCollab\Quickbooks\Quickbooks;
use Guzzle\Http\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\TokenCredentials;
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
        $this->assertEquals('https://appcenter.intuit.com/api/v1/connection', $this->server->urlConnection());
    }

    /**
     * Test getting user details
     */
    public function testGettingUserDetails()
    {
        // mock server
        $mockServer = $this->mockServer(['createHttpClient', 'getHeaders', 'urlUserDetails']);

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
     * Test reconnect
     */
    public function testReconnect()
    {
        $expectedNewAccessToken = 'newaccesstoken';
        $expectedNewAccessTokenSecret = 'newaccesstokensecret';

        $expectedResponse = http_build_query([
            "oauth_token" => $expectedNewAccessToken,
            "oauth_token_secret" => $expectedNewAccessTokenSecret,
        ]);

        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier('tokencredentialsidentifier');
        $tokenCredentials->setSecret('tokencredentialssecret');

        // mock server
        $mockServer = $this->mockServer(['createHttpClient', 'getHeaders', 'urlConnection']);
        $mockServer->expects($this->once())
                   ->method('urlConnection')
                   ->will($this->returnValue('http://www.example.com/connection'));
        $mockServer->expects($this->once())
                   ->method('getHeaders')
                   ->will($this->returnValue(null));
        $mockServer->expects($this->once())
                   ->method('createHttpClient')
                   ->will($this->returnValue($mockHttpClient = $this->getMock('stdClass', [ 'get' ])));

        $mockHttpClient->expects($this->once())
                       ->method('get')
                       ->with('http://www.example.com/connection/reconnect', null)
                       ->will($this->returnValue($mockRequest = $this->getMock('stdClass', [ 'send' ])));

        $mockRequest->expects($this->once())
                    ->method('send')
                    ->will($this->returnValue($mockResponse = $this->getMock('stdClass', [ 'getBody' ])));

        $mockResponse->expects($this->once())
                     ->method('getBody')
                     ->will($this->returnValue($expectedResponse));

        $newTokenCredentials = $mockServer->reconnect($tokenCredentials);
        $this->assertInstanceOf('League\OAuth1\Client\Credentials\TokenCredentials', $newTokenCredentials);
        $this->assertEquals($expectedNewAccessToken, $newTokenCredentials->getIdentifier());
        $this->assertEquals($expectedNewAccessTokenSecret, $newTokenCredentials->getSecret());
    }

    /**
     * @expectedException League\OAuth1\Client\Credentials\CredentialsException
     */
    public function testErrorOnReconnect()
    {
        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier('tokencredentialsidentifier');
        $tokenCredentials->setSecret('tokencredentialssecret');

        // mock server
        $mockServer = $this->mockServer(['createHttpClient', 'getHeaders', 'urlConnection']);
        $mockServer->expects($this->once())
                   ->method('urlConnection')
                   ->will($this->returnValue('http://www.example.com/connection'));
        $mockServer->expects($this->once())
                   ->method('getHeaders')
                   ->will($this->returnValue(null));
        $mockServer->expects($this->once())
                   ->method('createHttpClient')
                   ->will($this->returnValue($mockHttpClient = $this->getMock('stdClass', [ 'get' ])));

        $e = new BadResponseException();
        $e->setResponse(new Response(404));

        $mockHttpClient->expects($this->once())
                       ->method('get')
                       ->with('http://www.example.com/connection/reconnect', null)
                       ->will($this->throwException($e));

        $mockServer->reconnect($tokenCredentials);
    }

    /**
     * Test disconnect
     */
    public function testDisconnect()
    {
        $expect = true;

        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier('tokencredentialsidentifier');
        $tokenCredentials->setSecret('tokencredentialssecret');

        // mock server
        $mockServer = $this->mockServer(['createHttpClient', 'getHeaders', 'urlConnection']);
        $mockServer->expects($this->once())
                   ->method('urlConnection')
                   ->will($this->returnValue('http://www.example.com/connection'));
        $mockServer->expects($this->once())
                   ->method('getHeaders')
                   ->will($this->returnValue(null));
        $mockServer->expects($this->once())
                   ->method('createHttpClient')
                   ->will($this->returnValue($mockHttpClient = $this->getMock('stdClass', [ 'get' ])));

        $mockHttpClient->expects($this->once())
                       ->method('get')
                       ->with('http://www.example.com/connection/disconnect', null)
                       ->will($this->returnValue($mockRequest = $this->getMock('stdClass', [ 'send' ])));

        $mockRequest->expects($this->once())
                    ->method('send');

        $this->assertEquals($expect, $mockServer->disconnect($tokenCredentials));
    }

    /**
     * @expectedException \Exception
     */
    public function testErrorOnDiconnect()
    {
        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier('tokencredentialsidentifier');
        $tokenCredentials->setSecret('tokencredentialssecret');

        // mock server
        $mockServer = $this->mockServer(['createHttpClient', 'getHeaders', 'urlConnection']);
        $mockServer->expects($this->once())
                   ->method('urlConnection')
                   ->will($this->returnValue('http://www.example.com/connection'));
        $mockServer->expects($this->once())
                   ->method('getHeaders')
                   ->will($this->returnValue(null));
        $mockServer->expects($this->once())
                   ->method('createHttpClient')
                   ->will($this->returnValue($mockHttpClient = $this->getMock('stdClass', [ 'get' ])));

        $e = new BadResponseException();
        $e->setResponse(new Response(404));

        $mockHttpClient->expects($this->once())
                       ->method('get')
                       ->with('http://www.example.com/connection/disconnect', null)
                       ->will($this->throwException($e));

        $mockServer->disconnect($tokenCredentials);
    }

    protected function mockServer(array $methods = [])
    {
        $server = $this->getMockBuilder('ActiveCollab\Quickbooks\Quickbooks');        
        $server->setConstructorArgs([ $this->getMockClientCredentials(), null ]);

        if (!empty($methods)) {
            $server->setMethods($methods);
        }
    
        return $server->getMock();
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
