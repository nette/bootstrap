<?php

declare(strict_types=1);

use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->setClassName('Container');
$compiler->addExtension('php', new Nette\Bootstrap\Extensions\PhpExtension);
$compiler->addConfig([
	'php' => [
		'date.timezone' => 'Europe/Rome',
		'exit_on_timeout' => null,
	],
]);
eval($compiler->compile());

ini_set('date.timezone', 'Europe/Prague');

$container = new Container;
$container->initialize();

Assert::same('Europe/Rome', ini_get('date.timezone'));
