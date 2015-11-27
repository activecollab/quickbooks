<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\Data\Entity;
use ActiveCollab\Quickbooks\Data\QueryResponse;

class QueryResponseTest extends TestCase
{

    /**
     * @var QueryResponse
     */
    protected $query_response;

    /**
     * Set up test environmeet
     */
    public function setUp()
    {
        parent::setUp();

        $data = [
            'Invoice' => [
                [ 'Id' => 1 ],
                [ 'Id' => 2 ]
            ]
        ];

        $this->query_response = new QueryResponse($data);
        $this->assertInstanceOf('ActiveCollab\Quickbooks\Data\QueryResponse', $this->query_response);
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        $this->query_response = null;

        parent::tearDown();
    }

    /**
     * Test collected entities
     */
    public function testEntitiesCollection()
    {
        $this->assertEquals(2, $this->query_response->count());

        foreach ($this->query_response as $entity) {
            $this->assertInstanceOf('ActiveCollab\Quickbooks\Data\Entity', $entity);
        }
    }

    /**
     * Test serialization
     */
    public function testSerialize()
    {
        $results = json_decode(json_encode($this->query_response), true);

        $this->assertTrue(is_array($results));

        foreach ($results as $result) {
            $this->assertTrue(isset($result['Id']));
        }
    }
}