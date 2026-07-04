<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Handler\PayloadHandler;

use Semitexa\Core\Attribute\AsPayloadHandler;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\ResourceResponse;
use Semitexa\WebApps\Application\Payload\Request\WebAppsListPayload;
use Semitexa\WebApps\Application\Service\WebAppStore;

/**
 * Returns the registered web-apps as JSON for the "Your apps" launcher.
 */
#[AsPayloadHandler(payload: WebAppsListPayload::class, resource: ResourceResponse::class)]
final class WebAppsListHandler implements TypedHandlerInterface
{
    public function handle(WebAppsListPayload $payload, ResourceResponse $resource): ResourceResponse
    {
        return $resource
            ->setContent((string) json_encode(['apps' => (new WebAppStore())->all()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->setHeader('Content-Type', 'application/json');
    }
}
