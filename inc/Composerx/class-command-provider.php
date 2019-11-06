<?php
/**
 * Local Server Composer Command Provider.
 */

namespace Altis\Local_Server\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class Command_Provider implements CommandProviderCapability {
	public function getCommands() {
		return [ new Command ];
	}
}
