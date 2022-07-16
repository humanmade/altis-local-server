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
		if ( ! function_exists( '\\Altis\\get_config' ) ) {
			// Composer <2.2 has broken autoloading, so we need to manually
			// load altis/core in.
			$vendor_altis = dirname( __DIR__, 3 );
			$path = $vendor_altis . '/core/inc/namespace.php';
			if ( file_exists( $path ) ) {
				require $path;
			} else {
				trigger_error( 'Altis\\get_config() not found, and cannot manually load altis/core module. See https://github.com/humanmade/altis-local-server/issues/501', E_USER_WARNING );
			}
		}
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

	/**
	 * {@inheritDoc}
	 *
	 * @param Composer $composer Composer object.
	 * @param IOInterface $io Composer disk interface.
	 * @return void
	 */
	public function deactivate( Composer $composer, IOInterface $io ) {
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Composer $composer Composer object.
	 * @param IOInterface $io Composer disk interface.
	 * @return void
	 */
	public function uninstall( Composer $composer, IOInterface $io ) {
	}
}
