<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Quickbooks\DataService;

use QuickBooksOnline\API\DataService\DataService;

class ResourceClient extends Client implements ResourceClientInterface
{
    private $access_token;
    private $refresh_token;
    private $realm_id;

    public function __construct(
        string $client_id,
        string $client_secret,
        string $base_url,
        string $access_token,
        string $refresh_token,
        string $realm_id
    )
    {
        parent::__construct($client_id, $client_secret, $base_url);

        $this->access_token = $access_token;
        $this->refresh_token = $refresh_token;
        $this->realm_id = $realm_id;
    }

    protected function getAccessToken(): string
    {
        return $this->access_token;
    }

    protected function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    protected function getRealmId(): string
    {
        return $this->realm_id;
    }

    private $data_service = false;

    protected function getDataService(): DataService
    {
        if ($this->data_service === false) {
            $this->data_service = DataService::Configure(
                [
                    'auth_mode' => 'oauth2',
                    'ClientID' => $this->getClientId(),
                    'ClientSecret' => $this->getClientSecret(),
                    'accessTokenKey' => $this->getAccessToken(),
                    'refreshTokenKey' => $this->getRefreshToken(),
                    'QBORealmID' => $this->getRealmId(),
                    'baseUrl' => $this->getBaseUrl(),
                ]
            );
        }

        return $this->data_service;
    }
}
