<?php declare(strict_types=1);

/**
 * Test: Nette\Configurator and the key of the container.
 */

use Nette\Bootstrap\Configurator;
use Nette\DI\Extensions\{DecoratorExtension, InjectExtension};
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function loadContainer(array $extensions, array $excludedClasses = []): string
{
	$configurator = new Configurator;
	$configurator->setTempDirectory(getTempDir());
	$configurator->defaultExtensions = $extensions;
	$configurator->autowireExcludedClasses = $excludedClasses;
	return $configurator->loadContainer();
}


$class = loadContainer(['foo' => DecoratorExtension::class]);

Assert::notSame($class, loadContainer([]));
Assert::notSame($class, loadContainer(['bar' => DecoratorExtension::class]));
Assert::notSame($class, loadContainer(['foo' => InjectExtension::class]));
Assert::notSame($class, loadContainer(['foo' => DecoratorExtension::class], [ArrayAccess::class]));

Assert::same($class, loadContainer(['foo' => DecoratorExtension::class]));
Assert::same($class, loadContainer(['foo' => [DecoratorExtension::class, []]]));
Assert::same($class, loadContainer(['foo' => [DecoratorExtension::class, [fn() => null]]]));
