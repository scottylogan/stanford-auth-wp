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

Configure using a custom filter:

```php

function stanford_auth_option ( $value, $option_name ) {
  $defaults = array(
    'group_attribute' => 'eduPersonEntitlement',
    'groups_to_roles' => array (
      'itlab:staff' => 'admin', // existing role
      'uit:all'     => 'subscriber', // existing role
    ),
    'groups_to_users' => array (
      'itlab:staff' => 'editor', // existing user
      '__default__' => array(    // create user if it doesn't exist
        'eppn' => 'susbscriber',
        'mail' => 'no-reply@stanford.edu',
        'givenName' => 'Default',
        'sn'        => 'Subscriber',
        'displayName' => 'Default Subscriber'
      )
    )

  $value = isset( $defaults[ $option_name ] ) ? $defaults[ $option_name ] : $value;
  return $value;
);

add_filter( 'stanford_auth_option', 'stanford_auth_option', 0, 2);
```


