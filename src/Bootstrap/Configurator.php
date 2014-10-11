<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette;

use Nette,
	Nette\DI,
	Tracy;


/**
 * Initial system DI container generator.
 *
 * @author     David Grudl
 *
 * @property   bool $debugMode
 * @property-write $tempDirectory
 */
class Configurator extends Object
{
	const AUTO = TRUE,
		NONE = FALSE;

	const COOKIE_SECRET = 'nette-debug';

	/** @var array of function(Configurator $sender, DI\Compiler $compiler); Occurs after the compiler is created */
	public $onCompile;

	/** @var array */
	public $defaultExtensions = array(
		'php' => 'Nette\DI\Extensions\PhpExtension',
		'constants' => 'Nette\DI\Extensions\ConstantsExtension',
		'extensions' => 'Nette\DI\Extensions\ExtensionsExtension',
		'inject' => 'Nette\DI\Extensions\InjectExtension',
		'decorator' => 'Nette\DI\Extensions\DecoratorExtension',
		'nette' => 'Nette\Bridges\Framework\NetteExtension',
		'application' => array('Nette\Bridges\ApplicationDI\ApplicationExtension', array('%debugMode%')),
		'routing' => array('Nette\Bridges\ApplicationDI\RoutingExtension', array('%debugMode%')),
		'latte' => array('Nette\Bridges\ApplicationDI\LatteExtension', array('%tempDir%/cache/latte', '%debugMode%')),
		'cache' => array('Nette\Bridges\CacheDI\CacheExtension', array('%tempDir%')),
		'database' => 'Nette\Bridges\DatabaseDI\DatabaseExtension',
		'mail' => 'Nette\Bridges\MailDI\MailExtension',
	);

	/** @var string[] of classes which shouldn't be autowired */
	public $autowireExcludedClasses = array(
		'Nette\Application\UI\Control',
		'stdClass',
	);

	/** @var array */
	protected $parameters;

	/** @var array */
	protected $files = array();


	public function __construct()
	{
		$this->parameters = $this->getDefaultParameters();
	}


	/**
	 * Set parameter %debugMode%.
	 * @param  bool|string|array
	 * @return self
	 */
	public function setDebugMode($value)
	{
		$this->parameters['debugMode'] = is_string($value) || is_array($value) ? static::detectDebugMode($value) : (bool) $value;
		$this->parameters['productionMode'] = !$this->parameters['debugMode']; // compatibility
		$this->parameters['environment'] = $this->parameters['debugMode'] ? 'development' : 'production';
		return $this;
	}


	/**
	 * @return bool
	 */
	public function isDebugMode()
	{
		return $this->parameters['debugMode'];
	}


	/**
	 * Sets path to temporary directory.
	 * @return self
	 */
	public function setTempDirectory($path)
	{
		$this->parameters['tempDir'] = $path;
		return $this;
	}


	/**
	 * Adds new parameters. The %params% will be expanded.
	 * @return self
	 */
	public function addParameters(array $params)
	{
		$this->parameters = DI\Config\Helpers::merge($params, $this->parameters);
		return $this;
	}


	/**
	 * @return array
	 */
	protected function getDefaultParameters()
	{
		$trace = debug_backtrace(PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : FALSE);
		$debugMode = static::detectDebugMode();

		if (isset($_SERVER['SCRIPT_FILENAME'])) {
			$wwwDir = dirname(realpath($_SERVER['SCRIPT_FILENAME']));

		} elseif (isset($_SERVER['argv'][0])) {
			$wwwDir = dirname(realpath($_SERVER['argv'][0])) ?: NULL;

		} else {
			$wwwDir = NULL;
		}

		return array(
			'appDir' => isset($trace[1]['file']) ? dirname($trace[1]['file']) : NULL,
			'wwwDir' => $wwwDir,
			'debugMode' => $debugMode,
			'productionMode' => !$debugMode,
			'environment' => $debugMode ? 'development' : 'production',
			'consoleMode' => PHP_SAPI === 'cli',
			'container' => array(
				'class' => 'SystemContainer',
				'parent' => 'Nette\DI\Container',
			)
		);
	}


	/**
	 * @param  string        error log directory
	 * @param  string        administrator email
	 * @return void
	 */
	public function enableDebugger($logDirectory = NULL, $email = NULL)
	{
		Tracy\Debugger::$strictMode = TRUE;
		Tracy\Debugger::enable(!$this->parameters['debugMode'], $logDirectory, $email);
	}


