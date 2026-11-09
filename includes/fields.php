<?php
/**
 * The organisation's details: field definitions, languages, stored and computed values.
 *
 * Everything that shows a detail (settings page, block bindings, inline insertion,
 * shortcode, editor panel, WP-CLI) reads it through adeldev_org_value(), so a value is
 * entered once and displayed everywhere.
 *
 * Storage: the option `adeldev_organisation` holds one array per language:
 *   array(
 *     'default' => array( 'contact.email' => '…', … ),   // the site's main language
 *     'ar'      => array( 'address.city' => '…', … ),    // translations, by locale
 *   )
 * Every field can have a value per language; an empty translation falls back to the
 * main language. The name and the tagline are WordPress's own site title and tagline
 * (`blogname`, `blogdescription`) in the main language, so the two never diverge.
 *
 * @package Adeldev_Organisation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Editable fields, grouped as on the settings page.
 *
 * Each field: label, type (text|textarea|email|tel|url|number), optional help and
 * default. Every field can be given a value per language. Sites add their own groups or fields with the
 * `adeldev_org_groups` filter (keys are "group.field", lowercase, digits and _).
 *
 * @return array<string, array{title: string, fields: array<string, array>}>
 */
function adeldev_org_groups(): array {
	$groups = array(
		'identity' => array(
			'title'  => __( 'Identity', 'adeldev-organisation' ),
			'fields' => array(
				'name'        => array(
					'label' => __( 'Name', 'adeldev-organisation' ),
					'help'  => __( 'Also the site title (Settings › General).', 'adeldev-organisation' ),
				),
				'tagline'     => array(
					'label' => __( 'Tagline', 'adeldev-organisation' ),
					'help'  => __( 'Also the site tagline (Settings › General).', 'adeldev-organisation' ),
				),
				'legal_name'  => array(
					'label' => __( 'Legal name', 'adeldev-organisation' ),
					'help'  => __( 'As registered, if different from the name.', 'adeldev-organisation' ),
				),
				'acronym'     => array( 'label' => __( 'Acronym', 'adeldev-organisation' ) ),
				'description' => array(
					'label' => __( 'Short description', 'adeldev-organisation' ),
					'type'  => 'textarea',
					'help'  => __( 'One or two sentences presenting the organisation.', 'adeldev-organisation' ),
				),
				'founded'     => array(
					'label' => __( 'Year founded', 'adeldev-organisation' ),
					'type'  => 'number',
					'help'  => __( 'Gives the computed value "years of experience".', 'adeldev-organisation' ),
				),
			),
		),
		'contact'  => array(
			'title'  => __( 'Contact', 'adeldev-organisation' ),
			'fields' => array(
				'phone'            => array(
					'label' => __( 'Phone', 'adeldev-organisation' ),
					'type'  => 'tel',
					'help'  => __( 'As it should be displayed, e.g. +216 71 000 000.', 'adeldev-organisation' ),
				),
				'phone_2'          => array(
					'label' => __( 'Second phone', 'adeldev-organisation' ),
					'type'  => 'tel',
				),
				'whatsapp'         => array(
					'label' => __( 'WhatsApp number', 'adeldev-organisation' ),
					'type'  => 'tel',
					'help'  => __( 'International format, e.g. +216 20 000 000.', 'adeldev-organisation' ),
				),
				'whatsapp_message' => array(
					'label' => __( 'Default WhatsApp message', 'adeldev-organisation' ),
					'type'  => 'textarea',
					'help'  => __( 'Prefilled in WhatsApp when a visitor clicks a WhatsApp link.', 'adeldev-organisation' ),
				),
				'email'            => array(
					'label' => __( 'Email', 'adeldev-organisation' ),
					'type'  => 'email',
				),
			),
		),
		'address'  => array(
			'title'  => __( 'Address', 'adeldev-organisation' ),
			'fields' => array(
				'street'      => array(
					'label' => __( 'Street', 'adeldev-organisation' ),
				),
				'postal_code' => array( 'label' => __( 'Postal code', 'adeldev-organisation' ) ),
				'city'        => array(
					'label' => __( 'City', 'adeldev-organisation' ),
				),
				'region'      => array(
					'label' => __( 'Region', 'adeldev-organisation' ),
					'help'  => __( 'State, governorate, department…', 'adeldev-organisation' ),
				),
				'country'     => array(
					'label' => __( 'Country', 'adeldev-organisation' ),
				),
				'maps_url'    => array(
					'label' => __( 'Map link', 'adeldev-organisation' ),
					'type'  => 'url',
					'help'  => __( 'Google Maps link. If empty, a map search for the address is used.', 'adeldev-organisation' ),
				),
			),
		),
		'hours'    => array(
			'title'  => __( 'Opening hours', 'adeldev-organisation' ),
			'fields' => array(
				'full'  => array(
					'label' => __( 'Opening hours', 'adeldev-organisation' ),
					'type'  => 'textarea',
					'help'  => __( 'One line per day or group of days.', 'adeldev-organisation' ),
				),
				'short' => array(
					'label' => __( 'Opening hours (short)', 'adeldev-organisation' ),
					'help'  => __( 'One line, for a header or a button, e.g. Mon–Sat 9am–7pm.', 'adeldev-organisation' ),
				),
			),
		),
		'social'   => array(
			'title'  => __( 'Social networks', 'adeldev-organisation' ),
			'fields' => array(
				'facebook'  => array( 'label' => 'Facebook', 'type' => 'url' ),
				'instagram' => array( 'label' => 'Instagram', 'type' => 'url' ),
				'linkedin'  => array( 'label' => 'LinkedIn', 'type' => 'url' ),
				'youtube'   => array( 'label' => 'YouTube', 'type' => 'url' ),
				'tiktok'    => array( 'label' => 'TikTok', 'type' => 'url' ),
				'x'         => array( 'label' => 'X', 'type' => 'url' ),
			),
		),
		'reviews'  => array(
			'title'  => __( 'Google reviews', 'adeldev-organisation' ),
			'fields' => array(
				'google_rating' => array(
					'label' => __( 'Average rating', 'adeldev-organisation' ),
					'help'  => __( 'Out of 5, as displayed, e.g. 4.8.', 'adeldev-organisation' ),
				),
				'google_count'  => array(
					'label' => __( 'Number of reviews', 'adeldev-organisation' ),
					'type'  => 'number',
				),
				'google_url'    => array(
					'label' => __( 'Google Business Profile link', 'adeldev-organisation' ),
					'type'  => 'url',
				),
			),
		),
		'legal'    => array(
			'title'  => __( 'Legal notice', 'adeldev-organisation' ),
			'fields' => array(
				'legal_form'   => array(
					'label' => __( 'Legal form', 'adeldev-organisation' ),
					'help'  => __( 'E.g. SARL, SAS, LLC, association.', 'adeldev-organisation' ),
				),
				'capital'      => array(
					'label' => __( 'Share capital', 'adeldev-organisation' ),
					'help'  => __( 'With its currency, e.g. 10 000 €.', 'adeldev-organisation' ),
				),
				'registration' => array(
					'label' => __( 'Registration number', 'adeldev-organisation' ),
					'help'  => __( 'The official identifier, as it should be displayed: SIRET or RCS (France), tax ID or RNE (Tunisia), ICE or RC (Morocco), trade licence (UAE), association number… The label in front of it is written on the site.', 'adeldev-organisation' ),
				),
				'publisher'    => array(
					'label' => __( 'Publication director', 'adeldev-organisation' ),
					'help'  => __( 'Person responsible for the site content (legal notice).', 'adeldev-organisation' ),
				),
			),
		),
		'hosting'  => array(
			'title'  => __( 'Hosting provider', 'adeldev-organisation' ),
			'fields' => array(
				'name'    => array( 'label' => __( 'Hosting provider', 'adeldev-organisation' ) ),
				'address' => array( 'label' => __( 'Address', 'adeldev-organisation' ) ),
				'phone'   => array(
					'label' => __( 'Phone', 'adeldev-organisation' ),
					'type'  => 'tel',
				),
				'website' => array(
					'label' => __( 'Website', 'adeldev-organisation' ),
					'type'  => 'url',
				),
			),
		),
	);

	/**
	 * Groups and fields of the organisation's details.
	 *
	 * @param array $groups Groups keyed by slug, each with a title and its fields.
	 */
	return (array) apply_filters( 'adeldev_org_groups', $groups );
}

