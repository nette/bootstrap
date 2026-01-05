<?php

/**
 * Test: Nette\Configurator::excludeExtension()
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('excludeExtension() disables extension from defaultExtensions', function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());
	$configurator->excludeExtension('application');

	$container = $configurator->createContainer();

	// application extension should not be registered
	Assert::false($container->hasService('application'));
	Assert::false($container->hasService('nette.presenterFactory'));

	// other extensions should still work
	Assert::true($container->hasService('httpRequest'));
});


test('excludeExtension() without arguments disables all auto-discovery', function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());
	$configurator->excludeExtension();

	// defaultExtensions should still be registered
	$container = $configurator->createContainer();
	Assert::true($container->hasService('application'));
	Assert::true($container->hasService('httpRequest'));
});


test('excludeExtension() can disable multiple extensions', function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());
	$configurator->excludeExtension('application', 'mail');

	$container = $configurator->createContainer();

	Assert::false($container->hasService('application'));
	Assert::false($container->hasService('nette.mailer'));
	Assert::true($container->hasService('httpRequest'));
});
