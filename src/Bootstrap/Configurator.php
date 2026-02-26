<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bootstrap;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Latte;
use Nette;
use Nette\DI;
use Nette\DI\Definitions\Statement;
use Tracy;
use function in_array, is_array, is_string;
use const PHP_RELEASE_VERSION, PHP_SAPI, PHP_VERSION_ID;


/**
 * Initial system DI container generator.
 */
class Configurator
{
	public const CookieSecret = 'nette-debug';

	/** @deprecated  use Configurator::CookieSecret */
	public const COOKIE_SECRET = self::CookieSecret;


	/** @var array<callable(static, DI\Compiler): void>  Occurs after the compiler is created */
	public array $onCompile = [];

	/** @var array<string, class-string<DI\CompilerExtension>|array{class-string<DI\CompilerExtension>, list<mixed>}> */
	public array $defaultExtensions = [
		'application' => [Nette\Bridges\ApplicationDI\ApplicationExtension::class, ['%debugMode%', ['%appDir%'], '%tempDir%/cache/nette.application']],
		'assets' => [Nette\Bridges\AssetsDI\DIExtension::class, ['%baseUrl%', '%wwwDir%', '%debugMode%']],
		'cache' => [Nette\Bridges\CacheDI\CacheExtension::class, ['%tempDir%/cache']],
		'constants' => Extensions\ConstantsExtension::class,
		'database' => [Nette\Bridges\DatabaseDI\DatabaseExtension::class, ['%debugMode%']],
		'decorator' => Nette\DI\Extensions\DecoratorExtension::class,
		'di' => [Nette\DI\Extensions\DIExtension::class, ['%debugMode%']],
		'extensions' => Nette\DI\Extensions\ExtensionsExtension::class,
		'forms' => Nette\Bridges\FormsDI\FormsExtension::class,
		'http' => [Nette\Bridges\HttpDI\HttpExtension::class, ['%consoleMode%']],
		'inject' => Nette\DI\Extensions\InjectExtension::class,
		'latte' => [Nette\Bridges\ApplicationDI\LatteExtension::class, ['%tempDir%/cache/latte', '%debugMode%']],
		'mail' => [Nette\Bridges\MailDI\MailExtension::class, ['%debugMode%']],
		'php' => Extensions\PhpExtension::class,
		'routing' => [Nette\Bridges\ApplicationDI\RoutingExtension::class, ['%debugMode%']],
		'search' => [Nette\DI\Extensions\SearchExtension::class, ['%tempDir%/cache/nette.search']],
		'security' => [Nette\Bridges\SecurityDI\SecurityExtension::class, ['%debugMode%']],
		'session' => [Nette\Bridges\HttpDI\SessionExtension::class, ['%debugMode%', '%consoleMode%']],
		'tracy' => [Tracy\Bridges\Nette\TracyExtension::class, ['%debugMode%', '%consoleMode%']],
	];

	/** @var list<class-string>  classes which shouldn't be autowired */
	public array $autowireExcludedClasses = [
		\ArrayAccess::class,
		\Countable::class,
		\IteratorAggregate::class,
		\stdClass::class,
		\Traversable::class,
	];

	/** @var array<string, mixed> */
	protected array $staticParameters;

	/** @var array<string, mixed> */
	protected array $dynamicParameters = [];

	/** @var array<string, object> */
	protected array $services = [];

	/** @var list<string|array<string, mixed>> */
	protected array $configs = [];

	/** @var array<string, mixed> */
	private array $defaultParameters;


	public function __construct()
	{
		$this->defaultParameters = $this->staticParameters = $this->getDefaultParameters();
	}


	/**
	 * Sets the %debugMode% parameter.
	 * @param  bool|string|list<string>  $value  IP addresses or computer names whitelist, or true/false
	 */
	public function setDebugMode(bool|string|array $value): static
	{
		if (is_string($value) || is_array($value)) {
			$value = static::detectDebugMode($value);
		}

		return $this->addStaticParameters([
			'debugMode' => $value,
			'productionMode' => !$value, // compatibility
		]);
	}


	public function isDebugMode(): bool
	{
		return $this->staticParameters['debugMode'];
	}


