<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Quickbooks\DataService;

class DataService implements DataServiceInterface
{
    private $client_id;
    private $client_secret;

    public function __construct(string $client_id, string $client_secret)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    protected function getClientId(): string
    {
        return $this->client_id;
    }

    protected function getClientSecret(): string
    {
        return $this->client_secret;
    }

    abstract public function getDataService(): \QuickBooksOnline\API\DataService\DataService
//    {
//        return DataService::Configure(
//            [
//                'auth_mode' => 'oauth2',
//                'ClientID' => $this->getClientId(),
//                'ClientSecret' => $this->getClientSecret(),
//                'RedirectURI' => $this->redirect_uri,
//                'scope' => 'com.intuit.quickbooks.accounting',
//                'baseUrl' => $this->base_url, // "Development/Production"
//            ]
//        );
//    }
}