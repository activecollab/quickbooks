<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\DataService;
use ActiveCollab\Quickbooks\Quickbooks;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use League\OAuth1\Client\Credentials\ClientCredentials;

class DataServiceTest extends TestCase
{
    /**
     * @var DataService
     */
    protected $dataService;

    /**
     * Set up test environment
     */
    public function setUp(): void
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
    public function tearDown(): void
    {
        $this->dataService = null;

        parent::tearDown();
    }

    /**
     * Test get api url
     */
    public function testGetApiUrl()
    {
        $this->assertStringContainsString('https://quickbooks.api.intuit.com', $this->dataService->getApiUrl(), 'Invalid api url');
    }

    /**
     * Test create http client
     */
    public function testCreateHttpClient()
    {
        $this->assertInstanceOf(Client::class, $this->dataService->createHttpClient());
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

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue(json_decode('{"Invoice": { "Id": "1" } }', true)));

        $mockDataService->setEntity('Invoice');

        $result1 = $mockDataService->create([ 'Id' => 1 ]);
        $result2 = $mockDataService->read(1);
        $result3 = $mockDataService->update([ 'Id' => 1 ]);

        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $result1);
        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $result2);
        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $result3);
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

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue(json_decode('{"Invoice":{"Id":"1"}}', true)));

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

        $value = json_decode('{"QueryResponse":{"Invoice":[{"Id":"1"}]}}', true);

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue($value));

        $result = $mockDataService->setEntity('Invoice')->query();

        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\QueryResponse', $result);
        $entities = $result->getIterator();

        $this->assertCount(1, $entities);
        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $entities[0]);

        $data = $entities[0]->getRawData();
        $this->assertArrayHasKey('Id', $data);
        $this->assertEquals(1, $data['Id']);
    }

    /**
     * Test CDCRequest
     */
    public function testCDCRequest()
    {
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->setMethods([ 'request' ])
                                ->getMock();

        $value = json_decode('{"CDCResponse":[{"QueryResponse":[{"Invoice":[{"Id":"1"},{"Id":"2"},{"Id":"3","status":"Deleted"}]}]}]}', true);

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue($value));

        $result = $mockDataService->cdc(['Invoice'], new DateTime());

        $this->assertArrayHasKey('Invoice', $result);
        $this->assertCount(3, $result['Invoice']);

        $entity1 = $result['Invoice'][0];
        $entity2 = $result['Invoice'][1];
        $entity3 = $result['Invoice'][2];

        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $entity1);
        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $entity2);
        $this->assertInstanceOf('\ActiveCollab\Quickbooks\Data\Entity', $entity3);

        $this->assertEquals(1, $entity1->getId());
        $this->assertEquals(2, $entity2->getId());
        $this->assertEquals(3, $entity3->getId());

        $entity3_raw_data = $entity3->getRawData();
        $this->assertArrayHasKey('status', $entity3_raw_data);
        $this->assertEquals('Deleted', $entity3_raw_data['status']);
    }

    public function testCDCRequestThrowsException() {
        $this->expectException(Exception::class);
        $mockDataService = $this->getMockBuilder('\ActiveCollab\Quickbooks\DataService')
                                ->setConstructorArgs($this->getTestArguments())
                                ->onlyMethods([ 'request' ])
                                ->getMock();

        $value = json_decode('{}', true);

        $mockDataService->expects($this->any())
                        ->method('request')
                        ->will($this->returnValue($value));

        $mockDataService->cdc(['Invoice'], new DateTime());
    }

    /**
     * Test request
     */
    public function testRequest()
    {
        $mockDataService = $this->getMockBuilder(DataService::class)
                                ->setConstructorArgs($this->getTestArguments())
                                ->onlyMethods([ 'createServer', 'createHttpClient' ])
                                ->getMock();

        $quickBooks = $this->createMock(QuickBooks::class);
        $quickBooks->method('getHeaders')
            ->willReturn($this->getMockAuthorizationHeaders());

        $mockDataService->expects($this->once())
                        ->method('createServer')
                        ->will($this->returnValue($quickBooks));

        $client = $this->createMockGuzzleClient([
            $this->createJsonResponse(['Invoice' => [
                'Id' => 1,
            ]])
        ]);

        $mockDataService->expects($this->once())
            ->method('createHttpClient')
            ->will($this->returnValue($client));




        $response = $mockDataService->request('POST', 'http://www.example.com', [ 'Id' => 1 ]);

        $this->assertArrayHasKey('Invoice', $response);
        $this->assertArrayHasKey('Id', $response['Invoice']);
        $this->assertEquals('1', $response['Invoice']['Id']);
    }

    /**
     * Test catch request exception
     *
     */
    public function testCatchRequestException()
    {
        $this->expectException(Exception::class);
        $mockDataService = $this->getMockBuilder(DataService::class)
            ->setConstructorArgs($this->getTestArguments())
            ->onlyMethods([ 'createServer', 'createHttpClient' ])
            ->getMock();

        $quickBooks = $this->createMock(QuickBooks::class);
        $quickBooks->method('getHeaders')
            ->willReturn($this->getMockAuthorizationHeaders());

        $mockDataService->expects($this->once())
            ->method('createServer')
            ->will($this->returnValue($quickBooks));

        $client = $this->createMockGuzzleClient([
            $this->createErrorResponse(500, 'POST', 'http://www.example.com', 'Server timeout')
        ]);

        $mockDataService->expects($this->once())
            ->method('createHttpClient')
            ->will($this->returnValue($client));

        $mockDataService->request('POST', 'http://www.example.com', [ 'Id' => 1 ]);
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


        $mockDataService = $this->getMockBuilder(DataService::class)
            ->setConstructorArgs($this->getTestArguments())
            ->onlyMethods([ 'createServer' ])
            ->getMock();

        $clientCredentials = $this->createMock(ClientCredentials::class);
        $clientCredentials->method('getIdentifier')->willReturn('1234');
        $clientCredentials->method('getSecret')->willReturn('test');

        $quickBooks = new Quickbooks($clientCredentials);

        $mockDataService->expects($this->any())
            ->method('createServer')
            ->will($this->returnValue($quickBooks));

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
