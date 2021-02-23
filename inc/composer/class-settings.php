<?php
/**
 * Setting handler.
 *
 * @package altis/local-server
 */

namespace Altis\Local_Server\Composer;

use Symfony\Component\Yaml\Yaml;

/**
 * Settings handler class.
 */
class Settings {

	/**
	 * The name of the current project.
	 *
	 * @var string
	 */
	protected $project_name;

	/**
	 * The root path for storing project setttings.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The settings file path.
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Settings handler contructor.
	 *
	 * @param string $project_name The project name.
	 * @param array $args Array of arguments for configuring the settings handler.
	 */
	public function __construct( string $project_name, array $args = [] ) {
		$this->project_name = $project_name;
		$this->path = $args['path'] ?? getenv( 'HOME' ) . DIRECTORY_SEPARATOR . '.altis';
		$this->file = $this->path . DIRECTORY_SEPARATOR . $this->project_name . '.yml';
		$this->args = array_merge( [
			'defaults' => [],
		], $args );
	}

	/**
	 * Get all the settings.
	 *
	 * @return array
	 */
	public function get() : array {
		$settings = [];
		if ( file_exists( $this->file ) ) {
			$settings = Yaml::parseFile( $this->file );
		}
		return array_merge( $this->args['defaults'], $settings );
	}

	/**
	 * Get a named item from the settings.
	 *
	 * @param string $key The key to get the value for.
	 * @return mixed
	 */
	public function get_item( string $key ) {
		return $this->get()[ $key ] ?? null;
	}

	/**
	 * Set a settings value.
	 *
	 * @param string $key The key for settings item.
	 * @param mixed $value The item value.
	 * @return void
	 */
	public function set( string $key, $value ) {
		$settings = $this->get();
		$settings[ $key ] = $value;
		$this->save( $settings );
	}

	/**
	 * Create a blank settings file.
	 *
	 * @param array $settings The settings to save.
	 * @return void
	 */
	protected function save( array $settings ) {
		if ( ! is_dir( $this->path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_mkdir
			mkdir( $this->path, 0775, true );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents( $this->file, Yaml::dump( $settings, 2, 2 ) );
	}

	/**
	 * Get the user's home directory path according to their OS.
	 *
	 * @return string
	 */
	protected function get_home_path() : string {
		$p1 = getenv( 'HOME' ) ?? null;       // Linux path.
		$p2 = $_SERVER['HOMEDRIVE'] ?? null;  // phpcs:ignore -- Win disk.
		$p3 = $_SERVER['HOMEPATH'] ?? null;   // phpcs:ignore -- Win path.
		return rtrim( $p1 . $p2 . $p3, '\\/' );
	}

}
