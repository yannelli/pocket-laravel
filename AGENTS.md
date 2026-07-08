# AGENTS.md

## Cursor Cloud specific instructions

This repository is the **Pocket Laravel SDK** — a PHP/Laravel client library (not a runnable
web app or service). There is no dev server to start; "running" it means executing its test
suite or exercising its classes from a PHP script.

### Environment
- Runtime is **PHP 8.3 CLI** (installed as a system dependency). Composer is not on `PATH`;
  use the repo-bundled phar via `php composer.phar ...`.
- `composer.lock` and `vendor/` are git-ignored, so dependencies are resolved fresh on install.
  The update script runs `php composer.phar install`, which populates `vendor/`.

### Standard commands
Composer scripts are documented in `CLAUDE.md` and `composer.json` (`test`, `analyse`, `format`,
`test-coverage`). Run them with the bundled phar, e.g. `php composer.phar test`, or invoke the
binaries directly (`vendor/bin/pest`, `vendor/bin/phpstan analyse`, `vendor/bin/pint`).

### Non-obvious caveats
- Tests mock the HTTP layer (see `tests/Helpers/MocksHttpResponses.php`), so **no `POCKET_API_KEY`
  or network access is required** to run `php composer.phar test`. A real key is only needed to
  hit the live Pocket API.
- `php composer.phar format` (Laravel Pint) **rewrites files in place**. To check formatting
  without modifying the tree, use `vendor/bin/pint --test`.
- CI (`.github/workflows/run-tests.yml`) only runs Pest — not Pint or PHPStan. The repo currently
  has some pre-existing Pint style diffs and PHPStan (level 5) findings that are unrelated to any
  given change; don't treat those as regressions you introduced.
