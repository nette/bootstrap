<?php

/**
 * Test: Nette\Configurator::addServices()
 */

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestExistingService
{

	private $scream;

	public function __construct($scream = TRUE)
	{
		$this->scream = $scream;
	}

	public function run()
	{
		if ($this->scream) {
			throw new \Exception('This is an instance created by container and should not be called');
		}
	}

}

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
services:
	existingService:
		class: TestExistingService
		run: yes
		setup:
			- run

', 'neon'));

$existingService = new TestExistingService(FALSE);
$newService = new stdClass();
$addServiceTwice = new stdClass();

$configurator->addServices([
	'existingService' => $existingService,
	'newService' => $newService,
	'addServiceTwice' => $addServiceTwice,
]);

$addServiceTwice = new stdClass();

$configurator->addServices([
	'addServiceTwice' => $addServiceTwice,
]);

$container = $configurator->createContainer();

Assert::same($existingService, $container->getService('existingService'));
Assert::same($newService, $container->getService('newService'));
Assert::same($addServiceTwice, $container->getService('addServiceTwice'));
