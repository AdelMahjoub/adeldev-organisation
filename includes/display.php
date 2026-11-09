<?php
/**
 * Showing the details in content, always in the language of the page:
 *
 * - Block bindings source "adeldev/organisation": a whole paragraph, heading, button
 *   (text and URL) or social icon shows a detail. Blocks bound to an empty detail are
 *   not rendered (no empty phone line).
 * - Inline insertion (building button of the block toolbar, assets/editor.js): stored as
 *   <span class="adeldev-org" data-adeldev-org="key"> or as a link
 *   <a class="adeldev-org-link" data-adeldev-org="key">. The saved text is only a
 *   preview: adeldev_org_refresh() writes the current value at render time.
 * - Shortcode [organisation key="…" link="1" text="…"], rendered in any block,
 *   templates and template parts included.
 * - Editor sidebar (building icon of the top bar) listing every detail and its shortcode.
 *
 * @package Adeldev_Organisation
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_block_bindings_source(
			'adeldev/organisation',
			array(
				'label'              => __( 'Organisation details', 'adeldev-organisation' ),
				'get_value_callback' => 'adeldev_org_binding_value',
			)
		);
		add_shortcode( 'organisation', 'adeldev_org_shortcode_render' );
	}
);

/**
 * Value of a bound attribute: an URL for url/href, else text (multi-line values keep
 * their line breaks; core filters rich text with wp_kses_post).
 *
 * @param array    $args      Binding args: key.
 * @param WP_Block $block     Block instance.
 * @param string   $attribute Bound attribute.
 */
function adeldev_org_binding_value( array $args, $block, string $attribute ): string {
	$value = adeldev_org_value( (string) ( $args['key'] ?? '' ) );
	if ( '' === $value ) {
		return '';
	}
	if ( in_array( $attribute, array( 'url', 'href' ), true ) ) {
		return esc_url( $value );
	}
	return nl2br( esc_html( $value ), false );
}

// Social icons can be bound too (their URL), so the footer follows the profile links.
add_filter(
	'block_bindings_supported_attributes_core/social-link',
	static fn( $attributes ) => array_values( array_unique( array_merge( (array) $attributes, array( 'url' ) ) ) )
);

/**
 * [organisation key="contact.email" link="1" text="…"]
 *
 * @param array|string $atts Shortcode attributes.
 */
function adeldev_org_shortcode_render( $atts ): string {
	$atts  = shortcode_atts(
		array(
			'key'  => '',
			'link' => '',
			'text' => '',
		),
		$atts,
		'organisation'
	);
	$key   = (string) $atts['key'];
	$value = adeldev_org_value( $key );
	if ( '' === $value ) {
		return '';
	}
	$text = '' !== $atts['text'] ? (string) $atts['text'] : $value;
	if ( $atts['link'] && adeldev_org_link_key( $key ) ) {
		return adeldev_org_anchor( $key, $text );
	}
	return nl2br( esc_html( $text ), false );
}

/**
 * Link to a detail (tel, mailto, WhatsApp, map, social network…); web addresses open
 * in a new tab.
 */
function adeldev_org_anchor( string $key, string $text ): string {
	$href = adeldev_org_value( adeldev_org_link_key( $key ) );
	if ( '' === $href ) {
		return esc_html( $text );
	}
	$external = str_starts_with( $href, 'http' );
	return sprintf(
		'<a href="%s" class="adeldev-org-link"%s>%s</a>',
		esc_url( $href ),
		$external ? ' target="_blank" rel="noopener"' : '',
		esc_html( $text )
	);
}

/**
 * Writes the current values into inserted details and expands the shortcode, in any
 * leaf block, wherever it is rendered (posts, pages, templates and template parts).
 */
add_filter(
	'render_block',
	static function ( $content, $parsed_block ) {
		if ( ! empty( $parsed_block['innerBlocks'] ) || ! is_string( $content ) ) {
			return $content;
		}
		if ( str_contains( $content, 'data-adeldev-org=' ) ) {
			$content = adeldev_org_refresh( $content );
		}
		if ( str_contains( $content, '[organisation ' ) ) {
			$content = do_shortcode( $content );
		}
		return $content;
	},
	10,
	2
);

