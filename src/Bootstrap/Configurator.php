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
		'decorator' => 'Nette\DI\Extensions\DecoratorExtension',
		'nette' => 'Nette\Bridges\Framework\NetteExtension',
		'application' => array('Nette\Bridges\ApplicationDI\ApplicationExtension', array('%debugMode%')),
		'routing' => array('Nette\Bridges\ApplicationDI\RoutingExtension', array('%debugMode%')),
		'latte' => array('Nette\Bridges\ApplicationDI\LatteExtension', array('%tempDir%/cache/latte', '%debugMode%')),
		'cache' => array('Nette\Bridges\CacheDI\CacheExtension', array('%tempDir%')),
		'database' => 'Nette\Bridges\DatabaseDI\DatabaseExtension',
		'mail' => 'Nette\Bridges\MailDI\MailExtension',
		'inject' => 'Nette\DI\Extensions\InjectExtension',
	);

	/** @var string[] of classes which shouldn't be autowired */
	public $autowireExcludedClasses = array(
		'Nette\Application\UI\Control',
		'stdClass',
	);

	/** @var array */
	protected $parameters;

	/** @var array */
	protected $services = array();

	/** @var array [file|array, section] */
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
		$this->parameters['debugMode'] = static::detectDebugMode($value);
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
	 * Add instances of services.
	 * @return self
	 */
	public function addServices(array $services)
	{
		$this->services = $services + $this->services;
		return $this;
	}


	/**
	 * @return array
	 */
	protected function getDefaultParameters()
	{
		$trace = debug_backtrace(PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : FALSE);
		$last = end($trace);
		$debugMode = static::detectDebugMode();
		return array(
			'appDir' => isset($trace[1]['file']) ? dirname($trace[1]['file']) : NULL,
			'wwwDir' => isset($last['file']) ? dirname($last['file']) : NULL,
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
		Nette\Bridges\Framework\TracyBridge::initialize();
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
		if ($section === NULL && is_string($file) && $this->parameters['debugMode']) { // back compatibility
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
		$loader = new DI\ContainerLoader(
			$this->getCacheDirectory() . '/Nette.Configurator',
			$this->parameters['debugMode']
		);
		$class = $loader->load(
			array($this->parameters, $this->files),
			array($this, 'generateContainer')
		);

		$container = new $class;
		foreach ($this->services as $name => $service) {
			$container->addService($name, $service);
		}
		$container->initialize();
		if (class_exists('Nette\Environment')) {
			Nette\Environment::setContext($container); // back compatibility
		}
		return $container;
	}


	/**
	 * @return array [string, array]
	 * @internal
	 */
	public function generateContainer($className)
	{
		$loader = $this->createLoader();
		$config = array();
		$code = '';
		foreach ($this->files as $info) {
			if (is_scalar($info[0])) {
				$code .= "// source: $info[0] $info[1]\n";
				$info[0] = $loader->load($info[0], $info[1]);
			}
			$config = DI\Config\Helpers::merge($info[0], $config);
		}
		$config = DI\Config\Helpers::merge($config, array('parameters' => $this->parameters));

		$compiler = $this->createCompiler();
		$compiler->getContainerBuilder()->addExcludedClasses($this->autowireExcludedClasses);

		foreach ($this->defaultExtensions as $name => $extension) {
			list($class, $args) = is_string($extension) ? array($extension, array()) : $extension;
			if (class_exists($class)) {
				$rc = new \ReflectionClass($class);
				$args = DI\Helpers::expand($args, $config['parameters'], TRUE);
				$compiler->addExtension($name, $args ? $rc->newInstanceArgs($args) : $rc->newInstance());
			}
		}

		$this->onCompile($this, $compiler);

		$code .= $compiler->compile($config, $className, $config['parameters']['container']['parent'])
			. (($parent = $config['parameters']['container']['class']) ? "\nclass $parent extends $className {}\n" : '');

		return array(
			$code,
			array_merge($loader->getDependencies(), $compiler->getContainerBuilder()->getDependencies())
		);
	}


	/**
	 * @return DI\Compiler
	 */
	protected function createCompiler()
	{
		return new DI\Compiler;
	}


	/**
	 * @return DI\Config\Loader
	 */
	protected function createLoader()
	{
		return new DI\Config\Loader;
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
	 * @param  bool|string|array  IP addresses or computer names whitelist
	 * @return bool
	 */
	public static function detectDebugMode($value = NULL)
	{
		$addr = isset($_SERVER['REMOTE_ADDR'])
			? $_SERVER['REMOTE_ADDR']
			: php_uname('n');
		$secret = isset($_COOKIE[self::COOKIE_SECRET]) && is_string($_COOKIE[self::COOKIE_SECRET])
			? $_COOKIE[self::COOKIE_SECRET]
			: NULL;
		$local = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? array() : array('127.0.0.1', '::1');

		if ($value === TRUE && isset($_SERVER['REMOTE_ADDR']) && !in_array($addr, $local, TRUE)) {
			echo __CLASS__ . '::setDebugMode(TRUE) is forbidden on production.';
			return FALSE;
		} elseif (is_bool($value)) {
			return $value;
		} elseif (!is_array($value)) {
			$value = preg_split('#[,\s]+#', $value);
		}
		$list = array_flip(array_merge($local, $value));
		return isset($list[$addr]) || isset($list["$secret@$addr"]);
	}

}
