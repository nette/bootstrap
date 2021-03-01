<?php

/**
 * Test: Nette\Configurator and services inheritance and overwriting.
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class MyApp extends Nette\Application\Application
{
}


$configurator = new Configurator;
$configurator->setDebugMode(false);
$configurator->setTempDirectory(getTempDir());
$configurator->addConfig(Tester\FileMock::create('
services:
	application!: # overwrites original application
		class: MyApp
		setup:
			- $errorPresenter(Error)
', 'neon'));
$container = $configurator->createContainer();

Assert::type(MyApp::class, $container->getService('application'));
Assert::same('Error', $container->getService('application')->errorPresenter);
