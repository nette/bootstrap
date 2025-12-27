# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Nette Bootstrap** is a foundational library for the Nette Framework that handles application initialization and Dependency Injection (DI) container generation. It's a standalone package (`nette/bootstrap`) that:

- Initializes application environment settings (debug mode, timezone, etc.)
- Generates and manages the DI container
- Loads and processes NEON configuration files
- Integrates all Nette framework extensions
- Provides fluent interface for application configuration

**Requirements:** PHP 8.2-8.5

## Basic Usage

Typical bootstrap sequence for standalone usage (outside of full Nette framework):

```php
// 1. Create configurator
$configurator = new Nette\Bootstrap\Configurator;

// 2. Set temp directory (required - for DI container cache)
$configurator->setTempDirectory(__DIR__ . '/temp');

// 3. Load configuration files
$configurator->addConfig(__DIR__ . '/database.neon');
// Multiple configs can be added - later files override earlier ones
$configurator->addConfig(__DIR__ . '/services.neon');

// 4. Optional: Add dynamic parameters (not cached, evaluated per request)
$configurator->addDynamicParameters([
	'remoteIp' => $_SERVER['REMOTE_ADDR'],
]);

// 5. Create DI container
$container = $configurator->createContainer();

// 6. Get services from container
$db = $container->getByType(Nette\Database\Connection::class);
// or by name when multiple instances exist
$db = $container->getByName('database.main.connection');
```

**Important:** On Linux/macOS, set write permissions for the `temp/` directory.

### Development vs Production Mode

The container behavior differs between modes:

- **Development mode:** Container auto-updates when configuration files change (convenience)
- **Production mode:** Container generated once, changes ignored (performance)

**Autodetection:**
- Development: Running on localhost (`127.0.0.1` or `::1`) without proxy
- Production: All other cases

**Manual control:**

```php
// Enable for specific IP addresses
$configurator->setDebugMode('23.75.345.200');
// or array of IPs
$configurator->setDebugMode(['23.75.345.200', '192.168.1.100']);

// Combine IP with cookie secret (recommended for production debugging)
// Requires 'nette-debug' cookie with value 'secret1234'
$configurator->setDebugMode('secret1234@23.75.345.200');

// Force disable even for localhost
$configurator->setDebugMode(false);
```

### Retrieving Services from Container

```php
// By type (when only one instance exists)
$db = $container->getByType(Nette\Database\Connection::class);
$explorer = $container->getByType(Nette\Database\Explorer::class);

// By name (when multiple instances exist)
$mainDb = $container->getByName('database.main.connection');
$logDb = $container->getByName('database.log.connection');
```

## Essential Commands

### Testing

```bash
# Run all tests
vendor/bin/tester tests -s -C

# Run all tests with coverage info
vendor/bin/tester tests -s -C

# Run specific test file
vendor/bin/tester tests/Bootstrap/Configurator.basic.phpt

# Run tests with lowest dependencies (CI check)
composer update --prefer-lowest && vendor/bin/tester tests -s -C
```

### Code Quality

```bash
# Run PHPStan static analysis
composer phpstan

# Run PHPStan without progress bar (CI mode)
composer phpstan -- --no-progress
```

### Development

```bash
# Install dependencies
composer install

# Update dependencies
composer update
```

## Architecture

### Core Component: Configurator Class

Location: `src/Bootstrap/Configurator.php`

The `Configurator` class is the heart of this library. It follows a fluent interface pattern and handles:

**1. Environment Setup:**
- `setDebugMode()` - Toggle debug/production mode with IP-based detection
- `setTempDirectory()` - Set cache directory for DI container
- `setTimeZone()` - Configure PHP timezone
- `enableTracy()` - Enable Tracy debugger (if installed)

**2. Parameter Management:**
- **Static parameters** (set at config time, cached): `addStaticParameters()`
- **Dynamic parameters** (evaluated per request, not cached): `addDynamicParameters()`
  - Useful for runtime values like `$_SERVER['REMOTE_ADDR']`, current user, etc.
  - Referenced in config using `%parameterName%` notation

**Default parameters available:**
- `appDir` - Application directory
- `wwwDir` - Web root directory
- `tempDir` - Temporary/cache directory
- `vendorDir` - Composer vendor directory
- `rootDir` - Project root (auto-detected from Composer)
- `baseUrl` - Base URL (dynamically resolved from HTTP request)
- `debugMode` / `productionMode` - Environment flags
- `consoleMode` - CLI detection

**3. Configuration Loading:**
- `addConfig(string|array)` - Load NEON files or inline arrays
- Multiple configs are merged (later configs override earlier ones)
- Parameter expansion with `%paramName%` syntax
- Recursive parameter substitution with type coercion

