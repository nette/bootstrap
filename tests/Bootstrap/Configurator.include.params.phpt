<?php

declare(strict_types=1);

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());
$configurator->addConfig(__DIR__ . '/files/includes.params.neon');
$configurator->addStaticParameters(['name' => 'includes.params.child']);
$container = $configurator->createContainer();

Assert::same('bar', $container->parameters['foo']);
