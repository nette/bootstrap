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
 * Constant definitions.
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


	public function loadConfiguration()
	{
		foreach ($this->getConfig() as $name => $value) {
			$this->initialization->addBody('define(?, ?);', [$name, $value]);
		}
	}
}
