<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bootstrap\Extensions;

use Nette;
use Nette\Schema\Expect;


/**
 * PHP directives definition.
 */
final class PhpExtension extends Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::arrayOf(
			Expect::type('scalar|null')->dynamic(),
			Expect::string(),
		);
	}


	public function loadConfiguration()
	{
		foreach ($this->getConfig() as $name => $value) {
			if (!function_exists('ini_set')) {
				throw new Nette\NotSupportedException('Required function ini_set() is disabled.');
			}

			if ($value !== null) {
				$this->initialization->addBody('ini_set(?, (string) (?));', [$name, $value]);
			}
		}
	}
}
