<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Handler\PayloadHandler;

use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Attribute\AsPayloadHandler;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\ResourceResponse;
use Semitexa\WebApps\Application\Payload\Request\WebAppRemovePayload;
use Semitexa\WebApps\Application\Service\WebAppStore;

/**
 * Removes a registered web-app from the user's list (the open dialog, if any,
 * is closed separately by the client).
 */
#[AsPayloadHandler(payload: WebAppRemovePayload::class, resource: ResourceResponse::class)]
final class WebAppRemoveHandler implements TypedHandlerInterface
{
    /**
     * The container's, which reads the site the request is for: built here
     * with `new`, its settings store had no tenant context, and every site
     * read and wrote the root site's list.
     */
    #[InjectAsReadonly]
    protected WebAppStore $store;

    public function handle(WebAppRemovePayload $payload, ResourceResponse $resource): ResourceResponse
    {
        $this->store->remove(trim($payload->getId()));

        return $resource
            ->setContent((string) json_encode(['ok' => true], JSON_UNESCAPED_SLASHES))
            ->setHeader('Content-Type', 'application/json');
    }
}
