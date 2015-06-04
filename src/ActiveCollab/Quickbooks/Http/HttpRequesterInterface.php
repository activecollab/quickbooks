<?php

namespace ActiveCollab\Quickbooks\Http;


interface HttpRequesterInterface
{
    /**
     * Make a request.
     *
     * @param $method
     * @param $url
     * @param array $payload
     * @param array $headers
     * @return mixed
     */
    public function request($method, $url, array $payload = null, array $headers);
}