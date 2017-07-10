<?php

/**
 * Test: Nette\Configurator presenters decoration
 */

declare(strict_types=1);

use Nette\Configurator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/files/MyPresenter.php';


test(function () {
	$configurator = new Configurator;
	$configurator->setDebugMode(false);
	$configurator->setTempDirectory(TEMP_DIR);
	$configurator->addConfig(Tester\FileMock::create('
		parameters:
			param: \'test\'
		decorator:
			BasePresenter:
				setup:
					- setAttr(%param%)
	', 'neon'));

	$container = $configurator->createContainer();
	$services = array_keys($container->findByTag('nette.presenter'), 'Presenter1', true);
	Assert::count(1, $services);
	$presenter = $container->createService($services[0]);
	Assert::same('test', $presenter->getAttr());
});


test(function () {
	$configurator = new Configurator;
	$configurator->setDebugMode(false);
	$configurator->setTempDirectory(TEMP_DIR);
	$configurator->addConfig(Tester\FileMock::create('
		decorator:
			BasePresenter:
				tags: [test.tag]
	', 'neon'));

	$container = $configurator->createContainer();
	$services = $container->findByTag('test.tag');
	Assert::count(1, $services);
});
