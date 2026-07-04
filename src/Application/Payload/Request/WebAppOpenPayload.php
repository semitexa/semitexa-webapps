<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Payload\Request;

use Semitexa\Core\Attribute\AsPublicPayload;
use Semitexa\Core\Contract\ValidatablePayloadInterface;
use Semitexa\Core\Http\Response\ResourceResponse;

/**
 * Relaunch a registered web-app by id (from the "Your apps" launcher) without a
 * round-trip through the planner.
 */
#[AsPublicPayload(
    path: '/os/webapp/open',
    methods: ['POST'],
    responseWith: ResourceResponse::class,
    consumes: ['application/json'],
    produces: ['application/json'],
)]
final class WebAppOpenPayload implements ValidatablePayloadInterface
{
    private string $id = '';

    /**
     * @return array<string, list<string>>
     */
    public function validate(): array
    {
        return trim($this->id) === '' ? ['id' => ['An app id is required.']] : [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
