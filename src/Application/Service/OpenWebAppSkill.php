<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Service;

use Semitexa\Llm\Attribute\AsAiSkill;
use Semitexa\Llm\Domain\Contract\InvocableSkillInterface;
use Semitexa\Llm\Domain\Enum\AiArgumentPolicy;
use Semitexa\Llm\Domain\Enum\AiConfirmationMode;
use Semitexa\Llm\Domain\Enum\AiRiskLevel;
use Semitexa\Os\Application\Service\OpenDialogStore;

/**
 * Open (and remember) any external website as an OS app. The model resolves the
 * service the user names into a short title + its canonical URL; this registers
 * it in {@see WebAppStore} and opens it as a dialog window wrapping the site
 * ({@see \Semitexa\WebApps\Application\Handler\PayloadHandler\WebAppLaunchHandler}),
 * where the user logs in and uses it. Registered apps persist and relaunch from
 * the "Your apps" launcher.
 *
 * This is the generic replacement for bespoke per-service dialogs (YouTube,
 * music, …): the model finds the site, we wrap it, the OS remembers it.
 */
#[AsAiSkill(
    name: 'open-web-app',
    summary: 'Open (and remember) an external website or service as an OS app.',
    useWhen: 'The user wants to open, add, or use any external website / online service — YouTube, Netflix, Gmail, Google Docs, Spotify, Twitch, a bank, any site ("open YouTube", "add Netflix", "put on some music", "відкрий гугл документи"). Put a short display name in `name` and the service\'s canonical https URL in `url` (e.g. "https://www.youtube.com", "https://docs.google.com").',
    avoidWhen: 'The user wants a built-in OS tool (calendar, tic-tac-toe) or a plain answer — not an external website.',
    riskLevel: AiRiskLevel::Low,
    confirmation: AiConfirmationMode::Never,
    argumentPolicy: AiArgumentPolicy::Allowlisted,
    exposeArguments: ['name', 'url'],
    argumentHints: [
        'name' => 'Short display name (e.g. "YouTube").',
        'url' => 'Canonical https URL (e.g. "https://www.youtube.com").',
    ],
    channels: ['web'],
)]
final class OpenWebAppSkill implements InvocableSkillInterface
{
    public function invoke(array $arguments): string
    {
        $name = trim((string) ($arguments['name'] ?? ''));
        $url = trim((string) ($arguments['url'] ?? ''));

        if ($url === '') {
            return $name !== ''
                ? 'What\'s the web address for "' . $name . '"?'
                : 'Which site should I open? Tell me the name and I\'ll open it.';
        }
        if ($name === '') {
            $name = (string) (parse_url(str_contains($url, '://') ? $url : 'https://' . $url, PHP_URL_HOST) ?: 'App');
        }

        try {
            $app = (new WebAppStore())->add($name, $url);
        } catch (\InvalidArgumentException $e) {
            return 'I couldn\'t open that: ' . $e->getMessage();
        }

        // Open it as a dialog wrapping the site. The client reveals the window
        // once it sees the new dialog (reveal-after-executed in the shell).
        (new OpenDialogStore())->open(
            skill: 'webapp:' . $app['id'],
            title: $app['name'],
            icon: $app['icon'],
            entry: '/os/webapp/' . rawurlencode($app['id']),
        );

        return 'Opened ' . $app['name'] . ' — log in and use it right here. I\'ll keep it in your apps.';
    }
}
