<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Payload\Request;

use Semitexa\Authorization\Attribute\AsProtectedPayload;
use Semitexa\Os\Domain\Contract\OsSurfacePayloadInterface;
use Semitexa\Core\Http\Response\ResourceResponse;

/**
 * Lists the user's registered web-apps as JSON for the "Your apps" launcher.
 *
 * A signed-in console surface: public, any visitor could list the site's web
 * apps, remove them, and push dialogs into the console.
 */
#[AsProtectedPayload(
    path: '/os/webapps',
    methods: ['GET'],
    responseWith: ResourceResponse::class,
    produces: ['application/json'],
)]
final class WebAppsListPayload implements OsSurfacePayloadInterface
{
}
