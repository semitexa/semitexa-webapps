<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Payload\Request;

use Semitexa\Core\Attribute\AsPublicPayload;
use Semitexa\Core\Http\Response\ResourceResponse;

/**
 * Entry route for a registered web-app dialog: renders the site wrapped in the
 * OS window. `{id}` selects which app from {@see \Semitexa\WebApps\Application\Service\WebAppStore}.
 */
#[AsPublicPayload(
    path: '/os/webapp/{id}',
    methods: ['GET'],
    responseWith: ResourceResponse::class,
    produces: ['text/html'],
)]
final class WebAppLaunchPayload
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
