<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Platform\Settings\Domain\Contract\SettingsStoreInterface;
use Semitexa\WebApps\Application\Service\WebAppStore;

/**
 * The registry behind "open YouTube".
 *
 * Its URL handling is a trust boundary rather than a formatting step: the
 * address arrives from the model through OpenWebAppSkill's exposed `url`
 * argument and is rendered as an iframe's src, so what it accepts is the whole
 * of what the OS will try to frame.
 */
final class WebAppStoreTest extends TestCase
{
    private WebAppStore $store;

    protected function setUp(): void
    {
        $this->store = new WebAppStore();
        (new \ReflectionProperty(WebAppStore::class, 'settings'))->setValue($this->store, $this->inMemorySettings());
    }

    #[Test]
    public function a_fresh_install_has_no_apps(): void
    {
        self::assertSame([], $this->store->all());
    }

    #[Test]
    public function a_bare_host_is_assumed_to_be_https(): void
    {
        self::assertSame('https://youtube.com', $this->store->add('YouTube', 'youtube.com')['url']);
    }

    #[Test]
    public function a_protocol_relative_url_is_assumed_to_be_https(): void
    {
        self::assertSame('https://netflix.com', $this->store->add('Netflix', '//netflix.com')['url']);
    }

    #[Test]
    public function an_explicit_http_url_is_left_alone(): void
    {
        self::assertSame('http://intranet.local/wiki', $this->store->add('Wiki', 'http://intranet.local/wiki')['url']);
    }

    /**
     * The regression this suite was written for. "ftp://files.example.com" used
     * to be rewritten as "https://ftp://files.example.com" — accepted, and with
     * a host of "ftp", so every ftp address in the world deduped onto one app.
     *
     * @param string $url something the OS cannot frame
     */
    #[Test]
    #[DataProvider('unframeableUrls')]
    public function a_url_the_os_cannot_frame_is_refused(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid http(s) URL is required.');

        $this->store->add('Whatever', $url);
    }

    /** @return iterable<string, array{string}> */
    public static function unframeableUrls(): iterable
    {
        yield 'another scheme' => ['ftp://files.example.com'];
        yield 'the local filesystem' => ['file:///etc/passwd'];
        yield 'script as a url' => ['javascript:alert(1)'];
        yield 'script, oddly cased' => ['JaVaScRiPt:alert(1)'];
        yield 'an inline document' => ['data:text/html,<script>x</script>'];
        yield 'a scheme with no host' => ['https://'];
        yield 'prose, not an address' => ['not a url'];
        yield 'a single-slash typo' => ['https:/example.com'];
        yield 'a scheme with no name' => ['://example.com'];
        yield 'an empty first label' => ['https://.com'];
        yield 'a websocket' => ['ws://example.com'];
    }

    /**
     * The refusals above must not cost the addresses people really use: a
     * bare host with a port, a raw IP, credentials, a punycode domain.
     *
     * @param string $url something the OS should happily open
     */
    #[Test]
    #[DataProvider('frameableUrls')]
    public function an_address_the_os_can_open_survives(string $url, string $expectedHost): void
    {
        self::assertSame($expectedHost, $this->store->add('App', $url)['host']);
    }

    /** @return iterable<string, array{string, string}> */
    public static function frameableUrls(): iterable
    {
        yield 'a bare host with a port' => ['localhost:9507/os', 'localhost'];
        yield 'an explicit port' => ['https://intranet.local:8443/x', 'intranet.local'];
        yield 'a raw address' => ['https://192.168.1.5:8080', '192.168.1.5'];
        yield 'credentials in the url' => ['https://user:pass@example.com', 'example.com'];
        yield 'punycode' => ['http://xn--80ak6aa92e.com', 'xn--80ak6aa92e.com'];
        yield 'an underscored host' => ['https://a_b.example.com', 'a_b.example.com'];
        yield 'an internationalised path' => ['https://пошта.укр/шлях', 'пошта.укр'];
    }

    #[Test]
    public function an_empty_url_is_refused_with_its_own_message(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A URL is required.');

        $this->store->add('YouTube', '   ');
    }

    #[Test]
    public function an_empty_name_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An app name is required.');