/**
 * Values built from the fields; they have no input of their own.
 *
 * @return array<string, string> Key => label.
 */
function adeldev_org_computed(): array {
	return array(
		'address.full'        => __( 'Full address', 'adeldev-organisation' ),
		'address.short'       => __( 'City and country', 'adeldev-organisation' ),
		'identity.years'      => __( 'Years of experience', 'adeldev-organisation' ),
		'reviews.google_text' => __( 'Google rating, as a sentence', 'adeldev-organisation' ),
		'link.tel'            => __( 'Phone link (tel:)', 'adeldev-organisation' ),
		'link.tel_2'          => __( 'Second phone link (tel:)', 'adeldev-organisation' ),
		'link.whatsapp'       => __( 'WhatsApp link', 'adeldev-organisation' ),
		'link.mailto'         => __( 'Email link (mailto:)', 'adeldev-organisation' ),
		'link.maps'           => __( 'Map link', 'adeldev-organisation' ),
		'site.copyright'      => __( 'Copyright line', 'adeldev-organisation' ),
		'site.year'           => __( 'Current year', 'adeldev-organisation' ),
	);
}

/**
 * Definition of a field, or null.
 */
function adeldev_org_field( string $key ): ?array {
	list( $group, $field ) = array_pad( explode( '.', $key, 2 ), 2, '' );
	$definition            = adeldev_org_groups()[ $group ]['fields'][ $field ] ?? null;
	if ( ! is_array( $definition ) ) {
		return null;
	}
	return $definition + array( 'type' => 'text' );
}

