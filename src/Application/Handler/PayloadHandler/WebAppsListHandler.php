<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Handler\PayloadHandler;

use Semitexa\Core\Attribute\InjectAsReadonly;
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
    /**
     * The container's, which reads the site the request is for: built here
     * with `new`, its settings store had no tenant context, and every site
     * read and wrote the root site's list.
     */
    #[InjectAsReadonly]
    protected WebAppStore $store;

    public function handle(WebAppsListPayload $payload, ResourceResponse $resource): ResourceResponse
    {
        return $resource
            ->setContent((string) json_encode(['apps' => $this->store->all()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->setHeader('Content-Type', 'application/json');
    }
}