	/**
	 * Sets path to temporary directory.
	 */
	public function setTempDirectory(string $path): static
	{
		return $this->addStaticParameters(['tempDir' => $path]);
	}


	/**
	 * Sets the default timezone.
	 */
	public function setTimeZone(string $timezone): static
	{
		date_default_timezone_set($timezone);
		@ini_set('date.timezone', $timezone); // @ - function may be disabled
		return $this;
	}


	/**
	 * @deprecated use addStaticParameters()
	 * @param  array<string, mixed>  $params
	 */
	public function addParameters(array $params): static
	{
		return $this->addStaticParameters($params);
	}


	/**
	 * Adds static parameters.
	 * @param  array<string, mixed>  $params
	 */
	public function addStaticParameters(array $params): static
	{
		$this->staticParameters = DI\Config\Helpers::merge($params, $this->staticParameters);
		$this->defaultParameters = array_diff_key($this->defaultParameters, $params);
		return $this;
	}


	/**
	 * Adds dynamic parameters.
	 * @param  array<string, mixed>  $params
	 */
	public function addDynamicParameters(array $params): static
	{
		$this->dynamicParameters = $params + $this->dynamicParameters;
		return $this;
	}


	/**
	 * Adds service instances.
	 * @param  array<string, object>  $services
	 */
	public function addServices(array $services): static
	{
		$this->services = $services + $this->services;
		return $this;
	}