/* Languages ---------------------------------------------------------------- */

/**
 * The site's main language: Polylang's default language when it is active, else
 * the site language (Settings › General).
 */
function adeldev_org_default_locale(): string {
	if ( function_exists( 'pll_default_language' ) ) {
		$locale = pll_default_language( 'locale' );
		if ( $locale ) {
			return (string) $locale;
		}
	}
	$locale = (string) get_option( 'WPLANG' );
	return '' !== $locale ? $locale : 'en_US';
}

/**
 * Every language the details can be entered in, main language first: the languages
 * installed in WordPress, plus Polylang's when it is active.
 *
 * @return string[] Locales.
 */
function adeldev_org_locales(): array {
	$locales = get_available_languages();
	if ( function_exists( 'pll_languages_list' ) ) {
		$locales = array_merge( $locales, (array) pll_languages_list( array( 'fields' => 'locale' ) ) );
	}
	$default = adeldev_org_default_locale();
	$locales = array_values( array_unique( array_merge( array( $default ), array_filter( $locales ) ) ) );

	/**
	 * Languages offered on the settings page (main language first).
	 *
	 * @param string[] $locales Locales.
	 */
	return (array) apply_filters( 'adeldev_org_locales', $locales );
}

/**
 * Name of a language, in that language when possible.
 */
function adeldev_org_locale_name( string $locale ): string {
	if ( function_exists( 'pll_languages_list' ) ) {
		$names   = (array) pll_languages_list( array( 'fields' => 'name' ) );
		$locales = (array) pll_languages_list( array( 'fields' => 'locale' ) );
		$index   = array_search( $locale, $locales, true );
		if ( false !== $index && ! empty( $names[ $index ] ) ) {
			return (string) $names[ $index ];
		}
	}
	if ( class_exists( 'Locale' ) ) {
		$name = Locale::getDisplayName( $locale, $locale );
		if ( $name && $name !== $locale ) {
			return mb_convert_case( mb_substr( $name, 0, 1 ), MB_CASE_UPPER ) . mb_substr( $name, 1 );
		}
	}
	return $locale;
}

/* Values ------------------------------------------------------------------- */

/**
 * Stored values, normalised: array( 'default' => array, '<locale>' => array… ).
 *
 * @return array<string, array<string, string>>
 */
