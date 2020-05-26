<?php

/**
 * Test: Nette\Configurator and createContainer.
 */

declare(strict_types=1);

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


date_default_timezone_set('America/Los_Angeles');

$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());
$configurator->addParameters([
	'wwwDir' => 'overwritten', // overwrites default value
	'foo2' => '%foo%',         // uses parameter from config file
	'foo3' => '%foo%',         // will overwrite config file
]);
$container = $configurator->addConfig('files/configurator.basic.neon')
	->createContainer();

Assert::same('overwritten', $container->parameters['wwwDir']);
Assert::same('hello world', $container->parameters['foo']);
Assert::same('%foo%', $container->parameters['foo2']);
Assert::same('%foo%', $container->parameters['foo3']);
Assert::same('hello', $container->parameters['bar']);
Assert::same('hello world', constant('BAR'));
Assert::same('Europe/Prague', date_default_timezone_get());

Assert::same([
	'dsn' => 'sqlite2::memory:',
	'user' => 'dbuser',
	'password' => 'secret',
], $container->parameters['database']);
