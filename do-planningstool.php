<?php
/*
 * DO_Planningtool. 
 *
 * Plugin Name:         Planning Tool digitaleoverheid.nl
 * Plugin URI:          https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/
 * Description:         Plugin voor digitaleoverheid.nl waarmee extra functionaliteit mogelijk wordt voor het tonen van een planning met actielijnen en gebeurtenissen.
 * Version:             0.0.1
 * Version description: First set up of plugin files.
 * Author:              Paul van Buuren
 * Author URI:          https://wbvb.nl
 * License:             GPL-2.0+
 *
 * Text Domain:         do-planningstool
 * Domain Path:         /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}


add_action( 'plugins_loaded', 'do_pt_init_load_plugin_textdomain' );

if ( ! class_exists( 'DO_Planning_Tool' ) ) :

/**
 * Register the plugin.
 *
 * Display the administration panel, add JavaScript etc.
 */
  class DO_Planning_Tool {
  
      /**
       * @var string
       */
      public $version = '0.0.1';
  
  
      /**
       * @var DO_Planningtool
       */
      public $gcmaturity = null;

      /**
       * @var DO_Planningtool
       */

      public $option_name = null;

      public $survey_answers = null;

      public $survey_data = null;

  
      /**
       * Init
       */
      public static function init() {
  
          $gcmaturity_this = new self();
  
      }
  
      //========================================================================================================
  
      /**
       * Constructor
       */
      public function __construct() {
  
          $this->define_constants();
          $this->includes();
          $this->do_pt_init_setup_actions();
          $this->do_pt_init_setup_filters();
          $this->do_pt_admin_do_system_check();
          $this->do_pt_frontend_append_comboboxes();

      }
  
      //========================================================================================================
  
      /**
       * Define DO_Planningtool constants
       */
      private function define_constants() {
  
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
  
        define( 'DOPT__VERSION',                 $this->version );
        define( 'DOPT__FOLDER',                  'do-planningstool' );
        define( 'DOPT__BASE_URL',                trailingslashit( plugins_url( DOPT__FOLDER ) ) );
        define( 'DOPT__ASSETS_URL',              trailingslashit( DOPT__BASE_URL . 'assets' ) );
        define( 'DOPT__MEDIAELEMENT_URL',        trailingslashit( DOPT__BASE_URL . 'mediaelement' ) );
        define( 'DOPT__PATH',                    plugin_dir_path( __FILE__ ) );
        define( 'DOPT__PATH_LANGUAGES',          trailingslashit( DOPT__PATH . 'languages' ) );;

        define( 'DOPT__ACTIELIJN_CPT',           "actielijn" );
        define( 'DOPT__GEBEURTENIS_CPT',         "gebeurtenis" );

        define( 'DOPT__SURVEY_DEFAULT_USERID',   2600 ); // 't is wat, hardgecodeerde userids (todo: invoerbaar maken via admin)

        define( 'DOPT__SURVEY_CT_ORG_TYPE',      "Organisation type" );

        define( 'DOPT__QUESTION_PREFIX',         DOPT__ACTIELIJN_CPT . '_pf_' ); // prefix for cmb2 metadata fields
        define( 'DOPT__CMBS2_PREFIX',            DOPT__QUESTION_PREFIX . '_form_' ); // prefix for cmb2 metadata fields
        define( 'DOPT__FORMKEYS',                DOPT__CMBS2_PREFIX . 'keys' ); // prefix for cmb2 metadata fields
        
        define( 'DOPT__PLUGIN_DO_DEBUG',         true );
//        define( 'DOPT__PLUGIN_DO_DEBUG',         false );
//        define( 'DOPT__PLUGIN_OUTPUT_TOSCREEN',  false );
        define( 'DOPT__PLUGIN_OUTPUT_TOSCREEN',  true );
        define( 'DOPT__PLUGIN_USE_CMB2',         true ); 
        define( 'DOPT__PLUGIN_GENESIS_ACTIVE',   true ); // todo: inbouwen check op actief zijn van Genesis framework
        define( 'DOPT__PLUGIN_AMCHART_ACTIVE',   true ); // todo: inbouwen check op actief zijn AM-chart of op AM-chart licentie

//        define( 'DOPT__FRONTEND_SHOW_AVERAGES',  true ); 
        define( 'DOPT__FRONTEND_SHOW_AVERAGES',  false ); 

        define( 'DOPT__AVGS_NR_SURVEYS',         'do_pt_total_number_surveys3' ); 
        define( 'DOPT__AVGS_OVERALL_AVG',        'do_pt_overall_average3' ); 

        define( 'DOPT__METABOX_ID',              'front-end-post-form' ); 
        define( 'DOPT_MB2_RANDOM_OBJECT_ID',     'fake-oject-id' ); 

        define( 'DOPT__ALGEMEEN_LABEL',          'lalala label' ); 
        define( 'DOPT__ALGEMEEN_KEY',            'lalala_key' ); 

        define( 'DOPT__PLUGIN_KEY',              'gcms' ); 
        
        define( 'DOPT__PLUGIN_SEPARATOR',        '__' );

        define( 'GCMS_SCORESEPARATOR',            DOPT__PLUGIN_SEPARATOR . 'score' . DOPT__PLUGIN_SEPARATOR );
 
        define( 'DOPT__SCORE_MAX',               5 ); // max 5 sterren, max 5 punten per vraag / onderdeel

        define( 'DOPT__TABLE_COL_TH',            0 );
        define( 'DOPT__TABLE_COL_USER_AVERAGE',  1 );
        define( 'DOPT__TABLE_COL_SITE_AVERAGE',  2 );

        define( 'DOPT__SURVEY_EMAILID',          'submitted_your_email' );
        define( 'DOPT__SURVEY_YOURNAME',         'submitted_your_name' );
        define( 'DOPT__SURVEY_GDPR_CHECK',       'gdpr_do_save_my_emailaddress' );

        define( 'DOPT__KEYS_VALUE',              '_value' );
        define( 'DOPT__KEYS_LABEL',              '_label' );

        define( 'DOPT__URLPLACEHOLDER',          '[[url]]' );
        define( 'DOPT__NAMEPLACEHOLDER',         '[[name]]' );

        define( 'DOPT__TEXTEMAIL',               'textforemail' );


        $this->option_name  = 'gcms-option';
        $this->survey_data  = array();
        

       }
  
      //========================================================================================================
  
      /**
       * All DO_Planningtool classes
       */
      private function plugin_classes() {
  
          return array(
              'DO_PT_SystemCheck'  => DOPT__PATH . 'inc/dopt.systemcheck.class.php',
          );
  
      }
  
      //========================================================================================================
  
      /**
       * Load required classes
       */
      private function includes() {
      
        if ( DOPT__PLUGIN_USE_CMB2 ) {
          // load CMB2 functionality
          if ( ! defined( 'CMB2_LOADED' ) ) {
            // cmb2 NOT loaded
            if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
              require_once dirname( __FILE__ ) . '/cmb2/init.php';
            }
            elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
              require_once dirname( __FILE__ ) . '/CMB2/init.php';
            }
          }
        }

        $autoload_is_disabled = defined( 'DOPT__AUTOLOAD_CLASSES' ) && DOPT__AUTOLOAD_CLASSES === false;
        
        if ( function_exists( "spl_autoload_register" ) && ! ( $autoload_is_disabled ) ) {
          
          // >= PHP 5.2 - Use auto loading
          if ( function_exists( "__autoload" ) ) {
            spl_autoload_register( "__autoload" );
          }
          spl_autoload_register( array( $this, 'autoload' ) );
          
        } 
        else {
          // < PHP5.2 - Require all classes
          foreach ( $this->plugin_classes() as $id => $path ) {
            if ( is_readable( $path ) && ! class_exists( $id ) ) {
              require_once( $path );
            }
          }
          
        }

      
      }
  
      //========================================================================================================
  
      /**
       * filter for when the CPT for the survey is previewed
       */
      public function do_pt_frontend_filter_for_preview( $content = '' ) {

        global $post;

        if ( is_singular( DOPT__ACTIELIJN_CPT ) && in_the_loop() ) {
          // lets go
          return $this->do_pt_frontend_display_survey_results( $post->ID );
        }
        else {
          return $content;
        }
        
      }
  
      //========================================================================================================
  
      /**
       * Autoload DO_Planningtool classes to reduce memory consumption
       */
      public function autoload( $class ) {
  
          $classes = $this->plugin_classes();
  
          $class_name = strtolower( $class );
  
          if ( isset( $classes[$class_name] ) && is_readable( $classes[$class_name] ) ) {
            echo 'require: ' . $classes[$class_name]. '<br>';
            die();
              require_once( $classes[$class_name] );
          }
  
      }
  
      //========================================================================================================
  
      /**
       * Hook DO_Planningtool into WordPress
       */
      private function do_pt_init_setup_actions() {

        
        // add a page temlate name
        $this->templates          = array();
        $this->templatefile   		= 'stelselcatalogus-template.php';

        add_action( 'init',                   array( $this, 'do_pt_init_register_post_type' ) );
        
        // add the page template to the templates list
        add_filter( 'theme_page_templates',   array( $this, 'do_pt_init_add_page_templates' ) );
        
        // activate the page filters
        add_action( 'template_redirect',      array( $this, 'do_pt_frontend_use_page_template' )  );
        
        // admin settings
        add_action( 'admin_init',             array( $this, 'do_pt_admin_register_settings' ) );
        
        // Hook do_sync method *todo*
        add_action( 'wp_ajax_do_pt_reset',    'gcms_data_reset_values');

        add_action( 'wp_enqueue_scripts',     array( $this, 'do_pt_frontend_register_frontend_style_script' ) );

        add_action( 'admin_enqueue_scripts',  array( $this, 'do_pt_admin_register_styles' ) );


      }
      //========================================================================================================
  
      /**
       * Hook DO_Planningtool into WordPress
       */
      private function do_pt_init_setup_filters() {

        	// content filter
          add_filter( 'the_content', array( $this, 'do_pt_frontend_filter_for_preview' ) );

      }

      //========================================================================================================
  
      /**
       * Register post type
       */
      public function do_pt_init_register_post_type() {

        $typeUC_single = _x( "Actielijn", "labels", "do-planningstool" );
        $typeUC_plural = _x( "Actielijnen", "labels", "do-planningstool" );
        
        $typeLC_single = _x( "survey", "labels", "do-planningstool" );
        $typeLC_plural = _x( "surveys", "labels", "do-planningstool" );

      	$labels = array(
      		"name"                  => sprintf( '%s', $typeUC_single ),
      		"singular_name"         => sprintf( '%s', $typeUC_single ),
      		"menu_name"             => sprintf( '%s', $typeUC_single ),
      		"all_items"             => sprintf( _x( 'All %s', 'labels', "do-planningstool" ), $typeLC_plural ),
      		"add_new"               => sprintf( _x( 'Add %s', 'labels', "do-planningstool" ), $typeLC_plural ),
      		"add_new_item"          => sprintf( _x( 'Add new %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"edit"                  => _x( "Edit?", "labels", "do-planningstool" ),
      		"edit_item"             => sprintf( _x( 'Edit %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"new_item"              => sprintf( _x( 'Add %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"view"                  => _x( "Show", "labels", "do-planningstool" ),
      		"view_item"             => sprintf( _x( 'Add %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"search_items"          => sprintf( _x( 'Search %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"not_found"             => sprintf( _x( 'No %s available', 'labels', "do-planningstool" ), $typeLC_single ),
      		"not_found_in_trash"    => sprintf( _x( 'No %s in trash', 'labels', "do-planningstool" ), $typeLC_plural ),
      		"parent"                => _x( "Parent", "labels", "do-planningstool" ),
      		
    		);
      
      	$args = array(
          "label"                 => $typeUC_plural,
          "labels"                => $labels,
          "description"           => "",
          "public"                => true,
          "publicly_queryable"    => true,
          "show_ui"               => true,
          "show_in_rest"          => false,
          "rest_base"             => "",
          "has_archive"           => false,
          "show_in_menu"          => true,
          "exclude_from_search"   => false,
          "capability_type"       => "post",
          "map_meta_cap"          => true,
          "hierarchical"          => false,
          "rewrite"               => array( "slug" => DOPT__ACTIELIJN_CPT, "with_front" => true ),
          "query_var"             => true,
      		"supports"              => array( "title", "editor" ),					
    		);
      		
      	register_post_type( DOPT__ACTIELIJN_CPT, $args );


        $typeUC_single = _x( "Organisation type", "labels", "do-planningstool" );
        $typeUC_plural = _x( "Organisation types", "labels", "do-planningstool" );
        
        $typeLC_single = _x( "organisation type", "labels", "do-planningstool" );
        $typeLC_plural = _x( "organisation types", "labels", "do-planningstool" );

        // organisation types
      	$labels = array(

      		"name"                  => sprintf( '%s', $typeUC_single ),
      		"singular_name"         => sprintf( '%s', $typeUC_single ),
      		"menu_name"             => sprintf( '%s', $typeUC_single ),
      		"all_items"             => sprintf( _x( 'All %s', 'labels', "do-planningstool" ), $typeLC_plural ),
      		"add_new"               => sprintf( _x( 'Add %s', 'labels', "do-planningstool" ), $typeLC_plural ),
      		"add_new_item"          => sprintf( _x( 'Add new %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"edit"                  => _x( "Edit?", "labels", "do-planningstool" ),
      		"edit_item"             => sprintf( _x( 'Edit %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"new_item"              => sprintf( _x( 'Add %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"view"                  => _x( "Show", "labels", "do-planningstool" ),
      		"view_item"             => sprintf( _x( 'Add %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"search_items"          => sprintf( _x( 'Search %s', 'labels', "do-planningstool" ), $typeLC_single ),
      		"not_found"             => sprintf( _x( 'No %s available', 'labels', "do-planningstool" ), $typeLC_single ),
      		"not_found_in_trash"    => sprintf( _x( 'No %s in trash', 'labels', "do-planningstool" ), $typeLC_plural ),
      		"parent"                => _x( "Parent", "labels", "do-planningstool" ),
      		"archives"              => _x( "Edit?", "labels", "do-planningstool" ),

    		);

      	$args = array(
      		"label"               => $typeUC_plural,
      		"labels"              => $labels,
      		"public"              => false,
      		"hierarchical"        => true,
      		"label"               => $typeUC_plural,
      		"show_ui"             => true,
      		"show_in_menu"        => true,
      		"show_in_nav_menus"   => true,
      		"query_var"           => true,
      		"rewrite"             => array( 'slug' => DOPT__SURVEY_CT_ORG_TYPE, 'with_front' => true, ),
      		"show_admin_column"   => true,
      		"show_in_rest"        => false,
      		"rest_base"           => "",
      		"show_in_quick_edit"  => false,
      	);
      	register_taxonomy( DOPT__SURVEY_CT_ORG_TYPE, array( DOPT__ACTIELIJN_CPT ), $args );



	      	
      	
      	flush_rewrite_rules();
  
      }
  
      //========================================================================================================
  
      /**
      * Hides the custom post template for pages on WordPress 4.6 and older
      *
      * @param array $post_templates Array of page templates. Keys are filenames, values are translated names.
      * @return array Expanded array of page templates.
      */
      function do_pt_init_add_page_templates( $post_templates ) {
      
        $post_templates[$this->templatefile]  		= _x( 'Maturity score template', "naam template", "do-planningstool" );    
        return $post_templates;
      
      }
  
      //========================================================================================================
  
    	/**
    	 * Register the options page
    	 *
    	 * @since    1.0.0
    	 */
    	public function do_pt_admin_register_settings() {
  
    		// Add a General section
    		add_settings_section(
    			$this->option_name . '_general',
    			__( 'General settings', "do-planningstool" ),
    			array( $this, $this->option_name . '_general_cb' ),
    			DOPT__PLUGIN_KEY
    		);

      }  

      //========================================================================================================
  
      /**
       * Register admin-side styles
       */
      public function do_pt_admin_register_styles() {
  
        if ( is_admin() ) {
          wp_enqueue_style( 'do-planningstool-admin', DOPT__ASSETS_URL . 'css/do-planningstool-admin.css', false, DOPT__VERSION );
        }
  
      }

      //========================================================================================================
  
      /**
       * Add the help tab to the screen.
       */
      public function do_pt_admin_help_tab() {
  
        $screen = get_current_screen();
  
        // documentation tab
        $screen->add_do_pt_admin_help_tab( array(
          'id'      => 'documentation',
          'title'   => __( 'Documentation', "do-planningstool" ),
          'content' => "<p><a href='https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/documentation/' target='blank'>" . __( 'GC Maturity documentation', "do-planningstool" ) . "</a></p>",
          )
        );
      }
  
      //====================================================================================================
  
      /**
       * Check our WordPress installation is compatible with DO_Planningtool
       */
      public function do_pt_admin_main_page_get() {
  
        echo '<div class="wrap">';
        echo '	<h2>' .  esc_html( get_admin_page_title() ) . '</h2>';
        echo '	<p>' .  _x( 'Here you can edit the surveys.', "admin", "do-planningstool" ) . '</p>';
        echo $this->do_pt_frontend_get_tableview( false, 0, true );
        // cmb2
        echo '</div>';
  
  
      }

      //========================================================================================================
  
      /**
       * Register admin JavaScript
       */
      public function do_pt_admin_register_scripts() {
  
          // media library dependencies
          wp_enqueue_media();
  
          // plugin dependencies
          wp_enqueue_script( 'jquery-ui-core', array( 'jquery' ) );
  
          $this->do_pt_admin_localize_scripts();
  
          do_action( 'do_pt_do_pt_admin_register_scripts' );
  
      }
  
      //========================================================================================================
  
      /**
       * Localise admin script
       */
      public function do_pt_admin_localize_scripts() {
  
          wp_localize_script( 'gcms-admin-script', 'gcms', array(
                  'url'               => _x( "URL", "js", "do-planningstool" ),
                  'caption'           => _x( "Caption", "js", "do-planningstool" ),
                  'new_window'        => _x( "New Window", "js", "do-planningstool" ),
                  'confirm'           => _x( "Are you sure?", "js", "do-planningstool" ),
                  'ajaxurl'           => admin_url( 'admin-ajax.php' ),
                  'resize_nonce'      => wp_create_nonce( 'do_pt_resize' ),
                  'iframeurl'         => admin_url( 'admin-post.php?action=do_pt_preview' ),
              )
          );
  
      }
  
    //====================================================================================================

    /**
     * Check our WordPress installation is compatible with DO_Planningtool
     */
    public function do_pt_admin_options_page() {

      echo '<div class="wrap">';
      echo '	<h2>' .  esc_html( get_admin_page_title() ) . '</h2>';
      echo '<div id="thetable">';
      echo $this->do_pt_frontend_get_tableview();      
      echo '</div>';

?>

    	<table class="form-table" id="progress">
    		<tr>
    			<td>
    				<input id="startsync" type="button" class="button button-primary" value="<?php _e( 'Reset statistics', "do-planningstool" ); ?>" />
    				<input id="clearlog" type="button" class="button button-secondary" value="<?php _e( 'Empty log', "do-planningstool" ); ?>" />
    			</td>
    		</tr>
    	</table>
      <noscript style="background: red; padding: .5em; font-size: 120%;display: block; margin-top: 1em !important; color: white;">
        <strong><?php _e( 'Ehm, please allow JavaScript.', "do-planningstool" );?></strong>
      </noscript>
      <div style="width: 100%; padding-top: 16px;" id="items">&nbsp;</div>
    	<div style="width: 100%; padding-top: 16px; font-style: italic;" id="log"><?php _e( 'Press the button!', "do-planningstool" );?></div>
    
    
    	<script type="text/javascript">
    
    
    		var _button       = jQuery('input#startsync');
    		var _clearbutton  = jQuery('input#clearlog');
    		var _lastrow      = jQuery('#progress tr:last');
    		var startrec = 1;
    
    		var setProgress = function (_message) {
    			_lastrow.append(_message);
    		}
    
    		jQuery(document).ready(function () {
    
    			_button.click(function (e) {
    
    				e.preventDefault();
    				jQuery(this).val('<?php _e( 'Just a moment please', "do-planningstool" );?>').prop('disabled', true);
    				jQuery( '#log' ).empty();
    				jQuery( '#thetable' ).empty();
    				_requestJob( );
    
    			});
    
    			// clear log div
    			_clearbutton.click(function() {
    				jQuery( '#log' ).empty();
    				jQuery( '#thetable' ).empty();
    			})
    
    		})
    
    		var _requestJob = function ( ) {
    			jQuery.post(ajaxurl, { 'action': 'do_pt_reset',  'dofeedback': '1' }, _jobResult);
    		}
    
    		var _jobResult = function (response) {
    
          _button.val('<?php _e( 'Reset statistics', "do-planningstool" ) ?>').prop('disabled', false);
    
    			if (response.ajaxrespons_item.length > 0) {
    				// new messages appear on top. .append() can be used to have new entries at the bottom
    				jQuery('#thetable').html( response.ajaxrespons_item );
    			}
    			if (response.ajaxrespons_messages.length > 0) {
    				for (var i = 0; i < response.ajaxrespons_messages.length; i++) {
    					// new messages appear on top. .append() can be used to have new entries at the bottom
    					jQuery('#log').prepend(response.ajaxrespons_messages[i] + '<br />');
    				}
    			}
    
    			jQuery(this).val('<?php _e( 'Just a moment please', "do-planningstool" );?>').prop('disabled', true);
    		}
    
    	</script>
    
    <?php
      
          echo '</div>';
    
        }
      
      //====================================================================================================
  
      /**
       * Check our WordPress installation is compatible with DO_Planningtool
       */
      public function do_pt_admin_do_system_check() {
  
  
  //        $systemCheck = new DO_PT_SystemCheck();
  //        $systemCheck->check();
  
      }
  
      //========================================================================================================
    /**
     * Register the form and fields for our admin form
     */
    public function do_pt_admin_form_register_cmb2_form_new() {

      /**
       * Registers options page menu item and form.
       */
      $cmb_options = new_cmb2_box( array(
      	'id'              => 'do_pt_admin_options_metabox',
      	'title'           => esc_html__( 'Maturity score', "do-planningstool" ),
      	'object_types'    => array( 'options-page' ),
      	'option_key'      => DOPT__PLUGIN_KEY, // The option key and admin menu page slug.
      	'icon_url'        => 'dashicons-admin-settings', // Menu icon. Only applicable if 'parent_slug' is left empty.
      	'capability'      => 'manage_options', // Cap required to view options-page.
        'tab_group'       => DOPT__PLUGIN_KEY,
        'tab_title'       => _x( 'Email settings &amp; general scoring', 'scoring menu', "do-planningstool"),      	
        'display_cb'      => 'yourprefix_options_display_with_tabs',
      	'save_button'     => esc_html__( 'Save options', "do-planningstool" ), 
      ) );
      /*
       * Options fields ids only need
       * to be unique within this box.
       * Prefix is not needed.
       */
      
      
        $tabscounter = 0;
        
        $key = 'SITESCORE';
        

        $formfields_data    = do_pt_data_get_survey_json();
        $sectiontitle_prev  = '';

        if ( $formfields_data ) {
          
          // there are questions and answers
          
          // first, let's set up the forms for the general test results

          $counter          = 0;
          $tabscounter      = 0;  
          $question_counter = 0;  
          $total_answer_cnt = 0;  
          $counter_group    = 0;

          $questionsnumber_start  =   1;
          $questionsnumber_end    =   0;
          $questionsnumber_prev   =   0;
          
          foreach ( $formfields_data as $group_key => $value) {

            // $group_key = 'g1', 'g2', 'g3', etc
            $tabscounter++;
            $counter_group++;

            $key_grouplabel         = $group_key . '_group_label';
            $key_group_description  = $group_key . '_group_description';
            $grouplabel             = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $value->group_label );
            $groupdescription       = gcms_aux_get_value_for_cmb2_key( $key_group_description, $value->group_description );
            $groupquestions         = (array) $value->group_questions[0];

            $questionsnumber_start  = ( $questionsnumber_prev + $tabscounter );
            $questionsnumber_end    = ( ( $questionsnumber_start - 1 ) + count( $groupquestions ) );
            $questionsnumber_prev   = $questionsnumber_end;

            $questionrange          = sprintf( _x( '%s - %s', 'question range', "do-planningstool" ), ( $question_counter + 1 ), ( $question_counter + count( $groupquestions ) ) );
            $tablabel               = sprintf( _x( '%s - questions %s', 'tablabel', "do-planningstool" ), $tabscounter, $questionrange );
            $groupheader            = sprintf( _x( 'Group %s: title, introduction and scoring texts', 'tablabel', "do-planningstool" ), $counter_group );
            $grouptitle_label       = sprintf( _x( 'Title for group %s', 'tablabel', "do-planningstool" ), $counter_group );
            $groupintro_label       = sprintf( _x( 'Introduction %s', 'tablabel', "do-planningstool" ), $counter_group );

            // maak een tab aan voor de vragen van een groep
            $args = array(
            	'id'           => DOPT__PLUGIN_KEY . $group_key,
            	'menu_title'   => sprintf( _x( 'Group %s - %s', 'tablabel', "do-planningstool" ), $tabscounter, '(' . $questionrange . ')' ),
            	'object_types' => array( 'options-page' ),
            	'option_key'   => DOPT__PLUGIN_KEY . DOPT__PLUGIN_SEPARATOR . $group_key,
            	'parent_slug'  => DOPT__PLUGIN_KEY,
            	'tab_group'    => DOPT__PLUGIN_KEY,
            	'tab_title'    => $tablabel,
              'display_cb'   => 'yourprefix_options_display_with_tabs',
            );
            
            $questions_tab    = new_cmb2_box( $args );

            $questions_tab->add_field( array(
            	'name'          => $groupheader,
            	'type'          => 'title',
            	'id'            => DOPT__CMBS2_PREFIX . 'start_section' . $counter_group
            ) );

            $questions_tab->add_field( array(
            	'name'          => $grouptitle_label,
          		'type'          => 'text',
            	'id'            => $key_grouplabel,
            	'default'       => $grouplabel
            ) );
      
            $questions_tab->add_field( array(
            	'name'          => $groupintro_label,
          		'type'          => 'textarea',
            	'id'            => $key_group_description,
            	'default'       => $groupdescription
            ) );

            
            // scoring texts
            $questions_tab->add_field( array(
            	'name'          => __( 'Score for this section', "do-planningstool" ),
            	'type'          => 'title',
            	'id'            => DOPT__CMBS2_PREFIX . 'start_scoring_section' . $counter_group
            ) );
            
            $counter = 0;
            
            // code voor onderhouden namen en inleiding
            while( $counter <  DOPT__SCORE_MAX ) {
            
              $counter++;
            
              $default = sprintf( __( 'For section <em>%s</em> you scored over %s, but lesss than %s. Here more about that.', "do-planningstool" ), gcms_aux_get_value_for_cmb2_key( $key ), ( $counter - 1 ), $counter );
              
              $label = sprintf( __( 'between %s and %s', "do-planningstool" ), ( $counter - 1 ), $counter );
              if ( DOPT__SCORE_MAX == $counter ) {
                $default = sprintf( __( 'Perfect score: %s!', "do-planningstool" ), $counter );
              }
            
              $fieldkey = $group_key . GCMS_SCORESEPARATOR . $counter;
            
              $questions_tab->add_field( array(
              	'name'          => sprintf( _x( 'Text %s<br><small>if score %s.</small>', 'score range', "do-planningstool" ), $counter, $label ),
              	'description'   => sprintf( __( 'Score %s', "do-planningstool" ), $label . ' (' . $fieldkey . ')' ),
            		'type'          => 'wysiwyg',
              	'id'            => $fieldkey,
              	'default'       => $default
              ) );
            
            }


            // loop through the questions
            foreach ( $groupquestions as $question_key => $question_single ) {

              $question_counter++;
  
              $options          = array();
              $selectrandom     = array();
              $default          = '';
              $answers          = (array) $question_single->question_answers[0];
              $question_label   = $question_single->question_label;

              $questionkey      =  $group_key . DOPT__PLUGIN_SEPARATOR . $question_key;

              $questions_tab->add_field( array(
              	'name'          =>  sprintf( __( 'Question %s - %s', "do-planningstool" ), $question_counter, gcms_aux_get_value_for_cmb2_key( $questionkey, $question_label, DOPT__PLUGIN_KEY . DOPT__PLUGIN_SEPARATOR . $group_key ) ),
              	'type'          => 'title',
              	'id'            => DOPT__CMBS2_PREFIX . '_section_' . $key . '_' . $group_key . '_' . $question_counter
              ) );
              
              // put it together
              $fieldkey = $group_key . DOPT__PLUGIN_SEPARATOR . $question_key;
              $questions_tab->add_field( array(
            		'name'          => sprintf( __( 'Question %s', "do-planningstool" ), $question_counter ),
              	'id'            => $fieldkey,
            		'type'          => 'text',
              	'default'       => $question_label,
              	'description'   => $fieldkey,
              	'attributes'    => array(
              		'required'    => 'required',
              	),
            	) );

              $optioncounter = 0;

              // get all possible answers
              foreach ( $answers as $answer_key => $answer ) {
                
                $total_answer_cnt++;
                $optioncounter++;
  
                $this_answer_key  = $group_key . DOPT__PLUGIN_SEPARATOR . $question_key . DOPT__PLUGIN_SEPARATOR . $answer_key;
                  
                // put it together
                $fieldkey = $this_answer_key . DOPT__KEYS_LABEL;
              	$questions_tab->add_field( array(
              		'name'          => sprintf( __( 'Label answer %s', "do-planningstool" ), $optioncounter ),
                	'id'            => $fieldkey,
              		'type'          => 'text',
                	'default'       => $answer->answer_label,
                	'description'   => $fieldkey,
                	'attributes'    => array(
                		'required'    => 'required',
                	),
              	) );
                
                // put it together
                $fieldkey = $this_answer_key . DOPT__KEYS_VALUE;
              	$questions_tab->add_field( array(
              		'type'          => 'text',
              		'name'          => sprintf( __( 'Value answer %s', "do-planningstool" ), $optioncounter ),
                	'id'            => $fieldkey,
                	'default'       => $answer->answer_value,
                	'description'   => $fieldkey,
                	'attributes'    => array(
                		'required'    => 'required',
                	),
              	) );

              }

            }

          }

        }
        else {
          // fout bij het ophalen van de formulierwaarden
          do_pt_aux_write_to_log('Fout bij ophalen van de formulierwaarden');
        }



        $cmb_options->add_field( array(
        	'name'          => _x( 'Email settings', "email settings", "do-planningstool" ),
        	'type'          => 'title',
        	'id'            => DOPT__CMBS2_PREFIX . 'start_section_emailsection'
        ) );

        $cmb_options->add_field( array(
        	'name'          => _x( 'Sender email address', "email settings", "do-planningstool" ),
      		'type'          => 'text',
        	'id'            => 'mail-from-address',
        	'default'       => _x( 'info@gebruikercentraal.nl', "email settings", "do-planningstool" )
        ) );

        $cmb_options->add_field( array(
        	'name'          => _x( 'Sender email name', "email settings", "do-planningstool" ),
      		'type'          => 'text',
        	'id'            => 'mail-from-name',
        	'default'       => _x( 'Gebruiker Centraal', "email settings", "do-planningstool" )
        ) );

        $cmb_options->add_field( array(
        	'name'          => _x( 'Email subject line', "email settings", "do-planningstool" ),
      		'type'          => 'text',
        	'id'            => 'mail-subject',
        	'default'       => _x( 'Link to your survey results', "email settings", "do-planningstool" )
        ) );

        $default = sprintf( _x( "Hi %s,\n\nThank you for using this tool.\nFor your future reference, here is a link to the results page of the survey you just filled out:\n%s\n\nKind regards,\nThe Gebruiker Centraal Team.", "email settings", "do-planningstool" ), DOPT__NAMEPLACEHOLDER, DOPT__URLPLACEHOLDER );

        $cmb_options->add_field( array(
        	'name'          => _x( 'Text in the email to the user', "email settings", "do-planningstool" ),
        	'description'   => '<span style="font-weight: 700; background: white; color: black;">' . sprintf( _x( 'This text should contain these strings:<br>%s - this is the placeholder for the link we send.<br>%s - this is the placeholder for user\'s name.', "email settings",  "do-planningstool" ), DOPT__URLPLACEHOLDER, DOPT__NAMEPLACEHOLDER ) . '</span>',
      		'type'          => 'wysiwyg',
        	'id'            => DOPT__TEXTEMAIL,
        	'default'       => $default,
        ) );
      

        $cmb_options->add_field( array(
        	'name'          => __( 'General scoring texts', "do-planningstool" ),
        	'type'          => 'title',
        	'id'            => DOPT__CMBS2_PREFIX . 'start_section_scoring'
        ) );

        // reset
        $counter = 0;

        while( $counter <  DOPT__SCORE_MAX ) {
    
          $counter++;
    
          $default  = sprintf( __( 'The text for scores between %s and %s. ', "do-planningstool" ), ( $counter - 1 ), $counter );
          $label    = sprintf( __( 'between %s and %s', "do-planningstool" ), ( $counter - 1 ), $counter );

          if ( DOPT__SCORE_MAX == $counter ) {
            $default = sprintf( __( 'Perfect score: %s!', "do-planningstool" ), $counter );
          }
    
          $fieldkey = $key . GCMS_SCORESEPARATOR . $counter;
    
          $cmb_options->add_field( array(
          	'name'          => sprintf( _x( 'Score text %s<br><small>if the total score is %s.</small>', 'score range', "do-planningstool" ), $counter, $label ),
          	'description'   => sprintf( __( 'General average score %s', "do-planningstool" ), $label . ' (' . $fieldkey . ')' ),
        		'type'          => 'wysiwyg',
          	'id'            => $fieldkey,
          	'default'       => $default
          ) );

        
        }

  
    }    

    //====================================================================================================  
      /**
       * Returns data from a survey and the site averages
       */
      public function do_pt_data_get_user_answers_and_averages( $postid = 0,  $context = '' ) {

        $yourdata         = array();
        $values           = array();
        $user_answers     = array();
        $systemaverages   = array();
        $valuescounter    = 0;

        $formfields_data  = do_pt_data_get_survey_json();

        if ( $postid ) {

          $user_answers_raw   = get_post_meta( $postid );    	

          if ( isset( $user_answers_raw[DOPT__FORMKEYS][0] ) ) {
            $user_answers     = maybe_unserialize( $user_answers_raw[DOPT__FORMKEYS][0] );
          }

          if ( $user_answers ) {
    
            foreach( $user_answers as $key => $value ){        

              // some values we do not need in our data structure
              if ( 
                ( $key == DOPT__QUESTION_PREFIX . DOPT__SURVEY_CT_ORG_TYPE ) ||
                ( $key == DOPT__SURVEY_YOURNAME ) ||
                ( $key == DOPT__SURVEY_GDPR_CHECK ) ||
                ( $key == DOPT__SURVEY_EMAILID ) 
                  ){
                // do not store values in this array for any of the custom taxonomies or for the email / name fields
                continue;
              }

              $array = array();
  
              $constituents = explode( DOPT__PLUGIN_SEPARATOR, $value ); // [0] = group, [1] = question, [2] = answer
              
              $group    = '';
              $question = '';
              $answer   = '';
              
              if ( isset( $constituents[0] ) ) {
                $group    = $constituents[0];
              }
              if ( isset( $constituents[1] ) ) {
                $question = $constituents[1];
              }
              if ( isset( $constituents[2] ) ) {
                $answer = $constituents[2];
              }

              $current_group    = (array) $formfields_data->$group;
              $current_question = (array) $formfields_data->$group->group_questions[0]->$question;
              $current_answer   = (array) $formfields_data->$group->group_questions[0]->$question->question_answers[0]->$answer;

              if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
                $current_answer['answer_site_average'] = get_option( $key, 1 );
              }

              $current_answer['question_label'] = $current_question['question_label'];
  
              $array['question_label']  = $current_question['question_label'];
              $array['question_answer'] = $current_answer;

              $values[ 'averages'][ 'groups'][ $group ][]   = $current_answer['answer_value'];
              $values[ 'all_values' ][]                     = $current_answer['answer_value'];
              $values[ 'user_answers' ][ $group ][ $key ]   = $current_answer;

            }

  
            if ( $values ) {

              $values['averages'][ 'overall' ]  = gcms_aux_get_average_for_array( $values[ 'all_values' ], 1 );

              unset( $values[ 'all_values' ] );

              foreach( $values[ 'averages'][ 'groups'] as $key => $value ){        
                $average = gcms_aux_get_average_for_array( $value, 1 );
                $values[ 'averages'][ 'groups'][ $key ] = round( $average, 0 );

                $columns = array();

                $rowname_translated = gcms_aux_get_value_for_cmb2_key( $key );
                
                if ( $key && $rowname_translated ) {

                  $key_grouplabel         = $key . '_group_label';
                  $collectionkey          = DOPT__PLUGIN_KEY . DOPT__PLUGIN_SEPARATOR . $key;
                  $default                = $formfields_data->$key->group_label;
                  
                  $columns[ DOPT__TABLE_COL_TH ] = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $default, $collectionkey );
                  $columns[ DOPT__TABLE_COL_USER_AVERAGE ] = $average;
  
                  if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
                    $columns[ DOPT__TABLE_COL_SITE_AVERAGE ] = get_option( $key, 1 );
                  }
                  
                  $values[ 'rows' ][ $key ]  = $columns;
                
                }
                
              }
  
      
              $values['cols'][ DOPT__TABLE_COL_TH ] = _x( "Chapter", "table header", "do-planningstool" );
              if ( $postid ) {
                $values['cols'][ DOPT__TABLE_COL_USER_AVERAGE ] = _x( "Your score", "table header", "do-planningstool" );
              }
    
              if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
                $values['cols'][ DOPT__TABLE_COL_SITE_AVERAGE ] = _x( "Average score", "table header", "do-planningstool" );
              }

              // add the default section titles to the collection
              foreach ( $formfields_data as $group_key => $value) {
                $key_grouplabel         = $group_key . '_group_label';
                $values[ 'default_titles' ][ $key_grouplabel ]  = $value->group_label;
              }
            }
          }
        }

        return $values;
  
      }
  
      //========================================================================================================
  
      /**
       * Register frontend styles
       */
      public function do_pt_frontend_register_frontend_style_script( ) {

        if ( !is_admin() ) {

          $postid               = get_the_ID();

          if ( ! $this->survey_data ) {
            $this->survey_data = $this->do_pt_data_get_user_answers_and_averages( $postid );
          }

          if ( ! $formfields_data ) {
            $formfields_data    = do_pt_data_get_survey_json();
          }

          $infooter = false;

          wp_enqueue_style( 'do-planningstool-frontend', DOPT__ASSETS_URL . 'css/do-planningstool.css', array(), DOPT__VERSION, $infooter );

          // contains minified versions of amcharts js files
          wp_enqueue_script( 'gcms-action-js', DOPT__ASSETS_URL . 'js/min/functions-min.js', array( 'jquery' ), DOPT__VERSION, $infooter );

          // get the graph for this user
          $mykeyname            = 'maturity_score';  
          $yourscore_color      = '#FF7700';  // see also @starcolor_gold
          $averagescore_color   = '#FF0000';  // as red as it gets

          if ( $this->survey_data ) {
            
            $averages = '';
            
            if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
              $averages = '{
            			"fillAlphas": 0.31,
            			"fillColors": "' . $averagescore_color . '",
            			"id": "AmGraph-2",
            			"lineColor": "' . $averagescore_color . '",
            			"title": "graph 2",
            			"valueField": "Lalal gemiddelde score",
            			"balloonText": "Gemiddelde score: [[value]]"
            		},';
            }

            
            $radardata = json_decode( '{
            	"type": "radar",
            	"categoryField": "' . $mykeyname . '",
            	"sequencedAnimation": false,
            	"fontFamily": "\'Montserrat light\',Helvetica,Arial,sans-serif",
            	"backgroundColor": "#FFFFFF",
            	"color": "#000000",
            	"handDrawScatter": 0,
            	"handDrawThickness": 3,
            	"percentPrecision": 1,
            	"processCount": 1004,
            	"theme": "dark",
            	"graphs": [
            		' . $averages . '
            		{
            			"balloonColor": "' . $yourscore_color . '",
            			"balloonText": "Jouw score: [[value]]",

                  "bullet": "custom",
                  "bulletBorderThickness": 1,
                  "bulletOffset": -1,
                  "bulletSize": 18,
                  "customBullet": "' . DOPT__ASSETS_URL . '/images/star.svg",
                  "customMarker": "",                  
                                        			
            			"fillAlphas": 0.15,
            			"fillColors": "' . $yourscore_color . '",
            			"id": "AmGraph-1",
            			"lineColor": "' . $yourscore_color . '",
            			"valueField": "' . _x( "your score", "labels", "do-planningstool" ) . '"
            		}
            	],
            	"guides": [],
            	"valueAxes": [
            		{
            			"axisTitleOffset": 20,
            			"id": "ValueAxis-1",
            			"minimum": 0,
            			"maximum": 5,
            			"zeroGridAlpha": 2,
            			"axisAlpha": 0.76,
            			"axisColor": "#6B6B6B",
            			"axisThickness": 2,
            			"dashLength": 0,
            			"fillAlpha": 0.49,
            			"gridAlpha": 0.68,
            			"gridColor": "#6B6B6B",
            			"minorGridAlpha": 0.4,
            			"minorGridEnabled": false
            		},
            		{
            			"id": "ValueAxis-2",
            			"dashLength": 0,
            			"fillAlpha": 0.43,
            			"gridAlpha": 0.44,
            			"gridColor": "' . $yourscore_color . '",
            			"minorGridAlpha": 0.32
            		}
            	],
            	"allLabels": [],
            	"balloon": {
            		"borderAlpha": 0.24,
            		"color": "#9400D3"
            	},
            	"titles": [],
            	"dataProvider": [
            		{
            			"jouw score": "4.2",
            			"gemiddelde score": "5",
            			"Score": "Eerste dinges"
            		},
            		{
            			"jouw score": "2.4",
            			"gemiddelde score": "4",
            			"Score": "Tweede dinges"
            		},
            		{
            			"jouw score": "3.5",
            			"gemiddelde score": "2",
            			"Score": "Derde dinges"
            		},
            		{
            			"jouw score": "2.8",
            			"gemiddelde score": "1",
            			"Score": "Vierde dinges"
            		},
            		{
            			"jouw score": "4",
            			"gemiddelde score": 2,
            			"Score": "Vijfde dinges"
            		}
            	]
            }
            ' );
          

            $columncounter  = 0;
            $rowcounter     = 0;

            $radardata->graphs[ ( DOPT__TABLE_COL_USER_AVERAGE - 1 ) ]->valueField     = $this->survey_data['cols'][ DOPT__TABLE_COL_USER_AVERAGE ] ;
            $radardata->graphs[ ( DOPT__TABLE_COL_USER_AVERAGE - 1 ) ]->balloonText    = $this->survey_data['cols'][ DOPT__TABLE_COL_USER_AVERAGE ] . ': [[value]]';

            if ( DOPT__FRONTEND_SHOW_AVERAGES ) {

              $radardata->graphs[ ( DOPT__TABLE_COL_SITE_AVERAGE - 1 ) ]->valueField   = $this->survey_data['cols'][ DOPT__TABLE_COL_SITE_AVERAGE ] ;
              $radardata->graphs[ ( DOPT__TABLE_COL_SITE_AVERAGE - 1 ) ]->balloonText  = $this->survey_data['cols'][ DOPT__TABLE_COL_SITE_AVERAGE ] . ': [[value]]';
              
            }
            else {
              unset( $radardata->graphs[ 1 ] );
            }

            $columncounter  = 0;

            $radardata->dataProvider = array();
          
            foreach( $this->survey_data['rows'] as $rowname => $rowvalue ) {
          
              $jouwscore        = isset( $rowvalue[ DOPT__TABLE_COL_USER_AVERAGE ] ) ? $rowvalue[ DOPT__TABLE_COL_USER_AVERAGE ] : 0;
              $gemiddeldescore  = isset( $rowvalue[ DOPT__TABLE_COL_SITE_AVERAGE ] ) ? $rowvalue[ DOPT__TABLE_COL_SITE_AVERAGE ] : 0;
          
              $columncounter = 0;
          
              foreach( $this->survey_data['cols'] as $columname => $columnsvalue ) {

                $rowname_translated = gcms_aux_get_value_for_cmb2_key( $rowname );
                
                if ( $rowname && $rowname_translated ) {
                
                  $key_grouplabel         = $rowname . '_group_label';
                  $collectionkey          = DOPT__PLUGIN_KEY . DOPT__PLUGIN_SEPARATOR . $rowname;
                  $default                = $formfields_data->$rowname->group_label;
                  
                  $radardata->dataProvider[$rowcounter]->$mykeyname = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $default, $collectionkey );
                  
                  if ( $columncounter == 2 ) {
                    $radardata->dataProvider[$rowcounter]->$columnsvalue = '';
                    if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
                      $radardata->dataProvider[$rowcounter]->$columnsvalue = $gemiddeldescore;
                    }
                  }
                  elseif ( $columncounter == 1 ) {
                    $radardata->dataProvider[$rowcounter]->$columnsvalue = $jouwscore;
                  }
                
                  $columncounter++;
                
                }
                
            
              }
          
              $rowcounter++;
          
            }  

            $thedata = wp_json_encode( $radardata );

          wp_add_inline_script( 'gcms-action-js', 
  '      try {
var amchart1 = AmCharts.makeChart( "amchart1", 
' . $thedata . ' );
}
catch( err ) { console.log( err ); } ' );


          } // if ( $this->survey_data ) {
        }
      }
  
      //========================================================================================================
  
      /**
       * Output the HTML
       */
      public function do_pt_frontend_display_survey_results( $postid ) {
        
        $returnstring     = '';

        if ( ! $this->survey_data ) {
          $this->survey_data = $this->do_pt_data_get_user_answers_and_averages( $postid );
        }

        if ( $this->survey_data ) {

          $returnstring .= $this->do_pt_frontend_get_graph( false );
          $returnstring .= $this->do_pt_frontend_get_interpretation( false );
          $returnstring .= $this->do_pt_frontend_get_tableview( false );      

        }
        else {
          $returnstring .= '<p>' . __( "Oopsy daisy. There are no data to display. The survey you requested is empty. The server may have made an error in saving or retrieving the data, or more likely: our developer botched up. It's not your fault.", "do-planningstool" ) . '</p>';
        }

        return $returnstring;
      
      }
  
      //========================================================================================================

      public function do_pt_frontend_append_comboboxes() {
      
        if ( DOPT__PLUGIN_USE_CMB2 ) {
  
          if ( ! defined( 'CMB2_LOADED' ) ) {
            return false;
            die( ' CMB2_LOADED not loaded ' );
            // cmb2 NOT loaded
          }

          add_shortcode( 'gcms_survey', 'do_pt_frontend_register_shortcode' );

          add_action( 'cmb2_init',        array( $this, 'do_pt_frontend_form_register_cmb2_form' ) );
          add_action( 'cmb2_after_init',  'do_pt_frontend_form_handle_posting' );
          
          add_action( 'cmb2_admin_init',  array( $this, 'do_pt_admin_form_register_cmb2_form_new' ) );

        }  // DOPT__PLUGIN_USE_CMB2
  
    }    

    //====================================================================================================

    /**
     * Register the form and fields for our front-end submission form
     */
    public function do_pt_frontend_form_register_cmb2_form() {

    	$cmb = new_cmb2_box( array(
    		'id'            => DOPT__METABOX_ID,
    		'title'         => __( "Questions and answers", "do-planningstool" ),
    		'object_types'  => array( 'post' ),
    		'hookup'        => false,
    		'save_fields'   => true,
        'cmb_styles'    => false, // false to disable the CMB stylesheet
    	) );

      $sectiontitle_prev  = '';
      $formfields_data    = do_pt_data_get_survey_json();

      if ( $formfields_data ) {

        // loop through the groups
        foreach ( $formfields_data as $group_key => $value ) {

          $collectionkey          = DOPT__PLUGIN_KEY . DOPT__PLUGIN_SEPARATOR . $group_key;
          $key_grouplabel         = $group_key . '_group_label';
          $key_group_description  = $group_key . '_group_description';
          $grouplabel             = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $value->group_label, $collectionkey );
          $groupdescription       = gcms_aux_get_value_for_cmb2_key( $key_group_description, $value->group_description, $collectionkey  );
          $groupquestions         = (array) $value->group_questions[0];

          $cmb->add_field( array(
          	'name'          => $grouplabel,
          	'description'   => $groupdescription,
          	'type'          => 'title',
          	'id'            => DOPT__CMBS2_PREFIX . '_section_' . $group_key
          ) );

          // loop through the questions
          foreach ( $groupquestions as $question_key => $question_single ) {

            $options          = array();
            $selectrandom     = array();
            $default          = '';
            $answers          = (array) $question_single->question_answers[0];
            $question_label   = $question_single->question_label;


            // get all possible answers
            foreach ( $answers as $answer_key => $answer ) {

              $this_answer_key              = $group_key . DOPT__PLUGIN_SEPARATOR . $question_key . DOPT__PLUGIN_SEPARATOR . $answer_key;

              $label = gcms_aux_get_value_for_cmb2_key( 
                $this_answer_key . DOPT__KEYS_LABEL, 
                $answer->answer_label,
                $collectionkey 
              );
              

              $options[ $this_answer_key  ] = $label;
              $selectrandom[]               = $this_answer_key;

            }

            // only set a random default if we are debugging
            if ( WP_DEBUG && DOPT__PLUGIN_DO_DEBUG ) {
              $default = $selectrandom[ array_rand( $selectrandom ) ];
            }

            $questionkey    = $group_key . DOPT__PLUGIN_SEPARATOR . $question_key;
            $question_label = $question_single->question_label;
            
            $label = gcms_aux_get_value_for_cmb2_key( 
                      $questionkey, 
                      $question_label, 
                      $collectionkey 
                    );

            // put it together
          	$cmb->add_field( array(
          		'name'    => $label,
          		'id'      => $group_key . DOPT__PLUGIN_SEPARATOR . $question_key,
          		'type'    => 'radio',
              'options' => $options,
              'default' => $default,
            	'attributes'  => array(
            		'required'    => 'required',
            	),
              
          	) );

          }
        }
      }
      else {
        // fout bij het ophalen van de formulierwaarden
        do_pt_aux_write_to_log('Fout bij ophalen van de formulierwaarden');
      }


      $yournamedefault = do_pt_get_post_or_cookie( DOPT__SURVEY_YOURNAME );
      $yourmaildefault = do_pt_get_post_or_cookie( DOPT__SURVEY_EMAILID );
      
      $cmb->add_field( array(
    		'name'    => _x( 'About you', 'About you section', "do-planningstool" ),
      	'type'    => 'title',
      	'id'      => DOPT__CMBS2_PREFIX . 'start_section_survey_user_data',
    		'desc'    => _x( 'We can send you a link to your survey results if you fill in your email address here.', 'About you section', "do-planningstool" ),
      ) );


    	$cmb->add_field( array(
    		'name'    => _x( 'Your name', 'About you section', "do-planningstool" ),
    		'id'      => DOPT__SURVEY_YOURNAME,
    		'type'    => 'text',
    		'desc'    => _x( "We store your name for a maximum of 3 years; it will be visible on the resultpage. Leave this empty if you don't want this.", 'About you section', "do-planningstool" ) . '<br>' . _x( 'Not required', 'About you section', "do-planningstool" ),
    		'default' => $yournamedefault,
    	) );
      
    	$cmb->add_field( array(
    		'name'    => _x( 'Your emailaddress', 'About you section', "do-planningstool" ),
    		'id'      => DOPT__SURVEY_EMAILID,
    		'type'    => 'text_email',
    		'desc'    => _x( 'We gebruiken dit e-mailadres om je een link te mailen naar jouw resultaatpagina.', 'About you section', "do-planningstool" ) . '<br>' . _x( 'Not required', 'About you section', "do-planningstool" ),
    		'default' => $yourmaildefault,
    	) );
    	

      $cmb->add_field( array(
    		'name'    => _x( 'Permission to store emailaddress', 'About you section', "do-planningstool" ),
    		'desc'    => _x( 'Yes, keep me up to date about any developments regarding the maturity model system. You may store my email address and name for at most 3 years. Only site-admins have access to these data.', 'About you section', "do-planningstool" ),
      	'id'   => DOPT__SURVEY_GDPR_CHECK,
      	'type' => 'checkbox',
      ) );    	
    	
    	$default = '';

      // organisation types
      $terms = get_terms( array(
        'taxonomy' => DOPT__SURVEY_CT_ORG_TYPE,
        'hide_empty' => false,
      ) );
    
      if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
        $options = array();
        $taxinfo = get_taxonomy( DOPT__SURVEY_CT_ORG_TYPE );
    
        foreach ( $terms as $term ) {
          $options[ $term->term_id ] = $term->name;
          // $default = $term->term_id;
        }
    
      	$cmb->add_field( array(
      		'name'    => $taxinfo->labels->singular_name,
      		'id'      => DOPT__QUESTION_PREFIX . DOPT__SURVEY_CT_ORG_TYPE,
      		'type'    => 'radio',
          'options' => $options,
          'default' => $default,
      	) );
    
      }




    }  

    //====================================================================================================

    private function do_pt_frontend_get_graph( $doecho = false ) {
      
      if ( ! $this->survey_data ) {
        $this->survey_data = $this->do_pt_data_get_user_answers_and_averages( $postid );
      }
      
      $return = '';
      
      if ( $this->survey_data ) {

        $titleid = 'grafiek_amchart';

        $return .= '<div class="radarchart" id="amchart1" style="min-height: 500px; width: 100%" aria-labelledby="' . $titleid . '"></div>';
        $return .= '<p id="' . $titleid . '">';
        $return .= '<span class="visuallyhidden">';
        $return .= _x( "Radar graph with your score in this survey.", "table description", "do-planningstool" );
        $return .= '</span>';


        if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
          $return .= _x( "Your score in red; average score in orange.", "table description", "do-planningstool" );
        }
        else {
          $return .= _x( "Your score in orange.", "table description", "do-planningstool" );
        }
        $return .= '</p>';

      }
      else {
        $return = '<p>' . _x( "No data available. Not your fault, blame the server.", "no data error", "do-planningstool" ) . '<p>';
      }
      

      if ( $doecho ) {
        echo $return;
      }
      else {
        return $return;
      }

    }

    //====================================================================================================

    private function do_pt_frontend_get_percentage( $score = 0, $max = 5, $doecho = false ) {

      $return = '';

      if ( $max ) {

        $displayvalue = ( 100 / $max ); // percentage            
        $return       = ( $score * $displayvalue ) . '%';
        
        $counter = 0;
        

        $return .= '<div class="star-rating">';
        
        while( $counter <  $max ) {
          if ( $score > $counter ) {
            $return .= '<svg style="display: none;" class="ja" height="494" viewBox="0 0 507 494" width="507" xmlns="http://www.w3.org/2000/svg"><path d="m253.5 408.75-156.6447697 84.207131 29.9164887-178.353566-126.72828059-126.310696 175.13417659-26.021434 78.322385-162.271435 78.322385 162.271435 175.134177 26.021434-126.728281 126.310696 29.916489 178.353566z" fill="#ffffff" fill-rule="evenodd" stroke="#000"/></svg>';
          }
          else {
            $return .= '<svg style="display: none;" class="nee" height="494" viewBox="0 0 507 494" width="507" xmlns="http://www.w3.org/2000/svg"><path d="m253.5 408.75-156.6447697 84.207131 29.9164887-178.353566-126.72828059-126.310696 175.13417659-26.021434 78.322385-162.271435 78.322385 162.271435 175.134177 26.021434-126.728281 126.310696 29.916489 178.353566z" fill="#ffffff" fill-rule="evenodd" stroke="#000"/></svg>';
          }
          $counter++;
          
        }
        $return .= '</div>';

      }
      
      if ( $doecho ) {
        echo $return;
      }
      else {
        return $return;
      }

    }

    //====================================================================================================

    private function do_pt_frontend_get_interpretation( $userdata = array(), $doecho = false ) {
      
      if ( ! $this->survey_data ) {
        $this->survey_data = $this->do_pt_data_get_user_answers_and_averages( $postid );
      }
      
      $return = '';
      
      if ( $this->survey_data ) {

        if ( isset( $this->survey_data['averages'] ) ) {

          $overall_average = number_format_i18n( $this->survey_data['averages']['overall'], 0 );

          $total_number_of_surveys  = get_option( DOPT__AVGS_NR_SURVEYS, 1 );
          $site_average             = get_option( DOPT__AVGS_OVERALL_AVG, 1 );
          $collectionkey            = DOPT__PLUGIN_KEY . DOPT__PLUGIN_SEPARATOR . $key;

          $punten     = sprintf( _n( '%s point', '%s points', $overall_average, "do-planningstool" ), $overall_average );
          $key        = 'SITESCORE';
          $fieldkey   = $key . GCMS_SCORESEPARATOR . number_format_i18n( $this->survey_data['averages']['overall'], 0 ); 

          $return     .= '<p>' . sprintf( __( 'Your average score was %s. ', "do-planningstool" ), $punten );
          $return     .= sprintf( _n( 'Thus far, we received %s survey.', 'Thus far, we received %s surveys. ', $total_number_of_surveys, "do-planningstool" ), $total_number_of_surveys );
          if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
            $return   .= sprintf( __( 'The overall average score is %s. ', "do-planningstool" ), $site_average );
          }          
          $return     .= '</p>';
          $return     .= '<p>' . gcms_aux_get_value_for_cmb2_key( $fieldkey, sprintf( __( 'No text available for %s.', "do-planningstool" ), $fieldkey ) ) . '</p>';
          $return     .= '<h2>' . __( 'Score per section', "do-planningstool" ) . '</h2>';
          
          $counter = 0;
          
          foreach( $this->survey_data['user_answers'] as $key => $value ){        

            $counter++;

            $jouwgemiddelde         = $this->survey_data['averages']['groups'][$key];
            $collectionkey          = DOPT__PLUGIN_KEY . DOPT__PLUGIN_SEPARATOR . $key;
            $jouwscore              = number_format_i18n( $jouwgemiddelde, 0 );
            $thesectionid           = sanitize_title( $key . "_" . $counter );
            $titleid                = sanitize_title( $thesectionid . '_title' );
            $key_grouplabel         = $key . '_group_label';
/*
          $key_group_description  = $group_key . '_group_description';
          $grouplabel             = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $value->group_label, $collectionkey );
          $groupdescription       = gcms_aux_get_value_for_cmb2_key( $key_group_description, $value->group_description, $collectionkey  );
*/  

            $fieldkey               = $key . GCMS_SCORESEPARATOR . $jouwscore; // g2_score_4
            $key_grouplabel         = $key . '_group_label';


            
            $titel                  = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $this->survey_data['default_titles'][ $key_grouplabel ], $collectionkey );

            $return .= '<section aria-labelledby="' . $titleid . '" id="' . $thesectionid . '" class="survey-result">';
            $return .= '<h3 class="rating-section-title"><span id="' . $titleid . '">' . $titel;
            $return .= ' <span class="visuallyhidden">' . _x( "Your score", "table description", "do-planningstool" ) . '</span></span> : ' . $this->do_pt_frontend_get_percentage( $jouwscore, DOPT__SCORE_MAX );
            $return .= '</h3>';
            $return .= '<p>' . gcms_aux_get_value_for_cmb2_key( $fieldkey ) . '</p>';
            $return .= '<details>';
            $return .= '  <summary>' . _x( "Review your answers", "interpretatie", "do-planningstool" ) . '</summary>';

            if ( $value ) {
              $return .= '<dl>';
              foreach( $value as $vragen => $antwoorden ){        
                $return .= '<dt>' . _x( "Question", "interpretatie", "do-planningstool" ) . '</dt>';
                $return .= '<dd>' . $antwoorden['question_label'] . '</dd>';

                $return .= '<dt>' . _x( "Your answer", "interpretatie", "do-planningstool" ) . '</dt>';
                $return .= '<dd>' . $antwoorden['answer_label'] . '</dd>';

                $score = sprintf( _n( '%s point', '%s points', $antwoorden['answer_value'], "do-planningstool" ), $antwoorden['answer_value'] );

                if ( DOPT__FRONTEND_SHOW_AVERAGES ) {

                  $return .= '<dt>' . _x( "Score", "interpretatie", "do-planningstool" ) . '</dt>';
                  $return .= '<dd>' . $score . '</dd>';
                  
                  $return .= '<dt class="space-me">' . _x( "Average score", "interpretatie", "do-planningstool" ) . '</dt>';
                  $return .= '<dd class="space-me">' . $antwoorden['answer_site_average'] . '</dd>';
                }
                else {
                  $return .= '<dt>' . _x( "Score", "interpretatie", "do-planningstool" ) . '</dt>';
                  $return .= '<dd class="space-me">' . $score . '</dd>';
                  
                }
              }
              $return .= '</dl>';
              
            }

            $return .= '</details>';
            $return .= '</section>';

          }

        }


      }
      else {
        $return .= '<p>' . _x( "No data available. Not your fault, blame the server.", "no data error", "do-planningstool" ) . '<p>';
      }      

      if ( $doecho ) {
        echo $return;
      }
      else {
        return $return;
      }

    }

    //====================================================================================================

    private function do_pt_frontend_get_tableview( $doecho = false ) {

      $return = '';

      if ( ! $this->survey_data ) {
        $this->survey_data = $this->do_pt_data_get_user_answers_and_averages( $postid );
      }
      
      if ( $this->survey_data ) {

        if ( isset( $this->survey_data['cols'] ) ) {

          $return .= '	<table class="gcms-score">' . "\n";
                  
          if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
            $return .= '<caption>' . _x( "Average score and your score per section", "table description", "do-planningstool" ) . "</caption>\n";
          }
          else {
            $return .= '<caption>' . _x( "Your score per section", "table description", "do-planningstool" ) . "</caption>\n";
          }
          $return .= '<tr>';
          foreach( $this->survey_data['cols'] as $key => $value ){        
            $return .= '<th scope="col">' . $value . "</th>\n";
          }
          $return .= "</tr>\n";
        
          if ( isset( $this->survey_data['rows'] ) ) {

            foreach( $this->survey_data['rows'] as $key => $value ){        
  
              $return .= '<tr>';
              $return .= '<th scope="row">' . $value[ DOPT__TABLE_COL_TH ] . '</th>';
  
              $return .= '<td>' . number_format_i18n( $value[ DOPT__TABLE_COL_USER_AVERAGE ], 1)  . "</td>";
              if ( DOPT__FRONTEND_SHOW_AVERAGES ) {
                  $return .= '<td>' . number_format_i18n( $value[ DOPT__TABLE_COL_SITE_AVERAGE ], 1)  . "</td>";
              }              
              $return .= "</tr>\n";
  
            }
          }
          $return .= "</table>\n";

        }
        else {
          $return .= '<p>' . _x( "No data available. Not your fault, blame the server.", "no data error", "do-planningstool" ) . '<p>';
        }

      
      }

      if ( $doecho ) {
        echo $return;
      }
      else {
        return $return;
      }
    
    }

    //====================================================================================================

    /**
    * Modify page content if using a specific page template.
    */
    public function do_pt_frontend_use_page_template() {
      
      global $post;
      
      $page_template  = get_post_meta( get_the_ID(), '_wp_page_template', true );
      
      if ( $this->templatefile == $page_template ) {
  
        add_action( 'genesis_entry_content',  'do_pt_do_frontend_form_submission_shortcode_echo', 15 );

        //* Force full-width-content layout
        add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
      
      }
    }

  
  }

