<?php

namespace ActiveCollab\Quickbooks\Tests;

use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Get fixture for test mocking
     *
     * @param  string $name
     * @param  bool   $asArray
     *
     * @return array|string
     */
    public function getFixture($name, $asArray = true)
    {
        $fixture = file_get_contents(__DIR__.'/fixtures/'.$name.'.json');

        return $asArray ? json_decode($fixture,true) : $fixture;
    }

    public function assertArray($data)
    {
        $this->assertTrue(is_array($data), 'Not array');
    }


}