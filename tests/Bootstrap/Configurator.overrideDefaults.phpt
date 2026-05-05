<?php declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('config overrides default wwwDir', function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());
	$configurator->addConfig([
		'parameters' => ['wwwDir' => '/from/config'],
	]);
	$container = $configurator->createContainer();
	Assert::same('/from/config', $container->parameters['wwwDir']);
});


test('addStaticParameters wins over config', function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());
	$configurator->addStaticParameters(['wwwDir' => '/from/static']);
	$configurator->addConfig([
		'parameters' => ['wwwDir' => '/from/config'],
	]);
	$container = $configurator->createContainer();
	Assert::same('/from/static', $container->parameters['wwwDir']);
});


test('setTempDirectory wins over config', function () {
	$tempDir = getTempDir();
	$configurator = new Configurator;
	$configurator->setTempDirectory($tempDir);
	$configurator->addConfig([
		'parameters' => ['tempDir' => '/from/config'],
	]);
	$container = $configurator->createContainer();
	Assert::same($tempDir, $container->parameters['tempDir']);
});


test('config can reference default parameter', function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());
	$configurator->addStaticParameters(['rootDir' => '/my/root']);
	$configurator->addConfig([
		'parameters' => ['wwwDir' => '%rootDir%/www'],
	]);
	$container = $configurator->createContainer();
	Assert::same('/my/root/www', $container->parameters['wwwDir']);
});


test('setter with default-equivalent value still wins over config', function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());

	// pin debugMode to its current (default) value via the setter
	$defaultDebugMode = $configurator->isDebugMode();
	$configurator->setDebugMode($defaultDebugMode);

	// config tries to flip it
	$configurator->addConfig([
		'parameters' => ['debugMode' => !$defaultDebugMode],
	]);

	$container = $configurator->createContainer();
	Assert::same($defaultDebugMode, $container->parameters['debugMode']);
});
