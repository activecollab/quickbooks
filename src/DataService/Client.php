<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Quickbooks\DataService;

use QuickBooksOnline\API\DataService\DataService;

abstract class Client implements ClientInterface
{
    private $client_id;
    private $client_secret;
    private $base_url;

    public function __construct(string $client_id, string $client_secret, string $base_url)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->base_url = $base_url;
    }

    protected function getClientId(): string
    {
        return $this->client_id;
    }

    protected function getClientSecret(): string
    {
        return $this->client_secret;
    }

    protected function getBaseUrl(): string
    {
        return $this->base_url;
    }

    abstract protected function getDataService(): DataService;
}
