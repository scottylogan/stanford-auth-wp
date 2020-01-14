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
   * Defaults for configurable options for WP-SAML-Auth
   *
   * @var object
   */
  private static $options = array (
    /**
     * Name of the IdP to use - default is stanford, itlab is also available
     *
     * @param string
     */
    'idp' => 'stanford',

    /**
     * File containing public X.509 certificate for SAML
     *
     * @param string
     */
    'sp_cert_file' => null,

    /**
     * String containing public X.509 certificate for SAML
     *
     * @param string
     */
    'sp_cert' => null,

    /**
     * File containing private X.509 key for SAML
     *
     * @param string
     */
    'sp_key_file'  => null,

    /**
     * String containing private X.509 key for SAML
     *
     * @param string
     */
    'sp_key'  => null,

    /**
     * Whether to deny users who were not mapped to a role or user
     * Default is false, which allows unmapped users, who are given
     * the default role for this site; set to true to deny them
     * access.
     *
     * @param bool
     */
    'deny_unmapped_users' => false,

    /**
     * SAML Entity ID
     * EntityID used for SAML - defaults to 'https://${HOSTAME-FROM-HOME-URL}'
     * (set in action_init function)
     *
     * @param string
     */
    'entityId' => '',


    /**
     * Allow regular Wordpress logins?
     *
     * @param bool
     */
    'permit_wp_users' => true,

    /**
     * Default user to use for unmapped users
     *
     * @param string
     */
    'default_user' => null,

    /**
     * Attribute map - mapping between attribute values and users / roles
     *
     * attribute_map is a hash of attribute names to hashes of
     * attributes values to roles or users. Strings without a 'role:'
     * prefix, or no prefix, are mapped to roles. Strings with a
     * 'user:' or 'eppn:' prefix are mapped to users.
     *
     * The WP roles and users must already exist - this plugin will
     * not create them.
     *
     * $attribute_map = array (
     *   'entitlement' => array (
     *     'itlab:staff'  => 'administrator',    // role
     *     'test:staff'   => 'role:editor',      // role
     *     'test:faculty' => 'role:contributor', // role
     *     'test:student' => 'eppn:subscriber',  // user
     *   ),
     *   'affiliation' => array (
     *     'faculty@itlab.stanford.edu' => 'role:contributor', // role
     *     'student@itlab.stanford.edu' => 'user:susbscriber', // user
     *   ),
     * );
     *
     * @param array
     */
    'attribute_map' => array ( ),
  );


  /**
   * Stanford IdPs
   *
   */
  private static $idps = array(
    'itlab' => array (
      'entityId'    => 'https://weblogin.itlab.stanford.edu/idp/shibboleth',
      'description' => 'Stanford IT Lab IdP',
      'md_url'      => 'https://login.itlab.stanford.edu/idp/shibboleth',
      'sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
    ),
    'stanford' => array(
      'entityId'    => 'https://idp.stanford.edu/',
      'description' => 'Stanford University IdP',
      'md_url'      => 'https://login.stanford.edu/idp/shibboleth',
      'sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
    )
  );
      
  /**
   * Attribute names for WP-SAML-Auth
   * These are pre-set for Stanford, and cannot be customized
   */
  private static $attribute_names = array(

    // Use 'eppn' (eduPersonPrincipalName) for WP's user_login
    'user_login_attribute' => 'eppn',

    // Use 'mail' for WP's user_email
    'user_email_attribute' => 'mail',

    // use 'displayName' for WP's display_name
    'display_name_attribute' => 'displayName',

    // use 'givenName' for WP's first_name
    // NOTE: given name and first name are not the same thing
    'first_name_attribute' => 'givenName',

    // use 'sn' for WP's last_name
    // NOTE: surname/sn and last name are not the same thing
    'last_name_attribute' => 'sn',
  );

  /**
   * Alternative attribute names
   *
   * SAML attributes can be named in multiple ways, so this hash
   * defines alternative names for the attributes used by
   * Stanford-Auth/WP-SAML-Auth
   *
   */
  private static $attribute_alt_names = array(
    'eppn' => array(
      'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
      'eduPersonPrincipalName',
    ),
    'entitlement' => array(
      'urn:oid:1.3.6.1.4.1.5923.1.1.1.7',
      'eduPersonEntitlement',
    ),
    'affiliation' => array(
      'urn:oid:1.3.6.1.4.1.5923.1.1.1.9',
      'eduPersonScopedAffiliation',
    ),
    'unscoped-affiliation' => array(
      'urn:oid:1.3.6.1.4.1.5923.1.1.1.1',
      'eduPersonAffiliation',
    ),
    'mail' => array(
      'urn:oid:0.9.2342.19200300.100.1.3',
    ),
    'sn' => array(
      'urn:oid:2.5.4.4',
      'surname',
    ),
    'givenName' => array(
      'urn:oid:2.5.4.42',
    ),
    'displayName' => array(
      'urn:oid:2.16.840.1.113730.3.1.241',
    ),
  );


  private static $wpsa_settings = array(
    'connection_type'        => 'internal',
    'auto_provision'         => true,
    'get_user_by'            => 'login',
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

      add_filter( 'plugin_action_links_' . plugin_basename( dirname( plugin_dir_path( __FILE__ ) ) )
                                         . '/stanford-auth.php',
                  array( self::$instance, 'plugin_settings_link' )
      );

    }
    return self::$instance;
  }


  /**
   * Add Settings link to plugins page
   *
   * @param array $links existing plugin links.
   * @return mixed
   */
  public static function plugin_settings_link( $links ) {
    $a = '<a href="' . menu_page_url( 'saml-metadata', false ) . '">' . esc_html__( 'SAML Metadata', 'saml-metadata' ) . '</a>';
    array_push( $links, $a );
    return $links;
  }

  /**
   * logger
   *
   * @param array $args for sprintf
   */
  private static function log( ...$args ) {
    error_log( sprintf( ...$args ) );
  }

  /**
   * debug logger
   *
   * @param array $args for sprintf
   */
  private static function debug( ...$args ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
      error_log( sprintf( ...$args ) );
    }
  }

  /**
   * Get a configuration option for this implementation.
   *
   * @param string $option_name Configuration option to produce.
   * @return mixed
   */
  public static function get_option( $option_name) {
    $value = isset( self::$options[ $option_name ] ) ? self::$options[ $option_name ] : null;
    $filtered = apply_filters( 'stanford_auth_option', $value, $option_name );
    return $filtered;
  }

  /**
   * Initialize the controller logic on the 'init' hook
   */
  public function action_init() {

    // assume everything's set up correctly
    $enabled = true;

    // we need access to classes within OneLogin\Saml2, so re-use the
    // WP-SAML-Auth autoloader
    
    if ( file_exists( WP_SAML_AUTH_AUTOLOADER ) ) {
      require_once WP_SAML_AUTH_AUTOLOADER;
    }

    // can't use this when setting up $options, so set it here
    
    self::$options[ 'entityId' ] = 'https://' . parse_url( home_url(), PHP_URL_HOST );

    if ( current_user_can( 'manage_options' ) ) {
      if ( ! class_exists( 'WP_SAML_Auth' ) ) {
        $enabled = false;
        add_action('admin_notices', function () {
          // Translators: Links to the WP SAML Auth plugin.
          echo '<div class="message error"><p>' . wp_kses_post( sprintf("Stanford Auth was unable to find the <code>WP_SAML_Auth</code> class. Please ensure the <a href='%s'>WP-SAML-Auth</a> plugin is installed."), 'https://wordpress.org/plugins/wp-saml-auth/' ) . '</p></div>';
        });
      }

      if ( ! class_exists( 'OneLogin\Saml2\IdPMetadataParser' ) ) {
        $enabled = false;
        add_action('admin_notices', function () {
          // Translators: Links to the WP SAML Auth plugin.
          echo '<div class="message error"><p>' . wp_kses_post( sprintf("Stanford Auth was unable to find the IdPMetadataParser class that shoule be installed with  <code>WP_SAML_Auth</code> class. Please ensure the <a href='%s'>WP-SAML-Auth</a> plugin is installed."), 'https://wordpress.org/plugins/wp-saml-auth/' ) . '</p></div>';
        });
      }
        
      if ( !has_filter( 'stanford_auth_option' ) ) {
        $enabled = false;
        add_action('admin_notices', function () {
          echo '<div class="message error"><p>'
            .  'Stanford Auth is unconfigured. Please ensure that there is a filter enabled for <code>stanford_auth_option</code> to provide configuration'
            . '</p></div>';
        });
      }
    }

    
    if ($enabled) {

      // WP-SAML-Auth supplies a filter hook to modify its
      // configuration Stanford-Auth does the same thing, but some of
      // the configuration for WP-SAML-Auth should be configured
      // directly by Stanford-Auth, not by the plugin that's
      // configuring Stanford-Auth

      self::$wpsa_settings[ 'user_login_attribute'   ] = self::$attribute_names[ 'user_login_attribute' ];
      self::$wpsa_settings[ 'user_email_attribute'   ] = self::$attribute_names[ 'user_email_attribute' ];
      self::$wpsa_settings[ 'display_name_attribute' ] = self::$attribute_names[ 'display_name_attribute' ];
      self::$wpsa_settings[ 'first_name_attribute'   ] = self::$attribute_names[ 'first_name_attribute' ];
      self::$wpsa_settings[ 'last_name_attribute'    ] = self::$attribute_names[ 'last_name_attribute' ];
      self::$wpsa_settings[ 'permit_wp_login'        ] = self::get_option('permit_wp_login');

      // Default WordPress role to grant when provisioning new users.
      self::$wpsa_settings[ 'default_role'           ] = get_option( 'default_role' );

      // update the SAML configuration
      self::configureSAML();
      
      // add the filter to customize the WP-SAML-Auth configuration
      add_filter( 'wp_saml_auth_option',
                  array( $this, 'filter_wpsa_option' ),
                  0, 2 );

      // add the filter to change the login button text, if it's
      // displayed
      add_filter( 'wp_saml_auth_login_strings',
                  array( $this, 'filter_login_strings' ),
                  0, 1 );

      // add a filter to fix up attribute names
      add_filter( 'wp_saml_auth_attributes',
                  array( $this, 'fix_attributes' ),
                  0, 2 );

      // add a filter to map attributes to a role or user
      add_filter( 'wp_saml_auth_attributes',
                  array( $this, 'map_attributes' ),
                  0, 2 );

      // WP-SAML-Auth always sets the role to the default role, so add
      // a filter to set the correct role before the new user is
      // created
      add_filter( 'wp_saml_auth_insert_user',
                  array( $this, 'fix_role_before_insert_user' ),
                  0, 2 );

      // add a filter to deny access to an unmapped user, if that
      // option is set
      add_filter( 'wp_saml_auth_pre_authentication',
                  array( $this, 'deny_unmapped_user' ),
                  0, 2 );
                         
      add_action( 'wp_saml_auth_existing_user_authenticated',
                  array( $this, 'update_user' ),
                  0, 2);
      add_action( 'wp_saml_auth_new_user_authenticated',
                  array( $this, 'update_user' ),
                  0, 2);
      //
      add_action( 'admin_menu',
                  array( $this, 'add_metadata_page' ) );

    }
  }

  /**
   */
  public function add_metadata_page () {
    add_options_page('SAML Metadata', 'SAML Metadata', 'manage_options', 'saml_metadata', array ($this, 'metadata_page' ) );
  }


  /**
   */
  private static function configureSAML ( ) {
    $idp      = self::$idps[ self::get_option( 'idp' ) ];

    $imp = new OneLogin\Saml2\IdPMetadataParser ();
    
    $idp_metadata = $imp->parseRemoteXml(
      $idp[ 'md_url' ],
      $idp[ 'entityId' ],
      OneLogin\Saml2\Constants::NAMEID_PERSISTENT,
      OneLogin\Saml2\Constants::BINDING_HTTP_REDIRECT,
      null
    );

    $sp_cert      = self::get_option( 'sp_cert' );
    $sp_cert_file = self::get_option( 'sp_cert_file' );
    $sp_key       = self::get_option( 'sp_key' );
    $sp_key_file  = self::get_option( 'sp_key_file' );

    if ( empty( $sp_cert ) && !empty( $sp_cert_file ) && file_exists( $sp_cert_file ) ) {
      $sp_cert = file_get_contents( $sp_cert_file );
    }
    if ( empty( $sp_key ) && !empty( $sp_key_file ) && file_exists( $sp_key_file ) ) {
      $sp_key = file_get_contents( $sp_key_file );
    }

    $saml_config = array(
      // Validation of SAML responses is required.
      'strict'  => true,
      'debug'   => defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false,
      'baseurl' => home_url(),
      'sp'      => array(
        'entityId'                 => self::get_option( 'entityId' ),
        'x509cert'                 => $sp_cert,
        'privateKey'               => $sp_key,
        'NameIDFormat'             => OneLogin\Saml2\Constants::NAMEID_PERSISTENT,
        'assertionConsumerService' => array(
          'url'     => home_url( 'wp-login.php' ),
          'binding' => OneLogin\Saml2\Constants::BINDING_HTTP_POST,
        ),
      ),
      'idp'     => array(
        'entityId' => $idp[ 'entityId' ],
      ),
      'security' => array(
        'authnRequestsSigned' => false,
        'wantAssertionsSigned' => true,
        'wantAssertionsEncrypted' => !empty( $sp_key ),
        'wantNameIdEncrypted' => false,
        'signMetadata' => false,
        'requestedAuthnContext' => false,
        'requestedAuthnContextComparison' => 'exact',
        
      ),
      'contactPerson' => array(
        'technical' => array(
          'givenName'    => esc_html( get_bloginfo( 'name' ) . ' Admin' ),
          'emailAddress' => get_bloginfo( 'admin_email' ),
        ),
        'support' => array(
          'givenName'    => esc_html( get_bloginfo( 'name' ) . ' Admin' ),
          'emailAddress' => get_bloginfo( 'admin_email' ),
        ),
      ),

      'organization' => array(
        'en-US' => array(
          'name'        => esc_html( get_bloginfo( 'name' ) ),
          'displayname' => esc_html( get_bloginfo( 'name' ) ),
          'url'         => get_bloginfo( 'url' ),
        ),
      ),
    );

    self::$wpsa_settings[ 'internal_config' ] = $imp->injectIntoSettings( $saml_config, $idp_metadata );
  }

  /**
   */
  public function metadata_page ( ) {
    echo '<h2>SAML Metadata</h2>';
    try {
      $auth = new OneLogin\Saml2\Auth( self::$wpsa_settings[ 'internal_config' ] );
      $settings = $auth->getSettings();
      $metadata = $settings->getSPMetadata(
        false,                // only include encryption cert if it's required
        time() + 86400 * 365, // metadata valid for 1 year
        86400 * 7             // metadata can be cached for 1 week
      );
      $errors = $settings->validateMetadata($metadata);
      if (empty($errors)) {
        $metadata = preg_replace_callback (
          '#( *)(\<ds:X509Certificate>)(.+)(\</ds:X509Certificate>)#i',
          function ( $matches ) {
            return $matches[1]
              . $matches[2] . "\n  "
              . $matches[1] . join("\n  " . $matches[1], str_split( $matches[3], 64 ) ) . "\n"
              . $matches[1] . $matches[4];
          },
          $metadata);

        ?>
        <p>
        Copy the XML metadata below and use it to
        <a target='_blank' href='https://spdb.stanford.edu/spconfigs/new'>create</a>
        or
        <a target='_blank' href='https://spdb.stanford.edu/spconfigs/list?utf8=✓&search=<?php 
          echo self::get_option( 'entityId' );
        ?>'>update</a> this site's entry in <a href='https://spdb.stanford.edu'>SPDB</a>.
        </p>
        <button id='copy_metadata'>Copy Metadata</button>
        <script type='text/javascript'>
        function copy_metadata (ev) {
          var clip = document.createElement('textarea'),
              srcEl = document.getElementById('metadata');

          if (srcEl) {
            clip.value = srcEl.innerText;
            clip.setAttribute('readonly', '');
            clip.style.position = 'absolute';
            clip.style.left = '-9999px';
            document.body.appendChild(clip);
            clip.select();
            document.execCommand('copy');
            document.body.removeChild(clip);
          }
        }
        document.getElementById('copy_metadata').addEventListener('click', copy_metadata);
        </script>
        <?php
        echo '<pre id="metadata">' . esc_html( $metadata ) . '</pre>';
      } else {
        echo '<p>The generated metadata is invalid:</p><ul><li>';
        echo implode( '</li><li>', $errors );
        echo '</ul>';
      }
    } catch (Exception $e) {
      echo '<p>There was an error while generating the metadata: ' . esc_html($e->getMessage());
      self::log( "SP Metadata exception:\n%s", $e->getMessage() );
    }
  }
  
  /**
   * Provides options for Stanford Auth to override WPSA defaults
   *
   * @param mixed  $default_value       Configuration value.
   * @param string $option_name Configuration option name.
   */
  public function filter_wpsa_option( $default_value, $option_name ) {
    return isset( self::$wpsa_settings[ $option_name ] ) ? self::$wpsa_settings[ $option_name ] : $default_value;
  }

  public function filter_login_strings ( $strings ) {
    $strings['title'] = 'Use Stanford Login';
    return $strings;
  }

  /**
   * Fix up the attributes recevied from the IdP to use consistent names
   *
   * @param array $attributes Array of attributes from the IdP
   * @param mixed $provider The SAML provider
   * @return array The (possibly modified) attributes
   */
  public function fix_attributes ( $attributes, $provider ) {

    // first, look for alternative attribute names, and fix things up
    foreach ( self::$attribute_alt_names as $name => $alt_names ) {
      if ( !isset( $attributes[ $name ] ) ) {
        foreach ( $alt_names as $alt_name ) {
          if ( isset( $attributes[ $alt_name ] ) ) {
            $attributes[ $name ] = $attributes[ $alt_name ];
            break;
          }
        }
      }
    }

    return $attributes;
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
  public function map_attributes ( $attributes, $provider ) {

    $roles = array();
    $user  = null;
    $default_user = self::get_option( 'default_user' );

    foreach ( self::get_option( 'attribute_map' ) as $attr_name => $value_map ) {

      if ( isset( $attributes[ $attr_name ] ) ) {
        
        foreach ( $value_map as $attr_value => $userrole ) {
          if (in_array( $attr_value, $attributes[ $attr_name ] ) ) {

            if ( strpos($userrole, ':' ) === false ) {
              $userrole = 'role:' . $userrole;
            }
              
            $pair = explode(':', $userrole);

            if ( $pair[0] === 'role') {
              $roles[] = $pair[1];
            } elseif ( $pair[0] === 'eppn' || $pair[0] === 'user' ) {
              $user = $pair[1];
            }
          }
        }
      }
    }

    // role mappings take precedence over user mapping
    if ( count($roles) > 0) {
      $attributes['__stanford_auth_mapped__'] = array( 'role' );
      $attributes['role'] = $roles;
    } else if ( !empty( $user ) || !empty( $default_user ) ) {
      $attributes['__stanford_auth_mapped__'] = array( 'user' );
      $attributes[ self::$attribute_names[ 'user_login_attribute' ] ] = array(
        empty( $user ) ? $default_user : $user
      );
    }
    return $attributes;
  }

  /**
   * Fix the user role before creating the new user
   *
   * @param array $user_args user parameters
   * @param array $attributes attributes from the IdP (and updated by fix_attributes and map_attributes)
   * @return array updated user parameters
   */
  public function fix_role_before_insert ( $user_args, $attributes ) {
    if ( !empty( $attributes[ 'role' ][0] ) ) {
      // this runs before the call to wp_insert_user, which only takes
      // a single role, so return the first role
      $user_args['role'] = $attributes[ 'role' ][0];
    }
    return $user_args;
  }

  public function update_user ( $user, $attributes) {
    
    // only update if the user was not mapped to a WP user

    if ( isset( $attributes[ '__stanford_auth_mapped__' ] ) && $attributes[ '__stanford_auth_mapped__' ][0] === 'role' ) {

      // update user role(s)
      if ( !empty( $attributes['role'][0] ) ) {
        $user->set_role( '' ); // remove existing roles
        foreach ( $attributes['role'] as $role ) {
          $user->add_role( $role );
        }
      }

      // update user attributes *except* user_login
      foreach ( array( 'display_name', 'user_email', 'first_name', 'last_name' ) as $type ) {
        $attribute = self::$attribute_names[ "{$type}_attribute" ];
        if ( isset( $attributes[ $attribute ][0] ) && $user->__get( $type ) != $attributes[ $attribute ][0] ) {
          $user->__set( $type, $attributes[ $attribute ][0] );
        }
      }
      wp_update_user( $user ); // update user with new role, so admins can see current roles
    }
  }
    

  public function deny_unmapped_user ( $default, $attributes ) {
    $ret = $default;
    if ( self::get_option( 'deny_unmapped_users' ) &&
         ! isset( $attributes[ '__stanford_auth_mapped__' ] )
    ) {
      self:debug( 'user is not mapped, returning error' );
      $ret = new WP_Error( 'unmapped_user_denied',
                           'You are not authorized to access this content' );
    }

    return $ret;
  }

}


