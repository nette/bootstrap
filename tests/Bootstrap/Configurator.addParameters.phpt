<?php

/**
 * Test: Nette\Configurator::addParameters()
 */

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
parameters:
	test: bar
', 'neon'));
$configurator->addParameters([
	'test' => 'foo',
]);
$container = $configurator->createContainer();
$parameters = $container->getParameters();

Assert::same('foo', $parameters['test']);
