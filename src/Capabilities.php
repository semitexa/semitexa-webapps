<?php

declare(strict_types=1);

namespace Semitexa\WebApps;

use Semitexa\Core\Attribute\Capability;

/**
 * What this package offers, for the capability catalog.
 *
 * Without this the package is invisible to anyone whose project has not
 * installed it - which is precisely the audience worth telling, since they are
 * the ones about to build it by hand. The convention is one `Capabilities` class
 * per package: a definite place to look, and a definite place for a guard to
 * check.
 *
 * Nothing reads this at runtime.
 */
#[Capability(
    id: 'webapps.registry',
    summary: 'External sites registered as first-class OS apps, framed with a companion extension where headers forbid it.',
    useWhen: 'Users should reach an outside web application from inside the OS shell without leaving it.',
    avoidWhen: 'The tool is yours - build it as a UI-skill and skip the framing entirely.',
    replaces: [
        'an iframe hard-coded into a template, blank because the site refuses to be framed',
        'a link that drops the user out of the shell and loses their session context',
    ],
    seeAlso: 'semitexa/os',
)]
final class Capabilities
{
}
