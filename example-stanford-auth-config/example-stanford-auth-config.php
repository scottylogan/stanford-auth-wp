<?php
/**
 * Plugin Name: My Stanford Authentication Configuration
 * Version: 0.1.0
 * Description: Site-specific configuration for Stanford Auth plugin for "My Site"
 * Author: John Doe
 * Author URI: https://mysite.stanford.edu
 * Plugin URI: https://code.stanford.edu.org/myorg/mysite-stanford-auth-config/
 *
 * @package Stanford_Auth_Config
 */

function my_stanford_auth_options( $default_value, $option_name ) {
  $options = array(
    'attribute_map' => array (
      'entitlement' => array (
        'itlab:staff' => 'role:administrator',
        'test:staff'  => 'role:author',
        'test:faculty' => 'user:general-user',
        'test:student' => 'role:contributor',
      ),
    ),
    'entityId' => 'http://wp-test.itlab.stanford.edu',
    'sp_cert_file' => plugin_dir_path( __FILE__ ) . 'private/sp.crt',
    'sp_key_file'  => plugin_dir_path( __FILE__ ) . 'private/sp.key',
    'permit_wp_login' => false,
    'idp' => 'itlab',
  );

  return isset( $options[ $option_name ] ) ? $options[ $option_name ] : $default_value;
}

add_filter( 'stanford_auth_option', 'my_stanford_auth_options', 0, 2 );
