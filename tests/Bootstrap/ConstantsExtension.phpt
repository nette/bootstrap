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
		'b' => 123,
		'c' => 1.23,
		'd' => true,
		'e' => false,
		'f' => null,
		'g' => [],
	],
]);
eval($compiler->compile());

$container = new Container;
$container->initialize();

Assert::same('hello', a);
Assert::same('WORLD', A);
Assert::same(123, b);
Assert::same(1.23, c);
Assert::same(true, d);
Assert::same(false, e);
Assert::same(null, f);
Assert::same([], g);
