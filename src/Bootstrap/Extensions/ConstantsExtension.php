<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bootstrap\Extensions;

use Nette;
use Nette\Schema\Expect;


/**
 * Defines PHP constants from configuration.
 */
final class ConstantsExtension extends Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::arrayOf(
			Expect::type('scalar|array|null')->dynamic(),
			Expect::string(),
		);
	}


	public function loadConfiguration(): void
	{
		foreach ((array) $this->config as $name => $value) {
			$this->initialization->addBody('define(?, ?);', [$name, $value]);
		}
	}
}
