<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Quickbooks\Exception;

use RuntimeException;
use Throwable;

class QuickbooksQueryException extends RuntimeException
{
    private string $query;
    private string $sdk_message;
    private ?string $raw_response;

    public function __construct(
        string $query,
        string $sdk_message,
        ?string $raw_response = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            sprintf('QuickBooks query failed to deserialize: %s', $sdk_message),
            0,
            $previous
        );

        $this->query = $query;
        $this->sdk_message = $sdk_message;
        $this->raw_response = $raw_response;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getSdkMessage(): string
    {
        return $this->sdk_message;
    }

    public function getRawResponse(): ?string
    {
        return $this->raw_response;
    }
}