//========================================================================================================

endif;

//========================================================================================================

add_action( 'plugins_loaded', array( 'DO_Planning_Tool', 'init' ), 10 );

//========================================================================================================

/**
 * Handle the do_pt_survey shortcode
 *
 * @param  array  $atts Array of shortcode attributes
 * @return string       Form html
 */

function do_pt_frontend_register_shortcode( $atts = array() ) {

	// Get CMB2 metabox object
	$cmb = do_pt_frontend_cmb2_get();

	// Get $cmb object_types
	$post_types = $cmb->prop( 'object_types' );

	// Initiate our output variable
	$output = '';

	// Get any submission errors
	if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {
		// If there was an error with the submission, add it to our ouput.
		$output .= '<h2>' . sprintf( __( 'Your survey is not saved; errors occurred: %s', "do-planningstool" ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</h2>';
	}


	// Get our form
	$output .= cmb2_get_metabox_form( $cmb, DOPT_MB2_RANDOM_OBJECT_ID, array( 'save_button' => __( "Submit", "do-planningstool" ) ) );

	return $output;

}

//========================================================================================================
  
/**
 * Gets the front-end-post-form cmb instance
 *
 * @return CMB2 object
 */
function do_pt_frontend_cmb2_get() {

	// Use ID of metabox in do_pt_frontend_form_register_cmb2_form
	$metabox_id = DOPT__METABOX_ID;

	// Post/object ID is not applicable since we're using this form for submission
	$object_id  = DOPT_MB2_RANDOM_OBJECT_ID;

	// Get CMB2 metabox object
	return cmb2_get_metabox( $metabox_id, $object_id );

}

//========================================================================================================
  
/**
 * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
 *
 * @return void
 */
function do_pt_frontend_form_handle_posting() {

	// If no form submission, bail
	if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
		return false;
	}

	// Get CMB2 metabox object
	$cmb = do_pt_frontend_cmb2_get();

	$post_data = array();

	// Get our shortcode attributes and set them as our initial post_data args
	if ( isset( $_POST['atts'] ) ) {
		foreach ( (array) $_POST['atts'] as $key => $value ) {
			$post_data[ $key ] = sanitize_text_field( $value );
		}
		unset( $_POST['atts'] );
	}

	// Check security nonce
	if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
		return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( "Security checks for your form submission failed. Your data will be discarded.", "do-planningstool" ) ) );
	}


	/**
	 * Fetch sanitized values
	 */
	$sanitized_values = $cmb->get_sanitized_values( $_POST );

  $datum  = date_i18n( get_option( 'date_format' ), current_time('timestamp') );

	// Check name submitted
	if ( empty( $_POST[ DOPT__SURVEY_YOURNAME ] ) ) {
    $sanitized_values[ DOPT__SURVEY_YOURNAME ] = __( "Your organisation's score", "do-planningstool" ) . ' (' . $datum . ')';
	}

  $rand   = $aantalenquetes . '-' . substr( md5( microtime() ),rand( 0, 26 ), 20 );	

	// Current user
	$user_id = get_current_user_id();

	// Set our post data arguments
	$post_data['post_title']  = $sanitized_values[ DOPT__SURVEY_YOURNAME ];
	$post_data['post_name']   = sanitize_title( $rand . '-' . $sanitized_values[ DOPT__SURVEY_YOURNAME ] );
  $post_data['post_author'] = $user_id ? $user_id : DOPT__SURVEY_DEFAULT_USERID;
  $post_data['post_status'] = 'publish';
  $post_data['post_type']   = DOPT__ACTIELIJN_CPT;

  $post_content = '';
  if ( $sanitized_values[ DOPT__SURVEY_YOURNAME ] ) {
    $post_content .= _x( 'Your name', 'naam', "do-planningstool" ) . '=' . $sanitized_values[ DOPT__SURVEY_YOURNAME ] . '<br>';
    setcookie( DOPT__SURVEY_YOURNAME , $sanitized_values[ DOPT__SURVEY_YOURNAME ], time() + ( 3600 * 24 * 60 ), '/');
  }
  
  if ( $sanitized_values[  DOPT__SURVEY_EMAILID  ] ) {
    $post_content .= _x( 'Your email address', 'email', "do-planningstool" ) . '=' . $sanitized_values[ DOPT__SURVEY_EMAILID ] . '<br>';
    setcookie( DOPT__SURVEY_EMAILID , $sanitized_values[ DOPT__SURVEY_EMAILID ], time() + ( 3600 * 24 * 60 ), '/');
  }
	




  // update the number of surveys taken
  update_option( DOPT__AVGS_NR_SURVEYS, get_option( DOPT__AVGS_NR_SURVEYS, 1) );
  

	// Create the new post
	$new_submission_id = wp_insert_post( $post_data, true );

	// If we hit a snag, update the user
	if ( is_wp_error( $new_submission_id ) ) {
		return $cmb->prop( 'submission_error', $new_submission_id );
	}
  

  $theurl   = get_permalink( $new_submission_id );

  // compose the mail
  if ( $sanitized_values[ DOPT__SURVEY_EMAILID ] ) {

    if ( filter_var( $sanitized_values[ DOPT__SURVEY_EMAILID ], FILTER_VALIDATE_EMAIL ) ) {    

      // the users mailaddress appears to be a valid mailaddress
      $mailtext = gcms_aux_get_value_for_cmb2_key( DOPT__TEXTEMAIL, _x( 'No mail text found', 'email', "do-planningstool" ) );
      $mailtext = str_replace( DOPT__URLPLACEHOLDER, '<a href="' . $theurl . '">' . $theurl . '</a>', $mailtext );
      $mailtext = str_replace( DOPT__NAMEPLACEHOLDER, $sanitized_values[ DOPT__SURVEY_YOURNAME ], $mailtext );

      $mailfrom_address = gcms_aux_get_value_for_cmb2_key( 'mail-from-address' );
      $mailfrom_name    = gcms_aux_get_value_for_cmb2_key( 'mail-from-name' );
  
      $subject  = gcms_aux_get_value_for_cmb2_key( 'mail-subject', _x( 'Link to your survey results', "email settings", "do-planningstool" ) );
      $headers  = array(
        'From: ' . $mailfrom_name . ' <' . $mailfrom_address . '>'
      );



      add_filter( 'wp_mail_content_type', 'do_pt_mail_set_html_mail_content_type' );
       
       
      wp_mail( $sanitized_values[ DOPT__SURVEY_EMAILID ], $subject, wpautop( $mailtext ), $headers );
       
      // Reset content-type to avoid conflicts -- https://core.trac.wordpress.org/ticket/23578
      remove_filter( 'wp_mail_content_type', 'do_pt_mail_set_html_mail_content_type' );
      
  
    }
  }

  if ( $sanitized_values[  DOPT__SURVEY_GDPR_CHECK  ] ) {
    // we are given permission to store the name and emailaddress
  }
  else {
  	// do not save the name nor email
  	unset( $sanitized_values[ DOPT__SURVEY_YOURNAME ] );
  	unset( $sanitized_values[ DOPT__SURVEY_EMAILID ] );
  	unset( $sanitized_values[ DOPT__SURVEY_GDPR_CHECK ] );
    
  }

  // save the extra fields as metadata
	$cmb->save_fields( $new_submission_id, 'post', $sanitized_values );
	update_post_meta( $new_submission_id, DOPT__FORMKEYS, $sanitized_values );

  gcms_data_reset_values( false );





	/*
	 * Redirect back to the form page with a query variable with the new post ID.
	 * This will help double-submissions with browser refreshes
	 */
	wp_redirect( get_permalink( $new_submission_id ) );


	/*
	 * Redirect back to the form page with a query variable with the new post ID.
	 * This will help double-submissions with browser refreshes
	 */
	wp_redirect( $theurl );
	
	exit;
	
}

