<?php
/**
 * Local Server Composer Plugin.
 *
 * @phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * @phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * @phpcs:disable HM.Files.NamespaceDirectoryName.NameMismatch
 * @phpcs:disable HM.Files.ClassFileName.MismatchedName
 */

namespace Altis\LocalServer\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable {
	public function activate( Composer $composer, IOInterface $io ) {
	}

	public function getCapabilities() {
		return [
			'Composer\Plugin\Capability\CommandProvider' => __NAMESPACE__ . '\\CommandProvider',
		];
	}
}
