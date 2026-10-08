# Semitexa Web Apps

`semitexa/webapps`

A registry of external websites (YouTube, Google Docs, any https site) that Semitexa OS opens as its own apps, each wrapped in a dialog window. Registered apps are remembered and relaunch from the launcher.

## Install

Not included by the installer. Add it to an existing project from the project root:

```bash
docker compose run --rm --no-deps --user "$(id -u):$(id -g)" app composer require semitexa/webapps
bin/semitexa server:restart
```

It depends on `semitexa/os` (not in the installer's set); Composer installs it with it.

## What it provides

- The `open-web-app` assistant skill: the model names the service and its canonical URL, the app is registered and opened.
- Routes: `GET /os/webapps` (list), `GET /os/webapp/{id}` (the wrapped site), `POST /os/webapp/open`, `POST /os/webapp/remove`.
- Storage in the platform settings store (module `os`, key `web_apps`); no tables of its own.

Many sites forbid being framed (`X-Frame-Options`, CSP `frame-ancestors`). For those, the dialog shows a hint unless the Semitexa companion browser extension is loaded: https://github.com/semitexa/semitexa-companion

No console commands or attributes.

## License

MIT, see [LICENSE](LICENSE).
