<?php

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());
$configurator->addConfig(Tester\FileMock::create('
parameters:
	dynamic: default
	expand: hello%dynamic%
', 'neon'));
$configurator->addDynamicParameters([
	'dynamic' => 123,
	'appDir' => '/path',
]);

$container = $configurator->createContainer();

Assert::same(123, $container->parameters['dynamic']);
Assert::same('/path', $container->parameters['appDir']);
