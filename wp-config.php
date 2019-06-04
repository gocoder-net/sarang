<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'c306art' );

/** MySQL database username */
define( 'DB_USER', 'c306art' );

/** MySQL database password */
define( 'DB_PASSWORD', '123qweasd!@#' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'v@Y,KGit$DmAGFbR:?wK_HR331*vx#JZt.)])*gwb@NDw98,tyPnz)q}{n#Un: r' );
define( 'SECURE_AUTH_KEY',  'js)PK^HZ8n+L>9eO=QLW4F;lte,5JQdUWP/-0J0<biuq65;(jL=&aU+,eOnUN3cs' );
define( 'LOGGED_IN_KEY',    'vpXNxcx<>%Rd-+CojfjOxGHur[%ZR$]??9V#+soKq8xqXh|wSCL~[C}L}+^Svc5.' );
define( 'NONCE_KEY',        'w[9~*Z`-6xgmn&NVH`TV^_*N>f(DkaF9ibhoB!A.ODXc:Plbl&V<ZwUyhn9|5XVk' );
define( 'AUTH_SALT',        'I2f^=gndO/KsoV}hd{:%5vj. hS92~FeJi<(+<V4MLwpa[.*FC+FqlUT}o!+Uy($' );
define( 'SECURE_AUTH_SALT', '6_yQF_a2W}baM9U4T,]jcoE!h+Tk^8@I1(dl8Ycn#QkqHvek(R|`F[g=q-w)NBP|' );
define( 'LOGGED_IN_SALT',   'DiURoh;daGI#C^pDv(D8v|NL47&/$<~O=:M%Ng32Y&<$57? t|`:I{Vm(|Xy5)vI' );
define( 'NONCE_SALT',       'B{7TrGkYdvQV&BSOiCYqT}quL`|@4)20BJ_LX&Q u(aBDfD_E,9j &X^b-eW8<u1' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'sarang_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
