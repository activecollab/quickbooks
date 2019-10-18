<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Quickbooks\DataService;

use ActiveCollab\Quickbooks\Data\Entity;
use ActiveCollab\Quickbooks\Data\QueryResponse;
use DateTime;

interface ResourceClientInterface extends ClientInterface
{
    public function setEntity(string $entity): ResourceClientInterface;

    public function query(string $query = null): QueryResponse;

    public function cdc(array $entities, DateTime $changed_since): array;

    public function read(int $id): Entity;

    public function create(array $payload): Entity;
}
