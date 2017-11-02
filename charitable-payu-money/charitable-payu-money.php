<?php
/**
 * Plugin Name: 		Charitable - PayUMoney
 * Plugin URI: 			https://www.wpcharitable.com/extensions/charitable-payu-money/
 * Description: 		Collect donations through PayUMoney in India.
 * Version: 			1.1.0
 * Author: 				WP Charitable
 * Author URI: 			https://www.wpcharitable.com
 * Requires at least: 	4.1
 * Tested up to: 		4.5.3
 *
 * Text Domain: 		charitable-payu-money
 * Domain Path: 		/languages/
 *
 * @package 			Charitable PayUMoney
 * @category 			Core
 * @author 				Studio164a
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Load plugin class, but only if Charitable is found and activated.
 *
 * @return 	void
 * @since 	1.0.0
 */
function charitable_payu_money_load() {	
	require_once( 'includes/class-charitable-payu-money.php' );

	$has_dependencies = true;

	/* Check for Charitable */
	if ( ! class_exists( 'Charitable' ) ) {

		if ( ! class_exists( 'Charitable_Extension_Activation' ) ) {

			require_once 'includes/class-charitable-extension-activation.php';

		}

		$activation = new Charitable_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		$has_dependencies = false;
	} 
	else {

		new Charitable_PayU_Money( __FILE__ );

	}	
}

add_action( 'plugins_loaded', 'charitable_payu_money_load', 1 );