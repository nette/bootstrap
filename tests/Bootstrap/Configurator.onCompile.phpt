<?php

/**
 * Test: Nette\Configurator and user extension.
 */

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class FooExtension extends Nette\DI\CompilerExtension
{
	public function loadConfiguration()
	{
		Assert::same(['foo' => 'hello'], $this->config);
	}
}


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->onCompile[] = function (Configurator $configurator, Nette\DI\Compiler $compiler) {
	$compiler->addExtension('foo', new FooExtension);
};
$configurator->addConfig(Tester\FileMock::create('
parameters:
	bar: hello

foo:
	foo: %bar%
', 'neon'));
$container = $configurator->createContainer();
