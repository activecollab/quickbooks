<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\Data\Entity;
use ActiveCollab\Quickbooks\Data\QueryResponse;
use ActiveCollab\Quickbooks\DataService\ResourceClient;
use ActiveCollab\Quickbooks\Exception\QuickbooksQueryException;
use DateTime;
use QuickBooksOnline\API\DataService\DataService;
use RuntimeException;

class ResourceClientTest extends TestCase
{
    private function createDataServiceMock(array $methods = [])
    {
        return $this->getMockBuilder(DataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                array_unique(
                    array_merge(
                        [
                            'Query',
                            'CDC',
                            'FindById',
                            'Add',
                            'getLastError',
                            'setLogLocation',
                            'enableLog',
                            'disableLog',
                        ],
                        $methods
                    )
                )
            )
            ->getMock();
    }

    private function createResourceClient(DataService $data_service): ResourceClient
    {
        $client = $this->getMockBuilder(ResourceClient::class)
            ->setConstructorArgs(
                [
                    'client-id',
                    'client-secret',
                    'https://base.example.com',
                    'access-token',
                    'refresh-token',
                    'realm-id',
                ]
            )
            ->onlyMethods(['getDataService'])
            ->getMock();

        $client->method('getDataService')->willReturn($data_service);

        return $client;
    }

    public function testQueryReturnsQueryResponseOnSuccess()
    {
        $data_service = $this->createDataServiceMock();
        $data_service->method('Query')->willReturn([(object) ['Id' => '1']]);
        $data_service->method('getLastError')->willReturn(null);

        $client = $this->createResourceClient($data_service)->setEntity('Invoice');

        $response = $client->query('select Id from Invoice');

        $this->assertInstanceOf(QueryResponse::class, $response);

        $entities = $response->getIterator();
        $this->assertCount(1, $entities);
        $this->assertInstanceOf(Entity::class, $entities[0]);
        $this->assertEquals('1', $entities[0]->getRawData()['Id']);
    }

    public function testQueryWrapsThrowableInQuickbooksQueryException()
    {
        $sdk_exception = new RuntimeException('Exception appears in converting Response to XML.');

        $data_service = $this->createDataServiceMock();
        $data_service->method('Query')->willThrowException($sdk_exception);

        $client = $this->createResourceClient($data_service)->setEntity('Creditmemo');

        try {
            $client->query('select Id, DocNumber, TxnDate from Creditmemo');
            $this->fail('Expected QuickbooksQueryException to be thrown.');
        } catch (QuickbooksQueryException $e) {
            $this->assertSame('select Id, DocNumber, TxnDate from Creditmemo', $e->getQuery());
            $this->assertStringContainsString('converting Response to XML', $e->getSdkMessage());
            $this->assertSame($sdk_exception, $e->getPrevious());
            $this->assertNull($e->getRawResponse());
        }
    }

    public function testReadWrapsThrowableInQuickbooksQueryException()
    {
        $sdk_exception = new RuntimeException('boom');

        $data_service = $this->createDataServiceMock();
        $data_service->method('FindById')->willThrowException($sdk_exception);

        $client = $this->createResourceClient($data_service)->setEntity('Invoice');

        $this->expectException(QuickbooksQueryException::class);

        $client->read(5);
    }

    public function testCreateWrapsThrowableInQuickbooksQueryException()
    {
        $sdk_exception = new RuntimeException('boom');

        $data_service = $this->createDataServiceMock();
        $data_service->method('Add')->willThrowException($sdk_exception);

        $client = $this->createResourceClient($data_service)->setEntity('Invoice');

        $this->expectException(QuickbooksQueryException::class);

        $client->create(['Id' => 1]);
    }

    public function testCdcWrapsThrowableInQuickbooksQueryException()
    {
        $sdk_exception = new RuntimeException('boom');

        $data_service = $this->createDataServiceMock();
        $data_service->method('CDC')->willThrowException($sdk_exception);

        $client = $this->createResourceClient($data_service)->setEntity('Invoice');

        $this->expectException(QuickbooksQueryException::class);

        $client->cdc(['Invoice'], new DateTime());
    }

    public function testRawResponseCaptureAttachesResponseBodyAndCleansUp()
    {
        $capture_root = sys_get_temp_dir() . '/qbo-capture-' . uniqid();
        mkdir($capture_root);

        $captured_dir = null;

        $data_service = $this->createDataServiceMock();
        $data_service->method('enableLog')->willReturnSelf();
        $data_service->method('disableLog')->willReturnSelf();
        $data_service->method('setLogLocation')->willReturnCallback(
            function ($dir) use (&$captured_dir, $data_service) {
                $captured_dir = $dir;

                return $data_service;
            }
        );
        $data_service->method('Query')->willReturnCallback(
            function () use (&$captured_dir) {
                // Simulate the SDK writing a response log file before it throws.
                file_put_contents(
                    $captured_dir . '/00001-Response-1699999999.txt',
                    "RESPONSE BODY\n=============\n<IntuitResponse>boom</IntuitResponse>\n"
                );

                throw new RuntimeException('Exception appears in converting Response to XML.');
            }
        );

        $client = $this->createResourceClient($data_service)->setEntity('Creditmemo');
        $client->logRawResponses(true, $capture_root);

        try {
            $client->query('select Id from Creditmemo');
            $this->fail('Expected QuickbooksQueryException to be thrown.');
        } catch (QuickbooksQueryException $e) {
            $this->assertNotNull($e->getRawResponse());
            $this->assertStringContainsString('<IntuitResponse>boom</IntuitResponse>', $e->getRawResponse());
        }

        // The per-call capture directory must be cleaned up.
        $this->assertNotNull($captured_dir);
        $this->assertDirectoryDoesNotExist($captured_dir);

        rmdir($capture_root);
    }

    public function testRawResponseCaptureIsOffByDefault()
    {
        $data_service = $this->createDataServiceMock();
        $data_service->expects($this->never())->method('enableLog');
        $data_service->method('Query')->willThrowException(new RuntimeException('boom'));

        $client = $this->createResourceClient($data_service)->setEntity('Invoice');

        try {
            $client->query('select Id from Invoice');
            $this->fail('Expected QuickbooksQueryException to be thrown.');
        } catch (QuickbooksQueryException $e) {
            $this->assertNull($e->getRawResponse());
        }
    }
}
