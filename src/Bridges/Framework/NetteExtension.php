<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Bridges\Framework;

use Nette,
	Nette\DI\ContainerBuilder;


/**
 * Core Nette Framework services.
 *
 * @author     David Grudl
 */
class NetteExtension extends Nette\DI\CompilerExtension
{
	public $defaults = array(
		'http' => array(
			'proxy' => array(),
			'headers' => array(
				'X-Powered-By' => 'Nette Framework',
				'Content-Type' => 'text/html; charset=utf-8',
			),
		),
		'session' => array(
			'debugger' => FALSE,
			'autoStart' => 'smart', // true|false|smart
			'expiration' => NULL,
		),
		'application' => array(),
		'routing' => array(),
		'security' => array(
			'debugger' => TRUE,
			'frames' => 'SAMEORIGIN', // X-Frame-Options
			'users' => array(), // of [user => password] or [user => ['password' => password, 'roles' => [role]]]
			'roles' => array(), // of [role => parents]
			'resources' => array(), // of [resource => parents]
		),
		'mailer' => array(), // BC
		'database' => array(), // BC
		'forms' => array(
			'messages' => array(),
		),
		'latte' => array(), // BC
		'container' => array(
			'debugger' => FALSE,
			'accessors' => FALSE,
		),
		'debugger' => array(
			'email' => NULL,
			'editor' => NULL,
			'browser' => NULL,
			'strictMode' => NULL,
			'maxLen' => NULL,
			'maxDepth' => NULL,
			'showLocation' => NULL,
			'scream' => NULL,
			'bar' => array(), // of class name
			'blueScreen' => array(), // of callback
		),
	);


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
		unset($config['xhtml']);
		$this->validate($config, $this->defaults, 'nette');

