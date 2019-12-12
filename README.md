# WP SAML Auth #
**Contributors:** Scotty Logan
**Tags:** authentication, SAML, Stanford
**Requires at least:** 4.5
**Tested up to:** 5.3
**Stable tag:** 0.1.0
**License:** MIT
**License URI:** https://opensource.org/licenses/MIT

Stanford-specific SAML authentication for WordPress.

## Description ##

This is a plugin to configure the WP-SAML-Auth plugin to work with
Stanford University's SAML IdP. While this plugin configures many of
the WP-SAML-Auth settings to work with Stanford's IdP, it also needs
some additional site-specific configuration.

## Configuration ##

Currently, there is no UI for setting up this plugin, so it must be
configured with a site specific plugin.

Configure using a custom filter - here's an example (similar to the one in `example-stanford-auth-config`):

```php
<?php
/**
 * Plugin Name: My Stanford Authentication Configuration
 * Version: 0.1.0
 * Description: Site-specific configuration for Stanford Auth plugin for My Site
 * Author: John Doe
 * Author URI: https://mysite.stanford.edu
 * Plugin URI: https://code.stanford.edu.org/myorg/mysite-stanford-auth-config/
 *
 * @package Stanford_Auth_Config
 */

function stanford_auth_filter_option( $default_value, $option_name ) {
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
  );

  return isset( $options[ $option_name ] ) ? $options[ $option_name ] : $default_value;
}

add_filter( 'stanford_auth_option', 'stanford_auth_filter_option', 0, 2 );

```

## Configuring the SAML Certificate and Key ##

Generally, you will need to create your plugin using a directory under `wp-content/plugins`, because you need to securely store the private key and public certificate used for SAML. These should not be the same private key and public certificate used for your client facing web server. Instead, generate a self-signed certificate using `openssl`:

```
openssl req -new -newkey rsa:2048 -keyout sp.key -days 7500 -out sp.crt -subj /CN=$YOUR_ENTITY_ID
```

replacing `$YOUR_ENTITY_ID` with the entity Id used by your site - usually just the base URL of your site, e.g. `https://mysite.stanford.edu`.

If your SAML certificate and key are stored somewhere else on the server, you can use a single file for the plugin - place your version of `example-stanford-auth-config.php` under `wp-content/plugins`, and update the plugin settings to point to where the certificate and key are stored:

```php
    'sp_cert_file' => '/path/to/my/sp.crt',
    'sp_key_file'  => '/path/to/my/sp.key',
```

If your SAML key and certificate are available as environment variables, you can also use a single file. Update the plugin settings to set `sp_cert` and `sp_key` (not `sp_cert_file` and `sp_key_file`) to the full filesystem paths of your certificate and key:

```php
    'sp_cert' => $_ENV['MY_CERT_ENV_VAR'],
    'sp_key'  => $_ENV['MY_KEY_ENV_VAR'],
```

## Other Configuration Options ##

The default entity ID for your site is `https://` + the hostname (as determined by PHP / WordPress). You can override this by setting the `entityId` option.

```php
    'entityId' => 'http://wp-test.itlab.stanford.edu',
```

You can disable WordPress form authentication by setting the `permit_wp_login` option to `false` - but please ensure SAML authentication is working properly before doing that.

## SAML Attribute to Role and User Mapping ##

The `attribute_map` option enables mapping Stanford users to WordPress roles, or to specific WordPress users, using attributes recevied from the IdP. The most common use of this is to map (workgroups)[https://workgroup.stanford.edu/] to roles. Stanford Workgroups are released as `eduPersonEntitlement` attributes, usually called `entitlement`:

```php
    'attribute_map' => array (
      'entitlement' => array (
        'itlab:staff' => 'role:administrator',
        'test:staff'  => 'role:author',
        'test:student' => 'role:contributor',
      ),
    ),
```

Additionally, many people can be mapped to existing WordPress users based on their Stanford workgroup memberships:

```php
    'attribute_map' => array (
      'entitlement' => array (
        'test:faculty' => 'user:general-user',
      ),
    ),
```

Or, using the `affiliation` attribute (also known as `eduPersonScopedAffiliation`) you could map all students to a single user:

```php
    'attribute_map' => array (
      'affiliation' => array (
        'student@stanford.edu' => 'user:general-user',
      ),
    ),
```

