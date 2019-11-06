<?php
/**
 * Local Server Composer Plugin.
 */

namespace Altis\Local_Server\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable {
	public function activate( Composer $composer, IOInterface $io ) {
	}

	public function getCapabilities() {
		return [
			'Composer\Plugin\Capability\CommandProvider' => __NAMESPACE__ . '\\Command_Provider',
		];
	}
}
