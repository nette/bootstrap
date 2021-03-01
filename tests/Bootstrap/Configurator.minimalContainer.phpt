<?php

/**
 * Test: Nette\Configurator and minimal container.
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;
$configurator->setTempDirectory(getTempDir());
$configurator->addStaticParameters([
	'hello' => 'world',
]);
$container = $configurator->createContainer();

Assert::type(Nette\DI\Container::class, $container);

Assert::same([
	'appDir' => __DIR__,
	'wwwDir' => __DIR__,
	'vendorDir' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor',
	'debugMode' => false,
	'productionMode' => true,
	'consoleMode' => PHP_SAPI === 'cli',
	'tempDir' => getTempDir(),
	'hello' => 'world',
], $container->parameters);

Assert::true($container->getService('nette.cacheJournal') instanceof Nette\Caching\Storages\FileJournal || $container->getService('nette.cacheJournal') instanceof Nette\Caching\Storages\SQLiteJournal);
Assert::type(Nette\Caching\Storages\FileStorage::class, $container->getService('cacheStorage'));
Assert::type(Nette\Http\Request::class, $container->getService('httpRequest'));
Assert::type(Nette\Http\Response::class, $container->getService('httpResponse'));
Assert::type(Nette\Http\Session::class, $container->getService('session'));
Assert::type(Nette\Security\User::class, $container->getService('user'));
Assert::type(
	class_exists(Nette\Bridges\SecurityHttp\SessionStorage::class) ? Nette\Bridges\SecurityHttp\SessionStorage::class : Nette\Http\UserStorage::class,
	$container->getService('nette.userStorage'),
);
Assert::type(Nette\Application\Application::class, $container->getService('application'));
Assert::type(Nette\Routing\SimpleRouter::class, $container->getService('router'));
Assert::type(Nette\Application\PresenterFactory::class, $container->getService('nette.presenterFactory'));
Assert::type(Nette\Mail\SendmailMailer::class, $container->getService('nette.mailer'));
Assert::type(Tracy\Logger::class, $container->getService('tracy.logger'));
Assert::type(Tracy\BlueScreen::class, $container->getService('tracy.blueScreen'));
Assert::type(Tracy\Bar::class, $container->getService('tracy.bar'));

Assert::type(Nette\Bridges\ApplicationLatte\LatteFactory::class, $container->createService('nette.latteFactory'));
Assert::type(Nette\Bridges\ApplicationLatte\TemplateFactory::class, $container->createService('nette.templateFactory'));

if (PHP_SAPI !== 'cli') {
	$headers = headers_list();
	Assert::contains('X-Frame-Options: SAMEORIGIN', $headers);
	Assert::contains('Content-Type: text/html; charset=utf-8', $headers);
	Assert::contains('X-Powered-By: Nette Framework 3', $headers);
}
