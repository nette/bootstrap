<?php

/**
 * Test: Nette\Configurator and autowiring blacklist
 */

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Foo extends stdClass
{
}


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);

$configurator->onCompile = function ($configurator, $compiler) {
	$compiler->getContainerBuilder()->addExcludedClasses(array('stdClass'));
};

$configurator->addConfig(Tester\FileMock::create('
services:
	- Foo
', 'neon'));
$container = $configurator->createContainer();


Assert::type('Foo', $container->getByType('Foo'));

Assert::exception(function () use ($container) {
	$container->getByType('stdClass');
}, '\Nette\DI\MissingServiceException');
