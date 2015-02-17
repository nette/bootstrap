<?php

/**
 * Test: Nette\Configurator and user extension.
 */

use Nette\Configurator,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class DatabaseExtension extends Nette\DI\CompilerExtension
{
	public function loadConfiguration()
	{
		Assert::same( array('foo' => 'hello'), $this->config );
	}
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
', 'neon'));
$container = $configurator->createContainer();
