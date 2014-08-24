<?php

/**
 * Test: Nette\Configurator and SimpleAuthenticator
 */

use Nette\Configurator,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addConfig(Tester\FileMock::create('
nette:
	security:
		users:
			john: john123
			admin: {password: admin123, roles: [admin, user]}
			user: {password: user123}
			moderator: {password: moderator123, roles: moderator}
', 'neon'));
$container = $configurator->createContainer();

/** @var \Nette\Security\SimpleAuthenticator $authenticator */
$authenticator = $container->getService('nette.authenticator');


$userList = array(
	'john'      => 'john123',
	'admin'     => 'admin123',
	'user'      => 'user123',
	'moderator' => 'moderator123',
);
$expectedRoles = array(
	'john'      => array(),
	'admin'     => array('admin', 'user'),
	'user'      => array(),
	'moderator' => array('moderator'),
);

foreach($userList as $username => $password) {
	$identity = $authenticator->authenticate(array($username, $password));
	Assert::equal($username, $identity->getId());
	Assert::equal($expectedRoles[$username], $identity->getRoles());
}
