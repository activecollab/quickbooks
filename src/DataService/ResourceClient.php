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
use Exception;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\FacadeHelper;

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

    protected $data_service = false;

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

    private $entity;

    public function setEntity(string $entity): ResourceClientInterface
    {
        $this->entity = $entity;

        return $this;
    }

    public function query(string $query = null): QueryResponse
    {
        if ($query === null) {
            $query = "SELECT * FROM {$this->entity}";
        }

        $entities = $this->getDataService()->Query($query);

        if ($error = $this->getDataService()->getLastError()) {
            throw new Exception($error->getResponseBody());
        }

        if (empty($entities)) {
            return new QueryResponse([]);
        }

        $result = [];

        foreach ($entities as $entity) {
            $result[0][] = $this->objectToArray($entity);
        }

        return new QueryResponse($result);
    }

    public function cdc(array $entities, DateTime $changed_since): array
    {
        $cdc_response = $this->getDataService()->CDC(
            $entities,
            urlencode(
                date_format(
                    $changed_since,
                    DateTime::ATOM
                )
            )
        );

        if ($error = $this->getDataService()->getLastError()) {
            throw new Exception($error->getResponseBody());
        }

        $result = [];

        foreach ($cdc_response->entities as $entity_name => $entity_array) {
            if (!isset($result[$entity_name])) {
                $result[$entity_name] = [];
            }

            if (is_array($entity_array)) {
                foreach ($entity_array as $entity) {
                    $result[$entity_name][] = new Entity($this->objectToArray($entity));
                }
            }
        }

        return $result;
    }

    public function read(int $id): Entity
    {
        $entity = $this->getDataService()->FindById($this->entity, $id);

        if ($error = $this->getDataService()->getLastError()) {
            throw new Exception(
                sprintf(
                    'OAuth error: %s; Response body: %s',
                    $error->getOAuthHelperError(),
                    $error->getResponseBody()
                ),
                $error->getHttpStatusCode()
            );
        }

        return new Entity($this->objectToArray($entity));
    }

    public function create(array $payload): Entity
    {
        $entity = $this
            ->getDataService()
            ->Add(
                FacadeHelper::reflectArrayToObject($this->entity, $payload)
            );

        if ($error = $this->getDataService()->getLastError()) {
            throw new Exception(
                sprintf(
                    'OAuth error: %s; Response body: %s',
                    $error->getOAuthHelperError(),
                    $error->getResponseBody()
                ),
                $error->getHttpStatusCode()
            );
        }

        return new Entity($this->objectToArray($entity));
    }

    private function objectToArray($entity = null): array
    {
        if (!$entity) {
            return [];
        }

        return json_decode(
            json_encode(
                is_array($entity) ? $entity[0] : $entity
            ),
            true
        );
    }
}
