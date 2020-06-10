<?php

/**
 * Test: Nette\Configurator and setTimeZone()
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


date_default_timezone_set('America/Los_Angeles');

$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());
$configurator->setTimeZone('Europe/Prague');

Assert::same('Europe/Prague', date_default_timezone_get());
Assert::same('Europe/Prague', ini_get('date.timezone'));
