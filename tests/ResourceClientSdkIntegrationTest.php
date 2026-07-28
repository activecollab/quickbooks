<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\Data\Entity;
use ActiveCollab\Quickbooks\Data\QueryResponse;
use ActiveCollab\Quickbooks\DataService\ResourceClient;
use QuickBooksOnline\API\Core\HttpClients\HttpClientInterface;
use QuickBooksOnline\API\Core\HttpClients\IntuitResponse;
use QuickBooksOnline\API\DataService\DataService;
use ReflectionMethod;
use ReflectionProperty;

class ResourceClientSdkIntegrationTest extends TestCase
{
    public function testQueryDeserializesRecordedInvoiceResponse()
    {
        $client = $this->clientReturning($this->recordedResponse('query_invoice_response'))
            ->setEntity('Invoice');

        $response = $client->query('select * from Invoice');

        $this->assertInstanceOf(QueryResponse::class, $response);
        $this->assertCount(2, $response);

        $entities = $response->getIterator();

        $this->assertInstanceOf(Entity::class, $entities[0]);
        $this->assertSame(130, $entities[0]->getId());
        $this->assertSame('1070', $entities[0]->getRawData()['DocNumber']);
        $this->assertSame('true', $entities[0]->getRawData()['AllowOnlineAffirmPayment']);

        $this->assertSame(131, $entities[1]->getId());
        $this->assertSame('1071', $entities[1]->getRawData()['DocNumber']);
    }

    public function testQueryDeserializesRecordedCreditMemoResponse()
    {
        $client = $this->clientReturning($this->recordedResponse('query_creditmemo_response'))
            ->setEntity('CreditMemo');

        $response = $client->query('select * from CreditMemo');

        $this->assertInstanceOf(QueryResponse::class, $response);
        $this->assertCount(2, $response);

        $entities = $response->getIterator();

        $this->assertSame(7, $entities[0]->getId());
        $this->assertSame('1001', $entities[0]->getRawData()['DocNumber']);
        $this->assertSame('50.00', $entities[0]->getRawData()['RemainingCredit']);

        $this->assertSame(8, $entities[1]->getId());
        $this->assertSame('1002', $entities[1]->getRawData()['DocNumber']);
    }

    public function testQueryToleratesUnknownSchemaElement()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<IntuitResponse xmlns="http://schema.intuit.com/finance/v3" time="2026-07-28T00:00:00.000-07:00">
  <QueryResponse startPosition="1" maxResults="1">
    <Invoice domain="QBO" sparse="false">
      <Id>200</Id>
      <SyncToken>0</SyncToken>
      <DocNumber>2000</DocNumber>
      <FieldQboAddedAfterThisSdkVersion>surprise</FieldQboAddedAfterThisSdkVersion>
      <TotalAmt>10.00</TotalAmt>
    </Invoice>
  </QueryResponse>
</IntuitResponse>
XML;

        $client = $this->clientReturning($xml)->setEntity('Invoice');

        $response = $client->query('select * from Invoice');

        $this->assertCount(1, $response);

        $entity = $response->getIterator()[0];
        $this->assertSame(200, $entity->getId());
        $this->assertSame('2000', $entity->getRawData()['DocNumber']);
        $this->assertArrayNotHasKey('FieldQboAddedAfterThisSdkVersion', $entity->getRawData());
    }

    public function testGetDataServiceSendsDeliberateMinorVersion()
    {
        $client = new ResourceClient(
            'client-id',
            'client-secret',
            'https://base.example.com',
            'access-token',
            'refresh-token',
            'realm-id'
        );

        $get_data_service = new ReflectionMethod(ResourceClient::class, 'getDataService');
        $get_data_service->setAccessible(true);

        $data_service = $get_data_service->invoke($client);

        $this->assertSame('75', ResourceClient::QBO_MINOR_VERSION);
        $this->assertSame(
            ResourceClient::QBO_MINOR_VERSION,
            (string) $data_service->getServiceContext()->minorVersion
        );
    }

    private function recordedResponse(string $name): string
    {
        return file_get_contents(__DIR__ . '/fixtures/' . $name . '.xml');
    }

    private function clientReturning(string $response_xml): ResourceClient
    {
        $data_service = $this->dataServiceReturning($response_xml);

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

    private function dataServiceReturning(string $response_xml): DataService
    {
        $data_service = DataService::Configure(
            [
                'auth_mode' => 'oauth2',
                'ClientID' => 'client-id',
                'ClientSecret' => 'client-secret',
                'accessTokenKey' => 'access-token',
                'refreshTokenKey' => 'refresh-token',
                'QBORealmID' => 'realm-id',
                'baseUrl' => 'https://sandbox-quickbooks.api.intuit.com',
            ]
        );

        $rest_handler_property = new ReflectionProperty($data_service, 'restHandler');
        $rest_handler_property->setAccessible(true);
        $rest_handler = $rest_handler_property->getValue($data_service);

        $http_client_property = new ReflectionProperty($rest_handler, 'httpClientInterface');
        $http_client_property->setAccessible(true);
        $http_client_property->setValue($rest_handler, $this->httpClientReturning($response_xml));

        return $data_service;
    }

    private function httpClientReturning(string $response_xml): HttpClientInterface
    {
        return new class($response_xml) implements HttpClientInterface {
            private $response_xml;
            private $last_response;

            public function __construct(string $response_xml)
            {
                $this->response_xml = $response_xml;
            }

            public function makeAPICall($url, $method, array $headers, $body, $timeOut, $verifySSL)
            {
                $this->last_response = new IntuitResponse(
                    ['Content-Type' => 'application/xml', 'intuit_tid' => 'test-tid'],
                    $this->response_xml,
                    200,
                    false
                );

                return $this->last_response;
            }

            public function prepareRequest($url, $method, array $headers, $body, $timeOut, $verifySSL)
            {
            }

            public function getLastResponse()
            {
                return $this->last_response;
            }

            public function setIntuitResponse($response)
            {
            }
        };
    }
}