function adeldev_org_stored(): array {
	$stored = get_option( ADELDEV_ORG_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();
	foreach ( $stored as $locale => $values ) {
		if ( ! is_array( $values ) ) {
			unset( $stored[ $locale ] );
		}
	}
	$stored['default'] = $stored['default'] ?? array();
	return $stored;
}

/**
 * Value of a detail, unescaped. Empty string when unknown or not filled in.
 *
 * @param string      $key    "group.field", or a computed key (adeldev_org_computed()).
 * @param string|null $locale Language; defaults to the language of the page being shown.
 */
function adeldev_org_value( string $key, ?string $locale = null ): string {
	$locale = $locale ?? get_locale();

	if ( isset( adeldev_org_computed()[ $key ] ) ) {
		return adeldev_org_computed_value( $key, $locale );
	}

	$field = adeldev_org_field( $key );
	if ( ! $field ) {
		return '';
	}

	$stored = adeldev_org_stored();
	if ( adeldev_org_default_locale() !== $locale ) {
		$translation = adeldev_org_translation( $stored, $key, $locale );
		if ( '' !== $translation ) {
			return $translation;
		}
	}

	switch ( $key ) {
		case 'identity.name':
			return html_entity_decode( (string) get_option( 'blogname' ), ENT_QUOTES, 'UTF-8' );
		case 'identity.tagline':
			return html_entity_decode( (string) get_option( 'blogdescription' ), ENT_QUOTES, 'UTF-8' );
	}

	$value = $stored['default'][ $key ] ?? null;
	return null === $value ? (string) ( $field['default'] ?? '' ) : (string) $value;
}

/**
 * Translation of a field: exact locale (ar_TN), else the same language (ar).
 */
function adeldev_org_translation( array $stored, string $key, string $locale ): string {
	if ( '' !== (string) ( $stored[ $locale ][ $key ] ?? '' ) ) {
		return (string) $stored[ $locale ][ $key ];
	}
	$language = strtok( $locale, '_' );
	foreach ( $stored as $candidate => $values ) {
		if ( 'default' !== $candidate && strtok( (string) $candidate, '_' ) === $language && '' !== (string) ( $values[ $key ] ?? '' ) ) {
			return (string) $values[ $key ];
		}
	}
	return '';
}

/**
 * Computed values.
 */
function adeldev_org_computed_value( string $key, string $locale ): string {
	$get = static fn( string $field ) => adeldev_org_value( $field, $locale );
	/* translators: separator between the parts of an address. */
	$comma = _x( ', ', 'address separator', 'adeldev-organisation' );

	switch ( $key ) {
		case 'address.full':
			$city   = trim( $get( 'address.postal_code' ) . ' ' . $get( 'address.city' ) );
			$region = $get( 'address.region' );
			$parts  = array( $get( 'address.street' ), $city, $region === $get( 'address.city' ) ? '' : $region, $get( 'address.country' ) );
			return implode( $comma, array_unique( array_filter( $parts ) ) );

		case 'address.short':
			return implode( $comma, array_unique( array_filter( array( $get( 'address.city' ), $get( 'address.country' ) ) ) ) );

		case 'identity.years':
			$founded = (int) $get( 'identity.founded' );
			return $founded > 0 ? (string) max( 0, (int) wp_date( 'Y' ) - $founded ) : '';

		case 'reviews.google_text':
			$rating = $get( 'reviews.google_rating' );
			if ( '' === $rating ) {
				return '';
			}
			$count = (int) $get( 'reviews.google_count' );
			if ( $count < 1 ) {
				/* translators: %s: average rating, e.g. 4.8. */
				return sprintf( __( '%s/5 on Google', 'adeldev-organisation' ), $rating );
			}
			/* translators: 1: average rating, e.g. 4.8, 2: number of reviews. */
			return sprintf( _n( '%1$s/5 on Google (%2$s review)', '%1$s/5 on Google (%2$s reviews)', $count, 'adeldev-organisation' ), $rating, number_format_i18n( $count ) );

		case 'link.tel':
		case 'link.tel_2':
			$phone = preg_replace( '/[^0-9+]/', '', $get( 'link.tel' === $key ? 'contact.phone' : 'contact.phone_2' ) );
			return $phone ? 'tel:' . $phone : '';

		case 'link.whatsapp':
			$number = preg_replace( '/[^0-9]/', '', $get( 'contact.whatsapp' ) );
			if ( ! $number ) {
				return '';
			}
			$message = $get( 'contact.whatsapp_message' );
			return 'https://wa.me/' . $number . ( '' !== $message ? '?text=' . rawurlencode( $message ) : '' );

		case 'link.mailto':
			$email = $get( 'contact.email' );
			return $email ? 'mailto:' . $email : '';

		case 'link.maps':
			$url = $get( 'address.maps_url' );
			if ( $url ) {
				return $url;
			}
			$address = $get( 'address.full' );
			return $address ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address ) : '';

		case 'site.copyright':
			return '© ' . wp_date( 'Y' ) . ' ' . $get( 'identity.name' );

		case 'site.year':
			return wp_date( 'Y' );
	}
	return '';
}

/**
 * Key of the link a detail naturally opens (phone → link.tel…), or '' if none.
 * A web address (social network, map link…) links to itself.
 */
