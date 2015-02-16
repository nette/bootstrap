<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Bridges\Framework;

use Nette,
	Nette\Framework,
	Tracy,
	Tracy\Helpers,
	Tracy\BlueScreen,
	Latte;


/**
 * Initializes Tracy
 */
class TracyBridge
{

	public static function initialize()
	{
		$blueScreen = Tracy\Debugger::getBlueScreen();

		if (class_exists('Nette\Framework')) {
			$version = Framework::VERSION . (Framework::REVISION ? ' (' . Framework::REVISION . ')' : '');
			Tracy\Debugger::getBar()->getPanel('Tracy:info')->data['Nette Framework'] = $version;
			$blueScreen->info[] = "Nette Framework $version";
		}

		$blueScreen->addPanel(function($e) {
			if ($e instanceof Latte\CompileException) {
				return array(
					'tab' => 'Template',
					'panel' => (@is_file($e->sourceName) // @ - may trigger error
							? '<p><b>File:</b> ' . Helpers::editorLink($e->sourceName, $e->sourceLine) . '</p>'
							: '')
						. '<pre>'
						. BlueScreen::highlightLine(htmlspecialchars($e->sourceCode, ENT_IGNORE, 'UTF-8'), $e->sourceLine)
						. '</pre>'
				);
			}
		});

		$blueScreen->addPanel(function($e) {
			if ($e instanceof Nette\Neon\Exception && preg_match('#line (\d+)#', $e->getMessage(), $m)
				&& ($trace = Helpers::findTrace($e->getTrace(), 'Nette\Neon\Decoder::decode'))
			) {
				return array(
					'tab' => 'NEON',
					'panel' => ($trace2 = Helpers::findTrace($e->getTrace(), 'Nette\DI\Config\Adapters\NeonAdapter::load'))
						? '<p><b>File:</b> ' . Helpers::editorLink($trace2['args'][0], $m[1]) . '</p>'
							. BlueScreen::highlightFile($trace2['args'][0], $m[1])
						: BlueScreen::highlightPhp($trace['args'][0], $m[1])
				);
			}
		});
	}

}
