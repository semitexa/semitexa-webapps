<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Authorization\Attribute\AsProtectedPayload;
use Semitexa\Core\Attribute\AsPublicPayload;
use Semitexa\Os\Domain\Contract\OsSurfacePayloadInterface;
use Semitexa\WebApps\Application\Payload\Request\WebAppLaunchPayload;
use Semitexa\WebApps\Application\Payload\Request\WebAppOpenPayload;
use Semitexa\WebApps\Application\Payload\Request\WebAppRemovePayload;
use Semitexa\WebApps\Application\Payload\Request\WebAppsListPayload;

/** Public, any visitor could list a site's web apps, remove them, and push dialogs into its console. */
final class WebAppRoutesAreConsoleSurfacesTest extends TestCase
{
    #[Test]
    public function every_route_is_a_signed_in_console_surface(): void
    {
        foreach ([WebAppsListPayload::class, WebAppLaunchPayload::class, WebAppOpenPayload::class, WebAppRemovePayload::class] as $payload) {
            $class = new \ReflectionClass($payload);
            self::assertNotSame([], $class->getAttributes(AsProtectedPayload::class), $payload);
            self::assertSame([], $class->getAttributes(AsPublicPayload::class), $payload);
            self::assertTrue($class->implementsInterface(OsSurfacePayloadInterface::class), $payload . ': the console gate must see it');
        }
    }
}
