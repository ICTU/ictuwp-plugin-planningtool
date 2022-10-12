<?php

// * DO_Planningtool - dopt.acf-definitions-functions.php
// * ----------------------------------------------------------------------------------
// * definitions and aux. functions for Advanced Custom Fields
// * ----------------------------------------------------------------------------------
// * @author  Paul van Buuren
// * @license GPL-2.0+
// * @package do-planning-tool
// * version: 1.2.1
// * @desc.   Beleidsonderwerp-taxonomie toegevoegd; paginafilter hiervoor verfijnd.
// * @link    https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/


if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

//========================================================================================================

function bidirectional_acf_update_value( $value, $post_id, $field ) {


	// vars
	$field_name  = $field['name'];
	$field_key   = $field['key'];
	$global_name = 'is_updating_' . $field_name;

	$debugstring = 'bidirectional_acf_update_value';

	$debugstring .= "value='" . implode( ", ", $value ) . "'";
	$debugstring .= ", post_id='" . $post_id . "'";
	$debugstring .= " (type=" . get_post_type( $post_id ) . ")";
	$debugstring .= ", field_key='" . $field_key . "'";
	$debugstring .= ", field_name='" . $field_name . "'";


	// bail early if this filter was triggered from the update_field() function called within the loop below
	// - this prevents an inifinte loop
	if ( ! empty( $GLOBALS[ $global_name ] ) ) {
		return $value;
	}


	// set global variable to avoid inifite loop
	// - could also remove_filter() then add_filter() again, but this is simpler
	$GLOBALS[ $global_name ] = 1;


	// loop over selected posts and add this $post_id
	if ( is_array( $value ) ) {

		// dodebug( 'bidirectional_acf_update_value: is array' );

		foreach ( $value as $post_id2 ) {

			$debugstring = "post_id2='" . $post_id2 . "'";
			$debugstring .= " (type=" . get_post_type( $post_id2 ) . ")";


			// dodebug( $debugstring );


			// load existing related posts
			$value2 = get_field( $field_name, $post_id2, false );


			// allow for selected posts to not contain a value
			if ( empty( $value2 ) ) {

				$value2 = array();

			}


			// bail early if the current $post_id is already found in selected post's $value2
			if ( in_array( $post_id, $value2 ) ) {
				continue;
			}


			// append the current $post_id to the selected post's 'related_posts' value
			$value2[] = $post_id;


			// update the selected post's value (use field's key for performance)
			update_field( $field_key, $value2, $post_id2 );

		}

	}


	// find posts which have been removed
	$old_value = get_field( $field_name, $post_id, false );

	if ( is_array( $old_value ) ) {

		foreach ( $old_value as $post_id2 ) {

			// bail early if this value has not been removed
			if ( is_array( $value ) && in_array( $post_id2, $value ) ) {
				continue;
			}


			// load existing related posts
			$value2 = get_field( $field_name, $post_id2, false );


			// bail early if no value
			if ( empty( $value2 ) ) {
				continue;
			}


			// find the position of $post_id within $value2 so we can remove it
			$pos = array_search( $post_id, $value2 );


			// remove
			unset( $value2[ $pos ] );


			// update the un-selected post's value (use field's key for performance)
			update_field( $field_key, $value2, $post_id2 );

		}

	}

	// reset global varibale to allow this filter to function as per normal
	$GLOBALS[ $global_name ] = 0;

	// return
	return $value;

}

//========================================================================================================

