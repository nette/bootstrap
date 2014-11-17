<?php

/**
 * Test: NetteExtension validation messages
 */

use Nette\Configurator,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
nette:
	forms:
		messages:
			FILLED: "Testing filled"
			EQUAL: "Testing equal %s."
			\'Nette\Forms\Controls\SelectBox::VALID\': "SelectBox test"
', 'neon'));
$container = $configurator->createContainer();
$container->initialize();

Assert::same(Nette\Forms\Validator::$messages[Nette\Forms\Form::FILLED], 'Testing filled');
Assert::same(Nette\Forms\Validator::$messages[Nette\Forms\Form::EQUAL], 'Testing equal %s.');
Assert::same(Nette\Forms\Validator::$messages[Nette\Forms\Controls\SelectBox::VALID], 'SelectBox test');

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
nette:
	forms:
		messages:
			Foo\Bar: custom validator
', 'neon'));

Assert::exception(function() use ($configurator) {
		$configurator->createContainer();
	}, 'Nette\InvalidArgumentException', 'Constant Nette\Forms\Form::Foo\Bar or constant Foo\Bar does not exist.');