//========================================================================================================

/**
 * Reset the statistics
 */
function gcms_data_reset_values( $givefeedback = true ) {
  
  if ( isset( $_POST['dofeedback'] ) ) {
    $givefeedback = true;
  }

  $log              = '';
  $subjects         = array();
  $allemetingen     = array();
  $formfields_data  = do_pt_data_get_survey_json();
  $counter          = 0;

  update_option( DOPT__AVGS_NR_SURVEYS, 0 );  
  update_option( DOPT__AVGS_OVERALL_AVG, 0 );  
  
  $args = array(
    'post_type'       => DOPT__ACTIELIJN_CPT,
    'posts_per_page'  => '-1',
		'post_status'     => 'publish',
    'order'           => 'ASC'
  );   
             

  if ( $formfields_data ) {

    foreach ( $formfields_data as $key => $value) {

      $optionkey = sanitize_title( $value->group_label );

      $subjects[] = 'Reset value for ' . $optionkey . ' = 0';
      
      update_option( $optionkey, '0' );  

    }
  }

  $the_query = new WP_Query( $args );

  if($the_query->have_posts() ) {
    
    while ( $the_query->have_posts() ) {
      
      $the_query->the_post();
      
      $counter++;
      $postid           = get_the_id();
      $subjects[]       = $counter . ' ' . DOPT__ACTIELIJN_CPT . ' = ' . get_the_title() . '(' . $postid . ')';
      
      $user_answers_raw     = get_post_meta( $postid );    	
      $user_answers         = maybe_unserialize( $user_answers_raw[DOPT__FORMKEYS][0] );
      
      foreach ( $user_answers as $key => $value) {

      
        $subjects[]   = '(' . $postid . ') ' . $key . '=' . $value . '.';
        $constituents = explode( DOPT__PLUGIN_SEPARATOR, $value ); // [0] = group, [1] = question, [2] = answer
        
        $group    = '';
        $question = '';
        $answer   = '';
        
        if ( isset( $constituents[0] ) ) {
          $group    = $constituents[0];
        }
        if ( isset( $constituents[1] ) ) {
          $question = $constituents[1];
        }
        if ( isset( $constituents[2] ) ) {
          $answer = $constituents[2];
        }

        $current_answer   = (array) $formfields_data->$group->group_questions[0]->$question->question_answers[0]->$answer;

        if ( intval( $current_answer['answer_value'] ) > 0 ) {
          $values[ $group . DOPT__PLUGIN_SEPARATOR . $question ][] = $current_answer['answer_value'];
          $values[ $group ][] = $current_answer['answer_value'];
        }
      }
    }        
  }

  // loop door alle keys en bereken hun gemiddelde
  foreach( $values as $key => $value ){        
    
    $systemaverage_score  = gcms_aux_get_average_for_array( $value, 1);
    $subjects[]           = 'nieuw gemiddelde voor ' . $key . ' = ' . $systemaverage_score . '.';

    $allemetingen[ $key ] = $systemaverage_score;
    
    // save het gemiddelde
    update_option( $key, $systemaverage_score );

  }

  // overall gemiddelde
  $average_overall  = gcms_aux_get_average_for_array( $allemetingen, 1);

  update_option( DOPT__AVGS_OVERALL_AVG, $average_overall );
  update_option( DOPT__AVGS_NR_SURVEYS, $counter );

  if ( $givefeedback ) {

  	wp_send_json( array(
  		'ajaxrespons_messages'  => $subjects,
  		'ajaxrespons_item'      => $log,
  	) );

  }

}