		$this->setupHttp($container, $config['http']);
		$this->setupTracy($container, $config['debugger']);
		$this->setupSession($container, $config['session']);
		$this->setupSecurity($container, $config['security']);
		$this->setupContainer($container, $config['container']);
	}


	private function setupHttp(ContainerBuilder $container, array $config)
	{
		$this->validate($config, $this->defaults['http'], 'nette.http');

		$container->addDefinition($this->prefix('httpRequestFactory'))
			->setClass('Nette\Http\RequestFactory')
			->addSetup('setProxy', array($config['proxy']));

		$container->addDefinition('httpRequest') // no namespace for back compatibility
			->setClass('Nette\Http\Request')
			->setFactory('@Nette\Http\RequestFactory::createHttpRequest');

		$container->addDefinition('httpResponse') // no namespace for back compatibility
			->setClass('Nette\Http\Response');

		$container->addDefinition($this->prefix('httpContext'))
			->setClass('Nette\Http\Context');
	}


	private function setupTracy(ContainerBuilder $container, array $config)
	{
		$container->addDefinition($this->prefix('logger'))
			->setClass('Tracy\ILogger')
			->setFactory('Tracy\Debugger::getLogger');

		$container->addDefinition($this->prefix('blueScreen'))
			->setFactory('Tracy\Debugger::getBlueScreen');

		$container->addDefinition($this->prefix('bar'))
			->setFactory('Tracy\Debugger::getBar');
	}


	private function setupSession(ContainerBuilder $container, array $config)
	{
		$session = $container->addDefinition('session') // no namespace for back compatibility
			->setClass('Nette\Http\Session');

		if (isset($config['expiration'])) {
			$session->addSetup('setExpiration', array($config['expiration']));
		}

		if ($container->parameters['debugMode'] && $config['debugger']) {
			$session->addSetup('@Tracy\Bar::addPanel', array(
				new Nette\DI\Statement('Nette\Bridges\HttpTracy\SessionPanel')
			));
		}

		unset($config['expiration'], $config['autoStart'], $config['debugger']);
		if (!empty($config)) {
			$session->addSetup('setOptions', array($config));
		}
	}


	private function setupSecurity(ContainerBuilder $container, array $config)
	{
		$this->validate($config, $this->defaults['security'], 'nette.security');

		$container->addDefinition($this->prefix('userStorage'))
			->setClass('Nette\Security\IUserStorage')
			->setFactory('Nette\Http\UserStorage');

		$user = $container->addDefinition('user') // no namespace for back compatibility
			->setClass('Nette\Security\User');

		if ($container->parameters['debugMode'] && $config['debugger']) {
			$user->addSetup('@Tracy\Bar::addPanel', array(
				new Nette\DI\Statement('Nette\Bridges\SecurityTracy\UserPanel')
			));
		}

		if ($config['users']) {
			$usersList = $usersRoles = array();
			foreach ($config['users'] as $username => $data) {
				$data = is_array($data) ? $data : array('password' => $data);
				$this->validate($data, array('password' => NULL, 'roles' => NULL), $this->prefix("security.users.$username"));
				$usersList[$username] = $data['password'];
				$usersRoles[$username] = isset($data['roles']) ? $data['roles'] : NULL;
			}

			$container->addDefinition($this->prefix('authenticator'))
				->setClass('Nette\Security\IAuthenticator')
				->setFactory('Nette\Security\SimpleAuthenticator', array($usersList, $usersRoles));
		}

		if ($config['roles'] || $config['resources']) {
			$authorizator = $container->addDefinition($this->prefix('authorizator'))
				->setClass('Nette\Security\IAuthorizator')
				->setFactory('Nette\Security\Permission');

			foreach ($config['roles'] as $role => $parents) {
				$authorizator->addSetup('addRole', array($role, $parents));
			}
			foreach ($config['resources'] as $resource => $parents) {
				$authorizator->addSetup('addResource', array($resource, $parents));
			}
		}
	}


	private function setupContainer(ContainerBuilder $container, array $config)
	{
		$this->validate($config, $this->defaults['container'], 'nette.container');

		if ($config['accessors']) {
			$container->parameters['container']['accessors'] = TRUE;
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		// debugger
		foreach (array('email', 'editor', 'browser', 'strictMode', 'maxLen', 'maxDepth', 'showLocation', 'scream') as $key) {
			if (isset($config['debugger'][$key])) {
				$initialize->addBody('Tracy\Debugger::$? = ?;', array($key, $config['debugger'][$key]));
			}
		}

		if ($container->parameters['debugMode']) {
			if ($config['container']['debugger']) {
				$config['debugger']['bar'][] = 'Nette\Bridges\DITracy\ContainerPanel';
			}

			foreach ((array) $config['debugger']['bar'] as $item) {
				$initialize->addBody($container->formatPhp(
					'$this->getService(?)->addPanel(?);',
					Nette\DI\Compiler::filterArguments(array($this->prefix('bar'), is_string($item) ? new Nette\DI\Statement($item) : $item))
				));
			}
		}

		foreach ((array) $config['debugger']['blueScreen'] as $item) {
			$initialize->addBody($container->formatPhp(
				'$this->getService(?)->addPanel(?);',
				Nette\DI\Compiler::filterArguments(array('@Tracy\BlueScreen', $item))
			));
		}

		foreach ((array) $config['forms']['messages'] as $name => $text) {
			$initialize->addBody('Nette\Forms\Rules::$defaultMessages[Nette\Forms\Form::?] = ?;', array($name, $text));
		}

		if ($config['session']['autoStart'] === 'smart') {
			$initialize->addBody('$this->getByType("Nette\Http\Session")->exists() && $this->getByType("Nette\Http\Session")->start();');
		} elseif ($config['session']['autoStart']) {
			$initialize->addBody('$this->getByType("Nette\Http\Session")->start();');
		}

		if (isset($config['security']['frames']) && $config['security']['frames'] !== TRUE) {
			$frames = $config['security']['frames'];
			if ($frames === FALSE) {
				$frames = 'DENY';
			} elseif (preg_match('#^https?:#', $frames)) {
				$frames = "ALLOW-FROM $frames";
			}
			$initialize->addBody('header(?);', array("X-Frame-Options: $frames"));
		}

		foreach ($container->findByTag('run') as $name => $on) {
			if ($on) {
				$initialize->addBody('$this->getService(?);', array($name));
			}
		}

		if (!empty($config['container']['accessors'])) {
			$definitions = $container->definitions;
			ksort($definitions);
			foreach ($definitions as $name => $def) {
				if (Nette\PhpGenerator\Helpers::isIdentifier($name)) {
					$type = $def->implement ?: $def->class;
					$class->addDocument("@property $type \$$name");
				}
			}
		}

		foreach ($config['http']['headers'] as $key => $value) {
			if ($value != NULL) { // intentionally ==
				$initialize->addBody('header(?);', array("$key: $value"));
			}
		}

		$initialize->addBody('Nette\Utils\SafeStream::register();');
		$initialize->addBody('Nette\Reflection\AnnotationsParser::setCacheStorage($this->getByType("Nette\Caching\IStorage"));');
		$initialize->addBody('Nette\Reflection\AnnotationsParser::$autoRefresh = ?;', array($container->parameters['debugMode']));
	}


	private function validate(array $config, array $expected, $name)
	{
		if ($extra = array_diff_key($config, $expected)) {
			$extra = implode(", $name.", array_keys($extra));
			throw new Nette\InvalidStateException("Unknown option $name.$extra.");
		}
	}

}
