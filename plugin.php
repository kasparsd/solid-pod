<?php
/**
 * Plugin Name: Solid POD
 * Plugin URI: https://github.com/kasparsd/solid-pod
 * Description: Solid POD provider for WordPress.
 * Version: 0.0.1
 * Author: Kaspars Dambis
 * Author URI: https://kaspars.net
 * Text Domain: solid-pod
 */

require_once dirname( __FILE__ ) . '/src/compat.php';

// Ensure we have PHP 5.3.0+.
$compat = new PresetoSolidPodCompat( __FILE__ );
$compat->init();

if ( $compat->is_php_supported() ) {
	require_once __DIR__ . '/vendor/autoload.php';
	$plugin = new Preseto\SolidPod\SolidPodPlugin( new Preseto\SolidPod\Plugin( __FILE__ ) );
}
