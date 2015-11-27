<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\Sandbox;

class SandboxTest extends TestCase
{
    /**
     * @var Sandbox
     */
    protected $sandbox;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->sandbox = new Sandbox(
            'consumer-key',
            'consumer-key-secret',
            'access-token',
            'access-token-secret',
            'realm-id'
        );

        $this->assertInstanceOf('ActiveCollab\Quickbooks\Sandbox', $this->sandbox);
    }

    /**
     * Tear down test environement
     */
    public function tearDown()
    {
        $this->sandbox = null;

        parent::tearDown();
    }

    /**
     * @test
     */
    public function is_proper_api_url_placed()
    {
        $this->assertContains(
            'https://sandbox-quickbooks.api.intuit.com',
            $this->sandbox->getApiUrl(),
            'Invalid sandbox api url'
        );
    }
}