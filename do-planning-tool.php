<?php
/*
 * DO_Planningtool. 
 *
 * Plugin Name:         ICTU / WP Planning Tool digitaleoverheid.nl
 * Plugin URI:          https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/
 * Description:         Plugin voor digitaleoverheid.nl waarmee extra functionaliteit mogelijk wordt voor het tonen van een planning met actielijnen en gebeurtenissen.
 * Version:             0.0.1
 * Version description: First set up of plugin files.
 * Author:              Paul van Buuren
 * Author URI:          https://wbvb.nl
 * License:             GPL-2.0+
 *
 * Text Domain:         do-planning-tool
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

      public $actielijn_answers = null;

      public $actielijn_data = null;

  
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

      }
  
      //========================================================================================================
  
      /**
       * Define DO_Planningtool constants
       */
      private function define_constants() {
  
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
  
        define( 'DOPT__VERSION',                 $this->version );
        define( 'DOPT__FOLDER',                  'do-planning-tool' );
        define( 'DOPT__BASE_URL',                trailingslashit( plugins_url( DOPT__FOLDER ) ) );
        define( 'DOPT__ASSETS_URL',              trailingslashit( DOPT__BASE_URL ) );
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

        define( 'DOPT__METABOX_ID',              'front-end-post-form' ); 
        define( 'DOPT_MB2_RANDOM_OBJECT_ID',     'fake-oject-id' ); 

        define( 'DOPT__ALGEMEEN_LABEL',          'ictudo_planning_label' ); 
        define( 'DOPT__ALGEMEEN_KEY',            'ictudo_planning_key' ); 
        define( 'DOPT__PLUGIN_KEY',              'ictudo_planning' ); 
        
        define( 'DOPT__PLUGIN_SEPARATOR',        '__' );

        define( 'DOPT__SCORESEPARATOR',          DOPT__PLUGIN_SEPARATOR . 'score' . DOPT__PLUGIN_SEPARATOR );
 
        define( 'DOPT__SURVEY_EMAILID',          'submitted_your_email' );
        define( 'DOPT__SURVEY_YOURNAME',         'submitted_your_name' );
        define( 'DOPT__SURVEY_GDPR_CHECK',       'gdpr_do_save_my_emailaddress' );

        define( 'DOPT__KEYS_VALUE',              '_value' );
        define( 'DOPT__KEYS_LABEL',              '_label' );

        define( 'DOPT__URLPLACEHOLDER',          '[[url]]' );
        define( 'DOPT__NAMEPLACEHOLDER',         '[[name]]' );

        $this->option_name  = 'ictudo_planning-option';
        

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
       * filter for when the CPT is previewed
       */
      public function do_pt_frontend_filter_for_preview( $content = '' ) {

        global $post;

        if( in_the_loop() && is_single() && ( DOPT__ACTIELIJN_CPT == get_post_type() || DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {
          return $content . do_pt_frontend_display_results( $post->ID );
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
        $this->templatefile   		= 'planningtool-template.php';

        add_action( 'init',                   array( $this, 'do_pt_init_register_post_type' ) );
        
        // add the page template to the templates list
        add_filter( 'theme_page_templates',   array( $this, 'do_pt_init_add_page_templates' ) );
        
        // activate the page filters
        add_action( 'template_redirect',      array( $this, 'do_pt_frontend_use_page_template' )  );
        
        // admin settings
        add_action( 'admin_init',             array( $this, 'do_pt_admin_register_settings' ) );
        
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

        $typeUC_single = _x( "Actielijn", "labels", "do-planning-tool" );
        $typeUC_plural = _x( "Actielijnen", "labels", "do-planning-tool" );
        
        $typeLC_single = _x( "actielijn", "labels", "do-planning-tool" );
        $typeLC_plural = _x( "actielijnen", "labels", "do-planning-tool" );

      	$labels = array(
      		"name"                  => sprintf( '%s', $typeUC_single ),
      		"singular_name"         => sprintf( '%s', $typeUC_single ),
      		"menu_name"             => sprintf( '%s', $typeUC_single ),
      		"all_items"             => sprintf( _x( 'All %s', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"add_new"               => sprintf( _x( 'Add %s', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"add_new_item"          => sprintf( _x( 'Add new %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"edit"                  => _x( "Edit?", "labels", "do-planning-tool" ),
      		"edit_item"             => sprintf( _x( 'Edit %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"new_item"              => sprintf( _x( 'Add %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"view"                  => _x( "Show", "labels", "do-planning-tool" ),
      		"view_item"             => sprintf( _x( 'View %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"search_items"          => sprintf( _x( 'Search %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"not_found"             => sprintf( _x( 'No %s available', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"not_found_in_trash"    => sprintf( _x( 'No %s in trash', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"parent"                => _x( "Parent", "labels", "do-planning-tool" ),
      		
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
          "menu_icon"             => "dashicons-calendar",          
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

        $typeUC_single = _x( "Gebeurtenis", "labels", "do-planning-tool" );
        $typeUC_plural = _x( "Gebeurtenissen", "labels", "do-planning-tool" );
        
        $typeLC_single = _x( "gebeurtenis", "labels", "do-planning-tool" );
        $typeLC_plural = _x( "gebeurtenissen", "labels", "do-planning-tool" );

      	$labels = array(
      		"name"                  => sprintf( '%s', $typeUC_single ),
      		"singular_name"         => sprintf( '%s', $typeUC_single ),
      		"menu_name"             => sprintf( '%s', $typeUC_single ),
      		"all_items"             => sprintf( _x( 'All %s', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"add_new"               => sprintf( _x( 'Add %s', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"add_new_item"          => sprintf( _x( 'Add new %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"edit"                  => _x( "Edit?", "labels", "do-planning-tool" ),
      		"edit_item"             => sprintf( _x( 'Edit %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"new_item"              => sprintf( _x( 'Add %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"view"                  => _x( "Show", "labels", "do-planning-tool" ),
      		"view_item"             => sprintf( _x( 'View %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"search_items"          => sprintf( _x( 'Search %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"not_found"             => sprintf( _x( 'No %s available', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"not_found_in_trash"    => sprintf( _x( 'No %s in trash', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"parent"                => _x( "Parent", "labels", "do-planning-tool" ),
      		
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
          "menu_icon"             => "dashicons-calendar",          
          "show_in_menu"          => true,
          "exclude_from_search"   => false,
          "capability_type"       => "post",
          "map_meta_cap"          => true,
          "hierarchical"          => false,
          "rewrite"               => array( "slug" => DOPT__GEBEURTENIS_CPT, "with_front" => true ),
          "query_var"             => true,
      		"supports"              => array( "title", "editor" ),					
    		);
      		
      	register_post_type( DOPT__GEBEURTENIS_CPT, $args );
        
              	
      	
      	


        $typeUC_single = _x( "Organisation type", "labels", "do-planning-tool" );
        $typeUC_plural = _x( "Organisation types", "labels", "do-planning-tool" );
        
        $typeLC_single = _x( "organisation type", "labels", "do-planning-tool" );
        $typeLC_plural = _x( "organisation types", "labels", "do-planning-tool" );

        // organisation types
      	$labels = array(

      		"name"                  => sprintf( '%s', $typeUC_single ),
      		"singular_name"         => sprintf( '%s', $typeUC_single ),
      		"menu_name"             => sprintf( '%s', $typeUC_single ),
      		"all_items"             => sprintf( _x( 'All %s', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"add_new"               => sprintf( _x( 'Add %s', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"add_new_item"          => sprintf( _x( 'Add new %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"edit"                  => _x( "Edit?", "labels", "do-planning-tool" ),
      		"edit_item"             => sprintf( _x( 'Edit %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"new_item"              => sprintf( _x( 'Add %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"view"                  => _x( "Show", "labels", "do-planning-tool" ),
      		"view_item"             => sprintf( _x( 'View %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"search_items"          => sprintf( _x( 'Search %s', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"not_found"             => sprintf( _x( 'No %s available', 'labels', "do-planning-tool" ), $typeLC_single ),
      		"not_found_in_trash"    => sprintf( _x( 'No %s in trash', 'labels', "do-planning-tool" ), $typeLC_plural ),
      		"parent"                => _x( "Parent", "labels", "do-planning-tool" ),
      		"archives"              => _x( "Edit?", "labels", "do-planning-tool" ),

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
//      	register_taxonomy( DOPT__SURVEY_CT_ORG_TYPE, array( DOPT__ACTIELIJN_CPT ), $args );

//      	flush_rewrite_rules();

      }
  
      //========================================================================================================
  
      /**
      * Hides the custom post template for pages on WordPress 4.6 and older
      *
      * @param array $post_templates Array of page templates. Keys are filenames, values are translated names.
      * @return array Expanded array of page templates.
      */
      function do_pt_init_add_page_templates( $post_templates ) {
      
        $post_templates[$this->templatefile]  = _x( 'Planning Tool Template', "naam template", "do-planning-tool" );    
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
    			__( 'General settings', "do-planning-tool" ),
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
          wp_enqueue_style( 'do-planning-tool-admin', DOPT__ASSETS_URL . 'css/do-planning-tool-admin.css', false, DOPT__VERSION );
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
          'title'   => __( 'Documentation', "do-planning-tool" ),
          'content' => "<p><a href='https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/documentation/' target='blank'>" . __( 'GC Maturity documentation', "do-planning-tool" ) . "</a></p>",
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
        echo '	<p>' .  _x( 'Hier onderhoud voor de actielijnen.', "admin", "do-planning-tool" ) . '</p>';
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
                  'url'               => _x( "URL", "js", "do-planning-tool" ),
                  'caption'           => _x( "Caption", "js", "do-planning-tool" ),
                  'new_window'        => _x( "New Window", "js", "do-planning-tool" ),
                  'confirm'           => _x( "Are you sure?", "js", "do-planning-tool" ),
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

?>

    	<table class="form-table" id="progress">
    		<tr>
    			<td>
    				<input id="startsync" type="button" class="button button-primary" value="<?php _e( 'Reset statistics', "do-planning-tool" ); ?>" />
    				<input id="clearlog" type="button" class="button button-secondary" value="<?php _e( 'Empty log', "do-planning-tool" ); ?>" />
    			</td>
    		</tr>
    	</table>
      <noscript style="background: red; padding: .5em; font-size: 120%;display: block; margin-top: 1em !important; color: white;">
        <strong><?php _e( 'Ehm, please allow JavaScript.', "do-planning-tool" );?></strong>
      </noscript>
      <div style="width: 100%; padding-top: 16px;" id="items">&nbsp;</div>
    	<div style="width: 100%; padding-top: 16px; font-style: italic;" id="log"><?php _e( 'Press the button!', "do-planning-tool" );?></div>
    
    
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
    				jQuery(this).val('<?php _e( 'Just a moment please', "do-planning-tool" );?>').prop('disabled', true);
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
    
          _button.val('<?php _e( 'Reset statistics', "do-planning-tool" ) ?>').prop('disabled', false);
    
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
    
    			jQuery(this).val('<?php _e( 'Just a moment please', "do-planning-tool" );?>').prop('disabled', true);
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
      	'title'           => esc_html__( 'Maturity score', "do-planning-tool" ),
      	'object_types'    => array( 'options-page' ),
      	'option_key'      => DOPT__PLUGIN_KEY, // The option key and admin menu page slug.
      	'icon_url'        => 'dashicons-admin-settings', // Menu icon. Only applicable if 'parent_slug' is left empty.
      	'capability'      => 'manage_options', // Cap required to view options-page.
        'tab_group'       => DOPT__PLUGIN_KEY,
        'tab_title'       => _x( 'Email settings &amp; general scoring', 'scoring menu', "do-planning-tool"),      	
        'display_cb'      => 'yourprefix_options_display_with_tabs',
      	'save_button'     => esc_html__( 'Save options', "do-planning-tool" ), 
      ) );
      /*
       * Options fields ids only need
       * to be unique within this box.
       * Prefix is not needed.
       */
      
      
        $tabscounter = 0;
        
        $key = 'SITESCORE';
        

        $sectiontitle_prev  = '';

        // fout bij het ophalen van de formulierwaarden
        do_pt_aux_write_to_log('Fout bij ophalen van de formulierwaarden');



        $cmb_options->add_field( array(
        	'name'          => _x( 'Email settings', "email settings", "do-planning-tool" ),
        	'type'          => 'title',
        	'id'            => DOPT__CMBS2_PREFIX . 'start_section_emailsection'
        ) );

        $cmb_options->add_field( array(
        	'name'          => _x( 'Sender email address', "email settings", "do-planning-tool" ),
      		'type'          => 'text',
        	'id'            => 'mail-from-address',
        	'default'       => _x( 'info@gebruikercentraal.nl', "email settings", "do-planning-tool" )
        ) );

        $cmb_options->add_field( array(
        	'name'          => _x( 'Sender email name', "email settings", "do-planning-tool" ),
      		'type'          => 'text',
        	'id'            => 'mail-from-name',
        	'default'       => _x( 'Gebruiker Centraal', "email settings", "do-planning-tool" )
        ) );

        $cmb_options->add_field( array(
        	'name'          => __( 'General scoring texts', "do-planning-tool" ),
        	'type'          => 'title',
        	'id'            => DOPT__CMBS2_PREFIX . 'start_section_scoring'
        ) );

        // reset
        $counter = 0;


  
    }    
  
      //========================================================================================================
  
      /**
       * Register frontend styles
       */
      public function do_pt_frontend_register_frontend_style_script( ) {

        if ( !is_admin() ) {

          $postid   = get_the_ID();
          $infooter = false;
          
          wp_enqueue_style( 'do-planning-tool-frontend', DOPT__ASSETS_URL . 'css/do-planning-tool.css', array(), DOPT__VERSION, $infooter );

        }
      }
  
    //====================================================================================================

    /**
     * Register the form and fields for our front-end submission form
     */
    public function do_pt_frontend_form_register_cmb2_form() {

    	$cmb = new_cmb2_box( array(
    		'id'            => DOPT__METABOX_ID,
    		'title'         => __( "Questions and answers", "do-planning-tool" ),
    		'object_types'  => array( 'post' ),
    		'hookup'        => false,
    		'save_fields'   => true,
        'cmb_styles'    => false, // false to disable the CMB stylesheet
    	) );

      $sectiontitle_prev  = '';

      // fout bij het ophalen van de formulierwaarden
      do_pt_aux_write_to_log('Fout bij ophalen van de formulierwaarden');

      $yournamedefault = do_pt_get_post_or_cookie( DOPT__SURVEY_YOURNAME );
      $yourmaildefault = do_pt_get_post_or_cookie( DOPT__SURVEY_EMAILID );
      
    	$cmb->add_field( array(
    		'name'    => _x( 'Your name', 'About you section', "do-planning-tool" ),
    		'id'      => DOPT__SURVEY_YOURNAME,
    		'type'    => 'text',
    		'desc'    => _x( "We store your name for a maximum of 3 years; it will be visible on the resultpage. Leave this empty if you don't want this.", 'About you section', "do-planning-tool" ) . '<br>' . _x( 'Not required', 'About you section', "do-planning-tool" ),
    		'default' => $yournamedefault,
    	) );
      
    	$cmb->add_field( array(
    		'name'    => _x( 'Your emailaddress', 'About you section', "do-planning-tool" ),
    		'id'      => DOPT__SURVEY_EMAILID,
    		'type'    => 'text_email',
    		'desc'    => _x( 'We gebruiken dit e-mailadres om je een link te mailen naar jouw resultaatpagina.', 'About you section', "do-planning-tool" ) . '<br>' . _x( 'Not required', 'About you section', "do-planning-tool" ),
    		'default' => $yourmaildefault,
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

    public function filter_breadcrumb( $crumb, $args ) {
    
      if ( $crumb ) {
        
        $span_before_start  = '<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';  
        $span_between_start = '<span itemprop="name">';  
        $span_before_end    = '</span>';  
        $loop               = rhswp_get_context_info();
        $berichtnaam        = get_the_title();

        $planning_page      = get_field( 'planning_page', 'option');
        $planning_page_id   = $planning_page->ID;
        
        if ( !$planning_page_id ) {
          $planning_page_id = get_option( 'page_for_posts' );
        }  
  
        if( ( is_single() && DOPT__ACTIELIJN_CPT == get_post_type() ) || 
            ( is_single() && DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {
  
  
        	if ( $planning_page_id ) {
        		return '<a href="' . get_permalink( $planning_page_id ) . '">' . get_the_title( $planning_page_id ) .'</a>' . $args['sep'] . ' ' . $berichtnaam;
        	}
        	else {
        		return $crumb;
        	}
      	}
      	else {
      		return $crumb;
      	}
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
  
        add_action( 'genesis_entry_content',  'do_pt_do_frontend_pagetemplate_add_actielijnen', 15 );

        //* Force full-width-content layout
        add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
      
      }

    	//=================================================
      if( ( is_single() && DOPT__ACTIELIJN_CPT == get_post_type() ) || 
          ( is_single() && DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {
  
        // check the breadcrumb
        add_filter( 'genesis_single_crumb',   array( $this, 'filter_breadcrumb' ), 10, 2 );
        add_filter( 'genesis_page_crumb',     array( $this, 'filter_breadcrumb' ), 10, 2 );
        add_filter( 'genesis_archive_crumb',  array( $this, 'filter_breadcrumb' ), 10, 2 ); 				

      }

    }

  }

//========================================================================================================

endif;

//========================================================================================================

add_action( 'plugins_loaded', array( 'DO_Planning_Tool', 'init' ), 10 );

//========================================================================================================

/**
 * Handle the shortcode
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
		$output .= '<h2>' . sprintf( __( 'Actielijn niet opgeslagen; errors occurred: %s', "do-planning-tool" ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</h2>';
	}


	// Get our form
	$output .= cmb2_get_metabox_form( $cmb, DOPT_MB2_RANDOM_OBJECT_ID, array( 'save_button' => __( "Submit", "do-planning-tool" ) ) );

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
function do_pt_do_frontend_pagetemplate_add_actielijnen() {


  $terms = get_terms( array(
    'taxonomy'    => RHSWP_CT_DIGIBETER,
    'hide_empty'  => true,
  ) );

  if ( $terms ) {

    foreach ( $terms as $term ) {

      $args = array(
          'post_type'       => DOPT__ACTIELIJN_CPT, // hiero
          'post_status'     => 'publish',
          'tax_query'       => array(
              array(
                'taxonomy'  => RHSWP_CT_DIGIBETER,
                'field'     => 'term_id',
                'terms'     => $term->term_id,
              )
          ),      
          'posts_per_page'  => -1,
        );

      $wp_queryposts = new WP_Query( $args );

    
      if ( $wp_queryposts->have_posts() ) {

        $digibeterclass  = get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $term->term_id );

        echo '<div class="programma ' . $digibeterclass . '">';      
        echo '<h2>' . $term->name . '</h2>';      

        $postcounter = 0;
    
        while ( $wp_queryposts->have_posts() ) : $wp_queryposts->the_post();
          $postcounter++;
          $theid = get_the_id();
          echo '<h3><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h3>';
          echo do_pt_frontend_display_results( $theid, false, true );          
          do_action( 'genesis_after_entry' );
    
        endwhile;

        echo '</div>';
    
      }
    }
  }

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
		return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( "Security checks for your form submission failed. Your data will be discarded.", "do-planning-tool" ) ) );
	}


	/**
	 * Fetch sanitized values
	 */
	$sanitized_values = $cmb->get_sanitized_values( $_POST );

  $datum  = date_i18n( get_option( 'date_format' ), current_time('timestamp') );

	// Check name submitted
	if ( empty( $_POST[ DOPT__SURVEY_YOURNAME ] ) ) {
    $sanitized_values[ DOPT__SURVEY_YOURNAME ] = __( "Your organisation's score", "do-planning-tool" ) . ' (' . $datum . ')';
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
    $post_content .= _x( 'Your name', 'naam', "do-planning-tool" ) . '=' . $sanitized_values[ DOPT__SURVEY_YOURNAME ] . '<br>';
    setcookie( DOPT__SURVEY_YOURNAME , $sanitized_values[ DOPT__SURVEY_YOURNAME ], time() + ( 3600 * 24 * 60 ), '/');
  }
  
  if ( $sanitized_values[  DOPT__SURVEY_EMAILID  ] ) {
    $post_content .= _x( 'Your email address', 'email', "do-planning-tool" ) . '=' . $sanitized_values[ DOPT__SURVEY_EMAILID ] . '<br>';
    setcookie( DOPT__SURVEY_EMAILID , $sanitized_values[ DOPT__SURVEY_EMAILID ], time() + ( 3600 * 24 * 60 ), '/');
  }

	// Create the new post
	$new_submission_id = wp_insert_post( $post_data, true );

	// If we hit a snag, update the user
	if ( is_wp_error( $new_submission_id ) ) {
		return $cmb->prop( 'submission_error', $new_submission_id );
	}
  

  $theurl   = get_permalink( $new_submission_id );


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

  //          load_plugin_textdomain( "do-planning-tool", false, DOPT__PATH_LANGUAGES );
  load_plugin_textdomain( "do-planning-tool", false, basename( dirname( __FILE__ ) ) . '/languages' );

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

add_filter('the_post_navigation', 'do_pt_remove_post_navigation_for_actielijn');

function do_pt_remove_post_navigation_for_actielijn( $args ){

  if ( DOPT__ACTIELIJN_CPT == get_type() ) {
    return '';
  }
  else {
    return '';
  }  
    return $args;

}

//========================================================================================================


if( function_exists('acf_add_local_field_group') ) {

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
  			'return_format' => 'j F Y',
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
  			'key' => 'field_5be9d869b3559',
  			'label' => 'Datum beschrijving (zichtbaar)',
  			'name' => 'actielijn_toon_datum',
  			'type' => 'text',
  			'instructions' => 'deze tekst is zichtbaar voor de gebruiker',
  			'required' => 1,
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
  			'key' => 'field_5be99a18c781e',
  			'label' => 'Kwartaal start (niet zichtbaar)',
  			'name' => 'actielijn_kwartaal_start',
  			'type' => 'text',
  			'instructions' => 'Format: yyyy-qx. Dus: 2018-q2 voor het tweede kwartaal van 2018.',
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
  			'key' => 'field_5be9cc4bd4b27',
  			'label' => 'Kwartaal eind (niet zichtbaar)',
  			'name' => 'actielijn_kwartaal_eind',
  			'type' => 'text',
  			'instructions' => 'Format: yyyy-qx. Dus: 2018-q2 voor het tweede kwartaal van 2018.',
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
  	'instruction_placement' => 'label',
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

// add custom tax support to actielijn
add_action( 'admin_init', 'do_pt_cpt_tag_support' );

function do_pt_cpt_tag_support() {
  
  if ( defined( 'RHSWP_CT_DIGIBETER' ) &&  defined( 'DOPT__ACTIELIJN_CPT' ) ) {

    register_taxonomy_for_object_type( RHSWP_CT_DIGIBETER, DOPT__ACTIELIJN_CPT );
  
  }

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

add_filter('acf/update_value/name=related_gebeurtenissen_actielijnen', 'bidirectional_acf_update_value', 10, 3);

//========================================================================================================

/**
 * Append related actielijnen or gebeurtenissen
 */
 
function do_pt_frontend_display_results( $postid, $showheader = false, $doecho = false ) {

  $returnstring = '';

  if( get_field( 'related_gebeurtenissen_actielijnen', $postid ) ) {

    if ( DOPT__GEBEURTENIS_CPT == get_post_type() ) {


      if ( $showheader ) {
        $returnstring = '<h2>' . _x( 'Actielijnen', 'tussenkopje', "do-planning-tool" ) . '</h2>';
      }

      $gebeurtenis_datum     = get_field( 'gebeurtenis_datum', $postid );

      if ( $gebeurtenis_datum ) {
        echo '<p>' . date_i18n( get_option( 'date_format' ), strtotime( $gebeurtenis_datum ) ) . '</p>';
      }

    }
    elseif ( DOPT__ACTIELIJN_CPT == get_post_type() ) {
      
      if ( $showheader ) {
        $returnstring = '<h2>' . _x( 'Gebeurtenissen', 'tussenkopje', "do-planning-tool" ) . '</h2>';
      }

      $actielijn_toon_datum     = get_field( 'actielijn_toon_datum', $postid );
      $actielijn_kwartaal_start = get_field( 'actielijn_kwartaal_start', $postid );
      $actielijn_kwartaal_eind  = get_field( 'actielijn_kwartaal_eind', $postid );

      if ( $actielijn_toon_datum || $actielijn_kwartaal_start || $actielijn_kwartaal_eind ) {
        echo '<p>';
        if ( $actielijn_toon_datum ) {
          echo $actielijn_toon_datum . '<br>';
        }
        if ( $actielijn_kwartaal_start ) {
          echo _x( 'Start', 'tussenkopje', "do-planning-tool" ) . ': ' . $actielijn_kwartaal_start . '<br>';
        }
        if ( $actielijn_kwartaal_eind ) {
          echo _x( 'Eind', 'tussenkopje', "do-planning-tool" ) . ': ' . $actielijn_kwartaal_eind . '<br>';
        }
        echo ' </p>';
      }
      
      
    } 

    $returnstring .= '<ul>';

    $relatedobjects = get_field('related_gebeurtenissen_actielijnen', $postid );
      
    foreach ( $relatedobjects as $relatedobject ) {

      $acfgebeurtenis_datum     = get_field( 'gebeurtenis_datum', $relatedobject->ID );
      $gebeurtenis_datum        = '';

      if ( $acfgebeurtenis_datum ) {
        $gebeurtenis_datum = date_i18n( get_option( 'date_format' ), strtotime( $acfgebeurtenis_datum ) );
      }

      $returnstring .= '<li><a href="' . get_permalink( $relatedobject->ID ) . '">' . $gebeurtenis_datum . ' - ' . get_the_title( $relatedobject->ID ) . '</a></li>';

    }

    $returnstring .= '</ul>';

  }
  
  if ( $doecho ) {
    echo $returnstring;
  }
  else {
    return $returnstring;
  }


}

//========================================================================================================


//========================================================================================================


