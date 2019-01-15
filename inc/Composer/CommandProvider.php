<?php

namespace HM\Platform\LocalServer\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability {
	public function getCommands() {
		return [ new Command ];
	}
}
