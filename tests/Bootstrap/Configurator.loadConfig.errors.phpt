<?php declare(strict_types=1);

/**
 * Test: Nette\Configurator and createContainer errors.
 */

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;

Assert::exception(
	fn() => $configurator->addConfig('files/missing.neon')->createContainer(),
	Nette\InvalidStateException::class,
	'Set path to temporary directory using setTempDirectory().',
);
