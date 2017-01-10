<?php

/**
 * Test: Nette\Configurator::createRobotLoader()
 */

declare(strict_types=1);

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;

Assert::exception(function () use ($configurator) {
	$configurator->createRobotLoader();
}, Nette\InvalidStateException::class, 'Set path to temporary directory using setTempDirectory().');


$configurator->setTempDirectory(TEMP_DIR);
$loader = $configurator->createRobotLoader();

Assert::type(Nette\Loaders\RobotLoader::class, $loader);