/**
 * Replaces inserted details by their current value.
 */
function adeldev_org_refresh( string $html ): string {
	return (string) preg_replace_callback(
		'#<(span|a)\b([^>]*)\bdata-adeldev-org="([a-z0-9_]+\.[a-z0-9_]+)"([^>]*)>(.*?)</\1>#s',
		static function ( $m ) {
			$key   = $m[3];
			$value = adeldev_org_value( $key );
			if ( '' === $value ) {
				return '';
			}
			if ( 'a' === $m[1] && adeldev_org_link_key( $key ) ) {
				// A link to a web address (social network, map) keeps the wording the editor
				// wrote; a link showing a detail (email, phone, address) shows its value.
				$text = str_starts_with( $value, 'http' ) ? wp_strip_all_tags( $m[5] ) : $value;
				return adeldev_org_anchor( $key, $text );
			}
			return '<span class="adeldev-org">' . nl2br( esc_html( $value ), false ) . '</span>';
		},
		$html
	);
}

// Blocks bound to an empty detail (e.g. no phone number yet) are not rendered.
add_filter(
	'render_block',
	static function ( $content, $parsed_block ) {
		foreach ( $parsed_block['attrs']['metadata']['bindings'] ?? array() as $binding ) {
			if ( 'adeldev/organisation' === ( $binding['source'] ?? '' ) && '' === adeldev_org_value( (string) ( $binding['args']['key'] ?? '' ) ) ) {
				return '';
			}
		}
		return $content;
	},
	10,
	2
);

/**
 * Language of the content being edited: the post's Polylang language, else the main one.
 */
function adeldev_org_editor_locale(): string {
	if ( function_exists( 'pll_get_post_language' ) ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read only.
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$locale  = $post_id ? pll_get_post_language( $post_id, 'locale' ) : '';
		if ( ! $locale && isset( $_GET['new_lang'] ) && function_exists( 'PLL' ) ) {
			$language = PLL()->model->get_language( sanitize_key( wp_unslash( $_GET['new_lang'] ) ) );
			$locale   = $language ? $language->locale : '';
		}
		// phpcs:enable
		if ( $locale ) {
			return (string) $locale;
		}
	}
	return adeldev_org_default_locale();
}

add_action(
	'enqueue_block_editor_assets',
	static function () {
		wp_enqueue_script(
			'adeldev-org-editor',
			ADELDEV_ORG_URL . 'assets/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-compose', 'wp-data', 'wp-block-editor', 'wp-rich-text', 'wp-editor', 'wp-plugins', 'wp-i18n', 'wp-notices' ),
			(string) filemtime( ADELDEV_ORG_DIR . 'assets/editor.js' ),
			true
		);
		wp_set_script_translations( 'adeldev-org-editor', 'adeldev-organisation', ADELDEV_ORG_DIR . 'languages' );

		$groups = array();
		foreach ( adeldev_org_groups() as $group => $definition ) {
			$groups[ $group ] = $definition['title'];
		}
		$groups['link'] = __( 'Links', 'adeldev-organisation' );
		$groups['site'] = __( 'Site', 'adeldev-organisation' );

		$catalog = adeldev_org_catalog( adeldev_org_editor_locale() );
		foreach ( $catalog as $key => &$entry ) {
			$entry['shortcode'] = adeldev_org_shortcode( $key );
		}
		unset( $entry );

		wp_add_inline_script(
			'adeldev-org-editor',
			'window.adeldevOrganisation = ' . wp_json_encode(
				array(
					'groups'      => $groups,
					'catalog'     => $catalog,
					'settingsUrl' => current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=' . ADELDEV_ORG_PAGE ) : '',
				)
			) . ';',
			'before'
		);
	}
);
