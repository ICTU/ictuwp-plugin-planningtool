<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

//========================================================================================================

function bidirectional_acf_update_value( $value, $post_id, $field  ) {
	
	// vars
	$field_name   = $field['name'];
	$field_key    = $field['key'];
	$global_name  = 'is_updating_' . $field_name;
	
	
	// bail early if this filter was triggered from the update_field() function called within the loop below
	// - this prevents an inifinte loop
	if( !empty($GLOBALS[ $global_name ]) ) return $value;
	
	
	// set global variable to avoid inifite loop
	// - could also remove_filter() then add_filter() again, but this is simpler
	$GLOBALS[ $global_name ] = 1;
	
	
	// loop over selected posts and add this $post_id
	if( is_array($value) ) {
	
		foreach( $value as $post_id2 ) {
			
			// load existing related posts
			$value2 = get_field($field_name, $post_id2, false);
			
			
			// allow for selected posts to not contain a value
			if( empty($value2) ) {
				
				$value2 = array();
				
			}
			
			
			// bail early if the current $post_id is already found in selected post's $value2
			if( in_array($post_id, $value2) ) continue;
			
			
			// append the current $post_id to the selected post's 'related_posts' value
			$value2[] = $post_id;
			
			
			// update the selected post's value (use field's key for performance)
			update_field($field_key, $value2, $post_id2);
			
		}
	
	}
	
	
	// find posts which have been removed
	$old_value = get_field($field_name, $post_id, false);
	
	if( is_array($old_value) ) {
		
		foreach( $old_value as $post_id2 ) {
			
			// bail early if this value has not been removed
			if( is_array($value) && in_array($post_id2, $value) ) continue;
			
			
			// load existing related posts
			$value2 = get_field($field_name, $post_id2, false);
			
			
			// bail early if no value
			if( empty($value2) ) continue;
			
			
			// find the position of $post_id within $value2 so we can remove it
			$pos = array_search($post_id, $value2);
			
			
			// remove
			unset( $value2[ $pos] );
			
			
			// update the un-selected post's value (use field's key for performance)
			update_field($field_key, $value2, $post_id2);
			
		}
		
	}

  // reset global varibale to allow this filter to function as per normal
  $GLOBALS[ $global_name ] = 0;

  // return
  return $value;
    
}

//========================================================================================================

