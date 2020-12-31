<?php

/**
 * Test: Nette\Configurator presenters decoration
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/files/MyPresenter.php';


test('', function () {
	$configurator = new Configurator;
	$configurator->setDebugMode(false);
	$configurator->setTempDirectory(getTempDir());
	$configurator->addConfig(Tester\FileMock::create('
		application:
			scanFilter: *Presenter*
		parameters:
			param: "test"
		decorator:
			BasePresenter:
				setup:
					- setAttr(%param%)
	', 'neon'));

	$container = $configurator->createContainer();
	$services = $container->findByType('Presenter1');
	Assert::count(1, $services);
	$presenter = $container->createService($services[0]);
	Assert::same('test', $presenter->getAttr());
});


test('', function () {
	$configurator = new Configurator;
	$configurator->setDebugMode(false);
	$configurator->setTempDirectory(getTempDir());
	$configurator->addConfig(Tester\FileMock::create('
		application:
			scanFilter: *Presenter*
		decorator:
			BasePresenter:
				tags: [test.tag]
	', 'neon'));

	$container = $configurator->createContainer();
	$services = $container->findByTag('test.tag');
	Assert::count(1, $services);
});