//========================================================================================================

if (! function_exists( 'do_pt_data_get_survey_json' ) ) {
  /**
   * Read a JSON file that contains the form definitions
   */
  function do_pt_data_get_survey_json() {

    $formfields_location = DOPT__BASE_URL . 'assets/antwoorden-vragen.json';
    
    $formfields_json = wp_remote_get( $formfields_location );

    if( is_wp_error( $formfields_json ) ) {
        return false; // Bail early
    }
 
     // Retrieve the data
    $formfields_body = wp_remote_retrieve_body( $formfields_json );
    $formfields_data = json_decode( $formfields_body );
    
    return $formfields_data;
  
  }    
}    

//========================================================================================================

add_action( 'wp_enqueue_scripts', 'do_pt_aux_remove_cruft', 100 ); // high prio, to ensure all junk is discarded

/**
 * Unhook DO_Planningtool styles from WordPress
 */
function do_pt_aux_remove_cruft() {

    wp_dequeue_style('cmb2-styles');
    wp_dequeue_style('cmb2-styles-css');

}

//========================================================================================================

if (! function_exists( 'do_pt_aux_write_to_log' ) ) {

	function do_pt_aux_write_to_log( $log ) {
		
    $subject = 'log';
    $subject .= ' (ID = ' . getmypid() . ')';

    $subjects = array();
    $subjects[] = $log;

		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( $subject . ' - ' .  print_r( $log, true ) );
			}
			else {
				error_log( $subject . ' - ' .  $log );
			}
		}
	}

}

