<?php

/**
 * Test: Nette\Configurator and autowiring blacklist
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Foo extends stdClass
{
}


$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());

$configurator->addConfig(Tester\FileMock::create('
services:
	- Foo
', 'neon'));
$container = $configurator->createContainer();


Assert::type(Foo::class, $container->getByType('Foo'));

Assert::exception(function () use ($container) {
	$container->getByType('stdClass');
}, Nette\DI\MissingServiceException::class);
