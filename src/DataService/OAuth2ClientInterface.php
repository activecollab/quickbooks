<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Quickbooks\DataService;

use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;

interface OAuth2ClientInterface extends ClientInterface
{
    public function getAuthorizationUrl(): string;

    public function getAuthorizationToken(string $authorization_code, string $realm_id): OAuth2AccessToken;

    public function refreshAccessToken(string $refresh_token): OAuth2AccessToken;

    public function revokeAccessToken(string $access_token): bool;

    public function migrateOAuth1ToOAuth2(
        string $consumer_key,
        string $consumer_secret,
        string $access_token,
        string $access_token_secret,
        string $environment
    ): OAuth2AccessToken;
}
