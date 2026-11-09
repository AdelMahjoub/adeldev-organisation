<?php
/**
 * Settings page "Organisation": one form per language, with every field. In a language
 * other than the main one, an empty field falls back to the main language.
 *
 * @package Adeldev_Organisation
 */

defined( 'ABSPATH' ) || exit;

const ADELDEV_ORG_PAGE = 'adeldev-organisation';

add_action( 'admin_init', 'adeldev_org_register_setting' );
add_action( 'rest_api_init', 'adeldev_org_register_setting' );

/**
 * The option, with its REST schema (one object of strings per language).
 */
function adeldev_org_register_setting(): void {
	register_setting(
		'adeldev_organisation',
		ADELDEV_ORG_OPTION,
		array(
			'type'              => 'object',
			'default'           => array(),
			'sanitize_callback' => 'adeldev_org_sanitize',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => array(
						'type'                 => 'object',
						'additionalProperties' => array( 'type' => 'string' ),
					),
				),
			),
		)
	);
}

/**
 * Sanitises the option. The settings form sends one language at a time
 * (`_locale` + `values`), merged into what is stored; anything else (REST, code)
 * is the whole structure.
 *
 * @param mixed $input Submitted value.
 */
function adeldev_org_sanitize( $input ): array {
	$input = is_array( $input ) ? wp_unslash( $input ) : array();

	if ( isset( $input['_locale'] ) ) {
		$locale = (string) $input['_locale'];
		if ( 'default' !== $locale && ! in_array( $locale, adeldev_org_locales(), true ) ) {
			return adeldev_org_stored();
		}
		return adeldev_org_merge( adeldev_org_stored(), (array) ( $input['values'] ?? array() ), $locale );
	}

	$clean = array();
	foreach ( $input as $locale => $values ) {
		if ( ! is_array( $values ) || ! preg_match( '/^(default|[a-z]{2,3}(_[A-Za-z0-9_]+)?)$/', (string) $locale ) ) {
			continue;
		}
		foreach ( $values as $key => $value ) {
			$field = adeldev_org_field( (string) $key );
			if ( $field ) {
				$clean[ $locale ][ $key ] = adeldev_org_sanitize_value( (string) $key, $value );
			}
		}
	}
	$clean['default'] = $clean['default'] ?? array();
	return $clean;
}

add_action(
	'admin_menu',
	static function () {
		$hook = add_menu_page(
			__( 'Organisation details', 'adeldev-organisation' ),
			__( 'Organisation', 'adeldev-organisation' ),
			'manage_options',
			ADELDEV_ORG_PAGE,
			'adeldev_org_render_page',
			'dashicons-building',
			3
		);
		add_action( "load-$hook", 'adeldev_org_help_tabs' );
		add_action(
			"admin_print_scripts-$hook",
			static function () {
				wp_enqueue_script( 'adeldev-org-admin', ADELDEV_ORG_URL . 'assets/admin.js', array( 'wp-a11y', 'wp-i18n' ), (string) filemtime( ADELDEV_ORG_DIR . 'assets/admin.js' ), true );
				wp_set_script_translations( 'adeldev-org-admin', 'adeldev-organisation', ADELDEV_ORG_DIR . 'languages' );
			}
		);
	}
);

/**
 * Language edited on the settings page: 'default' or a locale from the tabs.
 */
