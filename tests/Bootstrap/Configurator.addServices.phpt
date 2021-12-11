<?php

/**
 * Test: Nette\Configurator::addServices()
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestExistingService
{
	private $scream;


	public function __construct($scream = true)
	{
		$this->scream = $scream;
	}


	public function run()
	{
		if ($this->scream) {
			throw new Exception('This is an instance created by container and should not be called');
		}
	}
}

$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());
$configurator->addConfig(Tester\FileMock::create('
services:
	existingService:
		class: TestExistingService
		tags: [run]
		setup:
			- run

', 'neon'));

$existingService = new TestExistingService(false);
$newService = new stdClass;
$addServiceTwice = new stdClass;

$configurator->addServices([
	'existingService' => $existingService,
	'newService' => $newService,
	'addServiceTwice' => $addServiceTwice,
]);

$addServiceTwice = new stdClass;

$configurator->addServices([
	'addServiceTwice' => $addServiceTwice,
]);

$container = @$configurator->createContainer(); // @ triggers service should be defined as 'imported'

Assert::same($existingService, $container->getService('existingService'));
Assert::same($newService, $container->getService('newService'));
Assert::same($addServiceTwice, $container->getService('addServiceTwice'));
