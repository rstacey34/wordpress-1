<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress-1' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ':l-.mpF-?j+5,&!8z.!Y~V ]au@Rmli[yMI3Eb+6Vfv{RB!2nlW!F%t7sb/{H+uU' );
define( 'SECURE_AUTH_KEY',  'zKJbd{zzKxpb1PU@CNs32l=>jdaa7 GpN6SG:^NIvY],q1-75bc2!P8[0a%TWb.g' );
define( 'LOGGED_IN_KEY',    'lC`jv(W|uK3z2%U]J&Su&q0vfDE}S?W^=J/+^a_s#R8iS#/sQIfHqg2UORH@,R}`' );
define( 'NONCE_KEY',        'WrzVEpBc$~&<mD]YQpL+(^X|n*/]AXODxP`(@)Ry%?]BzQi+XDlhF9JX/Np+|#F4' );
define( 'AUTH_SALT',        '07c~+llTDo .V$g=-1e1UZrZEM,jmX]D^g)a3M6X&^kl&W?_nI^R4&VEgFd;EOAI' );
define( 'SECURE_AUTH_SALT', 'g_X6t^.=4V_+WY`boBI}i)8xmI>V51,L}/@ce>?)Y4C,x#I8|c?S(aG5$Ek>}v~L' );
define( 'LOGGED_IN_SALT',   '<b>]7weBYqAX51 WH!Xl /XG.E~B32=}Vz=JK&iu]l-11`)4~0-M!!C$eL>RN-mL' );
define( 'NONCE_SALT',       '2mq0N@k$Jkqy2w<MhKe]WD]Q~Qs[*2JkdLx#0|<Pm9+tFri/j3z?}.fCAb5`CfPl' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
