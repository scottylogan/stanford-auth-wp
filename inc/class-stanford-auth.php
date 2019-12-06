<?php
/**
 * Class Stanford_Auth
 *
 * @package Stanford_Auth
 */

/**
 * Main controller class for Stanford Auth
 */
class Stanford_Auth {

  /**
   * Controller instance as a singleton
   *
   * @var object
   */
  private static $instance;

  /**
   * Default options
   *
   * @var object
   */
  private static $options = array (
    'group_attribute' => 'eduPersonEntitlement',
    'groups_to_roles' => array (),
    'groups_to_users' => array (),
  );


  /**
   * Get the controller instance
   *
   * @return object
   */
  public static function get_instance() {
    if ( ! isset( self::$instance ) ) {
      self::$instance = new Stanford_Auth;
      add_action( 'init', array( self::$instance, 'action_init' ) );
    }
    return self::$instance;
  }

  /**
   * Get a configuration option for this implementation.
   *
   * @param string $option_name Configuration option to produce.
   * @return mixed
   */
  public static function get_option( $option_name ) {
    $value = isset( self::$options[ $option_name ] ) ? self::$options[ $option_name ] : null;
    return apply_filters( 'stanford_auth_option', $value, $option_name );
  }

  /**
   * Initialize the controller logic on the 'init' hook
   */
  public function action_init() {
    $enabled = true;
    if ( current_user_can( 'manage_options' ) ) {
      if ( ! class_exists( 'WP_SAML_Auth' ) ) {
        $enabled = false;
        add_action('admin_notices', function () {
          // Translators: Links to the WP SAML Auth plugin.
          echo '<div class="message error"><p>' . wp_kses_post( sprintf( __("Stanford Auth was unable to find the <code>WP_SAML_Auth</code> class. Please ensure the <a href='%s'>WP-SAML-Auth</a> plugin is installed.", "stanford-auth"), 'https://wordpress.org/plugins/wp-saml-auth/' ) ) . '</p></div>';
        });
      }

      if ( !has_filter( 'stanford_auth_option' ) ) {
        $enabled = false;
        add_action('admin_notices', function () {
          echo '<div class="message error"><p>'
            .  __("Stanford Auth is unconfigured. Please ensure that there is a filter enabled for <code>stanford_auth_option</code> to provide configuration", "stanford-auth")
            . '</p></div>';
        });
      }
    }


    if ($enabled) {
      add_filter( 'wp_saml_auth_option',
                  array( this, 'filter_wpsa_option' ),
                  0, 2 );
      add_filter( 'wp_saml_auth_login_strings',
                  array( this, 'filter_login_strings' ),
                  0, 1 );
      add_filter( 'wp_saml_auth_attributes',
                  array( this, 'filter_attributes' ),
                  0, 2 );
      add_filter( 'wp_saml_auth_insert_user',
                  array( this, 'filter_insert_user' ),
                  0, 2 );

      add_action( 'wp_saml_auth_existing_user_authenticated',
                  array( this, 'action_user_authn' ) );
      add_action( 'wp_saml_auth_new_user_authenticated',
                  array( this, 'action_user_authn' ) );
      /*
       * Maybe later
       *
      add_action( 'admin_menu',
                  array( this, 'add_options_page' ) );
      */
    }
  }

  /**
   * Get a configuration option for this implementation.
   *
   * @param string $option_name Configuration option to produce.
   * @return mixed
   */
  public static function get_option( $option_name ) {
    return apply_filters( 'stanford_auth_option', null, $option_name );
  }


  /**
   * Provides options for Stanford Auth to override WPSA defaults
   *
   * @param mixed  $value       Configuration value.
   * @param string $option_name Configuration option name.
   */
  public function filter_wpsa_option( $value, $option_name ) {
    $wpsa_settings = array (

      // see the wp-saml-auth plugin for documentation on these settings

      'connection_type'        => 'simplesamlphp',
      'simplesamlphp_autoload' => dirname( __FILE__ ) . '/simplesamlphp/lib/_autoload.php',
      'auth_source'            => 'default-sp',
      'auto_provision'         => true,
      'permit_wp_login'        => false,
      'get_user_by'            => 'login',
      'user_login_attribute'   => 'eppn',
      'user_email_attribute'   => 'mail',
      'display_name_attribute' => 'displayName',
      // NOTE: given name and first name are not the same thing
      'first_name_attribute'   => 'givenName',
      // NOTE: family name / surname and last name are not the same thing
      'last_name_attribute'    => 'sn',
      // Default WordPress role to grant when provisioning new users.
      'default_role'           => get_option( 'default_role' ),
    );

    $value = isset( $wpsa_settings[ $option_name ] ) ? $wpsa_settings[ $option_name ] : $value;

    return $value;
  }

  public function filter_login_strings ( $strings ) {
    $strings['title'] = __( 'Use Stanford Login:', 'stanford-auth' ),
    return $strings;
  }

  /**
   * Filter attributes before authentication processing
   *
   * This is critical for mapping group memberships to users, since
   * this is called before WP-SAML-Auth checks if the user exists
   *
   * @param array $attributes Array of attributes from the IdP
   * @param mixed $provider The SAML provider
   * @return array The (possibly modified) attributes
   */
  public function filter_attributes ( $attributes, $provider ) {

    if ( isset( $attributes[ self::get_option( 'group_attribute' ) ] ) ) {
      $user_mapping = self::get_option( 'groups_to_users' );
      $role_mapping = self::get_option( 'groups_to_roles' );

      foreach ( $attributes[ self::get_option( 'group_attribute' ) ] as $group_name) {
        if ( isset( $user_mapping[ $group_name ] ) ) {
          if ( is_array( $user_mapping[ $group_name ] ) ) {
            foreach ( $user_mapping[ $group_name ] as $attr => $value ) {
              $attributes[ $attr ] = array ( $value );
            }
          } else {
            $attributes[ 'eppn' ] = array( $user_mapping[ $group_name ] );
          }
        } else if ( isset( $role_mapping[ $group_name ] ) ) {
          $attributes[ 'role' ] = array( $role_mapping[ $group_name ] );
        }
      }
    }

    if ( !isset( $attributes['eppn'] ) && isset( $attributes['eduPersonPrincipalName'] ) ) {
      $attributes['eppn'] = $attributes['eduPersonPrincipalName'];
    }

    if ( !isset ( $attributes[ 'mail' ] ) && isset( $attributes[ 'eppn' ] ) ) {
      $attributes['mail'] = $attributes['eppn'];
    }

    return $attributes;
  }

  public function filter_insert_user ( $user_args, $attributes ) {
    if ( !empty( $attributes[ 'role' ][0] ) ) {
      $user_args['role'] = $attributes[ 'role' ][0];
    }
    return $user_args;
  }

  public function action_user_authn ( $user, $attributes) {
    // update user object
    // save changes ???
    //  $user_id = wp_update_user ($user);
    if ( !empty( $attributes[ 'role' ][0] ) ) {
      $user->set_role( $attributes[ 'role' ][0] );
      wp_update_user( $user ); // update user with new role, so admins can see current roles
    }
  }

}