//========================================================================================================

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string $key     Options array key
 * @param  mixed  $default Optional default value
 * @return mixed           Option value
 */
function gcms_aux_get_value_for_cmb2_key( $key = '', $default = false, $optionkey = DOPT__PLUGIN_KEY ) {

  $return = '';

  if ( function_exists( 'cmb2_get_option' ) ) {

    return cmb2_get_option( $optionkey, $key, $default );
    
  }
    
  // Fallback to get_option if CMB2 is not loaded yet.
  $opts = get_option( 'do_pt_admin_options_metabox', $default );
  
  $val = $default;
  
  if ( 'all' == $key ) {
    $val = $opts;
  } elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
    $val = $opts[ $key ];
  }

  return $val;

}

//========================================================================================================

/**
 * gcms_aux_get_average_for_array : get average values from an array
 * @param  array      $inputarray     Options array key
 * @param  number     $roundby  Optional default value
 * @return $return    0 or an average value
 */
function gcms_aux_get_average_for_array( $inputarray = '', $roundby = 0 ) {

  $return   = 0;
  $roundby  = intval( $roundby );

  if ( is_array( $inputarray ) ) {
    $return = round( ( array_sum( $inputarray ) / count( $inputarray ) ), $roundby );
  }

  return $return;

}

//========================================================================================================