        $this->store->add("  \n ", 'youtube.com');
    }

    /**
     * Hosts are case-insensitive, so these are one site. Before the host was
     * lower-cased they registered as two apps and the launcher showed both.
     */
    #[Test]
    public function the_same_host_in_different_case_is_one_app(): void
    {
        $first = $this->store->add('YouTube', 'https://YouTube.com');
        $again = $this->store->add('youtube', 'https://youtube.com/feed');

        self::assertSame($first['id'], $again['id'], 'the second call should return the app already registered');
        self::assertCount(1, $this->store->all());
        self::assertSame('youtube.com', $first['host']);
    }

    #[Test]
    public function registering_a_second_site_keeps_the_newest_first(): void
    {
        $this->store->add('YouTube', 'youtube.com');
        $this->store->add('Netflix', 'netflix.com');

        self::assertSame(['Netflix', 'YouTube'], array_column($this->store->all(), 'name'));
    }

    #[Test]
    public function a_name_is_collapsed_and_capped(): void
    {
        $app = $this->store->add("  Google   Docs \n ", 'docs.google.com');
        self::assertSame('Google Docs', $app['name']);

        $long = $this->store->add(str_repeat('a', 80), 'example.com');
        self::assertSame(60, mb_strlen($long['name']));
    }

    #[Test]
    public function a_registered_app_is_findable_by_id_and_removable(): void
    {
        $app = $this->store->add('YouTube', 'youtube.com');

        self::assertSame($app, $this->store->find($app['id']));
        self::assertNull($this->store->find('no-such-id'));

        $this->store->remove($app['id']);
        self::assertSame([], $this->store->all());
        self::assertNull($this->store->find($app['id']));
    }

    #[Test]
    public function removing_an_unknown_id_leaves_the_registry_alone(): void
    {
        $app = $this->store->add('YouTube', 'youtube.com');
        $this->store->remove('no-such-id');

        self::assertSame([$app], $this->store->all());
    }

    /**
     * The settings value is whatever was persisted last — including by an older
     * version, or by hand. A malformed row must not take the launcher down.
     */
    #[Test]
    public function malformed_stored_rows_are_skipped_rather_than_returned(): void
    {
        $settings = $this->inMemorySettings();
        $settings->set('os', 'web_apps', [
            ['id' => 'a', 'name' => 'Good', 'url' => 'https://good.example', 'host' => 'good.example'],
            ['name' => 'no id', 'url' => 'https://x.example'],
            ['id' => 'c', 'url' => 'https://no-name.example'],
            'a bare string',
            42,
        ]);
        (new \ReflectionProperty(WebAppStore::class, 'settings'))->setValue($this->store, $settings);

        self::assertSame(['Good'], array_column($this->store->all(), 'name'));
    }

    #[Test]
    public function a_stored_row_without_a_host_derives_one_from_its_url(): void
    {
        $settings = $this->inMemorySettings();
        $settings->set('os', 'web_apps', [['id' => 'a', 'name' => 'Docs', 'url' => 'https://Docs.Google.com/x']]);
        (new \ReflectionProperty(WebAppStore::class, 'settings'))->setValue($this->store, $settings);

        self::assertSame('docs.google.com', $this->store->all()[0]['host']);
    }

    #[Test]
    public function a_stored_value_that_is_not_a_list_reads_as_empty(): void
    {
        $settings = $this->inMemorySettings();
        $settings->set('os', 'web_apps', 'corrupted');
        (new \ReflectionProperty(WebAppStore::class, 'settings'))->setValue($this->store, $settings);

        self::assertSame([], $this->store->all());
    }

    #[Test]
    public function an_internationalised_host_is_accepted(): void
    {
        // FILTER_VALIDATE_URL rejects these; real sites use them.
        self::assertSame('https://пошта.укр', $this->store->add('Пошта', 'https://пошта.укр')['url']);
    }

    private function inMemorySettings(): SettingsStoreInterface
    {
        return new class implements SettingsStoreInterface {
            /** @var array<string, mixed> */
            private array $data = [];

            public function get(string $moduleKey, string $key): mixed
            {
                return $this->data[$moduleKey . '/' . $key] ?? null;
            }

            public function getForUser(string $moduleKey, string $key, string $userId): mixed
            {
                return null;
            }

            public function set(string $moduleKey, string $key, mixed $value): void
            {
                $this->data[$moduleKey . '/' . $key] = $value;
            }

            public function setForUser(string $moduleKey, string $key, mixed $value, string $userId): void
            {
            }

            public function claim(string $moduleKey, string $key, mixed $expected, mixed $next): bool
            {
                if (($this->data[$moduleKey . '/' . $key] ?? null) === $expected) {
                    $this->data[$moduleKey . '/' . $key] = $next;

                    return true;
                }

                return false;
            }

            public function getAll(string $moduleKey): array
            {
                return [];
            }

            public function getAllForUser(string $moduleKey, string $userId): array
            {
                return [];
            }

            public function remove(string $moduleKey, string $key): void
            {
                unset($this->data[$moduleKey . '/' . $key]);
            }

            public function removeForUser(string $moduleKey, string $key, string $userId): void
            {
            }

            public function has(string $moduleKey, string $key): bool
            {
                return \array_key_exists($moduleKey . '/' . $key, $this->data);
            }

            public function hasForUser(string $moduleKey, string $key, string $userId): bool
            {
                return false;
            }
        };
    }
}
