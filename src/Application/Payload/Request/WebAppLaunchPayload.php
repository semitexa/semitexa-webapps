<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Payload\Request;

use Semitexa\Authorization\Attribute\AsProtectedPayload;
use Semitexa\Os\Domain\Contract\OsSurfacePayloadInterface;
use Semitexa\Core\Http\Response\ResourceResponse;

/**
 * Entry route for a registered web-app dialog: renders the site wrapped in the
 * OS window. `{id}` selects which app from {@see \Semitexa\WebApps\Application\Service\WebAppStore}.
 *
 * A signed-in console surface: public, any visitor could list the site's web
 * apps, remove them, and push dialogs into the console.
 */
#[AsProtectedPayload(
    path: '/os/webapp/{id}',
    methods: ['GET'],
    responseWith: ResourceResponse::class,
    produces: ['text/html'],
)]
final class WebAppLaunchPayload implements OsSurfacePayloadInterface
{
    private string $id = '';

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
