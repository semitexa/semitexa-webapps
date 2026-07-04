<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Payload\Request;

use Semitexa\Core\Attribute\AsPublicPayload;
use Semitexa\Core\Http\Response\ResourceResponse;

/**
 * Lists the user's registered web-apps as JSON for the "Your apps" launcher.
 */
#[AsPublicPayload(
    path: '/os/webapps',
    methods: ['GET'],
    responseWith: ResourceResponse::class,
    produces: ['application/json'],
)]
final class WebAppsListPayload
{
}
