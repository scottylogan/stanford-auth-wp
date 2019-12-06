<?php
/**
 * Plugin Name: Stanford Authentication
 * Version: 0.1.0
 * Description: Stanford-specific SAML authentication for WordPress, using WP-SAML-Auth
 * Author: Scotty Logan
 * Author URI: https://uit.stanford.edu
 * Plugin URI: https://code.stanford.edu.org/et/stanford-auth-wp/
 * Text Domain: stanford-auth
 * Domain Path: /languages
 *
 * @package Stanford_Auth
 */

/**
 * Initialize the Stanford Auth plugin.
 *
 * Core logic for the plugin is in the Stanford_Auth class.
 */

require_once dirname( __FILE__ ) . '/inc/class-stanford-auth.php';
Stanford_Auth::get_instance();

/*
if ( defined( 'WP_CLI' ) && WP_CLI ) {
  require_once dirname( __FILE__ ) . '/inc/class-stanford-auth-cli.php';
  WP_CLI::add_command( 'su-saml-auth', 'Stanford_Auth_CLI' );
}
*/

/**
 * Initialize the Stanford Auth plugin settings page.
 */
/*
require_once dirname( __FILE__ ) . '/inc/class-stanford-auth-settings.php';
if ( is_admin() ) {
  Stanford_Auth_Settings::get_instance();
}
*/

/**
 * Initialize the Stanford Auth options from WordPress DB.
 */
/*
require_once dirname( __FILE__ ) . '/inc/class-stanford-auth-options.php';
Stanford_Auth_Options::get_instance();
*/
