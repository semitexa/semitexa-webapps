<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Payload\Request;

use Semitexa\Authorization\Attribute\AsProtectedPayload;
use Semitexa\Os\Domain\Contract\OsSurfacePayloadInterface;
use Semitexa\Core\Contract\ValidatablePayloadInterface;
use Semitexa\Core\Http\Response\ResourceResponse;

/**
 * Relaunch a registered web-app by id (from the "Your apps" launcher) without a
 * round-trip through the planner.
 *
 * A signed-in console surface: public, any visitor could list the site's web
 * apps, remove them, and push dialogs into the console.
 */
#[AsProtectedPayload(
    path: '/os/webapp/open',
    methods: ['POST'],
    responseWith: ResourceResponse::class,
    consumes: ['application/json'],
    produces: ['application/json'],
)]
final class WebAppOpenPayload implements ValidatablePayloadInterface, OsSurfacePayloadInterface
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
