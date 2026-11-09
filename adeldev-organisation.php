<?php
/**
 * Plugin Name:       Adeldev Organisation
 * Plugin URI:        https://github.com/AdelMahjoub/adeldev-organisation
 * Description:       The organisation's details (name, contact, address, opening hours, social networks, legal notice) entered once, in every site language, and reused everywhere: block bindings, inline insertion in text, shortcode, editor panel.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            Adel Mahjoub
 * Author URI:        https://adeldev.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       adeldev-organisation
 * Domain Path:       /languages
 * Update URI:        https://github.com/AdelMahjoub/adeldev-organisation
 *
 * @package Adeldev_Organisation
 */

defined( 'ABSPATH' ) || exit;

define( 'ADELDEV_ORG_VERSION', '0.1.0' );
define( 'ADELDEV_ORG_FILE', __FILE__ );
define( 'ADELDEV_ORG_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADELDEV_ORG_URL', plugin_dir_url( __FILE__ ) );
define( 'ADELDEV_ORG_OPTION', 'adeldev_organisation' );
define( 'ADELDEV_ORG_REPOSITORY', 'AdelMahjoub/adeldev-organisation' );

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'adeldev-organisation', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	},
	0
);

require_once ADELDEV_ORG_DIR . 'includes/fields.php';
require_once ADELDEV_ORG_DIR . 'includes/settings.php';
require_once ADELDEV_ORG_DIR . 'includes/display.php';
require_once ADELDEV_ORG_DIR . 'includes/updater.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once ADELDEV_ORG_DIR . 'includes/cli.php';
}
