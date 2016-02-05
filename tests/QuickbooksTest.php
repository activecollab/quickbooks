<?php

namespace ActiveCollab\Quickbooks\Tests;

use Guzzle\Http\Message\Response;
use ActiveCollab\Quickbooks\Quickbooks;
use Guzzle\Http\Exception\BadResponseException;
use ActiveCollab\Quickbooks\Data\ConnectionResponse;
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
     * Test headers
     */
    public function testHeaders()
    {
        $tokenCredentials = $this->getTokenCredentials();

        $headers = $this->server->getHeaders($tokenCredentials, 'GET', 'http://www.example.com');

        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('application/json', $headers['Accept']);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    /**
     * Test send connection request
     */
    public function testSendConnectionRequest()
    {
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
                    ->will($this->returnValue($mockResponse = $this->getMock('stdClass', [ 'json' ])));

        $expectedResponse = [
            'ErrorCode' => 0,
            'ErrorMessage' => '',
            'OAuthToken' => '12345',
            'OAuthTokenSecret' => '54321',
        ];

        $mockResponse->expects($this->once())
                     ->method('json')
                     ->will($this->returnValue($expectedResponse));

        $response = $mockServer->sendConnectionRequest($this->getTokenCredentials(), 'reconnect');
        $this->assertInstanceOf('ActiveCollab\Quickbooks\Data\ConnectionResponse', $response);
        $this->assertEquals($expectedResponse['ErrorCode'], $response->getErrorCode());
        $this->assertEquals($expectedResponse['ErrorMessage'], $response->getErrorMessage());
        $this->assertEquals($expectedResponse['OAuthToken'], $response->getOAuthToken());
        $this->assertEquals($expectedResponse['OAuthTokenSecret'], $response->getOAuthTokenSecret());
    }

    /**
     * @expectedException League\OAuth1\Client\Credentials\CredentialsException
     */
    public function testErrorOnSendConnectionRequest()
    {
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

        $mockServer->sendConnectionRequest($this->getTokenCredentials(), 'reconnect');
    }

    /**
     * Test reconnect
     */
    public function testReconnect()
    {
        $expectedResponse = $this->mockConnectionResponse(0, '', '12345', '54321');

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
                    ->will($this->returnValue($mockResponse = $this->getMock('stdClass', [ 'json' ])));

        $mockResponse->expects($this->once())
                     ->method('json')
                     ->will($this->returnValue($expectedResponse));

        $result = $mockServer->reconnect($this->getTokenCredentials());
        $this->assertInstanceOf('ActiveCollab\Quickbooks\Data\ConnectionResponse', $result);
        $this->assertEquals(false, $result->hasError());
        $this->assertEquals($expectedResponse['ErrorCode'], $result->getErrorCode());
        $this->assertEquals($expectedResponse['ErrorMessage'], $result->getErrorMessage());
        $this->assertEquals($expectedResponse['OAuthToken'], $result->getOAuthToken());
        $this->assertEquals($expectedResponse['OAuthTokenSecret'], $result->getOAuthTokenSecret());
    }

    /**
     * Test disconnect
     */
    public function testDisconnect()
    {
        $expectedResponse = $this->mockConnectionResponse(0);

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
                    ->method('send')
                    ->will($this->returnValue($mockResponse = $this->getMock('stdClass', [ 'json' ])));

        $mockResponse->expects($this->once())
                     ->method('json')
                     ->will($this->returnValue($expectedResponse));

        $result = $mockServer->disconnect($this->getTokenCredentials());
        $this->assertInstanceOf('ActiveCollab\Quickbooks\Data\ConnectionResponse', $result);
        $this->assertEquals(false, $result->hasError());
        $this->assertEquals($expectedResponse['ErrorCode'], $result->getErrorCode());
        $this->assertEquals($expectedResponse['ErrorMessage'], $result->getErrorMessage());
        $this->assertEquals($expectedResponse['OAuthToken'], $result->getOAuthToken());
        $this->assertEquals($expectedResponse['OAuthTokenSecret'], $result->getOAuthTokenSecret());
    }

    /**
     * Retrun mocked server.
     * 
     * @param  array                                    $methods
     * @return PHPUnit_Framework_MockObject_MockObject
     */
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

    /**
     * Return token credentials
     * 
     * @return TokenCredentials
     */
    protected function getTokenCredentials()
    {
        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier('tokencredentialsidentifier');
        $tokenCredentials->setSecret('tokencredentialssecret');

        return $tokenCredentials;
    }

    /**
     * Return mocked connection response.
     * 
     * @param  int      $code
     * @param  string   $message
     * @param  string   $oauth_token
     * @param  string   $oauth_token_secret
     * @return array
     */
    protected function mockConnectionResponse($code = 0, $message = '', $oauth_token = null, $oauth_token_secret = null)
    {
        return [
            'ErrorCode' => $code,
            'ErrorMessage' => $message,
            'OAuthToken' => $oauth_token,
            'OAuthTokenSecret' => $oauth_token_secret,
        ];
    }
}
