<?php
/**
 * Updates from GitHub releases. The plugin header's "Update URI" points to
 * github.com, so WordPress asks the update_plugins_github.com filter instead
 * of WordPress.org.
 *
 * Each release carries release.json ({"version", "package", "url"}), built by
 * .github/workflows/release.yml. It is read through the github.com
 * "releases/latest/download" redirect, which is not subject to the REST API's
 * 60 requests/hour limit per IP (often reached on shared hosting).
 *
 * No update is offered on a local site (wp_get_environment_type() === 'local'):
 * there the plugin is the working copy of the repository.
 *
 * @package Adeldev_Organisation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Latest release, cached for 6 hours (1 hour after a failed request).
 *
 * @return array{version: string, package: string, url: string}|null
 */
function adeldev_org_latest_release(): ?array {
	$cached = get_site_transient( 'adeldev_org_release' );
	if ( is_array( $cached ) ) {
		return $cached['version'] ? $cached : null;
	}

	$release  = array(
		'version' => '',
		'package' => '',
		'url'     => '',
	);
	$response = wp_remote_get(
		'https://github.com/' . ADELDEV_ORG_REPOSITORY . '/releases/latest/download/release.json',
		array( 'timeout' => 10 )
	);

	if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
		$data    = json_decode( wp_remote_retrieve_body( $response ), true );
		$version = is_array( $data ) ? (string) ( $data['version'] ?? '' ) : '';
		$package = is_array( $data ) ? (string) ( $data['package'] ?? '' ) : '';
		$prefix  = 'https://github.com/' . ADELDEV_ORG_REPOSITORY . '/releases/download/';

		// Only accept packages from this repository's releases.
		if ( preg_match( '/^\d+\.\d+\.\d+$/', $version ) && str_starts_with( $package, $prefix ) ) {
			$release = array(
				'version' => $version,
				'package' => $package,
				'url'     => (string) ( $data['url'] ?? '' ),
			);
		}
	}

	set_site_transient( 'adeldev_org_release', $release, $release['version'] ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );

	return $release['version'] ? $release : null;
}

add_filter( 'update_plugins_github.com', 'adeldev_org_update', 10, 3 );

/**
 * Answer WordPress' update check. When GitHub can't be reached, report the
 * installed version: the plugin is then listed as "no update" and keeps its
 * auto-update toggle instead of looking unsupported.
 *
 * @param array|false $update      Update data from earlier filters.
 * @param array       $plugin_data Plugin headers.
 * @param string      $plugin_file Plugin basename.
 * @return array|false
 */
function adeldev_org_update( $update, array $plugin_data, string $plugin_file ) {
	if ( plugin_basename( ADELDEV_ORG_FILE ) !== $plugin_file ) {
		return $update;
	}

	$installed = array(
		'version' => ADELDEV_ORG_VERSION,
		'package' => '',
		'url'     => 'https://github.com/' . ADELDEV_ORG_REPOSITORY,
	);

	/**
	 * Whether this site looks for new releases (not on a local working copy).
	 *
	 * @param bool $enabled Default: true unless the environment type is "local".
	 */
	$enabled = apply_filters( 'adeldev_org_updates_enabled', 'local' !== wp_get_environment_type() );
	$release = $enabled ? ( adeldev_org_latest_release() ?? $installed ) : $installed;

	return array(
		'id'      => $plugin_data['UpdateURI'],
		'slug'    => 'adeldev-organisation',
		'version' => $release['version'],
		'url'     => $release['url'],
		'package' => $release['package'],
	);
}

add_action( 'upgrader_process_complete', 'adeldev_org_clear_release_cache' );

/**
 * Forget the cached release once an update has run.
 */
function adeldev_org_clear_release_cache(): void {
	delete_site_transient( 'adeldev_org_release' );
}
