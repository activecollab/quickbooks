<?php 

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\DataService;

class DataServiceTest extends TestCase
{
    /**
     * @var DataService
     */
    protected $dataService;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->dataService = new DataService(
            'consumer-key',
            'consumer-key-secret',
            'access-token',
            'access-token-secret',
            'realm-id'
        );

        $this->assertInstanceOf('Activecollab\Quickbooks\DataService', $this->dataService);
    }

    /**
     * Tear down test environemnt
     */
    public function tearDown()
    {
        $this->dataService = null;

        parent::tearDown();
    }

    /**
     * Test get api url
     */
    public function testGetApiUrl()
    {
        $this->assertContains('https://quickbooks.api.intuit.com', $this->dataService->getApiUrl(), 'Invalid api url');
    }

    /**
     * Test create http client
     */
    public function testCreateHttpClient()
    {
        $this->assertInstanceOf('Guzzle\Service\Client', $this->dataService->createHttpClient());
    }

    /**
     * Test create server
     */
    public function testCreateServer()
    {
        $this->assertInstanceOf('ActiveCollab\Quickbooks\Quickbooks', $this->dataService->createServer());
    }

    /**
     * Test fetch methotds
     */
    public function testFetchMethods()
    {
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->setMethods([ 'request' ])
                                ->getMock();

        $entity = new \ActiveCollab\Quickbooks\Data\Entity([ 'Id' => 1 ]);

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue($entity));

        $entity1 = $mockDataService->create([ 'Id' => 1 ]);
        $entity2 = $mockDataService->read(1);
        $entity3 = $mockDataService->update([ 'Id' => 1 ]);
        $this->assertSame($entity, $entity1);
        $this->assertSame($entity, $entity2);
        $this->assertSame($entity, $entity3);
    }

    /**
     * Test delete
     */
    public function testDelete()
    {
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->setMethods([ 'request' ])
                                ->getMock();

        $entity = new \ActiveCollab\Quickbooks\Data\Entity([ 'Id' => 1 ]);

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue($entity));

        $this->assertSame(null, $mockDataService->delete([ 'Id' => 1 ]));
    }

    /**
     * Test query
     */
    public function testQuery()
    {
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->setMethods([ 'request' ])
                                ->getMock();

        $query_response = new \ActiveCollab\Quickbooks\Data\QueryResponse([]);

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue($query_response));

        $collection = $mockDataService->setEntity('Invoice')->query();
        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\QueryResponse', $collection);
    }

    /**
     * Test request
     */
    public function testRequest()
    {
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->setMethods([ 'createServer', 'createHttpClient' ])
                                ->getMock();

        $mockDataService->expects($this->once())
                        ->method('createServer')
                        ->will($this->returnValue($mockServer = $this->getMock('stdClass', [ 'getHeaders' ])));

        $mockServer->expects($this->once())
                   ->method('getHeaders')
                   ->will($this->returnValue($this->getMockAuthorizationHeaders()));

        $mockDataService->expects($this->once())
                        ->method('createHttpClient')
                        ->will($this->returnValue($mockHttpClient = $this->getMock('stdClass', ['createRequest'])));

        $mockHttpClient->expects($this->once())
                       ->method('createRequest')
                       ->with('POST', 'http://www.example.com', $this->getTestHeaders(), json_encode([ 'Id' => 1 ]))
                       ->will($this->returnValue($request = $this->getMock('stdClass', [ 'send' ])));

        $request->expects($this->once())
                ->method('send')
                ->will($this->returnValue($response = $this->getMock('stdClass', [ 'json' ])));

        $response->expects($this->once())
                 ->method('json')
                 ->will($this->returnValue(json_decode('{"Invoice": { "Id": "1" } }', true)));

        $entity = $mockDataService->request('POST', 'http://www.example.com', [ 'Id' => 1 ]);

        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $entity);
    }

    /**
     * Test catch request exception
     * 
     * @expectedException       Exception
     */
    public function testCatchRequestException()
    {
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->setMethods([ 'createServer', 'createHttpClient' ])
                                ->getMock();

        $mockDataService->expects($this->once())
                        ->method('createServer')
                        ->will($this->returnValue($mockServer = $this->getMock('stdClass', [ 'getHeaders' ])));

        $mockServer->expects($this->once())
                   ->method('getHeaders')
                   ->will($this->returnValue($this->getMockAuthorizationHeaders()));

        $mockDataService->expects($this->once())
                        ->method('createHttpClient')
                        ->will($this->returnValue($mockClient = $this->getMock('stdClass', [ 'createRequest' ])));

        $mockClient->expects($this->once())
                        ->method('createRequest')
                        ->with('GET', 'http://www.example.com', $this->getTestHeaders(), null)
                        ->will($this->returnValue($request = $this->getMock('stdClass', [ 'send' ])));

        $request->expects($this->once())
                ->method('send')
                ->will($this->returnValue($response = $this->getMock('stdClass', [ 'json' ])));

        $httpResponseException = new \Guzzle\Http\Exception\BadResponseException();
        $httpResponseException->setResponse(new \Guzzle\Http\Message\Response(500));

        $response->expects($this->once())->method('json')->will($this->throwException($httpResponseException));

        $mockDataService->request('GET', 'http://www.example.com');
    }

    /**
     * Test set user agent
     */
    public function testSetUserAgent()
    {
        $userAgentName = 'Chrome';
        $dataService = $this->dataService->setUserAgent($userAgentName);

        $this->assertSame($userAgentName, $dataService->getUserAgent());
    }

    /**
     * Test collect headers
     */
    public function testCollectHeaders()
    {
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->setMethods(['createServer'])
                                ->getMock();

        $mockDataService->expects($this->any())
                        ->method('createServer')
                        ->will($this->returnValue($mockServer = $this->getMock('stdClass', [ 'getHeaders' ])));

        $mockServer->expects($this->any())
                   ->method('getHeaders')
                   ->will($this->returnValue($this->getMockAuthorizationHeaders()));

        $headers = $mockDataService->getHeaders('GET', 'http://www.example.com');

        $this->assertTrue(isset($headers['Accept']));
        $this->assertTrue(isset($headers['Content-Type']));
        $this->assertFalse(isset($headers['User-Agent']));
        $this->assertTrue(isset($headers['Authorization']));

        $headers = $mockDataService->setUserAgent('Test')->getHeaders('GET', 'http://www.example.com');

        $this->assertTrue(isset($headers['Accept']));
        $this->assertTrue(isset($headers['Content-Type']));
        $this->assertTrue(isset($headers['User-Agent']));
        $this->assertTrue(isset($headers['Authorization']));
    }

    /**
     * Get test arguments
     * 
     * @return array
     */
    public function getTestArguments()
    {
        return [
            'consumer-key',
            'consumer-key-secret',
            'access-token',
            'access-token-secret',
            123456789
        ];
    }

    /**
     * Get test headers
     * 
     * @return array
     */
    public function getTestHeaders()
    {
        $headers = $this->getMockAuthorizationHeaders();

        return array_merge($headers, [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ]);
    }

    protected function getMockAuthorizationHeaders()
    {
        return [
            'Authorization' => '',
        ];
    }
}
