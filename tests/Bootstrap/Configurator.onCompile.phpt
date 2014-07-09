<?php

/**
 * Test: Nette\Configurator and user extension.
 */

use Nette\Configurator,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class DatabaseExtension extends Nette\DI\CompilerExtension
{
}


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->onCompile[] = function(Configurator $configurator, Nette\DI\Compiler $compiler) {
	$compiler->addExtension('database', new DatabaseExtension);
};
$configurator->addConfig(Tester\FileMock::create('
parameters:
	bar: hello

database:
	foo: %bar%

	services:
		foo: stdClass

services:
	alias: @database.foo
', 'neon'));
$container = $configurator->createContainer();

Assert::type( 'stdClass', $container->getService('database.foo') );
