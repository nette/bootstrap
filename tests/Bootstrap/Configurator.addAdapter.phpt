<?php

/**
 * Test: Nette\Configurator and createContainer.
 */

use Nette\Configurator;
use Tester\Assert;
use Nette\DI\Config\Adapters\NeonAdapter;


require __DIR__ . '/../bootstrap.php';

class TestNeonAdapter extends NeonAdapter {

	public function load($file) {
		$config = parent::load($file);
		$config['production']['parameters']['wwwDir']  = 'overwritten';
		return $config;
	}
}


date_default_timezone_set('America/Los_Angeles');

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
Assert::same($configurator, $configurator->addAdapter('neon', new \TestNeonAdapter));
$container = $configurator->addConfig('files/configurator.basic.neon', 'production')
		->createContainer();
Assert::same('overwritten', $container->parameters['wwwDir']);
