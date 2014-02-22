<?php

/**
 * Test: Nette\Configurator and autowiring blacklist
 */

use Nette\Configurator,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Foo extends stdClass
{
}


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);

$container = $configurator->addConfig('files/configurator.autowireExcluded.neon')
	->createContainer();


Assert::type('Foo', $container->getByType('Foo'));

Assert::exception(function () use ($container) {
	$container->getByType('stdClass');
}, '\Nette\DI\MissingServiceException');
