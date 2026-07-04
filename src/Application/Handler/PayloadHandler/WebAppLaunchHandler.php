<?php

declare(strict_types=1);

namespace Semitexa\WebApps\Application\Handler\PayloadHandler;

use Semitexa\Core\Attribute\AsPayloadHandler;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\ResourceResponse;
use Semitexa\WebApps\Application\Payload\Request\WebAppLaunchPayload;
use Semitexa\WebApps\Application\Service\WebAppStore;

/**
 * Renders a registered web-app: the site wrapped full-bleed in an iframe inside
 * the OS window (no restrictive sandbox, so real logins work). A small
 * "Open in new tab" affordance is always present, and — for sites that block
 * framing (`X-Frame-Options`/CSP) — a hint appears unless the Semitexa companion
 * extension is active (it strips those headers and sets a marker on <html>).
 */
#[AsPayloadHandler(payload: WebAppLaunchPayload::class, resource: ResourceResponse::class)]
final class WebAppLaunchHandler implements TypedHandlerInterface
{
    public function handle(WebAppLaunchPayload $payload, ResourceResponse $resource): ResourceResponse
    {
        $app = (new WebAppStore())->find(trim($payload->getId()));

        if ($app === null) {
            return $resource
                ->setStatusCode(404)
                ->setContent($this->notFound())
                ->setHeader('Content-Type', 'text/html; charset=utf-8');
        }

        $url = htmlspecialchars($app['url'], ENT_QUOTES);
        $name = htmlspecialchars($app['name'], ENT_QUOTES);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$name} · Semi</title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  html, body { margin: 0; height: 100%; background: #0c1020; font-family: system-ui, sans-serif; }
  .wrap { position: relative; height: 100%; display: flex; flex-direction: column; }
  .frame { flex: 1 1 auto; min-height: 0; border: 0; width: 100%; background: #0c1020; }
  .hint {
    position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
    max-width: 380px; text-align: center; color: #c7d2e8; background: rgba(15,23,42,.94);
    border: 1px solid rgba(148,163,184,.22); border-radius: 14px; padding: 22px 24px;
    font-size: 14px; line-height: 1.55; display: none; z-index: 2;
  }
  .hint b { color: #eaf2ff; }
  .hint a { color: #a78bfa; text-decoration: none; font-weight: 600; }
  .hint a:hover { text-decoration: underline; }
  .newtab {
    position: absolute; right: 12px; top: 10px; z-index: 3;
    font-size: 12px; color: #c7d2e8; background: rgba(15,23,42,.82);
    border: 1px solid rgba(148,163,184,.22); border-radius: 8px; padding: 6px 10px; text-decoration: none;
  }
  .newtab:hover { border-color: #a78bfa; color: #eaf2ff; }
</style></head>
<body>
  <div class="wrap">
    <a class="newtab" href="{$url}" target="_blank" rel="noopener">Open in new tab ↗</a>
    <iframe class="frame" src="{$url}" title="{$name}"
      allow="autoplay; encrypted-media; fullscreen; clipboard-write; picture-in-picture"></iframe>
    <div class="hint" id="hint">
      <b>{$name}</b> blocks being embedded.<br>
      Install the <b>Semitexa companion</b> extension to run it here with your login —
      or <a href="{$url}" target="_blank" rel="noopener">open it in a new tab ↗</a>.
    </div>
  </div>
<script>
  // If the companion is active it sets data-semitexa-companion on <html> and
  // strips the framing headers, so the frame just works — no hint needed.
  // Otherwise, after a grace period with no successful load, surface the hint.
  (function () {
    var hint = document.getElementById('hint');
    var frame = document.querySelector('.frame');
    var loaded = false;
    frame.addEventListener('load', function () { loaded = true; });
    setTimeout(function () {
      var companion = document.documentElement.dataset.semitexaCompanion;
      if (!companion && !loaded) { hint.style.display = 'block'; }
    }, 3500);
  })();
</script>
</body></html>
HTML;

        return $resource
            ->setContent($html)
            ->setHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function notFound(): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>:root{color-scheme:dark}'
            . 'html,body{margin:0;height:100%;background:#0c1020;color:#8d9bb8;'
            . 'font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center}</style></head>'
            . '<body><div>This app is no longer in your list.</div></body></html>';
    }
}
