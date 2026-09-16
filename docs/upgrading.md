# Upgrading nette/bootstrap

## 3.2.9

- **Changed** `Nette\Bootstrap\Configurator::$defaultExtensions` passes `%debugMode%` as a constructor argument to `Nette\Bridges\MailDI\MailExtension`, which requires nette/mail 4.1.2 or newer; previously the extension was registered without arguments; an older nette/mail is detected and the argument omitted; affects code that overrides the `mail` entry (02f7462f)

## 3.2.8

- **Changed** `Nette\Bootstrap\Configurator` injects the auto-detected default parameters (`appDir`, `wwwDir`, `rootDir`, `tempDir`, `debugMode`, `consoleMode`) before the config files, so a `parameters` section in a config file overrides them; previously the defaults were injected after the configs and always won; values passed to `Nette\Bootstrap\Configurator::setDebugMode()` and `Nette\Bootstrap\Configurator::setTempDirectory()` still win over the configs (8350eaf5)

## 3.2.6

- **Changed** `Nette\Bootstrap\Configurator::$defaultExtensions` registers the `assets` extension as `Nette\Bridges\AssetsDI\DIExtension` with the arguments `%baseUrl%`, `%wwwDir%` and `%debugMode%`; previously as `Nette\Bridges\Assets\DIExtension` without arguments; affects code that overrides the `assets` entry (28724083)

## 3.2.2

- **Changed** `Nette\Bootstrap\Configurator::$defaultExtensions` passes the complete cache path `%tempDir%/cache` to `Nette\Bridges\CacheDI\CacheExtension`, which requires nette/caching 3.3.0 or newer; previously `%tempDir%`; an older nette/caching is detected and the old argument kept; affects code that overrides the `cache` entry (226e2df8)

## 3.2.1

- **Signature** `Nette\Bootstrap\Configurator::$onCompile`: typed `array`; previously `iterable`, so assigning a `Traversable` now throws a `TypeError`, pass a plain array of callables (ed25974a)
- **Changed** `Nette\Bootstrap\Configurator` no longer uses `Nette\SmartObject`; reading an undefined property or calling an undefined method no longer throws `Nette\MemberAccessException` but behaves like plain PHP, and invoking the handlers as `$configurator->onCompile(...)` throws an `\Error`; use `Nette\Utils\Arrays::invoke()` instead (70546ba4)
- **Config** `constants.<name>`: validated by a schema, the name must be a string and the value scalar, array or `null`; previously anything was accepted; another shape now fails container compilation (b8892e2b)
- **Config** `php.<name>`: validated by a schema, the name must be a string and the value scalar or `null`; previously any key was accepted; another shape now fails container compilation (a5d0ff97)

## 3.2.0

- **Deprecated** `Nette\Bootstrap\Configurator::enableDebugger()`: use `Nette\Bootstrap\Configurator::enableTracy()`; the method carries a `@deprecated` tag, so the old name is silently deprecated (alias since 3.0.0) (70f9e962)
- **Deprecated** `Nette\Bootstrap\Configurator::addParameters()`: use `Nette\Bootstrap\Configurator::addStaticParameters()`; the method carries a `@deprecated` tag, so the old name is silently deprecated (alias since 3.1.0) (70f9e962)
- **Signature** native types were added throughout the package, so a value of another type now throws a `TypeError` and a subclass overriding the method must repeat the type: `Nette\Bootstrap\Configurator::$onCompile` (`iterable`), `Nette\Bootstrap\Configurator::$defaultExtensions` (`array`), `Nette\Bootstrap\Configurator::$autowireExcludedClasses` (`array`), `Nette\Bootstrap\Configurator::$staticParameters` (`array`), `Nette\Bootstrap\Configurator::$dynamicParameters` (`array`), `Nette\Bootstrap\Configurator::$services` (`array`), `Nette\Bootstrap\Configurator::$configs` (`array`), `Nette\Bootstrap\Configurator::addConfig()` (`string|array $config`, returns `static`), `Nette\Bootstrap\Configurator::detectDebugMode()` (`string|array $list`), `Nette\Bootstrap\Configurator::setDebugMode()` (`bool|string|array $value`), and `Nette\Bootstrap\Configurator::addParameters()`, `Nette\Bootstrap\Configurator::addStaticParameters()`, `Nette\Bootstrap\Configurator::addDynamicParameters()`, `Nette\Bootstrap\Configurator::addServices()`, `Nette\Bootstrap\Configurator::setTempDirectory()`, `Nette\Bootstrap\Configurator::setTimeZone()` which all return `static` (ad94f107, 15f21097)
- **Changed** `Nette\Bootstrap\Configurator::setDebugMode()` throws a `TypeError` when the value is neither `bool`, `string` nor `array`; previously `Nette\InvalidArgumentException`; affects code catching it (ad94f107)
- **Changed** `php.include_path` is passed to `ini_set()` verbatim; previously `;` was translated to the platform path separator; separate the paths with the separator of the target platform yourself (7fde23cc)

## 3.1.4

- **Renamed** `Nette\Bootstrap\Configurator::COOKIE_SECRET` → `Nette\Bootstrap\Configurator::CookieSecret`; the old name is silently deprecated (96dee13d)
- **Signature** `Nette\Bootstrap\Configurator::createContainer()`: takes a new optional parameter `bool $initialize = true`; affects subclasses overriding it, which must declare the parameter as well (5a2f562e)
- **Changed** `php.<name>` with the value `false` sets the directive to `''`; previously to `'0'`, because the value is now cast with `(string)` at runtime; use `0` to keep the previous result (1a7965b4)

## 3.1.1

