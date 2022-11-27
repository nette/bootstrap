<?php

/**
 * Test: Nette\Configurator and production mode.
 */

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';



test('', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR']);
	$_SERVER['REMOTE_ADDR'] = 'xx';

	$configurator = new Configurator;
	Assert::false($configurator->isDebugMode());

	$configurator->setDebugMode(true);
	Assert::true($configurator->isDebugMode());

	$configurator->setDebugMode(false);
	Assert::false($configurator->isDebugMode());

	$configurator->setDebugMode($_SERVER['REMOTE_ADDR']);
	Assert::true($configurator->isDebugMode());
});


Assert::exception(function () {
	$configurator = new Configurator;
	$configurator->setDebugMode(1);
}, Nette\InvalidArgumentException::class);


test('localhost', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR']);

	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	Assert::true(Configurator::detectDebugMode());
	Assert::true(Configurator::detectDebugMode('192.168.1.1'));

	$_SERVER['REMOTE_ADDR'] = '::1';
	Assert::true(Configurator::detectDebugMode());

	$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
	Assert::false(Configurator::detectDebugMode());
	Assert::false(Configurator::detectDebugMode('192.168.1.1.0'));
	Assert::true(Configurator::detectDebugMode('192.168.1.1'));
	Assert::true(Configurator::detectDebugMode('a,192.168.1.1,b'));
	Assert::true(Configurator::detectDebugMode('a 192.168.1.1 b'));

	Assert::false(Configurator::detectDebugMode([]));
	Assert::true(Configurator::detectDebugMode(['192.168.1.1']));
});


test('localhost + proxy', function () {
	$_SERVER['HTTP_X_FORWARDED_FOR'] = 'xx';

	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	Assert::false(Configurator::detectDebugMode());

	$_SERVER['REMOTE_ADDR'] = '::1';
	Assert::false(Configurator::detectDebugMode());

	$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
	Assert::false(Configurator::detectDebugMode());
	Assert::true(Configurator::detectDebugMode($_SERVER['REMOTE_ADDR']));
});


test('missing $_SERVER[REMOTE_ADDR]', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);

	Assert::false(Configurator::detectDebugMode());
	Assert::false(Configurator::detectDebugMode('127.0.0.1'));

	Assert::true(Configurator::detectDebugMode(php_uname('n')));
	Assert::true(Configurator::detectDebugMode([php_uname('n')]));
});


test('secret', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR']);
	$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
	$_COOKIE[Configurator::CookieSecret] = '*secret*';

	Assert::false(Configurator::detectDebugMode());
	Assert::true(Configurator::detectDebugMode('192.168.1.1'));
	Assert::false(Configurator::detectDebugMode('abc@192.168.1.1'));
	Assert::true(Configurator::detectDebugMode('*secret*@192.168.1.1'));

	$_COOKIE[Configurator::CookieSecret] = ['*secret*'];
	Assert::false(Configurator::detectDebugMode('*secret*@192.168.1.1'));
});
