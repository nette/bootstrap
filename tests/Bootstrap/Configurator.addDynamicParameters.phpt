<?php

declare(strict_types=1);

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
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
Assert::same('hello123', $container->parameters['expand']);
Assert::same('/path', $container->parameters['appDir']);