- **Changed** `Nette\Bootstrap\Configurator::$onCompile` defaults to `[]`; previously `null`; the handlers are invoked through `Nette\Utils\Arrays::invoke()`, so calling `$configurator->onCompile(...)` as a magic event method is no longer supported (27ae1f31)

## 3.1.0

- **Renamed** `Nette\Configurator` → `Nette\Bootstrap\Configurator`; the old name still works as an alias (8ff4ddb3)
- **Renamed** `Nette\DI\Extensions\ConstantsExtension` → `Nette\Bootstrap\Extensions\ConstantsExtension`; the class moved here from nette/di; using the old name triggers `E_USER_DEPRECATED` (e1efcebf)
- **Renamed** `Nette\DI\Extensions\PhpExtension` → `Nette\Bootstrap\Extensions\PhpExtension`; the class moved here from nette/di; using the old name triggers `E_USER_DEPRECATED` (e1efcebf)
- **Renamed** `Nette\Bootstrap\Configurator::addParameters()` → `Nette\Bootstrap\Configurator::addStaticParameters()`; the old name still works as an alias (019ae17d)
- **Renamed** `Nette\Configurator::$parameters` → `Nette\Bootstrap\Configurator::$staticParameters`; affects subclasses reading or writing it; the old name was removed (64453574)
- **Changed** `Nette\Bootstrap\Configurator::addParameters()` stores the values verbatim; previously they were escaped on the way in and a `%` in a value was later expanded as a `%parameter%` reference; the same applies to the path given to `Nette\Bootstrap\Configurator::setTempDirectory()` (7dd230cd)
- **Changed** `Nette\Bootstrap\Configurator::enableTracy()` throws `Nette\NotSupportedException` when tracy/tracy is not installed; previously the missing class raised an `\Error` (756e3289)

## 3.0.0

- **Removed** `Nette\Configurator::AUTO`: it was passed as the second argument of `Nette\Configurator::addConfig()` to pick the config section by debug mode; config file sections are no longer supported (3966d8ba)
- **Removed** `Nette\Configurator::NONE`: the counterpart of `Nette\Configurator::AUTO`, nothing replaces it (3966d8ba)
- **Removed** `Nette\Configurator::fixCompatibility()`: the protected method that translated the `nette.*` config sections is gone; affects subclasses overriding it (3966d8ba)
- **Renamed** `Nette\Bridges\Framework\TracyBridge` → `Tracy\Bridges\Nette\Bridge`; the bridge moved to tracy/tracy 2.5; the old name was removed (769d0755)
- **Renamed** `Nette\Configurator::$files` → `Nette\Configurator::$configs`; each item is now the file name or the config array itself, previously a `[file, section]` pair; affects subclasses; the old name was removed (8b43475d, 3966d8ba)
- **Renamed** `Nette\Configurator::enableDebugger()` → `Nette\Configurator::enableTracy()`; the old name still works as an alias (61f4ff53)
- **Signature** `Nette\Configurator::addConfig()`: takes a single parameter `$config`; the second `$section` parameter was removed, config file sections are no longer supported, so split the config into separate files or merge the arrays yourself (3966d8ba, 8b43475d)
- **Signature** native types were added throughout the package, so a value of another type now throws a `TypeError` and a subclass overriding the method must repeat the type: `Nette\Configurator::setTempDirectory()` (`string $path`), `Nette\Configurator::setTimeZone()` (`string $timezone`), `Nette\Configurator::enableDebugger()` and `Nette\Configurator::enableTracy()` (`string $logDirectory = null, string $email = null`, returning `void`), `Nette\Configurator::detectDebugMode()` and `Nette\Configurator::isDebugMode()` (returning `bool`), `Nette\Configurator::createContainer()` (returning `Nette\DI\Container`), `Nette\Configurator::loadContainer()` (returning `string`), `Nette\Configurator::createLoader()` (returning `Nette\DI\Config\Loader`), `Nette\Configurator::createRobotLoader()` (returning `Nette\Loaders\RobotLoader`), `Nette\Configurator::getCacheDirectory()` (returning `string`), `Nette\Configurator::getDefaultParameters()` (returning `array`) (30fcd19c)
- **Changed** `Nette\Configurator::$autowireExcludedClasses` excludes `ArrayAccess`, `Countable`, `IteratorAggregate` and `Traversable` besides `stdClass`; previously only `stdClass`; a service implementing one of them is no longer autowired by that interface (aff3cc83)
- **Changed** `Nette\Configurator` gives the parameters added with `Nette\Configurator::addParameters()` and the default parameters precedence over the `parameters` section of a loaded config file; previously the config file won (e1075af0)
- **Changed** `Nette\Configurator::getCacheDirectory()` throws `Nette\IOException` when the cache directory cannot be created; previously it continued silently with a missing directory (07c3a578)
- **Changed** `Nette\Configurator` stores the compiled container in `<tempDir>/cache/nette.configurator` and the RobotLoader cache in `<tempDir>/cache/nette.robotLoader`, and passes `%tempDir%/cache/nette.application` to the application extension; previously `<tempDir>/cache/Nette.Configurator`, `<tempDir>/cache/Nette.RobotLoader` and `%tempDir%/cache` (b6a68558)
- **Config** `nette.application` → `application`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.cache` → `cache`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.database` → `database`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.di` → `container`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.forms` → `forms`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.http` → `http`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.latte` → `latte`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.mail` → `mailer`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.routing` → `routing`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.security` → `security`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.security.frames` → `http.frames`: the old key was removed; previously it was translated silently (3966d8ba)
- **Config** `nette.session` → `session`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.tracy` → `debugger`: the old section was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
- **Config** `nette.xhtml` → `latte.xhtml`: the old key was removed; previously it was translated with an `E_USER_DEPRECATED` notice (3966d8ba)
