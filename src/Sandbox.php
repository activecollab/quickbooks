<?php

namespace ActiveCollab\Quickbooks;

/**
 * Quickbooks sandbox
 */
class Sandbox extends DataService
{
    /**
     * Return sandbox api url
     * 
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://sandbox-quickbooks.api.intuit.com/v'.parent::API_VERSION;
    }
}