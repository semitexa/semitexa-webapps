<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Payload\Request;

use Semitexa\Authorization\Attribute\AsProtectedPayload;
use Semitexa\Os\Domain\Contract\OsSurfacePayloadInterface;
use Semitexa\Core\Contract\ValidatablePayloadInterface;
use Semitexa\Core\Http\Response\ResourceResponse;

/**
 * Remove a registered web-app from the user's list.
 *
 * A signed-in console surface: public, any visitor could list the site's web
 * apps, remove them, and push dialogs into the console.
 */
#[AsProtectedPayload(
    path: '/os/webapp/remove',
    methods: ['POST'],
    responseWith: ResourceResponse::class,
    consumes: ['application/json'],
    produces: ['application/json'],
)]
final class WebAppRemovePayload implements ValidatablePayloadInterface, OsSurfacePayloadInterface
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
