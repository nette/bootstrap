<?php

/**
 * Test: Nette\Configurator and services inheritance and overwriting.
 */

declare(strict_types=1);

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class MyApp extends Nette\Application\Application
{
}


$configurator = new Configurator;
$configurator->setDebugMode(FALSE);
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
services:
	app2 < application: # inherits from overwritten application
		autowired: no
		setup!: # overwrites inherited setup

	application!: # overwrites original application
		class: MyApp
		setup:
			- $errorPresenter(Error)
', 'neon'));
$container = @$configurator->createContainer(); // @ inheritance is deprecated


Assert::type(MyApp::class, $container->getService('application'));
Assert::null($container->getService('application')->catchExceptions);
Assert::same('Error', $container->getService('application')->errorPresenter);

Assert::type(MyApp::class, $container->getService('app2'));
Assert::null($container->getService('app2')->catchExceptions);
Assert::null($container->getService('app2')->errorPresenter);
