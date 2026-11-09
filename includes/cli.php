<?php
/**
 * WP-CLI: `wp organisation list|get|set|import`, to read and seed the details.
 *
 * @package Adeldev_Organisation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the organisation details.
 */
class Adeldev_Org_Command {

	/**
	 * Lists every detail with its value.
	 *
	 * ## OPTIONS
	 *
	 * [--locale=<locale>]
	 * : Language of the values (default: the site's main language).
	 *
	 * [--format=<format>]
	 * : table, csv, json or yaml.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Options.
	 */
	public function list( array $args, array $assoc_args ): void {
		$locale = $assoc_args['locale'] ?? adeldev_org_default_locale();
		$rows   = array();
		foreach ( adeldev_org_catalog( $locale ) as $key => $entry ) {
			$rows[] = array(
				'key'   => $key,
				'label' => $entry['label'],
				'value' => $entry['value'],
			);
		}
		WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, array( 'key', 'label', 'value' ) );
	}

	/**
	 * Prints one detail.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Key, e.g. contact.phone or link.whatsapp.
	 *
	 * [--locale=<locale>]
	 * : Language of the value.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Options.
	 */
	public function get( array $args, array $assoc_args ): void {
		WP_CLI::line( adeldev_org_value( $args[0], $assoc_args['locale'] ?? adeldev_org_default_locale() ) );
	}

	/**
	 * Sets one detail.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Key, e.g. contact.phone.
	 *
	 * <value>
	 * : Value; an empty string clears it.
	 *
	 * [--locale=<locale>]
	 * : Value for this language. Default: main language.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Options.
	 */
	public function set( array $args, array $assoc_args ): void {
		list( $key, $value ) = $args;
		$field               = adeldev_org_field( $key );
		$locale              = $this->locale( $assoc_args );
		if ( ! $field ) {
			WP_CLI::error( "Unknown key: $key" );
		}
		adeldev_org_save( array( $key => $value ), $locale );
		WP_CLI::success( "$key = " . adeldev_org_value( $key, 'default' === $locale ? adeldev_org_default_locale() : $locale ) );
	}

	/**
	 * Imports details from a JSON file, merged into what is stored.
	 *
	 * The file holds one object per language: {"default": {"contact.phone": "…"},
	 * "ar": {"address.city": "…"}}. A flat object {"contact.phone": "…"} is the main
	 * language. Unknown keys are reported and skipped.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : JSON file.
	 *
	 * @param array $args Positional arguments.
	 */
	public function import( array $args ): void {
		$data = json_decode( (string) file_get_contents( $args[0] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Invalid JSON.' );
		}
		if ( ! array_filter( $data, 'is_array' ) ) {
			$data = array( 'default' => $data );
		}
		$count = 0;
		foreach ( $data as $locale => $values ) {
			$locale = $this->locale( array( 'locale' => (string) $locale ) );
			foreach ( array_keys( (array) $values ) as $key ) {
				$field = adeldev_org_field( (string) $key );
				if ( ! $field ) {
					WP_CLI::warning( "Unknown key skipped: $key." );
					unset( $values[ $key ] );
				}
			}
			adeldev_org_save( (array) $values, $locale );
			$count += count( (array) $values );
		}
		WP_CLI::success( "$count values imported." );
	}

	/**
	 * 'default' for the main language, else the locale.
	 *
	 * @param array $assoc_args Options.
	 */
	private function locale( array $assoc_args ): string {
		$locale = (string) ( $assoc_args['locale'] ?? 'default' );
		return in_array( $locale, array( '', 'default', adeldev_org_default_locale() ), true ) ? 'default' : $locale;
	}
}

WP_CLI::add_command( 'organisation', 'Adeldev_Org_Command' );
