<?php

/**
 * Use legacy PHP to check if we have the required PHP version.
 */
class PresetoSolidPodCompat {

	protected $file;

	public function __construct( $plugin_file ) {
		$this->file = $plugin_file;
	}

	public function init() {
		register_activation_hook( $this->file, array( $this, 'activation_hook' ) );
	}

	public function activation_hook() {
		if ( ! $this->is_php_supported() ) {
			deactivate_plugins( plugin_basename( $this->file ) );

			wp_die(
				wp_sprintf(
					__( 'The Solid POD plugin requires PHP 5.3.0 or later. Your server is currently running PHP %2s. Please ask your host to upgrade to a recent version of PHP.', 'solid-pod' ),
					PHP_VERSION
				)
			);
		}
	}

	public function is_php_supported() {
		return version_compare( PHP_VERSION, '5.3.0', '>=' );
	}

}
