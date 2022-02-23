<?php 
  /*
	Plugin Name:  WA Coastline
	Plugin URI:   https://morganleek.me/
	Description:  WA Coastline Custom Plugin
	Version:      1.0.0
	Author:       https://morganleek.me/
	Author URI:   https://morganleek.me/
	License:      GPL2
	License URI:  https://www.gnu.org/licenses/gpl-2.0.html
	Text Domain:  wporg
	Domain Path:  /languages
  */
  
  // Security
  defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
  
	// Paths
	define( 'WAC__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
  define( 'WAC__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
  
  // API
	require_once( WAC__PLUGIN_DIR . 'api/api.php' );