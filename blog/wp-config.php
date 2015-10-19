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
define('DB_NAME', 'camerarw_blog');

/** MySQL database username */
define('DB_USER', 'camerarw_user');

/** MySQL database password */
define('DB_PASSWORD', 'artconfex1');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '*|YQmn>b$PoVpB6[RB=Ay-M]*ey#cW48-oZ_M^7Q+&>4+_l@4DgeaTt&Rq+_V3Km');
define('SECURE_AUTH_KEY',  'U+O:&;&>^&sG-SW:CX)j.~S(>Bn~pBo#V|OpAY]!(e4nWufmWK%!CdKRfNx a?$&');
define('LOGGED_IN_KEY',    '/%taH-<*(-$c+Qu_T:<,h>bG1QjY]Uky[{$yIO(e|>iV|TXTXVWc2d;Hsen:Xu<O');
define('NONCE_KEY',        'PW+~-.-.s`$fYF.zHm5Jn!R7=i .BZ(vQ&Mc*NU$]0(1p_}I)DQ!qf9:l2^uUd [');
define('AUTH_SALT',        '[-+9<eTJ=/||!-z!i7r)Ulsz=S:7|d&eI@j<1bmnKxXlz/|SGhGXhv[w4`5:5*zQ');
define('SECURE_AUTH_SALT', '.-_tlp%+Kac{Lj2;QdlJpT%A1F<q ~WT(z;q;:~{hZxO:M-M~4v`ala[4]|k/uXv');
define('LOGGED_IN_SALT',   '[N6Mq}f9+hG^:|`dhgns>Q|uQ|g+8lz-p,l[]7E{.0FPONUL*Q.!@0&OcRhr&BVv');
define('NONCE_SALT',       'GO~N%6#9yXz[CH>U0xPGWo5SCl*J4M(LJM|-u86n0=1,rAXh|y:H&jAj4i]59Hmk');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