if( function_exists('acf_add_local_field_group') ) {

  acf_add_local_field_group(array(
  	'key' => 'group_5beaf126f2d00',
  	'title' => 'Planning Tool Page',
  	'fields' => array(
  		array(
  			'key' => 'field_5beaf13ac4396',
  			'label' => 'Actielijnen per thema',
  			'name' => 'actielijnen_per_thema',
  			'type' => 'repeater',
  			'instructions' => 'Kies een thema en voeg de bijbehorende actielijnen toe',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'collapsed' => '',
  			'min' => 0,
  			'max' => 0,
  			'layout' => 'row',
  			'button_label' => 'Blok met actielijnen toevoegen',
  			'sub_fields' => array(
  				array(
  					'key' => 'field_5beaf173c4397',
  					'label' => 'Thema-titel',
  					'name' => 'actielijnen_per_thema_titel',
  					'type' => 'text',
  					'instructions' => '',
  					'required' => 0,
  					'conditional_logic' => 0,
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'default_value' => '',
  					'placeholder' => '',
  					'prepend' => '',
  					'append' => '',
  					'maxlength' => '',
  				),
  				array(
  					'key' => 'field_5bec22d240e6c',
  					'label' => 'Kies een kleur',
  					'name' => 'actielijnen_per_thema_kleur',
  					'type' => 'taxonomy',
  					'instructions' => '',
  					'required' => 0,
  					'conditional_logic' => 0,
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'taxonomy' => 'digitaleagenda',
  					'field_type' => 'radio',
  					'allow_null' => 1,
  					'add_term' => 0,
  					'save_terms' => 0,
  					'load_terms' => 0,
  					'return_format' => 'id',
  					'multiple' => 0,
  				),
  				array(
  					'key' => 'field_5bec22f340e6d',
  					'label' => 'Kies de actielijnen in dit blok',
  					'name' => 'actielijnen_per_thema_actielijnen',
  					'type' => 'relationship',
  					'instructions' => '',
  					'required' => 0,
  					'conditional_logic' => 0,
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'post_type' => array(
  						0 => 'actielijn',
  					),
  					'taxonomy' => '',
  					'filters' => array(
  						0 => 'search',
  						1 => 'taxonomy',
  					),
  					'elements' => '',
  					'min' => 1,
  					'max' => '',
  					'return_format' => 'object',
  				),
  			),
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'page_template',
  				'operator' => '==',
  				'value' => 'planningtool-template.php',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'acf_after_title',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));
  
  acf_add_local_field_group(array(
  	'key' => 'group_5be9756fd880f',
  	'title' => 'Gebeurtenis: datum + gekoppelde actielijnen',
  	'fields' => array(
  		array(
  			'key' => 'field_5be99930b1843',
  			'label' => 'Datum',
  			'name' => 'gebeurtenis_datum',
  			'type' => 'date_picker',
  			'instructions' => '',
  			'required' => 1,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'display_format' => 'j F Y',
  			'return_format' => 'Ymd',
  			'first_day' => 1,
  		),
  		array(
  			'key' => 'field_5be975841eea9',
  			'label' => 'Bijbehorende actielijnen',
  			'name' => 'related_gebeurtenissen_actielijnen',
  			'type' => 'relationship',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'post_type' => array(
  				0 => 'actielijn',
  			),
  			'taxonomy' => '',
  			'filters' => array(
  				0 => 'search',
  				1 => 'taxonomy',
  			),
  			'elements' => '',
  			'min' => '',
  			'max' => '',
  			'return_format' => 'object',
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'post_type',
  				'operator' => '==',
  				'value' => 'gebeurtenis',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'acf_after_title',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));
  
  acf_add_local_field_group(array(
  	'key' => 'group_5be97485d6c95',
  	'title' => 'Actielijn: datums + link met gebeurtenissen',
  	'fields' => array(
  		array(
  			'key' => 'field_5bec3c8db4fd6',
  			'label' => 'Heeft start- of einddatums?',
  			'name' => 'heeft_start-_of_einddatums',
  			'type' => 'radio',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'choices' => array(
  				'start' => 'Alleen startdatum',
  				'eind' => 'Alleen einddatum',
  				'start_eind' => 'Zowel een start- als einddatum',
  			),
  			'allow_null' => 0,
  			'other_choice' => 0,
  			'default_value' => 'start',
  			'layout' => 'vertical',
  			'return_format' => 'value',
  			'save_other_choice' => 0,
  		),
  		array(
  			'key' => 'field_5be99a18c781e',
  			'label' => 'Start lwartaal',
  			'name' => 'actielijn_kwartaal_start_kwartaal',
  			'type' => 'select',
  			'instructions' => '(niet zichtbaar)',
  			'required' => 1,
  			'conditional_logic' => array(
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'start',
  					),
  				),
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'start_eind',
  					),
  				),
  			),
  			'wrapper' => array(
  				'width' => '48',
  				'class' => '',
  				'id' => '',
  			),
  			'choices' => array(
  				'q1' => 'Kwartaal 1',
  				'q2' => 'Kwartaal 2',
  				'q3' => 'Kwartaal 3',
  				'q4' => 'Kwartaal 4',
  			),
  			'default_value' => array(
  				0 => 'q1',
  			),
  			'allow_null' => 0,
  			'multiple' => 0,
  			'ui' => 0,
  			'return_format' => 'value',
  			'ajax' => 0,
  			'placeholder' => '',
  		),
  		array(
  			'key' => 'field_5bec3d241a410',
  			'label' => 'Jaar start',
  			'name' => 'actielijn_kwartaal_start_jaar',
  			'type' => 'number',
  			'instructions' => '(niet zichtbaar)
  Format: xxxx',
  			'required' => 1,
  			'conditional_logic' => array(
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'start',
  					),
  				),
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'start_eind',
  					),
  				),
  			),
  			'wrapper' => array(
  				'width' => '48',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'min' => '',
  			'max' => '',
  			'step' => '',
  		),
  		array(
  			'key' => 'field_5bec3e0b8f5f6',
  			'label' => 'Kwartaal eind',
  			'name' => 'actielijn_kwartaal_eind_kwartaal',
  			'type' => 'select',
  			'instructions' => '(niet zichtbaar)',
  			'required' => 1,
  			'conditional_logic' => array(
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'eind',
  					),
  				),
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'start_eind',
  					),
  				),
  			),
  			'wrapper' => array(
  				'width' => '48',
  				'class' => '',
  				'id' => '',
  			),
  			'choices' => array(
  				'q1' => 'Kwartaal 1',
  				'q2' => 'Kwartaal 2',
  				'q3' => 'Kwartaal 3',
  				'q4' => 'Kwartaal 4',
  			),
  			'default_value' => array(
  				0 => 'q1',
  			),
  			'allow_null' => 0,
  			'multiple' => 0,
  			'ui' => 0,
  			'return_format' => 'value',
  			'ajax' => 0,
  			'placeholder' => '',
  		),
  		array(
  			'key' => 'field_5bec3e248f5f7',
  			'label' => 'Jaar eind',
  			'name' => 'actielijn_kwartaal_eind_jaar',
  			'type' => 'number',
  			'instructions' => '(niet zichtbaar)
  Format: xxxx',
  			'required' => 1,
  			'conditional_logic' => array(
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'eind',
  					),
  				),
  				array(
  					array(
  						'field' => 'field_5bec3c8db4fd6',
  						'operator' => '==',
  						'value' => 'start_eind',
  					),
  				),
  			),
  			'wrapper' => array(
  				'width' => '48',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'min' => '',
  			'max' => '',
  			'step' => '',
  		),
  		array(
  			'key' => 'field_5be974b66ea85',
  			'label' => 'Kies bijbehorende gebeurtenissen',
  			'name' => 'related_gebeurtenissen_actielijnen',
  			'type' => 'relationship',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'post_type' => array(
  				0 => 'gebeurtenis',
  			),
  			'taxonomy' => '',
  			'filters' => array(
  				0 => 'search',
  				1 => 'taxonomy',
  			),
  			'elements' => '',
  			'min' => '',
  			'max' => '',
  			'return_format' => 'object',
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'post_type',
  				'operator' => '==',
  				'value' => 'actielijn',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'acf_after_title',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'field',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));


  acf_add_local_field_group(array(
  	'key' => 'group_5be5e4ee3cb94',
  	'title' => 'Planning Tool instellingen',
  	'fields' => array(
  		array(
  			'key' => 'field_5be5e5070642f',
  			'label' => 'Planning-pagina',
  			'name' => 'planning_page',
  			'type' => 'post_object',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'post_type' => array(
  				0 => 'page',
  			),
  			'taxonomy' => '',
  			'allow_null' => 0,
  			'multiple' => 0,
  			'return_format' => 'object',
  			'ui' => 1,
  		),
  		array(
  			'key' => 'field_5bee9098c1202',
  			'label' => 'Start jaar',
  			'name' => 'planning_page_start_jaar',
  			'type' => 'number',
  			'instructions' => 'format: xxxx',
  			'required' => 1,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => 2017,
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'min' => 1900,
  			'max' => 2100,
  			'step' => '',
  		),
  		array(
  			'key' => 'field_5bee90d2a5dad',
  			'label' => 'Eind jaar',
  			'name' => 'planning_page_end_jaar',
  			'type' => 'number',
  			'instructions' => 'format: xxxx',
  			'required' => 1,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => 2020,
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'min' => 1900,
  			'max' => 2100,
  			'step' => '',
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'options_page',
  				'operator' => '==',
  				'value' => 'instellingen',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'normal',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));

  

}

//========================================================================================================

