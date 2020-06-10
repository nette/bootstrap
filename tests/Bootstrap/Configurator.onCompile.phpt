<?php

/**
 * Test: Nette\Configurator and user extension.
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
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
$configurator->setTempDirectory(getTempDir());
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
