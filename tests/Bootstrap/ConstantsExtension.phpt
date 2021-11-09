<?php

declare(strict_types=1);

use Nette\DI;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->setClassName('Container');
$compiler->addExtension('constants', new Nette\Bootstrap\Extensions\ConstantsExtension);
$compiler->addConfig([
	'constants' => [
		'a' => 'hello',
		'A' => 'WORLD',
	],
]);
eval($compiler->compile());

$container = new Container;
$container->initialize();

Assert::same('hello', a);
Assert::same('WORLD', A);
