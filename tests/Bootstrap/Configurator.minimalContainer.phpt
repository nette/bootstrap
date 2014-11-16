<?php

/**
 * Test: Nette\Configurator and minimal container.
 */

use Nette\Configurator,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters(array(
	'hello' => 'world',
));
$container = $configurator->createContainer();

Assert::type( 'Nette\DI\Container', $container );

Assert::same( array(
	'appDir' => __DIR__,
	'wwwDir' => __DIR__,
	'debugMode' => FALSE,
	'productionMode' => TRUE,
	'environment' => 'production',
	'consoleMode' => PHP_SAPI === 'cli',
	'container' => array(
		'class' => NULL,
		'parent' => 'Nette\\DI\\Container',
	),
	'tempDir' => TEMP_DIR,
	'hello' => 'world',
), $container->parameters );

Assert::type( 'Nette\Caching\Storages\FileJournal', $container->getService('nette.cacheJournal') );
Assert::type( 'Nette\Caching\Storages\FileStorage', $container->getService('cacheStorage') );
Assert::type( 'Nette\Http\Request', $container->getService('httpRequest') );
Assert::type( 'Nette\Http\Response', $container->getService('httpResponse') );
Assert::type( 'Nette\Http\Context', $container->getService('nette.httpContext') );
Assert::type( 'Nette\Http\Session', $container->getService('session') );
Assert::type( 'Nette\Security\User', $container->getService('user') );
Assert::type( 'Nette\Http\UserStorage', $container->getService('nette.userStorage') );
Assert::type( 'Nette\Application\Application', $container->getService('application') );
Assert::type( 'Nette\Application\Routers\RouteList', $container->getService('router') );
Assert::type( 'Nette\Application\PresenterFactory', $container->getService('nette.presenterFactory') );
Assert::type( 'Nette\Mail\SendmailMailer', $container->getService('nette.mailer') );
Assert::type( 'Tracy\Logger', $container->getService('nette.logger') );
Assert::type( 'Tracy\BlueScreen', $container->getService('nette.blueScreen') );
Assert::type( 'Tracy\Bar', $container->getService('nette.bar') );

Assert::type( 'Nette\Bridges\ApplicationLatte\ILatteFactory', $container->createService('nette.latteFactory') );
Assert::type( 'Nette\Bridges\ApplicationLatte\TemplateFactory', $container->createService('nette.templateFactory') );

if (PHP_SAPI !== 'cli') {
	$headers = headers_list();
	Assert::contains( 'X-Frame-Options: SAMEORIGIN', $headers );
	Assert::contains( 'Content-Type: text/html; charset=utf-8', $headers );
	Assert::contains( 'X-Powered-By: Nette Framework', $headers );
}
