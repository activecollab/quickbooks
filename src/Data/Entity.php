<?php

namespace ActiveCollab\Quickbooks\Data;

class Entity implements \JsonSerializable
{
    protected $data = [];

    /**
     * Construct entity
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $values = array_values($data);
        $this->data = isset($values[0]) && is_array($values[0]) ? $values[0] : $data;
    }

    /**
     * Return id
     *
     * @return int
     */
    public function getId()
    {
        return isset($this->data['Id']) ? (int) $this->data['Id'] : null;
    }

    /**
     * Return raw data
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->data;
    }

    /**
     * Serialize data
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}