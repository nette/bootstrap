# Bootstrap internals

How `nette/bootstrap` turns a `Configurator` into a compiled DI container, for
agents editing it. One class does almost everything (`Configurator`), so one file.
The value is concentrated in three non-local invariants: the static/dynamic
parameter split, the parameter-override ordering, and extension registration.

## Static vs dynamic parameters — and why a value change may not recompile

This is the package's defining distinction and its sharpest trap:

- **Static parameters** are baked **into the compiled container and into its cache
  key** (`generateContainerKey`). `addStaticParameters` merges them (`DI\Config\
  Helpers::merge`, later-wins).
- **Dynamic parameters** are **not** in the cache key by value — only their
  **names** are (`array_keys($this->dynamicParameters)`). Their values are passed
  to the container constructor at runtime (`new $class($this->dynamicParameters)`)
  and referenced in config as `%name%`.

**Consequence:** two runs with the same dynamic-parameter *names* but different
*values* reuse the **same cached container** — that is the entire point (compile
once, inject per request), but it burns anyone who expects a value change to
regenerate. Adding or removing a dynamic-parameter *name*, however, does
recompile. Note `baseUrl` is **always** a dynamic parameter (a `Statement`
resolved from the HTTP request), injected implicitly.

## Parameter override order: defaults < config files < explicit static

`getDefaultParameters()` seeds auto-detected params (`appDir`, `wwwDir`,
`vendorDir`, `rootDir`, `debugMode`, `consoleMode`, `baseUrl`) into **both**
`staticParameters` and `defaultParameters`. Then `addStaticParameters` removes any
overridden key from `defaultParameters` (`array_diff_key`), so `defaultParameters`
shrinks to *only the untouched auto-detected ones*.

`generateContainer` uses that split to impose a precedence that is **not visible
from any single call**:

1. `defaultParameters` are added as a `parameters` config **first** — so config
   files can override them;
2. the user's config files are loaded;
3. the **explicit** static params (`array_diff_key(staticParameters,
   defaultParameters)`) are added as a `parameters` config **last** — so they
   override the config files.

So the effective order is **auto-detected defaults < config-file parameters <
explicitly set static parameters**. Move either `addConfig` step and this
precedence breaks.

## Container cache key

`generateContainerKey` invalidates the compiled container on any of: static
parameters, explicit static params, **dynamic parameter names** (not values),
the config list, the **minor** PHP version (`PHP_VERSION_ID - PHP_RELEASE_VERSION`
— patch releases do *not* invalidate), the Composer `ClassLoader` file mtime (so
`composer install/update` regenerates). The dynamic-value omission is the same
trap as above, seen from the key side.

## Extension registration: the hand-tuned defaults map is the only source

`generateContainer` registers extensions solely from the public
`defaultExtensions` map (name → class, or name → `[class, args]` with `%param%`
placeholders expanded from static parameters):

- A class that does **not exist is silently skipped** — its package is optional
  and simply not installed. There is no error path for a missing extension class.
- There is **no auto-discovery of extensions from Composer metadata** (no reading
  of `installed.json`) and no `excludeExtension()` API — do not look for either;
  they don't exist in this version. Third-party extensions enter via the
  `extensions` config section, handled by nette/di's `ExtensionsExtension`
  (itself one of the defaults).
- The map is a plain public property; `createRobotLoader` mutates the
  `application` entry's args in place to hand the loader to
  `ApplicationExtension`.

## Debug-mode detection is exact-match, and a proxy disables the localhost default

`detectDebugMode($list)` decides `debugMode` and is security-relevant:

- The client address is `$_SERVER['REMOTE_ADDR']`, falling back to `php_uname('n')`
  (the hostname) in CLI where there is no remote address.
- **Matching is strict equality only** (`in_array(..., strict: true)`), against the
  whitelist and against `"$secret@$addr"` where `$secret` is the `nette-debug`
  cookie. There is **no CIDR / subnet matching** here — a `1.2.3.0/24` entry is
  compared literally and will never match. (Some prose docs claim subnet support;
  the code does not implement it.)
- **`127.0.0.1`/`::1` are auto-whitelisted only when no proxy header is present**
  (`HTTP_X_FORWARDED_FOR` / `HTTP_FORWARDED`). Behind a proxy, localhost is **not**
  auto-trusted — the guard against a forwarded request appearing to originate
  locally.

## Boot sequence

`createContainer` → `loadContainer` (a `DI\ContainerLoader` compiles-or-loads by
cache key) → `new $class($dynamicParameters)` → pre-registered `addService`s →
`initialize()`. Inside compilation (`generateContainer`): set static parameters on
the loader, add default params, load configs, add explicit params, declare dynamic
parameter names, add `autowireExcludedClasses` (generic interfaces like
`ArrayAccess`/`Traversable`/`stdClass` excluded from autowiring), register
extensions, then fire `onCompile`.

## Navigation map

| Concern | Where |
|---|---|
| Static/dynamic split, cache key | `addStaticParameters`, `addDynamicParameters`, `generateContainerKey` |
| Parameter override ordering | `generateContainer` (params configs), `getDefaultParameters` |
| Extension registration | `defaultExtensions`, `generateContainer` (extension loop) |
| Debug detection | `detectDebugMode` |
| Boot / compile flow | `createContainer`, `loadContainer`, `generateContainer` |
