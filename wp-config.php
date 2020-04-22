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
/** Database name for Wordpress */
define( 'DB_NAME', '4wayvoice' );

/** MySQL 資料庫使用者名稱 */
define( 'DB_USER', 'ap_4wayvoice' );

/** MySQL 資料庫密碼 */
define( 'DB_PASSWORD', 'Aemee2Eb>u6wi0U' );

/** MySQL 主機位址 */
define( 'DB_HOST', '172.20.3.2' );

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

define('WP_MEMORY_LIMIT', '128M');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'l0Rn6xPfT4b67o335jRRFCpsEcgrHPOcvZyFn226ulK08csN9L55PseLk0TRfdLN');
define('SECURE_AUTH_KEY',  'yUoqM1k9s3KfayiloJeA3JwEfRZyfotw8ipxpLub6HyedOXDk47uJwAUlB9wrDpi');
define('LOGGED_IN_KEY',    'i2tA6VlhlGcpJL8TvfiK3sxMEZoax1tvAhOzZWOA3lW3dRepAioumyayQqyrf6mP');
define('NONCE_KEY',        'BVGtBRIbNvucMvrxIbY5n7eYKoonhEWeJHcpfkXvAbkE00rM6aADpW0idpaY6fQ7');
define('AUTH_SALT',        'dzBX5UMZ7kCrCUYWWAYgajmu8M3YTQQu52Aw9sjZqGpRDPVGJ3AGclfkMvCRflvM');
define('SECURE_AUTH_SALT', 'hnUxfFqoJHOKQjL6wwvqdj4MMuGx35YrTMk2jegdM2TolUqalbtJ80pHqXQvhhdc');
define('LOGGED_IN_SALT',   'jcPs1MDC3uHXJsW0JpK62WEhfUk2P6MRVsyaOpO3RlQ5fVXsKjsotE10exXRXyBC');
define('NONCE_SALT',       'LOZJpXaJ63M6FQ7bsmpXURjcvma1LtIbI6tPeVUuh50D50HE0mvlUkpRAw5XlSIj');

/**
 * Other customizations.
 */
define('FS_METHOD','direct');define('FS_CHMOD_DIR',0755);define('FS_CHMOD_FILE',0644);
define('WP_TEMP_DIR',dirname(__FILE__).'/wp-content/uploads');

/**
 * Turn off automatic updates since these are managed upstream.
 */
define('AUTOMATIC_UPDATER_DISABLED', true);


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

define('WP_SITEURL', 'https://4wayvoice.nownews.com');
define('WP_HOME', 'https://4wayvoice.nownews.com');