	/** @return array<string, mixed> */
	protected function getDefaultParameters(): array
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$last = end($trace);
		$debugMode = static::detectDebugMode();
		$loaderRc = class_exists(ClassLoader::class)
			? new \ReflectionClass(ClassLoader::class)
			: null;
		$rootDir = class_exists(InstalledVersions::class)
			? rtrim(Nette\Utils\FileSystem::normalizePath(InstalledVersions::getRootPackage()['install_path']), '\/')
			: null;
		$baseUrl = new Statement('trim($this->getByType(?)->getUrl()->getBaseUrl(), "/")', [Nette\Http\IRequest::class]);
		return [
			'appDir' => isset($trace[1]['file']) ? dirname($trace[1]['file']) : null,
			'wwwDir' => isset($last['file']) ? dirname($last['file']) : null,
			'vendorDir' => $loaderRc ? dirname($loaderRc->getFileName(), 2) : null,
			'rootDir' => $rootDir,
			'debugMode' => $debugMode,
			'productionMode' => !$debugMode,
			'consoleMode' => PHP_SAPI === 'cli',
			'baseUrl' => $baseUrl,
		];
	}


	/**
	 * Enables Tracy debugger and configures it for the current mode.
	 */
	public function enableTracy(?string $logDirectory = null, ?string $email = null): void
	{
		if (!class_exists(Tracy\Debugger::class)) {
			throw new Nette\NotSupportedException('Tracy not found, do you have `tracy/tracy` package installed?');
		}

		Tracy\Debugger::$strictMode = true;
		Tracy\Debugger::enable(!$this->staticParameters['debugMode'], $logDirectory, $email);
		Tracy\Bridges\Nette\Bridge::initialize();
		if (class_exists(Latte\Bridges\Tracy\BlueScreenPanel::class)) {
			Latte\Bridges\Tracy\BlueScreenPanel::initialize();
		}
	}


	/** @deprecated use enableTracy() */
	public function enableDebugger(?string $logDirectory = null, ?string $email = null): void
	{
		$this->enableTracy($logDirectory, $email);
	}


	/**
	 * Creates RobotLoader for automatic class discovery and caching.
	 * @throws Nette\NotSupportedException if RobotLoader is not available
	 */
	public function createRobotLoader(): Nette\Loaders\RobotLoader
	{
		if (!class_exists(Nette\Loaders\RobotLoader::class)) {
			throw new Nette\NotSupportedException('RobotLoader not found, do you have `nette/robot-loader` package installed?');
		}

		$loader = new Nette\Loaders\RobotLoader;
		$loader->setTempDirectory($this->getCacheDirectory() . '/nette.robotLoader');
		$loader->setAutoRefresh($this->staticParameters['debugMode']);

		if (isset($this->defaultExtensions['application'])) {
			$this->defaultExtensions['application'][1][1] = null;
			$this->defaultExtensions['application'][1][3] = $loader;
		}

		return $loader;
	}


	/**
	 * Adds a configuration file path or configuration array.
	 * @param  string|array<string, mixed>  $config
	 */
	public function addConfig(string|array $config): static
	{
		$this->configs[] = $config;
		return $this;
	}


	/**
	 * Returns system DI container.
	 */
	public function createContainer(bool $initialize = true): DI\Container
	{
		$class = $this->loadContainer();
		$container = new $class($this->dynamicParameters);
		foreach ($this->services as $name => $service) {
			$container->addService($name, $service);
		}

		if ($initialize) {
			$container->initialize();
		}

		return $container;
	}


	/**
	 * Loads system DI container class and returns its name.
	 * @return class-string<DI\Container>
	 */
	public function loadContainer(): string
	{
		$loader = new DI\ContainerLoader(
			$this->getCacheDirectory() . '/nette.configurator',
			$this->staticParameters['debugMode'],
		);
		return $loader->load(
			$this->generateContainer(...),
			$this->generateContainerKey(),
		);
	}


	/**
	 * @internal
	 */
	public function generateContainer(DI\Compiler $compiler): void
	{
		$loader = $this->createLoader();
		$loader->setParameters($this->staticParameters);

		$compiler->addConfig(['parameters' => DI\Helpers::escape($this->defaultParameters)]);

		foreach ($this->configs as $config) {
			if (is_string($config)) {
				$compiler->loadConfig($config, $loader);
			} else {
				$compiler->addConfig($config);
			}
		}

		$explicit = array_diff_key($this->staticParameters, $this->defaultParameters);
		$compiler->addConfig(['parameters' => DI\Helpers::escape($explicit)]);
		$compiler->setDynamicParameterNames(array_merge(array_keys($this->dynamicParameters), ['baseUrl']));

		$builder = $compiler->getContainerBuilder();
		$builder->addExcludedClasses($this->autowireExcludedClasses);

		foreach ($this->defaultExtensions as $name => $extension) {
			[$class, $args] = is_string($extension)
				? [$extension, []]
				: $extension;
			if (class_exists($class)) {
				$args = DI\Helpers::expand($args, $this->staticParameters);
				$compiler->addExtension($name, (new \ReflectionClass($class))->newInstanceArgs($args));
			}
		}

		Nette\Utils\Arrays::invoke($this->onCompile, $this, $compiler);
	}


	protected function createLoader(): DI\Config\Loader
	{
		return new DI\Config\Loader;
	}


	/** @return list<mixed> */
	protected function generateContainerKey(): array
	{
		return [
			$this->staticParameters,
			array_diff_key($this->staticParameters, $this->defaultParameters),
			array_keys($this->dynamicParameters),
			$this->configs,
			PHP_VERSION_ID - PHP_RELEASE_VERSION, // minor PHP version
			class_exists(ClassLoader::class) // composer update
				? filemtime((new \ReflectionClass(ClassLoader::class))->getFilename())
				: null,
		];
	}


	protected function getCacheDirectory(): string
	{
		if (empty($this->staticParameters['tempDir'])) {
			throw new Nette\InvalidStateException('Set path to temporary directory using setTempDirectory().');
		}

		$dir = $this->staticParameters['tempDir'] . '/cache';
		Nette\Utils\FileSystem::createDir($dir);
		return $dir;
	}


	/**
	 * Detects debug mode based on IP address or computer name matching.
	 * @param  string|list<string>|null  $list  IP addresses or computer names whitelist
	 */
	public static function detectDebugMode(string|array|null $list = null): bool
	{
		$addr = $_SERVER['REMOTE_ADDR'] ?? php_uname('n');
		$secret = is_string($_COOKIE[self::CookieSecret] ?? null)
			? $_COOKIE[self::CookieSecret]
			: null;
		$list = is_string($list)
			? preg_split('#[,\s]+#', $list)
			: (array) $list;
		if (!isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !isset($_SERVER['HTTP_FORWARDED'])) {
			$list[] = '127.0.0.1';
			$list[] = '::1';
		}

		return in_array($addr, $list, strict: true) || in_array("$secret@$addr", $list, strict: true);
	}
}


class_exists(Nette\Configurator::class);
