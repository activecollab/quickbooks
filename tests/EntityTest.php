<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\Data\Entity;

class EntityTest extends TestCase
{
    /**
     * @var Entity
     */
    protected $entity;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->entity = new Entity($this->getEntityTestData());

        $this->assertInstanceOf(Entity::class, $this->entity);
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        $this->entity = null;

        parent::tearDown();
    }

    /**
     * Test methods
     */
    public function testMethods()
    {
        $data = $this->getEntityTestData();

        $this->assertEquals($data['Id'], $this->entity->getId());
        $this->assertEquals($data, $this->entity->getRawData());
    }

    /**
     * Test serialization
     */
    public function testSerialize()
    {
        $result = json_decode(json_encode($this->entity), true);

        $this->assertEquals(1, $result['Id']);
    }

    /**
     * Return entity test data
     * 
     * @return array
     */
    public function getEntityTestData()
    {
        return [
            "Id" => 1
        ];
    }
}