function adeldev_org_link_key( string $key ): string {
	$links = array(
		'contact.phone'    => 'link.tel',
		'contact.phone_2'  => 'link.tel_2',
		'contact.whatsapp' => 'link.whatsapp',
		'contact.email'    => 'link.mailto',
		'address.full'     => 'link.maps',
		'address.short'    => 'link.maps',
		'address.street'   => 'link.maps',
	);
	if ( isset( $links[ $key ] ) ) {
		return $links[ $key ];
	}
	$field = adeldev_org_field( $key );
	return $field && 'url' === $field['type'] ? $key : '';
}

/**
 * Every key with its group, label, current value and link, for the editor and help.
 *
 * @param string|null $locale Language of the values.
 * @return array<string, array{group: string, label: string, value: string, link: string}>
 */
function adeldev_org_catalog( ?string $locale = null ): array {
	$locale  = $locale ?? adeldev_org_default_locale();
	$catalog = array();
	foreach ( adeldev_org_groups() as $group => $definition ) {
		foreach ( $definition['fields'] as $field => $args ) {
			$catalog[ "$group.$field" ] = array(
				'group' => $group,
				'label' => $args['label'],
			);
		}
	}
	foreach ( adeldev_org_computed() as $key => $label ) {
		$catalog[ $key ] = array(
			'group' => strtok( $key, '.' ),
			'label' => $label,
		);
	}
	foreach ( $catalog as $key => &$entry ) {
		$entry['value'] = adeldev_org_value( $key, $locale );
		$entry['link']  = adeldev_org_link_key( $key );
	}
	return $catalog;
}

/**
 * Sanitises a value for its field type.
 */
function adeldev_org_sanitize_value( string $key, $value ): string {
	$field = adeldev_org_field( $key );
	$value = is_scalar( $value ) ? (string) $value : '';
	switch ( $field['type'] ?? 'text' ) {
		case 'email':
			return sanitize_email( $value );
		case 'url':
			return esc_url_raw( trim( $value ) );
		case 'number':
			$value = trim( str_replace( ',', '.', $value ) );
			return is_numeric( $value ) ? (string) ( 0 + $value ) : '';
		case 'textarea':
			return sanitize_textarea_field( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Saves values of one language (WP-CLI, site setup scripts).
 *
 * @param array<string, string> $values Key => raw value.
 * @param string                $locale 'default' or a locale.
 * @param bool                  $merge  Keep the other stored values of that language.
 */
function adeldev_org_save( array $values, string $locale = 'default', bool $merge = true ): void {
	update_option( ADELDEV_ORG_OPTION, adeldev_org_merge( adeldev_org_stored(), $values, $locale, $merge ) );
}

/**
 * Stored values with the values of one language replaced or merged, sanitised.
 * In the main language, the name and tagline go to the site title and tagline.
 *
 * @param array                 $stored Current stored values (adeldev_org_stored()).
 * @param array<string, string> $values Key => raw value.
 * @param string                $locale 'default' or a locale.
 * @param bool                  $merge  Keep the other stored values of that language.
 */
function adeldev_org_merge( array $stored, array $values, string $locale, bool $merge = true ): array {
	$clean = $merge ? ( $stored[ $locale ] ?? array() ) : array();

	foreach ( $values as $key => $value ) {
		$key   = (string) $key;
		$field = adeldev_org_field( $key );
		if ( ! $field ) {
			continue;
		}
		$value = adeldev_org_sanitize_value( $key, $value );

		if ( 'default' === $locale && in_array( $key, array( 'identity.name', 'identity.tagline' ), true ) ) {
			update_option( 'identity.name' === $key ? 'blogname' : 'blogdescription', $value );
			continue;
		}
		if ( 'default' !== $locale && '' === $value ) {
			unset( $clean[ $key ] );
			continue;
		}
		$clean[ $key ] = $value;
	}

	$stored[ $locale ] = $clean;
	if ( 'default' !== $locale && ! $clean ) {
		unset( $stored[ $locale ] );
	}
	return $stored;
}

/**
 * Shortcode that shows a detail (as a link when it opens one).
 */
function adeldev_org_shortcode( string $key ): string {
	return adeldev_org_link_key( $key ) ? sprintf( '[organisation key="%s" link="1"]', $key ) : sprintf( '[organisation key="%s"]', $key );
}
