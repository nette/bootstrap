<?php

/**
 * Test: Nette\Configurator and headers.
 */

use Nette\Configurator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Debugger Bar is not rendered in CLI mode');
}


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
nette:
	http:
		headers:
			A: b
			C:
', 'neon'));
$container = $configurator->createContainer();


$headers = headers_list();
Assert::contains('X-Frame-Options: SAMEORIGIN', $headers);
Assert::contains('Content-Type: text/html; charset=utf-8', $headers);
Assert::contains('X-Powered-By: Nette Framework', $headers);
Assert::contains('A: b', $headers);
Assert::notContains('C:', $headers);



echo ' '; @ob_flush(); flush();

Assert::true(headers_sent());

Assert::error(function () {
	$configurator = new Configurator;
	$configurator->setTempDirectory(TEMP_DIR);
	$configurator->addParameters(array('container' => array('class' => 'Container2')));
	$container = $configurator->addConfig(Tester\FileMock::create('
		nette:
			http:
				headers:
					A: b
					C:
		', 'neon'))
		->createContainer();
}, array(
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
));
