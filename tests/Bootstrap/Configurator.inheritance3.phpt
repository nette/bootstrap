<?php

/**
 * Test: Nette\Configurator and services inheritance and overwriting.
 */

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;
$configurator->setDebugMode(FALSE);
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
services:
	application < application:
', 'neon'));


Assert::exception(function () use ($configurator) {
	$configurator->createContainer();
}, Nette\DI\ServiceCreationException::class, "Circular reference detected for service 'application'.");