if ( function_exists( 'acf_add_local_field_group' ) ) {

	//======================================================================================================
	// planning tool page
	/*
	 *
	acf_add_local_field_group( array(
		'key'                   => 'group_5beaf126f2d00',
		'title'                 => 'Planning Tool Page',
		'fields'                => array(
			array(
				'key'               => 'field_5beaf13ac4396',
				'label'             => 'Actielijnen per thema',
				'name'              => 'actielijnen_per_thema',
				'type'              => 'repeater',
				'instructions'      => 'Kies een thema en voeg de bijbehorende actielijnen toe',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'collapsed'         => '',
				'min'               => 0,
				'max'               => 0,
				'layout'            => 'row',
				'button_label'      => 'Blok met actielijnen toevoegen',
				'sub_fields'        => array(
					array(
						'key'               => 'field_5beaf173c4397',
						'label'             => 'Thema-titel',
						'name'              => 'actielijnen_per_thema_titel',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => array(
							'width' => '',
							'class' => '',
							'id'    => '',
						),
						'default_value'     => '',
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
						'maxlength'         => '',
					),
					array(
						'key'               => 'field_5bec22d240e6c',
						'label'             => 'Kies een kleur',
						'name'              => 'actielijnen_per_thema_kleur',
						'type'              => 'taxonomy',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => array(
							'width' => '',
							'class' => '',
							'id'    => '',
						),
						'taxonomy'          => 'beleidsterreinen',
						'field_type'        => 'radio',
						'allow_null'        => 1,
						'add_term'          => 0,
						'save_terms'        => 0,
						'load_terms'        => 0,
						'return_format'     => 'id',
						'multiple'          => 0,
					),
					array(
						'key'               => 'field_5bec22f340e6d',
						'label'             => 'Kies de actielijnen in dit blok',
						'name'              => 'actielijnen_per_thema_actielijnen',
						'type'              => 'relationship',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => array(
							'width' => '',
							'class' => '',
							'id'    => '',
						),
						'post_type'         => array(
							0 => DOPT__ACTIELIJN_CPT,
						),
						'taxonomy'          => '',
						'filters'           => array(
							0 => 'search',
							1 => 'taxonomy',
						),
						'elements'          => '',
						'min'               => 1,
						'max'               => '',
						'return_format'     => 'object',
					),
					array(
						'key'               => 'field_5c2e0648c28f0',
						'label'             => 'HTML-ID',
						'name'              => 'actielijnen_per_thema_htmlid',
						'type'              => 'text',
						'instructions'      => 'Via dit ID kun je rechtstreeks naar dit blok verwijzen in je URL',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => array(
							'width' => '',
							'class' => '',
							'id'    => '',
						),
						'default_value'     => '',
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
						'maxlength'         => '',
					),
				),
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'page_template',
					'operator' => '==',
					'value'    => 'planningtool-template.php',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'acf_after_title',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => 1,
		'description'           => '',
	) );
	 */


	//======================================================================================================

	acf_add_local_field_group( array(
		'key'                   => 'group_5be9756fd880f',
		'title'                 => 'Gebeurtenis: datum + gekoppelde actielijnen',
		'fields'                => array(
			array(
				'key'               => 'field_5be99930b1843',
				'label'             => 'Datum',
				'name'              => 'gebeurtenis_datum',
				'type'              => 'date_picker',
				'instructions'      => 'Deze exacte datum is nodig om de gebeurtenis in de planning op de juiste plek te tonen. Daarom moet je hier sowieso iets invoeren.',
				'required'          => 1,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'display_format'    => 'j F Y',
				'return_format'     => 'Y-m-d',
				'first_day'         => 1,
			),
			array(
				'key'               => 'field_5bf6ab4d3ec49',
				'label'             => 'Geschatte datum',
				'name'              => 'gebeurtenis_geschatte_datum',
				'type'              => 'text',
				'instructions'      => 'Als je nog geen exacte datum kunt noemen, kun je hier een tekst invoeren. Deze wordt dan getoond in plaats van de exacte datum hierboven. Als je niks invoert, tonen we de exacte datum gewoon.',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			),
			array(
				'key'               => 'field_5be975841eea9',
				'label'             => 'Bijbehorende actielijnen',
				'name'              => 'related_gebeurtenissen_actielijnen',
				'type'              => 'relationship',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'post_type'         => array(
					0 => DOPT__ACTIELIJN_CPT,
				),
				'taxonomy'          => '',
				'filters'           => array(
					0 => 'search',
					1 => 'taxonomy',
				),
				'elements'          => '',
				'min'               => '',
				'max'               => '',
				'return_format'     => 'object',
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => DOPT__GEBEURTENIS_CPT,
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'acf_after_title',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => 1,
		'description'           => '',
	) );

	//======================================================================================================

	acf_add_local_field_group( array(
		'key'                   => 'group_5be97485d6c95',
		'title'                 => 'Actielijn: datums + link met gebeurtenissen',
		'fields'                => array(
			array(
				'key'               => 'field_5be9d869b3559',
				'label'             => 'Datum beschrijving (zichtbaar)',
				'name'              => 'actielijn_toon_datum',
				'type'              => 'text',
				'instructions'      => 'deze tekst is zichtbaar voor de gebruiker',
				'required'          => 1,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			),
			array(
				'key'               => 'field_5bec3c8db4fd6',
				'label'             => 'Heeft start- of einddatums?',
				'name'              => 'heeft_start-_of_einddatums',
				'type'              => 'radio',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'choices'           => array(
					'start'      => 'Alleen startdatum',
					'eind'       => 'Alleen einddatum',
					'start_eind' => 'Zowel een start- als einddatum',
				),
				'allow_null'        => 0,
				'other_choice'      => 0,
				'default_value'     => 'start',
				'layout'            => 'vertical',
				'return_format'     => 'value',
				'save_other_choice' => 0,
			),
			array(
				'key'               => 'field_5be99a18c781e',
				'label'             => 'Start kwartaal',
				'name'              => 'actielijn_kwartaal_start_kwartaal',
				'type'              => 'select',
				'instructions'      => '(niet zichtbaar)',
				'required'          => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'start',
						),
					),
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'start_eind',
						),
					),
				),
				'wrapper'           => array(
					'width' => '48',
					'class' => '',
					'id'    => '',
				),
				'choices'           => array(
					'q1' => 'Kwartaal 1',
					'q2' => 'Kwartaal 2',
					'q3' => 'Kwartaal 3',
					'q4' => 'Kwartaal 4',
				),
				'default_value'     => 'q1',
				'return_format'     => 'value',
				'multiple'          => 0,
				'allow_null'        => 0,
				'ui'                => 0,
				'ajax'              => 0,
				'placeholder'       => '',
			),
			array(
				'key'               => 'field_5bec3d241a410',
				'label'             => 'Jaar start',
				'name'              => 'actielijn_kwartaal_start_jaar',
				'type'              => 'number',
				'instructions'      => '(niet zichtbaar)
Format: xxxx',
				'required'          => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'start',
						),
					),
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'start_eind',
						),
					),
				),
				'wrapper'           => array(
					'width' => '48',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
				'min'               => '',
				'max'               => '',
				'step'              => '',
			),
			array(
				'key'               => 'field_5bec3e0b8f5f6',
				'label'             => 'Kwartaal eind',
				'name'              => 'actielijn_kwartaal_eind_kwartaal',
				'type'              => 'select',
				'instructions'      => '(niet zichtbaar)',
				'required'          => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'eind',
						),
					),
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'start_eind',
						),
					),
				),
				'wrapper'           => array(
					'width' => '48',
					'class' => '',
					'id'    => '',
				),
				'choices'           => array(
					'q1' => 'Kwartaal 1',
					'q2' => 'Kwartaal 2',
					'q3' => 'Kwartaal 3',
					'q4' => 'Kwartaal 4',
				),
				'default_value'     => 'q1',
				'allow_null'        => 0,
				'multiple'          => 0,
				'ui'                => 0,
				'return_format'     => 'value',
				'ajax'              => 0,
				'placeholder'       => '',
			),
			array(
				'key'               => 'field_5bec3e248f5f7',
				'label'             => 'Jaar eind',
				'name'              => 'actielijn_kwartaal_eind_jaar',
				'type'              => 'number',
				'instructions'      => '(niet zichtbaar)
Format: xxxx',
				'required'          => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'eind',
						),
					),
					array(
						array(
							'field'    => 'field_5bec3c8db4fd6',
							'operator' => '==',
							'value'    => 'start_eind',
						),
					),
				),
				'wrapper'           => array(
					'width' => '48',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
				'min'               => '',
				'max'               => '',
				'step'              => '',
			),
			array(
				'key'               => 'field_5be974b66ea85',
				'label'             => 'Kies bijbehorende gebeurtenissen',
				'name'              => 'related_gebeurtenissen_actielijnen',
				'type'              => 'relationship',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'post_type'         => array(
					0 => 'gebeurtenis',
				),
				'taxonomy'          => '',
				'filters'           => array(
					0 => 'search',
					1 => 'taxonomy',
				),
				'elements'          => '',
				'min'               => '',
				'max'               => '',
				'return_format'     => 'object',
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'actielijn',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'acf_after_title',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'field',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
		'show_in_rest'          => 0,
	) );

	//======================================================================================================
	// doel en resultaat voor een actielijn.
	// since 1.1.4

	acf_add_local_field_group( array(
		'key'                   => 'group_5c49a13678524',
		'title'                 => 'Actielijn : doel en resultaat',
		'fields'                => array(
			array(
				'key'               => 'field_5c49a14deda73',
				'label'             => 'Doel',
				'name'              => 'actielijn_doel',
				'type'              => 'wysiwyg',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'tabs'              => 'all',
				'toolbar'           => 'basic',
				'media_upload'      => 1,
				'delay'             => 0,
			),
			array(
				'key'               => 'field_5c49a17918caa',
				'label'             => 'Resultaat',
				'name'              => 'actielijn_resultaat',
				'type'              => 'wysiwyg',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'tabs'              => 'all',
				'toolbar'           => 'basic',
				'media_upload'      => 1,
				'delay'             => 0,
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'actielijn',
				),
			),
		),
		'menu_order'            => 1,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => 1,
		'description'           => '',
	) );

	//======================================================================================================

	acf_add_local_field_group( array(
		'key'                   => 'group_5be5e4ee3cb94',
		'title'                 => 'Planning Tool instellingen',
		'fields'                => array(

			array(
				'key'               => 'field_5bee9098c1202',
				'label'             => 'Start jaar',
				'name'              => 'planning_page_start_jaar',
				'type'              => 'number',
				'instructions'      => 'format: xxxx',
				'required'          => 1,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => 2017,
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
				'min'               => 1900,
				'max'               => 2100,
				'step'              => '',
			),
			array(
				'key'               => 'field_5bee90d2a5dad',
				'label'             => 'Eind jaar',
				'name'              => 'planning_page_end_jaar',
				'type'              => 'number',
				'instructions'      => 'format: xxxx',
				'required'          => 1,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => 2020,
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
				'min'               => 1900,
				'max'               => 2100,
				'step'              => '',
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'instellingen',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => 1,
		'description'           => '',
	) );

	//======================================================================================================

	acf_add_local_field_group( array(
		'key'                   => 'group_5c6ec39a6be33',
		'title'                 => 'Pagina bij dit beleidsonderwerp',
		'fields'                => array(
			array(
				'key'               => 'field_5c6ec3eac0509',
				'label'             => 'Welke pagina hoort bij dit beleidsonderwerp?',
				'name'              => 'dopt_ct_onderwerp_page',
				'type'              => 'post_object',
				'instructions'      => '',
				'required'          => 1,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'post_type'         => array(
					0 => 'page',
				),
				'taxonomy'          => '',
				'allow_null'        => 0,
				'multiple'          => 0,
				'return_format'     => 'object',
				'ui'                => 1,
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => DOPT_CT_ONDERWERP,
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
	) );

	//======================================================================================================


}

//========================================================================================================

/**
 * Have the ACF (advanced custom fields) plugin read the settings from this plugin's acf-json folder
 *
 * @since    1.0.0
 */
function fn_ictu_do_planningtool_add_acf_folder( $paths ) {

	// append path
	$paths[] = plugin_dir_path( __FILE__ ) . 'acf-json-planningtool';

	// return
	return $paths;

}

add_filter( 'acf/settings/load_json', 'fn_ictu_do_planningtool_add_acf_folder' );

//========================================================================================================

/**
 * Have the ACF (advanced custom fields) plugin save settings to a json file in this plugin's acf-json folder
 *
 * @since    1.0.0
 */
function fn_ictu_do_planningtool_acf_json_save_point( $path ) {

	// update path
	$path = plugin_dir_path( __FILE__ ) . 'acf-json-planningtool';

	// return
	return $path;

}

add_filter( 'acf/settings/save_json', 'fn_ictu_do_planningtool_acf_json_save_point' );

//========================================================================================================

add_filter( 'acf/load_field/name=actielijnen_per_thema_kleur', 'acf_load_color_field_choices' );

function acf_load_color_field_choices( $field ) {

	// reset choices
	$field['choices'] = array();

	$choices = array(
		'digibeter-blauw'  => _x( "Blauw (Rijkshuisstijl)", "kleuren actielijnblok", "do-planning-tool" ),
		'digibeter-oranje' => _x( "Oranje (Rijkshuisstijl)", "kleuren actielijnblok", "do-planning-tool" ),
		'digibeter-groen'  => _x( "Groen (Rijkshuisstijl)", "kleuren actielijnblok", "do-planning-tool" ),
		'digibeter-violet' => _x( "Violet (Rijkshuisstijl)", "kleuren actielijnblok", "do-planning-tool" ),
		'digibeter-paars'  => _x( "Paars (Rijkshuisstijl)", "kleuren actielijnblok", "do-planning-tool" )
	);

	// loop through array and add to field 'choices'
	if ( is_array( $choices ) ) {

		foreach ( $choices as $key => $value ) {

			$field['choices'][ $key ] = $value;

		}

	}

	// return the field
	return $field;

}

//========================================================================================================