	/**
	 * @return Nette\Loaders\RobotLoader
	 * @throws Nette\NotSupportedException if RobotLoader is not available
	 */
	public function createRobotLoader()
	{
		if (!class_exists('Nette\Loaders\RobotLoader')) {
			throw new Nette\NotSupportedException('RobotLoader not found, do you have `nette/robot-loader` package installed?');
		}

		$loader = new Nette\Loaders\RobotLoader;
		$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage($this->getCacheDirectory()));
		$loader->autoRebuild = $this->parameters['debugMode'];
		return $loader;
	}


	/**
	 * Adds configuration file.
	 * @return self
	 */
	public function addConfig($file, $section = NULL)
	{
		if ($section === NULL && $this->parameters['debugMode']) { // back compatibility
			try {
				$loader = new DI\Config\Loader;
				$loader->load($file, $this->parameters['environment']);
				trigger_error("Config file '$file' has sections, call addConfig() with second parameter Configurator::AUTO.", E_USER_WARNING);
				$section = $this->parameters['environment'];
			} catch (\Exception $e) {}
		}
		$this->files[] = array($file, $section === self::AUTO ? $this->parameters['environment'] : $section);
		return $this;
	}


	/**
	 * Returns system DI container.
	 * @return \SystemContainer|DI\Container
	 */
	public function createContainer()
	{
		$container = $this->createContainerFactory()->create();
		$container->initialize();
		if (class_exists('Nette\Environment')) {
			Nette\Environment::setContext($container); // back compatibility
		}
		return $container;
	}


	/**
	 * @return DI\ContainerFactory
	 */
	protected function createContainerFactory()
	{
		$factory = new DI\ContainerFactory(NULL);
		$factory->autoRebuild = $this->parameters['debugMode'] ? TRUE : 'compat';
		$factory->class = $this->parameters['container']['class'];
		$factory->config = array('parameters' => $this->parameters);
		$factory->configFiles = $this->files;
		$factory->tempDirectory = $this->getCacheDirectory() . '/Nette.Configurator';
		if (!is_dir($factory->tempDirectory)) {
			@mkdir($factory->tempDirectory); // @ - directory may already exist
		}

		$me = $this;
		$factory->onCompile[] = function(DI\ContainerFactory $factory, DI\Compiler $compiler, $config) use ($me) {
			$builder = $compiler->getContainerBuilder();
			$builder->addExcludedClasses($me->autowireExcludedClasses);

			foreach ($me->defaultExtensions as $name => $extension) {
				list($class, $args) = is_string($extension) ? array($extension, array()) : $extension;
				if (class_exists($class)) {
					$rc = new \ReflectionClass($class);
					$args = DI\Helpers::expand($args, $config['parameters'], TRUE);
					$compiler->addExtension($name, $args ? $rc->newInstanceArgs($args) : $rc->newInstance());
				}
			}
			$factory->parentClass = $config['parameters']['container']['parent'];
			$me->onCompile($me, $compiler);
		};
		return $factory;
	}


	protected function getCacheDirectory()
	{
		if (empty($this->parameters['tempDir'])) {
			throw new Nette\InvalidStateException("Set path to temporary directory using setTempDirectory().");
		}
		$dir = $this->parameters['tempDir'] . '/cache';
		if (!is_dir($dir)) {
			@mkdir($dir); // @ - directory may already exist
		}
		return $dir;
	}


	/********************* tools ****************d*g**/


	/**
	 * Detects debug mode by IP address.
	 * @param  string|array  IP addresses or computer names whitelist detection
	 * @return bool
	 */
	public static function detectDebugMode($list = NULL)
	{
		$addr = isset($_SERVER['REMOTE_ADDR'])
			? $_SERVER['REMOTE_ADDR']
			: php_uname('n');
		$secret = isset($_COOKIE[self::COOKIE_SECRET]) && is_string($_COOKIE[self::COOKIE_SECRET])
			? $_COOKIE[self::COOKIE_SECRET]
			: NULL;
		$list = is_string($list)
			? preg_split('#[,\s]+#', $list)
			: (array) $list;
		if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$list[] = '127.0.0.1';
			$list[] = '::1';
		}
		return in_array($addr, $list, TRUE) || in_array("$secret@$addr", $list, TRUE);
	}

}