function adeldev_org_current_tab(): string {
	$locale = isset( $_GET['locale'] ) ? sanitize_text_field( wp_unslash( $_GET['locale'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only.
	$others = array_slice( adeldev_org_locales(), 1 );
	return in_array( $locale, $others, true ) ? $locale : 'default';
}

function adeldev_org_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab     = adeldev_org_current_tab();
	$locales = adeldev_org_locales();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Organisation details', 'adeldev-organisation' ); ?></h1>
		<p><?php esc_html_e( 'These details are entered once and reused everywhere on the site: header, footer, contact page, buttons, legal notice. Change them here and every page follows.', 'adeldev-organisation' ); ?></p>

		<?php if ( count( $locales ) > 1 ) : ?>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Languages', 'adeldev-organisation' ); ?>">
				<?php
				foreach ( $locales as $index => $locale ) {
					$slug = 0 === $index ? 'default' : $locale;
					$url  = add_query_arg( array( 'page' => ADELDEV_ORG_PAGE, 'locale' => 0 === $index ? false : $locale ), admin_url( 'admin.php' ) );
					printf(
						'<a href="%s" class="nav-tab%s"%s>%s</a>',
						esc_url( $url ),
						$tab === $slug ? ' nav-tab-active' : '',
						$tab === $slug ? ' aria-current="page"' : '',
						esc_html( adeldev_org_locale_name( $locale ) . ( 0 === $index ? ' — ' . __( 'main language', 'adeldev-organisation' ) : '' ) )
					);
				}
				?>
			</nav>
			<?php if ( 'default' !== $tab ) : ?>
				<p><?php esc_html_e( 'Every detail can have its own value in this language. An empty field shows the value of the main language (in grey).', 'adeldev-organisation' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'adeldev_organisation' ); ?>
			<input type="hidden" name="<?php echo esc_attr( ADELDEV_ORG_OPTION ); ?>[_locale]" value="<?php echo esc_attr( $tab ); ?>">
			<?php
			foreach ( adeldev_org_groups() as $group => $definition ) {
				echo '<h2>' . esc_html( $definition['title'] ) . '</h2><table class="form-table" role="presentation">';
				foreach ( array_keys( $definition['fields'] ) as $field ) {
					adeldev_org_render_field( "$group.$field", $tab );
				}
				echo '</table>';
			}
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * One row of the form.
 *
 * @param string $key Field key.
 * @param string $tab 'default' or the locale being edited.
 */
function adeldev_org_render_field( string $key, string $tab ): void {
	$args   = adeldev_org_field( $key );
	$id     = 'adeldev-org-' . str_replace( '.', '-', $key );
	$name   = ADELDEV_ORG_OPTION . '[values][' . $key . ']';
	$type   = $args['type'];
	$main   = adeldev_org_value( $key, adeldev_org_default_locale() );
	$value  = 'default' === $tab ? $main : (string) ( adeldev_org_stored()[ $tab ][ $key ] ?? '' );
	$hint   = 'default' === $tab ? '' : $main;
	$ltr    = in_array( $type, array( 'email', 'url', 'tel', 'number' ), true );
	$dir    = $ltr ? 'ltr' : 'auto';
	$lang   = 'default' === $tab ? '' : ' lang="' . esc_attr( str_replace( '_', '-', $tab ) ) . '"';

	echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $args['label'] ) . '</label></th><td>';
	if ( 'textarea' === $type ) {
		printf(
			'<textarea id="%1$s" name="%2$s" rows="3" class="large-text" dir="%3$s" placeholder="%4$s"%6$s>%5$s</textarea>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $dir ),
			esc_attr( $hint ),
			esc_textarea( $value ),
			$lang // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	} else {
		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" dir="%5$s" placeholder="%6$s"%7$s%8$s>',
			esc_attr( 'number' === $type ? 'text' : $type ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			esc_attr( $dir ),
			esc_attr( $hint ),
			'number' === $type ? ' inputmode="decimal"' : '',
			$lang // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	}
	echo '<p class="description">';
	if ( ! empty( $args['help'] ) ) {
		echo esc_html( $args['help'] ) . ' ';
	}
	echo esc_html__( 'Shortcode:', 'adeldev-organisation' ) . ' <code class="adeldev-org-copy" dir="ltr">' . esc_html( adeldev_org_shortcode( $key ) ) . '</code>';
	echo '</p></td></tr>';
}

/**
 * Help tabs: how to reuse the details, computed values, shortcode options.
 */
function adeldev_org_help_tabs(): void {
	$screen = get_current_screen();

	$screen->add_help_tab(
		array(
			'id'      => 'adeldev-org-how',
			'title'   => __( 'Using the details', 'adeldev-organisation' ),
			'content' =>
				'<p>' . esc_html__( 'There are three ways to show a detail in a page, a post or a template. In every case the site shows the value entered here, in the language of the page, so changing it here updates every page.', 'adeldev-organisation' ) . '</p><ol>'
				. '<li><strong>' . esc_html__( 'Building button of the toolbar', 'adeldev-organisation' ) . '</strong> — ' . esc_html__( 'while writing a paragraph, heading, list or button, click the building icon in the block toolbar and choose a detail. It is inserted in the sentence, as text or as a link.', 'adeldev-organisation' ) . '</li>'
				. '<li><strong>' . esc_html__( 'Block attributes', 'adeldev-organisation' ) . '</strong> — ' . esc_html__( 'for a whole block (a "Call us" button, a social icon), select the block, open "Attributes" in the settings sidebar, choose "Organisation details" and the detail.', 'adeldev-organisation' ) . '</li>'
				. '<li><strong>' . esc_html__( 'Shortcode', 'adeldev-organisation' ) . '</strong> — ' . esc_html__( 'type or paste the code shown under each field. Click a code to copy it. The building icon at the top of the editor lists them all.', 'adeldev-organisation' ) . '</li></ol>'
				. '<p>' . esc_html__( 'A block bound to a detail that is not filled in is not shown at all.', 'adeldev-organisation' ) . '</p>',
		)
	);

	$rows = '';
	foreach ( adeldev_org_computed() as $key => $label ) {
		$rows .= '<tr><td>' . esc_html( $label ) . '</td><td><code class="adeldev-org-copy" dir="ltr">' . esc_html( adeldev_org_shortcode( $key ) ) . '</code></td><td dir="auto">' . esc_html( adeldev_org_value( $key, adeldev_org_default_locale() ) ) . '</td></tr>';
	}
	$screen->add_help_tab(
		array(
			'id'      => 'adeldev-org-computed',
			'title'   => __( 'Computed values', 'adeldev-organisation' ),
			'content' => '<p>' . esc_html__( 'These values have no field: they are built from the details.', 'adeldev-organisation' ) . '</p><table class="widefat striped"><tbody>' . $rows . '</tbody></table>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'adeldev-org-shortcode',
			'title'   => __( 'Shortcode options', 'adeldev-organisation' ),
			'content' =>
				'<p><code dir="ltr">[organisation key="contact.email"]</code> — ' . esc_html__( 'the value as text.', 'adeldev-organisation' ) . '</p>'
				. '<p><code dir="ltr">[organisation key="contact.email" link="1"]</code> — ' . esc_html__( 'the value as a link (email, phone, WhatsApp, map, social network).', 'adeldev-organisation' ) . '</p>'
				. '<p><code dir="ltr">[organisation key="social.facebook" link="1" text="…"]</code> — ' . esc_html__( 'a link with your own text.', 'adeldev-organisation' ) . '</p>',
		)
	);

	$screen->set_help_sidebar( '<p><a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Site title and tagline', 'adeldev-organisation' ) . '</a></p>' );
}
