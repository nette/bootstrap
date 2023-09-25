<?php

/**
 * Test: Nette\Configurator::createRobotLoader()
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;

Assert::exception(
	fn() => $configurator->createRobotLoader(),
	Nette\InvalidStateException::class,
	'Set path to temporary directory using setTempDirectory().',
);


$configurator->setTempDirectory(getTempDir());
$loader = $configurator->createRobotLoader();

Assert::type(Nette\Loaders\RobotLoader::class, $loader);
