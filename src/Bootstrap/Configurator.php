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

	/** @var callable[]  function(Configurator $sender, DI\Compiler $compiler); Occurs after the compiler is created */
	public $onCompile;

	/** @var array */
	public $defaultExtensions = array(
		'php' => 'Nette\DI\Extensions\PhpExtension',
		'constants' => 'Nette\DI\Extensions\ConstantsExtension',
		'extensions' => 'Nette\DI\Extensions\ExtensionsExtension',
		'decorator' => 'Nette\DI\Extensions\DecoratorExtension',
		'application' => array('Nette\Bridges\ApplicationDI\ApplicationExtension', array('%debugMode%', array('%appDir%'))),
		'cache' => array('Nette\Bridges\CacheDI\CacheExtension', array('%tempDir%')),
		'database' => array('Nette\Bridges\DatabaseDI\DatabaseExtension', array('%debugMode%')),
		'di' => array('Nette\DI\Extensions\DIExtension', array('%debugMode%')),
		'forms' => 'Nette\Bridges\FormsDI\FormsExtension',
		'http' => 'Nette\Bridges\HttpDI\HttpExtension',
		'latte' => array('Nette\Bridges\ApplicationDI\LatteExtension', array('%tempDir%/cache/latte', '%debugMode%')),
		'mail' => 'Nette\Bridges\MailDI\MailExtension',
		'reflection' => array('Nette\Bridges\ReflectionDI\ReflectionExtension', array('%debugMode%')),
		'routing' => array('Nette\Bridges\ApplicationDI\RoutingExtension', array('%debugMode%')),
		'security' => array('Nette\Bridges\SecurityDI\SecurityExtension', array('%debugMode%')),
		'session' => array('Nette\Bridges\HttpDI\SessionExtension', array('%debugMode%')),
		'tracy' => array('Tracy\Bridges\DI\TracyExtension', array('%debugMode%')),
		'inject' => 'Nette\DI\Extensions\InjectExtension',
	);

	/** @var string[] of classes which shouldn't be autowired */
	public $autowireExcludedClasses = array(
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
				'class' => NULL,
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
	 * @return DI\Container
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
		$builder = $compiler->getContainerBuilder();
		$builder->addExcludedClasses($this->autowireExcludedClasses);

		foreach ($this->defaultExtensions as $name => $extension) {
			list($class, $args) = is_string($extension) ? array($extension, array()) : $extension;
			if (class_exists($class)) {
				$rc = new \ReflectionClass($class);
				$args = DI\Helpers::expand($args, $config['parameters'], TRUE);
				$compiler->addExtension($name, $args ? $rc->newInstanceArgs($args) : $rc->newInstance());
			}
		}

		$this->fixCompatibility($config);

		$this->onCompile($this, $compiler);

		$code .= $compiler->compile($config, $className, $config['parameters']['container']['parent'])
			. (($parent = $config['parameters']['container']['class']) ? "\nclass $parent extends $className {}\n" : '');

		return array($code, array_merge($loader->getDependencies(), $builder->getDependencies()));
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


	/**
	 * Back compatiblity with < v2.3
	 * @return void
	 */
	protected function fixCompatibility(& $config)
	{
		if (isset($config['nette']['security']['frames'])) {
			$config['nette']['http']['frames'] = $config['nette']['security']['frames'];
			unset($config['nette']['security']['frames']);
		}
		foreach (array('application', 'cache', 'database', 'di' => 'container', 'forms', 'http',
			'latte', 'mail' => 'mailer', 'routing', 'session', 'tracy' => 'debugger') as $new => $old) {
			if (isset($config['nette'][$old])) {
				$new = is_int($new) ? $old : $new;
				if (isset($config[$new])) {
					throw new Nette\DeprecatedException("Configuration section 'nette.$old' is deprecated, move it to section '$new'.");
				}
				$config[$new] = $config['nette'][$old];
				unset($config['nette'][$old]);
			}
		}
		if (isset($config['nette']['xhtml'])) {
			trigger_error("Configuration option 'nette.xhtml' is deprecated, use section 'latte.xhtml' instead.", E_USER_DEPRECATED);
			$config['latte']['xhtml'] = $config['nette']['xhtml'];
			unset($config['nette']['xhtml']);
		}

		if (empty($config['nette'])) {
			unset($config['nette']);
		}
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
