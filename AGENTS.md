# AGENTS.md

## Cursor Cloud specific instructions

This repository is the **Pocket Laravel SDK** (`yannelli/pocket-laravel`) — a PHP/Laravel
package (a library, not a runnable web app) that wraps the Pocket API. There is no server or
UI to start; "running" it means executing the test suite and exercising the SDK classes.

### Environment
- Requires **PHP 8.3+** (CLI) with `mbstring`, `xml`/`dom`, `curl`, `zip`, `bcmath`, `intl`,
  `sqlite3`, `gd`, `soap` extensions. These are installed via the startup update script.
- Composer is bundled in the repo as `composer.phar` (no global composer required). Invoke it
  with `php composer.phar <cmd>`.
- `composer.lock` and `/vendor` are git-ignored, so dependencies are resolved fresh on each
  setup (the update script runs `php composer.phar install`).

### Common commands (defined in `composer.json` scripts)
- Tests: `php composer.phar test` (Pest + Orchestra Testbench, ~105 tests, no network needed —
  HTTP is mocked via Guzzle `MockHandler`).
- Single test: `vendor/bin/pest tests/Feature/RecordingsResourceTest.php` or
  `vendor/bin/pest --filter "test name"`.
- Static analysis: `php composer.phar analyse` (PHPStan/Larastan level 5).
- Formatting: `php composer.phar format` (Laravel Pint); check-only with `vendor/bin/pint --test`.

### Non-obvious notes
- The test suite mocks all HTTP calls, so it runs fully offline. Real usage of the SDK against
  the live Pocket API (`https://public.heypocketai.com`) requires a `POCKET_API_KEY`.
- To exercise the SDK standalone (outside a Laravel app), construct a `PocketClient` with a
  custom Guzzle `HandlerStack` and pass it to a resource (e.g. `new RecordingsResource($client)`),
  as the tests do in `tests/Helpers/MocksHttpResponses.php`.
- As of environment setup, `php composer.phar analyse` and `vendor/bin/pint --test` report
  pre-existing findings (a few Larastan `env()`/nullable-offset notes and Pint style diffs).
  These are existing code-quality items, not environment breakage — the tools run correctly.
