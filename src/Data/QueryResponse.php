<?php

namespace ActiveCollab\Quickbooks\Data;

use ArrayIterator;

class QueryResponse implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * Collection of entities
     *
     * @var array
     */
    protected $entities = [];

    /**
     * Construct query response
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $values = array_values($data);
        $rows = isset($values[0]) && is_array($values[0]) ? $values[0] : [];

        foreach ($rows as $row) {
            $this->entities[] = new Entity($row);
        }
    }

    /**
     * Return iterator
     *
     * @return ArrayIterator
     */
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->entities);
    }

    /**
     * Return number of object in collection
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->entities);
    }

    /**
     * Return serialize data
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->entities;
    }
}