if (! function_exists( 'dovardump' ) ) {
  
  function dovardump($data, $context = '', $echo = true ) {

    if ( WP_DEBUG && DOPT__PLUGIN_DO_DEBUG ) {
      $contextstring  = '';
      $startstring    = '<div class="debug-context-info">';
      $endtring       = '</div>';
      
      if ( $context ) {
        $contextstring = '<p>Vardump ' . $context . '</p>';        
      }

      do_pt_aux_write_to_log( print_r($data), true );      

      
      if ( $echo && DOPT__PLUGIN_OUTPUT_TOSCREEN ) {
        
        echo $startstring . '<hr>';
        echo $contextstring;        
        echo '<pre>';
        print_r($data);
        echo '</pre><hr>' . $endtring;
      }
      else {
        return '<hr>' . $contextstring . '<pre>' . print_r($data, true) . '</pre><hr>';
      }
    }        
  }
}

//========================================================================================================

if (! function_exists( 'dodebug' ) ) {
  
  function dodebug( $string, $tag = 'span' ) {
    
    if ( WP_DEBUG && DOPT__PLUGIN_DO_DEBUG ) {

      do_pt_aux_write_to_log( $string );      
      if ( DOPT__PLUGIN_OUTPUT_TOSCREEN ) {
        echo '<' . $tag . ' class="debugstring" style="border: 1px solid red; background: yellow; display: block; "> ' . $string . '</' . $tag . '>';
      }
    }
  }

}

