<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\DataService\LegacyResourceClient;
use ActiveCollab\Quickbooks\DataService\ResourceClient;
use QuickBooksOnline\API\DataService\DataService;
use ReflectionMethod;

class MinorVersionTest extends TestCase
{
    private const MINOR_VERSION = '68';

    private function getDataService(ResourceClient $client): DataService
    {
        $method = new ReflectionMethod($client, 'getDataService');
        $method->setAccessible(true);

        return $method->invoke($client);
    }

    private function sdkDefaultMinorVersion(): string
    {
        return DataService::Configure(
            [
                'auth_mode' => 'oauth2',
                'ClientID' => 'client-id',
                'ClientSecret' => 'client-secret',
                'accessTokenKey' => 'access-token',
                'refreshTokenKey' => 'refresh-token',
                'QBORealmID' => 'realm-id',
                'baseUrl' => 'https://base.example.com',
            ]
        )->getMinorVersion();
    }

    public function testResourceClientAppliesMinorVersionWhenProvided()
    {
        $client = new ResourceClient(
            'client-id',
            'client-secret',
            'https://base.example.com',
            'access-token',
            'refresh-token',
            'realm-id',
            self::MINOR_VERSION
        );

        $this->assertSame(self::MINOR_VERSION, $this->getDataService($client)->getMinorVersion());
    }

    public function testResourceClientLeavesSdkDefaultWhenMinorVersionOmitted()
    {
        $client = new ResourceClient(
            'client-id',
            'client-secret',
            'https://base.example.com',
            'access-token',
            'refresh-token',
            'realm-id'
        );

        $this->assertSame($this->sdkDefaultMinorVersion(), $this->getDataService($client)->getMinorVersion());
    }

    public function testLegacyResourceClientAppliesMinorVersionWhenProvided()
    {
        $client = new LegacyResourceClient(
            'client-id',
            'client-secret',
            'https://base.example.com',
            'access-token',
            'refresh-token',
            'realm-id',
            self::MINOR_VERSION
        );

        $this->assertSame(self::MINOR_VERSION, $this->getDataService($client)->getMinorVersion());
    }

    public function testLegacyResourceClientLeavesSdkDefaultWhenMinorVersionOmitted()
    {
        $client = new LegacyResourceClient(
            'client-id',
            'client-secret',
            'https://base.example.com',
            'access-token',
            'refresh-token',
            'realm-id'
        );

        $this->assertSame($this->sdkDefaultMinorVersion(), $this->getDataService($client)->getMinorVersion());
    }
}
