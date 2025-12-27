# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

Almost everything here is one class (`Configurator`) compiling a DI container, and
its value is in a few non-local invariants - the static/dynamic parameter split,
parameter-override ordering, extension registration, and debug detection. Read
`docs/internals.md` before touching them.

## Project Overview

**Nette Bootstrap** initializes a Nette application and generates its DI container:
it sets up the environment (debug mode, timezone), loads NEON configuration, wires
in framework extensions, and compiles/caches the container. Standalone package
(`nette/bootstrap`) built around the fluent `Configurator`.

- **PHP Version**: 8.3 - 8.5
- **Package**: `nette/bootstrap`

## Essential Commands

```bash
# Run all tests
vendor/bin/tester tests -s -C

# Run a single test file
vendor/bin/tester tests/Bootstrap/Configurator.basic.phpt

# Lowest-dependencies run (CI check)
composer update --prefer-lowest && vendor/bin/tester tests -s -C

# Static analysis
composer phpstan
```

The `-C` flag makes Tester use the system-wide PHP configuration.

## Conventions

- Every file starts with `declare(strict_types=1);`; Nette Coding Standard.
- Tests are Nette Tester `.phpt` files under `tests/Bootstrap/`; `tests/bootstrap.php`
  provides `getTempDir()` (per-process temp dir with GC) and a `test()` wrapper.
  NEON fixtures live in `tests/Bootstrap/files/`.

## Working in this repo

- **Static vs dynamic parameters is the defining trap.** Static parameters are
  baked into the compiled container and its cache key by value; dynamic parameters
  are keyed only by *name* - so changing a dynamic value does **not** recompile
  (that's the point: compile once, inject per request). `baseUrl` is always dynamic.
- **Parameter precedence is emergent:** auto-detected defaults < config-file
  parameters < explicitly set static parameters, imposed by the order params are
  added in `generateContainer`. Don't reorder those steps.
- **Extension registration:** everything comes from the hand-tuned
  `defaultExtensions` map; a class that does not exist is silently skipped
  (optional package not installed). There is **no auto-discovery** of extensions
  from Composer metadata - user extensions enter via the `extensions` config
  section (nette/di's `ExtensionsExtension`).
- **Debug detection is exact-match only - there is no CIDR/subnet support** (older
  prose claims otherwise; the code uses strict `in_array`). Localhost is
  auto-trusted only when no proxy header is present. See `detectDebugMode`.
- User-facing how-to (Configurator usage, NEON config format) is manual material
  and lives in the public web docs, not here.