//========================================================================================================

/**
 * Initialise translations
 */
function do_pt_init_load_plugin_textdomain() {

  //          load_plugin_textdomain( "do-planningstool", false, DOPT__PATH_LANGUAGES );
  load_plugin_textdomain( "do-planningstool", false, basename( dirname( __FILE__ ) ) . '/languages' );

}

//========================================================================================================


/**
 * Helper function for reading post values or cookie values
 */
function do_pt_get_post_or_cookie( $key = '', $default = '' ) {
  
  $return = '';

  if ( $default ) {
    $return = $default;
  }

  if ( $key ) {
    
    if ( isset( $_POST[ $key ] ) && ( ! empty( $_POST[ $key ] ) ) ) {
      $return = $_POST[ $key ];
    }
    elseif ( isset( $_COOKIE[ $key ] ) && ( ! empty( $_COOKIE[ $key ] ) ) ) {
      $return = $_COOKIE[ $key ];
    }
    
  }
  
  return $return;
  
}

//========================================================================================================

/**
 * Filter the mail content type.
 */
function do_pt_mail_set_html_mail_content_type() {
    return 'text/html';
}

//========================================================================================================

add_filter('the_post_navigation', 'do_pt_remove_post_navigation_for_survey');

function do_pt_remove_post_navigation_for_survey( $args ){

  if ( DOPT__ACTIELIJN_CPT == get_type() ) {
    return '';
  }
  else {
    return '';
  }  
    return $args;

}


//========================================================================================================

