<?php
/**
 * Local Server Composer Plugin.
 *
 * @package altis/local-server
 */

namespace Altis\Local_Server\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

/**
 * Altis Local Server Composer Plugin.
 *
 * @package altis/local-server
 */
class Plugin implements PluginInterface, Capable {
	/**
	 * Plugin activation callback.
	 *
	 * @param Composer $composer Composer object.
	 * @param IOInterface $io Composer disk interface.
	 * @return void
	 */
	public function activate( Composer $composer, IOInterface $io ) {
	}

	/**
	 * Return plugin capabilities.
	 *
	 * @return array
	 */
	public function getCapabilities() {
		return [
			'Composer\Plugin\Capability\CommandProvider' => __NAMESPACE__ . '\\Command_Provider',
		];
	}
}
