<?php

namespace ActiveCollab\Quickbooks\Tests;

use ActiveCollab\Quickbooks\Data\Entity;
use ActiveCollab\Quickbooks\Data\QueryResponse;

class QueryResponseTest extends TestCase
{
    /**
     * Test result
     */
    public function testResult()
    {
        $data = [
            'Invoice' => [
                [ 'Id' => 1 ],
                [ 'Id' => 2 ]
            ]
        ];

        $queryResponse = new QueryResponse($data);

        $this->assertInstanceOf(QueryResponse::class, $queryResponse);
        $this->assertEquals(2, $queryResponse->count());

        foreach ($queryResponse as $entity) {
            $this->assertInstanceOf(Entity::class, $entity);
        }
    }
}