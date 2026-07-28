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
use ActiveCollab\Quickbooks\Exception\QuickbooksQueryException;
use DateTime;
use Exception;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\FacadeHelper;
use SensitiveParameter;
use Throwable;

class ResourceClient extends Client implements ResourceClientInterface
{
    private bool $log_raw_responses = false;
    private ?string $raw_response_log_dir = null;

    public function __construct(
        string $client_id,
        #[SensitiveParameter] string $client_secret,
        string $base_url,
        #[SensitiveParameter] private string $access_token,
        #[SensitiveParameter] private string $refresh_token,
        private string $realm_id,
        private ?string $minor_version = null
    )
    {
        parent::__construct($client_id, $client_secret, $base_url);
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
            $this->data_service = $this->applyMinorVersion(
                DataService::Configure(
                    [
                        'auth_mode' => 'oauth2',
                        'ClientID' => $this->getClientId(),
                        'ClientSecret' => $this->getClientSecret(),
                        'accessTokenKey' => $this->getAccessToken(),
                        'refreshTokenKey' => $this->getRefreshToken(),
                        'QBORealmID' => $this->getRealmId(),
                        'baseUrl' => $this->getBaseUrl(),
                    ]
                )
            );
        }

        return $this->data_service;
    }

    protected function applyMinorVersion(DataService $data_service): DataService
    {
        if ($this->minor_version !== null) {
            $data_service->setMinorVersion($this->minor_version);
        }

        return $data_service;
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

        $entities = $this->runDataServiceCall(
            fn () => $this->getDataService()->Query($query),
            $query
        );

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
        $cdc_response = $this->runDataServiceCall(
            fn () => $this->getDataService()->CDC(
                $entities,
                urlencode(
                    date_format(
                        $changed_since,
                        DateTime::ATOM
                    )
                )
            ),
            sprintf('CDC %s', implode(', ', $entities))
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
        $entity = $this->runDataServiceCall(
            fn () => $this->getDataService()->FindById($this->entity, $id),
            sprintf('FindById %s #%d', $this->entity, $id)
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

    public function create(array $payload): Entity
    {
        $entity = $this->runDataServiceCall(
            fn () => $this
                ->getDataService()
                ->Add(
                    FacadeHelper::reflectArrayToObject($this->entity, $payload)
                ),
            sprintf('Add %s', $this->entity)
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

    public function logRawResponses(bool $enable = true, ?string $dir = null): ResourceClientInterface
    {
        $this->log_raw_responses = $enable;
        $this->raw_response_log_dir = $dir;

        return $this;
    }

    private function runDataServiceCall(callable $call, string $query)
    {
        $response_capture_dir = $this->startRawResponseCapture();

        try {
            return $call();
        } catch (Throwable $failure) {
            throw new QuickbooksQueryException(
                $query,
                $failure->getMessage(),
                $this->readCapturedRawResponse($response_capture_dir),
                $failure
            );
        } finally {
            $this->stopRawResponseCapture($response_capture_dir);
        }
    }

    private function startRawResponseCapture(): ?string
    {
        if (!$this->log_raw_responses) {
            return null;
        }

        $response_capture_dir = $this->createResponseCaptureDirectory();

        if ($response_capture_dir === null) {
            return null;
        }

        $this->getDataService()
            ->setLogLocation($response_capture_dir)
            ->enableLog();

        return $response_capture_dir;
    }

    private function createResponseCaptureDirectory(): ?string
    {
        $parent_dir = $this->raw_response_log_dir ?: sys_get_temp_dir();
        $response_capture_dir = rtrim($parent_dir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . uniqid('qbo-response-', true);

        if (is_dir($response_capture_dir)) {
            return $response_capture_dir;
        }

        return mkdir($response_capture_dir, 0700, true) ? $response_capture_dir : null;
    }

    private function readCapturedRawResponse(?string $response_capture_dir): ?string
    {
        if ($response_capture_dir === null || !is_dir($response_capture_dir)) {
            return null;
        }

        $response_bodies = [];

        foreach ($this->listCapturedResponseFiles($response_capture_dir) as $response_file) {
            $response_body = @file_get_contents($response_file);

            if ($response_body !== false && $response_body !== '') {
                $response_bodies[] = $response_body;
            }
        }

        return $response_bodies ? implode("\n", $response_bodies) : null;
    }

    private function stopRawResponseCapture(?string $response_capture_dir): void
    {
        if ($response_capture_dir === null) {
            return;
        }

        $this->getDataService()->disableLog();

        if (!is_dir($response_capture_dir)) {
            return;
        }

        foreach ((array) glob($response_capture_dir . DIRECTORY_SEPARATOR . '*') as $captured_file) {
            @unlink($captured_file);
        }

        @rmdir($response_capture_dir);
    }

    private function listCapturedResponseFiles(string $response_capture_dir): array
    {
        return (array) glob($response_capture_dir . DIRECTORY_SEPARATOR . '*Response-*.txt');
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
