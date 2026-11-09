/**
 * Organisation details in the block editor (see includes/display.php).
 * Plain script, no build step: uses the wp.* globals. Data comes from PHP
 * (window.adeldevOrganisation), so nothing is requested.
 *
 * 1. Bindings source "adeldev/organisation" (block "Attributes" panel).
 * 2. Building button of the block toolbar: inserts a detail in the text, as text or link.
 * 3. Building icon of the top bar: sidebar listing every detail, its value and shortcode.
 */
( function ( wp, data ) {
	if ( ! wp || ! data ) {
		return;
	}

	const { createElement: el, Fragment, useState } = wp.element;
	const { __, sprintf } = wp.i18n;
	const entries = Object.entries( data.catalog ).map( ( [ key, entry ] ) => ( { key, ...entry } ) );
	const ICON = 'building';
	const TITLE = __( 'Organisation details', 'adeldev-organisation' );

	/* 1. Bindings source ---------------------------------------------------- */

	wp.blocks.registerBlockBindingsSource( {
		name: 'adeldev/organisation',
		label: TITLE,
		getValues( { bindings } ) {
			const values = {};
			for ( const [ attribute, binding ] of Object.entries( bindings ) ) {
				const key = binding.args?.key;
				values[ attribute ] = data.catalog[ key ]?.value || data.catalog[ key ]?.label || key;
			}
			return values;
		},
		canUserEditValue: () => false,
		getFieldsList: () =>
			entries.map( ( entry ) => ( { label: entry.label, type: 'string', args: { key: entry.key } } ) ),
	} );

	/* 2. Building button of the block toolbar ------------------------------- */

	const { registerFormatType, insert, create, applyFormat } = wp.richText;
	const { BlockFormatControls } = wp.blockEditor;
	const { ToolbarGroup, ToolbarDropdownMenu } = wp.components;

	// Links are reached through the detail they open (email → mailto), not listed on their own.
	const insertable = entries.filter( ( entry ) => entry.value && ! entry.key.startsWith( 'link.' ) );

	const piece = ( entry, asLink ) => {
		const text = entry.value;
		const format = asLink
			? {
					type: 'adeldev/org-link',
					attributes: { field: entry.key, href: data.catalog[ entry.link ]?.value || '#' },
			  }
			: { type: 'adeldev/org', attributes: { field: entry.key } };
		return applyFormat( create( { text } ), format, 0, text.length );
	};

	registerFormatType( 'adeldev/org', {
		title: __( 'Organisation detail', 'adeldev-organisation' ),
		tagName: 'span',
		className: 'adeldev-org',
		attributes: { field: 'data-adeldev-org' },
		edit( { value, onChange } ) {
			const controls = [];
			insertable.forEach( ( entry ) => {
				controls.push( {
					title: entry.label + ' — ' + entry.value,
					onClick: () => onChange( insert( value, piece( entry, false ) ) ),
				} );
				if ( entry.link && entry.link !== entry.key && data.catalog[ entry.link ]?.value ) {
					controls.push( {
						/* translators: %s: name of a detail, e.g. Email. */
						title: sprintf( __( '%s (link)', 'adeldev-organisation' ), entry.label ),
						onClick: () => onChange( insert( value, piece( entry, true ) ) ),
					} );
				}
			} );

			if ( ! controls.length ) {
				return null;
			}

			return el(
				BlockFormatControls,
				null,
				el(
					ToolbarGroup,
					null,
					el( ToolbarDropdownMenu, {
						icon: ICON,
						label: __( 'Insert an organisation detail', 'adeldev-organisation' ),
						controls,
					} )
				)
			);
		},
	} );

	registerFormatType( 'adeldev/org-link', {
		title: __( 'Organisation detail (link)', 'adeldev-organisation' ),
		tagName: 'a',
		className: 'adeldev-org-link',
		attributes: { field: 'data-adeldev-org', href: 'href' },
		edit: () => null,
	} );

	/* 3. Sidebar of the top bar --------------------------------------------- */

	const { PanelBody, Button, SearchControl, ExternalLink } = wp.components;
	const { useCopyToClipboard } = wp.compose;
	const { useDispatch } = wp.data;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editor;
	const NAME = 'adeldev-organisation';

	function Item( { entry } ) {
		const { createNotice } = useDispatch( 'core/notices' );
		const ref = useCopyToClipboard( entry.shortcode, () =>
			/* translators: %s: the copied shortcode. */
			createNotice( 'info', sprintf( __( 'Copied: %s', 'adeldev-organisation' ), entry.shortcode ), {
				type: 'snackbar',
				isDismissible: true,
			} )
		);
		return el(
			'div',
			{ style: { marginBottom: '16px' } },
			el( 'div', { style: { fontWeight: 500 } }, entry.label ),
			el(
				'div',
				{ dir: 'auto', style: { color: '#757575', fontSize: '12px', overflowWrap: 'anywhere', whiteSpace: 'pre-line', margin: '2px 0 6px' } },
				entry.value || __( '(empty)', 'adeldev-organisation' )
			),
			el(
				Button,
				{
					ref,
					variant: 'secondary',
					size: 'compact',
					icon: 'admin-page',
					label: __( 'Copy the shortcode', 'adeldev-organisation' ),
					showTooltip: true,
					style: { maxWidth: '100%', height: 'auto', minHeight: '32px', whiteSpace: 'normal', justifyContent: 'flex-start', textAlign: 'start' },
				},
				el( 'code', { dir: 'ltr', style: { background: 'none', padding: 0, overflowWrap: 'anywhere' } }, entry.shortcode )
			)
		);
	}

	function Help() {
		return el(
			PanelBody,
			{ title: __( 'Help', 'adeldev-organisation' ), initialOpen: false },
			el( 'p', null, __( 'Details are entered once, in Organisation (admin menu). Every page shows the current value, in its own language.', 'adeldev-organisation' ) ),
			el(
				'ul',
				{ style: { listStyle: 'disc', paddingInlineStart: '1.2em' } },
				el( 'li', null, __( 'In a sentence: click the building icon of the block toolbar and choose a detail, as text or as a link.', 'adeldev-organisation' ) ),
				el( 'li', null, __( 'For a whole block (button, social icon): Block settings › Attributes › Organisation details.', 'adeldev-organisation' ) ),
				el( 'li', null, __( 'Or copy a shortcode below and paste it in the text. Add link="1" to make it a link, text="…" for your own wording.', 'adeldev-organisation' ) )
			),
			data.settingsUrl
				? el( ExternalLink, { href: data.settingsUrl }, __( 'Edit the organisation details', 'adeldev-organisation' ) )
				: null
		);
	}

	function Panel() {
		const [ search, setSearch ] = useState( '' );
		const term = search.trim().toLowerCase();
		const searchBox = el( SearchControl, {
			__nextHasNoMarginBottom: true,
			value: search,
			onChange: setSearch,
			label: __( 'Search a detail', 'adeldev-organisation' ),
		} );

		if ( term ) {
			const found = entries.filter( ( e ) => ( e.key + ' ' + e.label + ' ' + e.value ).toLowerCase().includes( term ) );
			return el(
				PanelBody,
				null,
				searchBox,
				found.length
					? found.map( ( entry ) => el( Item, { key: entry.key, entry } ) )
					: el( 'p', null, __( 'No detail matches.', 'adeldev-organisation' ) )
			);
		}

		return el(
			Fragment,
			null,
			el( PanelBody, null, searchBox ),
			el( Help ),
			Object.entries( data.groups ).map( ( [ group, title ], index ) => {
				const items = entries.filter( ( e ) => e.group === group );
				return items.length
					? el(
							PanelBody,
							{ key: group, title, initialOpen: 0 === index },
							items.map( ( entry ) => el( Item, { key: entry.key, entry } ) )
					  )
					: null;
			} )
		);
	}

	wp.plugins.registerPlugin( NAME, {
		icon: ICON,
		render: () =>
			el(
				Fragment,
				null,
				el( PluginSidebarMoreMenuItem, { target: NAME, icon: ICON }, TITLE ),
				el( PluginSidebar, { name: NAME, title: TITLE, icon: ICON }, el( Panel ) )
			),
	} );
} )( window.wp, window.adeldevOrganisation );
