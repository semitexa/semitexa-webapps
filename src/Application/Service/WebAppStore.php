<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Service;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Platform\Settings\Application\Service\SettingsStore;
use Semitexa\Platform\Settings\Domain\Contract\SettingsStoreInterface;

/**
 * The registry of the user's external web-apps (YouTube, Netflix, Google Docs…).
 * Each app is a name + a URL that the OS wraps in a dialog window. Persisted as a
 * JSON list in the platform settings store (module `os`, key `web_apps`), global
 * to the single-user OS — same mechanism {@see \Semitexa\Os\Application\Service\OsPreferences}
 * uses. No ORM table needed.
 *
 * The settings store is injected for container-managed callers and lazily
 * constructed for the skill that `new`s this outside DI.
 *
 * @phpstan-type WebApp array{id: string, name: string, url: string, host: string, icon: string, createdAt: string}
 */
#[AsService]
final class WebAppStore
{
    private const MODULE = 'os';
    private const KEY = 'web_apps';
    private const MAX_NAME = 60;

    #[InjectAsReadonly]
    protected SettingsStoreInterface $settings;

    /**
     * All registered apps, newest first.
     *
     * @return list<WebApp>
     */
    public function all(): array
    {
        $raw = $this->settings()->get(self::MODULE, self::KEY);
        if (!is_array($raw)) {
            return [];
        }

        $apps = [];
        foreach ($raw as $row) {
            if (is_array($row) && isset($row['id'], $row['name'], $row['url'])) {
                $apps[] = $this->shape($row);
            }
        }

        return $apps;
    }

    /**
     * Register (or return the existing) app for a URL. Dedupes by host so
     * "open YouTube" twice doesn't pile up duplicates.
     *
     * @return WebApp
     *
     * @throws \InvalidArgumentException on an empty name or non-http(s) URL
     */
    public function add(string $name, string $url, string $icon = 'globe'): array
    {
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            throw new \InvalidArgumentException('An app name is required.');
        }
        if (mb_strlen($name) > self::MAX_NAME) {
            $name = mb_substr($name, 0, self::MAX_NAME);
        }

        $url = $this->normalizeUrl($url);
        $host = (string) (parse_url($url, PHP_URL_HOST) ?: '');

        $apps = $this->all();
        foreach ($apps as $app) {
            if ($host !== '' && $app['host'] === $host) {
                return $app; // already registered — reuse it
            }
        }

        $app = $this->shape([
            'id' => bin2hex(random_bytes(8)),
            'name' => $name,
            'url' => $url,
            'host' => $host,
            'icon' => trim($icon) !== '' ? trim($icon) : 'globe',
            'createdAt' => (new \DateTimeImmutable())->format('c'),
        ]);

        array_unshift($apps, $app);
        $this->persist($apps);

        return $app;
    }

    /**
     * @return WebApp|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $app) {
            if ($app['id'] === $id) {
                return $app;
            }
        }

        return null;
    }

    public function remove(string $id): void
    {
        $apps = array_values(array_filter($this->all(), static fn (array $a): bool => $a['id'] !== $id));
        $this->persist($apps);
    }

    /**
     * @param list<WebApp> $apps
     */
    private function persist(array $apps): void
    {
        $this->settings()->set(self::MODULE, self::KEY, $apps);
    }

    /**
     * Accept only absolute http/https URLs; default a bare host to https://.
     *
     * @throws \InvalidArgumentException
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new \InvalidArgumentException('A URL is required.');
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $host = parse_url($url, PHP_URL_HOST);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($host === null || $host === '' || !in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('A valid http(s) URL is required.');
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return WebApp
     */
    private function shape(array $row): array
    {
        $url = (string) ($row['url'] ?? '');

        return [
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'url' => $url,
            'host' => (string) ($row['host'] ?? (parse_url($url, PHP_URL_HOST) ?: '')),
            'icon' => (string) ($row['icon'] ?? 'globe'),
            'createdAt' => (string) ($row['createdAt'] ?? ''),
        ];
    }

    private function settings(): SettingsStoreInterface
    {
        return $this->settings ??= new SettingsStore();
    }
}
