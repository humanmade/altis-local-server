<?php
/**
 * Local Server Composer Command Provider.
 *
 * @phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * @phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * @phpcs:disable HM.Files.NamespaceDirectoryName.NameMismatch
 * @phpcs:disable HM.Files.ClassFileName.MismatchedName
 */

namespace Altis\LocalServer\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability {
	public function getCommands() {
		return [ new Command ];
	}
}
