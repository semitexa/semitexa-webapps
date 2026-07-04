<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Handler\PayloadHandler;

use Semitexa\Core\Attribute\AsPayloadHandler;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\ResourceResponse;
use Semitexa\Os\Application\Service\OpenDialogStore;
use Semitexa\WebApps\Application\Payload\Request\WebAppOpenPayload;
use Semitexa\WebApps\Application\Service\WebAppStore;

/**
 * Relaunch a registered web-app as a dialog by id (from the "Your apps"
 * launcher) — no planner round-trip. Reuses the open dialog if one is already
 * running for this app.
 */
#[AsPayloadHandler(payload: WebAppOpenPayload::class, resource: ResourceResponse::class)]
final class WebAppOpenHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected OpenDialogStore $dialogs;

    public function handle(WebAppOpenPayload $payload, ResourceResponse $resource): ResourceResponse
    {
        $app = (new WebAppStore())->find(trim($payload->getId()));
        if ($app === null) {
            return $resource
                ->setStatusCode(404)
                ->setContent((string) json_encode(['error' => 'Unknown app.'], JSON_UNESCAPED_SLASHES))
                ->setHeader('Content-Type', 'application/json');
        }

        $skill = 'webapp:' . $app['id'];
        $dialog = null;
        foreach ($this->dialogs->list() as $existing) {
            if (($existing['skill'] ?? null) === $skill) {
                $dialog = $existing;
                break;
            }
        }

        $dialog ??= $this->dialogs->open(
            skill: $skill,
            title: $app['name'],
            icon: $app['icon'],
            entry: '/os/webapp/' . rawurlencode($app['id']),
        );

        return $resource
            ->setContent((string) json_encode(['dialog' => $dialog], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->setHeader('Content-Type', 'application/json');
    }
}