**4. Container Generation:**
- `createContainer()` - Create and initialize DI container
- `loadContainer()` - Load cached container or generate new one
- Caching based on: configs, static params, dynamic param names, PHP version, Composer mtime

### Default Extensions System

Bootstrap pre-registers 17 framework extensions in `$defaultExtensions`:

```
application, assets, cache, constants, database, decorator, di,
extensions, forms, http, inject, latte, mail, php, routing,
search, security, session, tracy
```

Each extension processes its own configuration section (e.g., `database:`, `forms:`, `mail:`).

### Custom Bootstrap Extensions

Two extensions in `src/Bootstrap/Extensions/`:

**ConstantsExtension** - Defines PHP constants from configuration:
```neon
constants:
    MY_CONST: value
    ANOTHER: %parameter%
```

**PhpExtension** - Sets PHP ini directives:
```neon
php:
    date.timezone: Europe/Prague
    display_errors: "0"
```

### Configuration Flow

```
1. User creates Configurator and sets environment
2. User adds config files via addConfig()
3. Configurator loads all NEON configs
4. Extensions are instantiated in order
5. Each extension processes its config section
6. DI Compiler generates PHP container class
7. Container cached in tempDir/cache/nette.configurator/
8. Dynamic parameters injected at runtime
```

### Debug Mode Detection

`Configurator::detectDebugMode($list)` supports:
- **IP whitelist matching** - Single IP or CIDR subnet (e.g., `'23.75.345.200'` or `['192.168.1.0/24']`)
- **Cookie-based secret** - Format: `'secret@ip'` (e.g., `'secret1234@23.75.345.200'`)
  - Checks for `nette-debug` cookie with the secret value
  - Recommended for production debugging - safe even if IP changes
- **X-Forwarded-For** proxy header detection
- **Auto-enables** for localhost (`127.0.0.1`, `::1`)
- **Fallback** to computer hostname when no REMOTE_ADDR (CLI mode)

## Testing Infrastructure

**Framework:** Nette Tester with PHPT format (18 test files)

**Test Organization:**
```
tests/
├── bootstrap.php          # Test setup with getTempDir() helper
├── Bootstrap/
│   ├── Configurator.*.phpt
│   ├── ConstantsExtension.phpt
│   ├── PhpExtension.phpt
│   └── files/            # NEON test fixtures
└── tmp/                  # Temporary test output
```

**Test Bootstrap Helpers:**
- `getTempDir()` - Creates per-process temp directory with automatic garbage collection
- `test($title, $function)` - Simple test wrapper

**Test Pattern:**
```php
require __DIR__ . '/../bootstrap.php';

$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());
$configurator->addConfig('files/config.neon');
$container = $configurator->createContainer();

Assert::same('expected', $container->parameters['foo']);
```

## CI/CD

**GitHub Actions workflows:**

1. **Tests** (`.github/workflows/tests.yml`):
   - Matrix: PHP 8.0, 8.1, 8.2, 8.3, 8.4, 8.5
   - Runs `vendor/bin/tester tests -s -C`
   - Lowest dependencies test with `composer update --prefer-lowest`
   - Code coverage with phpdbg and php-coveralls

2. **Static Analysis** (`.github/workflows/static-analysis.yml`):
   - Runs on master branch (informative only)
   - Command: `composer phpstan -- --no-progress`
   - Continues on error

## NEON Configuration Format

Bootstrap uses NEON (Nette's configuration language). Key sections:

**Parameters:**
```neon
parameters:
    appDir: /path/to/app
    debugMode: true
    custom: value
```

**Extensions:**
```neon
extensions:
    myExt: App\MyExtension
```

**Framework-specific sections:**
```neon
constants:
    MY_CONST: value

php:
    date.timezone: Europe/Prague

database:
    dsn: "mysql:host=127.0.0.1;dbname=test"
    user: root
    password: secret
```

**Parameter expansion** - Use `%paramName%` to reference parameters in config files.

## Key Design Patterns

1. **Fluent Interface** - All configuration methods return `static` for method chaining
2. **Extension Pattern** - Extensible via DI extensions with `onCompile` callback
3. **Container Caching** - Lazy loading with intelligent cache invalidation
4. **Parameter Substitution** - Both compile-time (static) and runtime (dynamic) parameters
5. **Singleton DI Container** - Generated PHP class with lazy service initialization

## Important Notes

- Container cache key includes PHP version, so cache is invalidated on PHP upgrades
- Dynamic parameters are NOT cached - they're evaluated per request
- Configuration merging uses `DI\Config\Helpers::merge()` for deep array merging
- Debug mode can be forced via cookie secret (useful for production debugging)
- The library has no direct dependency on tracy/tracy (optional integration)
