<?php declare(strict_types=1);

/**
 * Test: Nette\Configurator and %baseUrl% without the HTTP request service.
 */

use Nette\Bootstrap\Configurator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;
$configurator->defaultExtensions = [];
$configurator->setTempDirectory(getTempDir());
$container = $configurator->createContainer();

Assert::same('', $container->getParameters()['baseUrl']);
