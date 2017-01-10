<?php

/**
 * Test: Nette\Configurator aliases for nette config
 */

declare(strict_types=1);

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestExtension extends Nette\DI\CompilerExtension
{
	static $cfg;

	function loadConfiguration()
	{
		self::$cfg = $this->getConfig();
	}
}


test(function () {
	$configurator = new Configurator;
	$configurator->defaultExtensions['mail'] = 'TestExtension';
	$configurator->setTempDirectory(TEMP_DIR);
	$configurator->addConfig(Tester\FileMock::create('
	nette:
		mailer:
			item: 10
	', 'neon'));
	$container = @$configurator->createContainer();

	Assert::same(['item' => 10], TestExtension::$cfg);
});


Assert::exception(function () {
	$configurator = new Configurator;
	$configurator->defaultExtensions['database'] = 'TestExtension';
	$configurator->setTempDirectory(TEMP_DIR);
	$configurator->addConfig(Tester\FileMock::create('
	nette:
		database:
			item: 10

	database:
		item: 20
	', 'neon'));
	$container = $configurator->createContainer();
}, Nette\DeprecatedException::class, "You can use (deprecated) section 'nette.database' or new section 'database', but not both of them.");
