<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Quickbooks\DataService;

use QuickBooksOnline\API\DataService\DataService;

class LegacyResourceClient extends ResourceClient
{
    protected function getDataService(): DataService
    {
        if ($this->data_service === false) {
            $this->data_service = DataService::Configure(
                [
                    'auth_mode' => 'oauth1',
                    'consumerKey' => $this->getClientId(),
                    'consumerSecret' => $this->getClientSecret(),
                    'accessTokenKey' => $this->getAccessToken(),
                    'accessTokenSecret' => $this->getRefreshToken(),
                    'QBORealmID' => $this->getRealmId(),
                    'baseUrl' => $this->getBaseUrl(),
                ]
            );
        }

        return $this->data_service;
    }
}
