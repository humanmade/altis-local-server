<?php
/**
 * Local Server Composer Command Provider.
 *
 * @package altis/local-server
 */

namespace Altis\Local_Server\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * Altis Local Server Composer Command Provider.
 *
 * @package altis/local-server
 */
class Command_Provider implements CommandProviderCapability {
	/**
	 * Return available commands.
	 *
	 * @return array
	 */
	public function getCommands() {
		return [ new Command ];
	}
}
