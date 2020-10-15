<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette;

if (false) {
	/** @deprecated use Nette\Bootstrap\Configurator */
	class Configurator
	{
	}
} elseif (!class_exists(Configurator::class)) {
	class_alias(Bootstrap\Configurator::class, Configurator::class);
}
