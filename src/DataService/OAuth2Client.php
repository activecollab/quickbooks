<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_type=1);

namespace ActiveCollab\Quickbooks\DataService;

use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;

class OAuth2Client extends Client implements OAuth2ClientInterface
{
    private $redirect_uri;

    public function __construct(
        string $client_id,
        string $client_secret,
        string $redirect_uri,
        string $base_url
    )
    {
        parent::__construct($client_id, $client_secret, $base_url);

        $this->redirect_uri = $redirect_uri;
    }

    protected function getRedirectUri(): string
    {
        return $this->redirect_uri;
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
                    'RedirectURI' => $this->getRedirectUri(),
                    'scope' => 'com.intuit.quickbooks.accounting',
                    'baseUrl' => $this->getBaseUrl(), // "Development/Production"
                ]
            );
        }

        return $this->data_service;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->getDataService()->getOAuth2LoginHelper()->getAuthorizationCodeURL();
    }

    public function getAuthorizationToken(
        string $authorization_code,
        string $realm_id
    ): OAuth2AccessToken
    {
        return $this->getDataService()->getOAuth2LoginHelper()->exchangeAuthorizationCodeForToken(
            $authorization_code,
            $realm_id
        );
    }
}
