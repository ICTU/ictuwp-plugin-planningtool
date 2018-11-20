<?php
/*
 * DO_Planningtool. 
 *
 * Plugin Name:         ICTU / WP Planning Tool digitaleoverheid.nl
 * Plugin URI:          https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/
 * Description:         Plugin voor digitaleoverheid.nl waarmee extra functionaliteit mogelijk wordt voor het tonen van een planning met actielijnen en gebeurtenissen.
 * Version:             0.0.1h
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
      public $version = '0.0.1h';
  
  
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

        define( 'DOPT_CT_PLANNINGLABEL',         "Planning label" );

        define( 'DOPT__QUESTION_PREFIX',         DOPT__ACTIELIJN_CPT . '_pf_' ); // prefix for cmb2 metadata fields
        define( 'DOPT__CMBS2_PREFIX',            DOPT__QUESTION_PREFIX . '_form_' ); // prefix for cmb2 metadata fields
        define( 'DOPT__FORMKEYS',                DOPT__CMBS2_PREFIX . 'keys' ); // prefix for cmb2 metadata fields
        
        define( 'DOPT__PLUGIN_DO_DEBUG',         true );
//        define( 'DOPT__PLUGIN_DO_DEBUG',         false );
//        define( 'DOPT__PLUGIN_OUTPUT_TOSCREEN',  false );
        define( 'DOPT__PLUGIN_OUTPUT_TOSCREEN',  true );
        define( 'DOPT__PLUGIN_USE_CMB2',         true ); 
        define( 'DOPT__PLUGIN_GENESIS_ACTIVE',   true ); // todo: inbouwen check op actief zijn van Genesis framework

        define( 'DOPT__ALGEMEEN_LABEL',          'ictudo_planning_label' ); 
        define( 'DOPT__ALGEMEEN_KEY',            'ictudo_planning_key' ); 
        define( 'DOPT__PLUGIN_KEY',              'ictudo_planning' ); 
 
        define( 'DOPT__NR_QUARTERS',              4 );

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

        if ( file_exists( dirname( __FILE__ ) . '/inc/dopt.acf-definitions-functions.php' ) ) {
          require_once dirname( __FILE__ ) . '/inc/dopt.acf-definitions-functions.php';
        }

        if ( file_exists( dirname( __FILE__ ) . '/inc/dopt.posttypes-taxonomies.php' ) ) {
          require_once dirname( __FILE__ ) . '/inc/dopt.posttypes-taxonomies.php';
        }

      
      }
  
      //========================================================================================================
  
      /**
       * filter for when the CPT is previewed
       */
      public function do_pt_frontend_filter_for_preview( $content = '' ) {

        global $post;

        if( in_the_loop() && is_single() && ( DOPT__ACTIELIJN_CPT == get_post_type() || DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {
//          return $content . do_pt_frontend_display_actielijn_info( $post->ID );
          return $content;
        }
        else {
          return $content;
        }
        
      }
  
    	//========================================================================================================
      /**
      * for single posts of the correct kind and type: NO post info
      *
      * @param  string  $post_info
      * @return string  $post_info
      */
      function filter_postinfo($post_info) {
        global $wp_query;
        global $post;
        

        if ( is_single() && ( DOPT__ACTIELIJN_CPT == get_post_type() || DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {
          return '';
        }
        else {
          return $post_info;
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

        add_action( 'init',                   'do_pt_init_register_post_type' );
        
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

  
      //========================================================================================================
  
      /**
       * Register frontend styles
       */
      public function do_pt_frontend_register_frontend_style_script( ) {

        if ( !is_admin() ) {

          $postid   = get_the_ID();
          $infooter = false;
          
          wp_enqueue_style( 'do-planning-tool-frontend', DOPT__ASSETS_URL . 'css/do-planning-tool.css', array(), DOPT__VERSION, $infooter );

          wp_enqueue_script( 'functions-frontend-min', DOPT__ASSETS_URL . 'js/functions-frontend.js', '', CHILD_THEME_VERSION, true );


        }
      }
  
    //====================================================================================================

    public function do_pt_frontend_filter_breadcrumb( $crumb, $args ) {
    
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
      if ( is_single() && ( DOPT__ACTIELIJN_CPT == get_post_type() || DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {

        // check the breadcrumb
        add_filter( 'genesis_single_crumb',   array( $this, 'do_pt_frontend_filter_breadcrumb' ), 10, 2 );
        add_filter( 'genesis_page_crumb',     array( $this, 'do_pt_frontend_filter_breadcrumb' ), 10, 2 );
        add_filter( 'genesis_archive_crumb',  array( $this, 'do_pt_frontend_filter_breadcrumb' ), 10, 2 ); 				

        add_filter( 'genesis_post_info',   array( $this, 'filter_postinfo' ), 10, 2 );


        if ( DOPT__ACTIELIJN_CPT == get_post_type() ) {
          add_action( 'genesis_entry_content',  'do_pt_do_frontend_pagetemplate_info_single_actielijn', 15 );
        }
        
        if ( DOPT__GEBEURTENIS_CPT == get_post_type() ) {
          add_action( 'genesis_entry_content',  'do_pt_do_frontend_pagetemplate_info_single_gebeurtenis', 15 );
        }

      }

    }

  }

//========================================================================================================

endif;

//========================================================================================================

add_action( 'plugins_loaded', array( 'DO_Planning_Tool', 'init' ), 10 );

//========================================================================================================
  
/**
 * Handles the front-end display. 
 *
 * @return void
 */
function do_pt_do_frontend_pagetemplate_info_single_gebeurtenis() {

  global $post;

  if ( is_single() && DOPT__GEBEURTENIS_CPT == get_post_type() ) {

    echo do_pt_frontend_display_actielijn_info( $post->ID, true, true );          
    
  }
  
}

//========================================================================================================
  
/**
 * Handles the front-end display. 
 *
 * @return void
 */
function do_pt_do_frontend_pagetemplate_info_single_actielijn() {

  global $post;

  if ( is_single() && DOPT__ACTIELIJN_CPT == get_post_type() ) {

    $planning = wp_get_post_terms( $post->ID, DOPT_CT_PLANNINGLABEL );

    if ( $planning ) {
      if ( $planning[0]->name ) {
        echo '<p>' . $planning[0]->name . '</p>';
      }
    }
    
    echo do_pt_frontend_display_actielijn_info( $post->ID, true, true );          

  }
  
}

//========================================================================================================
  
/**
 * Handles the front-end display. 
 *
 * @return void
 */
function do_pt_do_frontend_pagetemplate_add_actielijnen() {

  $acfid                    = get_the_id();
  $actielijnblokken         = get_field( 'actielijnen_per_thema', $acfid );
  $year_now                 = date("Y");
  $q_now                    = date("m");
  $year_start               = ( $year_now - 1 );
  $year_end                 = ( $year_now + 1 );
  $planning_page_start_jaar = get_field( 'planning_page_start_jaar', 'option');
  $planning_page_end_jaar   = get_field( 'planning_page_end_jaar', 'option');
  $unique_actielijnid       = 0;
  $emptycontentcounter      = 0;
  $numberofyears            = 0;
  $emsperyear               = 16;
  $emsperquarter            = 4;
  
  $dinges                   = 'gantt';
//  $dinges                   = 'table';

  if ( $q_now >= 10 ) {
    $q_now = 4;  
  }
  elseif ( $q_now >= 7 ) {
    $q_now = 3;  
  }
  elseif ( $q_now >= 4 ) {
    $q_now = 2;  
  }
  else {
    $q_now = 1;  
  }


  $actielijnen_array = array();

  if ( intval( $planning_page_start_jaar > 0 ) && ( intval( $planning_page_start_jaar ) < intval( $year_start ) ) ) {
    $year_start = $planning_page_start_jaar;  
  }
  if ( intval( $planning_page_end_jaar > 0 ) && ( intval( $planning_page_end_jaar ) > intval( $year_end ) ) ) {
    $year_end = $planning_page_end_jaar;  
  }



  if( have_rows('actielijnen_per_thema', $acfid ) ) {
  
    if ( 'gantt' == $dinges ) {
  
      
      // twee keer dezelfde loop: 
      // - 1e keer voor het ophalen van de mogelijke jaren
      // - 2e keer voor het tonen van gegevens
      

      // eerst alle mogelijke jaren ophalen
  
      foreach( $actielijnblokken as $actielijnblok ) {
        
        $select_actielijnen     = $actielijnblok[ 'actielijnen_per_thema_actielijnen' ];
        
        if( $select_actielijnen ) {

          foreach( $select_actielijnen as $select_actielijn ) {
            
            $actielijn_kwartaal_start_kwartaal  = get_field( 'actielijn_kwartaal_start_kwartaal', $select_actielijn->ID );
            $actielijn_kwartaal_start_jaar      = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID );
            $actielijn_kwartaal_eind_kwartaal   = get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID );
            $actielijn_kwartaal_eind_jaar       = get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID );
            $actielijn_info                     = array();
      
            if ( intval( $actielijn_kwartaal_start_jaar > 0 ) && ( intval( $actielijn_kwartaal_start_jaar ) < intval( $year_start ) ) ) {
              $year_start = $actielijn_kwartaal_start_jaar;  
            }
            
            if ( intval( $actielijn_kwartaal_eind_jaar > 0 ) && ( intval( $actielijn_kwartaal_eind_jaar ) > intval( $year_end ) ) ) {
              $year_end = $actielijn_kwartaal_eind_jaar;  
            }

            $actielijn_info['id']               = $select_actielijn->ID;
            $actielijn_info['permalink']        = get_the_permalink( $select_actielijn->ID );
            $actielijn_info['title']            = get_the_title( $select_actielijn->ID );
            $actielijn_info['datumreekstype']   = get_field( 'heeft_start-_of_einddatums', $select_actielijn->ID );

            $actielijn_info['active_start_at']  = '';
            $actielijn_info['active_end_at']    = '';


            $actielijnen_array[] = $actielijn_info;
      
          }
        }

      }
      
      $numberofyears = ( $year_end - $year_start ) + 1;
      
      // $yearstart en $yearend zijn nu bekend
      $planningstrook = '<div class="timescaleblock">';
      $currentyear    = $year_start;
      $currentquarter = 1;

      while ( intval( $currentyear ) <= intval( $year_end ) ) : 

        $extraclass_year = '';

        if ( $currentyear == $year_now ) {
          $extraclass_year = ' class="current"';
        } 
     
        $planningstrook .= '<div class="timescale-year"><span' . $extraclass_year . '>' . $currentyear . '</span>';
      
        while ( intval( $currentquarter ) <= intval( DOPT__NR_QUARTERS ) ) : 

          $extraclass_year = '';

          if ( ( $currentyear == $year_now ) && ( $currentquarter == $q_now ) ) {
            $extraclass_year = ' current';
          } 
      
          $planningstrook .= '<div class="timescale-quarteryear' . $extraclass_year . '">Q' . $currentquarter . '</div>';
          $currentquarter++;
        
        endwhile;
      
        $planningstrook .= '</div>';
        $currentquarter = 1;
        $currentyear++;
          
      endwhile;
      
      $planningstrook .= '</div>'; // class=timescaleblock



      // nu gegevens tonen
      
      $actielijnblok_counter = 0;
    
      foreach( $actielijnblokken as $actielijnblok ) {
        
        $actielijnblok_counter++;
  
        $actielijnblok_titel    = esc_html( $actielijnblok[ 'actielijnen_per_thema_titel' ] );
        $digibeterclass         = get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $actielijnblok[ 'actielijnen_per_thema_kleur' ] );
        $select_actielijnen     = $actielijnblok[ 'actielijnen_per_thema_actielijnen' ];
          
  //      echo '<p>1: Jaren: van ' . $year_start . ' tot ' . $year_end . ' (actielijnen: ' . count( $actielijnen_array ) . ')</p>';


        // 16em voor een jaar
        // 30em voor padding left
        $possiblewidth = ( ( $numberofyears * 16 ) + 30 );

        echo '<div id="' . sanitize_title( $actielijnblok_titel ) . '" class="programma ' . $digibeterclass . '" data-possiblewidth="' . $possiblewidth . 'em" data-possibleyears="' . $numberofyears . ' years">';      
        
        
        echo '<h2>' . $actielijnblok_titel . '</h2>';      

        echo '<div class="timescale-container">';
  

        $planningstrook2 = preg_replace('/class="timescaleblock"/', 'class="timescaleblock" id="timescaleblock_' . $actielijnblok_counter . '"', $planningstrook );          

        echo $planningstrook2;
        
        echo '<div class="actielijnen">';

        if( $select_actielijnen ) {
  
//        foreach( $actielijnen_array as $actielijn ) {
        foreach( $select_actielijnen as $select_actielijn ) {
            
            $currentyear    = $year_start;
            $startatyearq   = intval( $year_start . 1 );
            $endatyearq     = intval( $year_end . 4 );
            $unique_actielijnid++;


            $actielijn_kwartaal_start_kwartaal  = get_field( 'actielijn_kwartaal_start_kwartaal', $select_actielijn->ID );
            $actielijn_kwartaal_start_jaar      = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID );
            $actielijn_kwartaal_eind_kwartaal   = get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID );
            $actielijn_kwartaal_eind_jaar       = get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID );
            $planning                           = wp_get_post_terms( $select_actielijn->ID, DOPT_CT_PLANNINGLABEL );

            $kwartaal_start = preg_replace("/[^0-9]/", "", $actielijn_kwartaal_start_kwartaal );          
            $kwartaal_end   = preg_replace("/[^0-9]/", "", $actielijn_kwartaal_eind_kwartaal );          
  
            switch ( get_field( 'heeft_start-_of_einddatums', $select_actielijn->ID ) ) {
              
              case 'start_eind':
                $actielijn_info['active_start_at']  = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID ) . $kwartaal_start;
                $startatyearq   = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID ) . $kwartaal_start;
                $endatyearq     = get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID ) . $kwartaal_end;
                break;
            
              case 'start':
                $startatyearq   = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID ) . $kwartaal_start;
                break;
            
              case 'eind':
                $endatyearq     = get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID ) . $kwartaal_end;

                break;
            
            }      

            echo '<div id="actielijn_' . $select_actielijn->ID . '_' . $unique_actielijnid . '" class="actielijn-info">';
            echo '<p><a href="' . get_the_permalink( $select_actielijn->ID ) . '">' . get_the_title( $select_actielijn->ID ) . '</a>';

            echo '<span class="verbergmij">';

            if ( $planning ) {
              if ( $planning[0]->name ) {
                echo 'label: "' . $planning[0]->name . '", ';
              }
            }
            
            echo 'ingevoerde kwartalen: ' . get_field( 'actielijn_kwartaal_start_kwartaal', $select_actielijn->ID ) . '-' . get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID ) . ' - ' . get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID ) . '-' . get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID ) . '.';

            echo '</span>';
            
            echo '</p>';
            
            echo '<div class="planning ' . get_field( 'heeft_start-_of_einddatums', $select_actielijn->ID ) . '">';
    
            while ( intval( $currentyear ) <= intval( $year_end ) ) : 
            
              while ( intval( $currentquarter ) <= intval( DOPT__NR_QUARTERS ) ) : 
            
                $active = ' inactive';
                $length = '';
                $emptycontentcounter++;          
                
                $compare = intval( $currentyear . $currentquarter );
                
                if ( ( $compare >= $startatyearq ) && ( $compare <= $endatyearq ) ) {
                  $active = ' current';
                  
                  if ( $compare == $startatyearq ) {
                    $active .= ' start';
                  }
                  if ( $compare == $endatyearq ) {
                    $active .= ' end';
                  }
                  if ( ( $currentyear == $year_end ) && ( $currentquarter == DOPT__NR_QUARTERS ) ) {
                    $active .= ' last';
                  }
                  
                }
                
//                $emptycontent = $compare . ' s:' . $startatyearq . ' e:' . $endatyearq ;
                $emptycontent = '&nbsp;';
                
                if ( ' current start' == $active ) {
                  
                  $emwidth = 10;

//                $startatyearq   = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID ) . $kwartaal_start;
//                $endatyearq     = get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID ) . $kwartaal_end;

                  $rekenjaarstart   = substr( $startatyearq, 0, 4);
                  $rekenjaarend     = substr( $endatyearq, 0, 4);

                  $rekenq_start     = substr( $startatyearq, 4, 1);
                  $rekenq_end       = substr( $endatyearq, 4, 1);


                  $jaarlengte       = ( $rekenjaarend - $rekenjaarstart );
                  $kwartaallengte   = ( ( $rekenq_end - $rekenq_start ) + 1 );
                  $emwidth          = ( ( $jaarlengte * $emsperyear ) + ( $kwartaallengte * $emsperquarter ) );

                  
                  $length = ' style="width: ' . $emwidth . 'em;" data-rekenjaarstart="' . $rekenjaarstart . '" '.
                  'data-rekenjaarend="' . $rekenjaarend . '"';

                  if ( $planning ) {
    
                    if ( $planning[0]->name ) {
                      $emptycontent = $planning[0]->name;
                    }
                    
                  }
                  
//                  $emptycontent .= ' (' . $rekenjaarstart . '-q' . $rekenq_start . ' - ' . $rekenjaarend . '-q' . $rekenq_end . ': ' . $jaarlengte . ' jaar + ' . $kwartaallengte . ' kwartalen)';
                    
                }
            
                if ( ( ' current start' == $active ) ||
                    ( ' inactive' == $active ) ) {
                  echo '<div class="strook strook-' . $currentyear . '-q' . $currentquarter . $active . '"' . $length . '>' . $emptycontent . '</div>';    
                }
            
                $currentquarter++;
      
              endwhile;
            
              $currentquarter = 1;
              
              $currentyear++;
                
            endwhile;

            echo do_pt_frontend_display_actielijn_info( $select_actielijn->ID, false, true, $year_start, $year_end );          
            
            echo '</div>'; // class=planning
            echo '</div>'; // class=actielijn-info
    
          }
          
        }        
  
        echo '</div>'; // class=actielijnen
  
        echo '</div>'; // class=timescale-container;
  
        echo '</div>'; // class=programma
  
      }
      
      
    }    //  if ( 'gantt' == $dinges
    elseif ( 'table' == $dinges ) {
    
      foreach( $actielijnblokken as $actielijnblok ) {
  
        $actielijnblok_titel    = esc_html( $actielijnblok[ 'actielijnen_per_thema_titel' ] );
        $digibeterclass         = get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $actielijnblok[ 'actielijnen_per_thema_kleur' ] );
        $select_actielijnen     = $actielijnblok[ 'actielijnen_per_thema_actielijnen' ];

        echo '<table id="' . sanitize_title( $actielijnblok_titel ) . '" class="programma ' . $digibeterclass . '">';      
        echo '<caption>' . $actielijnblok_titel . '</caption>';      
  
        if( $select_actielijnen ) {
  
          echo '<tr>';
          echo '<th scope="col" id="th_col_actielijn">' . _x( 'Actielijn', 'tussenkopje', "do-planning-tool" ) . '</th>';
          echo '<th scope="col" id="th_col_planning">' . _x( 'Planning', 'tussenkopje', "do-planning-tool" ) . '</th>';
          echo '<th scope="col" id="th_col_gebeurtenis">' . _x( 'Gebeurtenissen', 'tussenkopje', "do-planning-tool" ) . '</th>';
  
          echo '</tr>';
  
          foreach( $select_actielijnen as $select_actielijn ) {
  
            echo '<tr>';

            echo '<th scope="row">';
            echo '<a href="' . get_the_permalink( $select_actielijn->ID ) . '">' . get_the_title( $select_actielijn->ID ) . '</a>';
            echo '</th>';

            echo '<td>';
            
            $actielijn_kwartaal_start_kwartaal  = get_field( 'actielijn_kwartaal_start_kwartaal', $select_actielijn->ID );
            $actielijn_kwartaal_start_jaar      = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID );
            $actielijn_kwartaal_eind_kwartaal   = get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID );
            $actielijn_kwartaal_eind_jaar       = get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID );
  
            if ( ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) || ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar )  ) {

              $planning = wp_get_post_terms( $select_actielijn->ID, DOPT_CT_PLANNINGLABEL );
  
              if ( $planning ) {
                if ( $planning[0]->name ) {
                  echo '<br>' . $planning[0]->name;
                }
              }
  
              if ( ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) || ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar ) ) {
      
                if ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) {
          
                  if ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar ) {
                    echo _x( 'Van', 'tussenkopje', "do-planning-tool" ) . ' ';
                  }
                  else {
                    echo _x( 'Start', 'tussenkopje', "do-planning-tool" ) . ': ';
                  }
          
                  echo $actielijn_kwartaal_start_kwartaal . '-' . $actielijn_kwartaal_start_jaar;
  
                }
                
                if ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar ) {
          
                  if ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) {
                    echo ' ' . _x( 'tot', 'tussenkopje', "do-planning-tool" ) . ' ';
                  }
                  else {
                    echo _x( 'Eind', 'tussenkopje', "do-planning-tool" ) . ': ';
                  }
          
                  echo $actielijn_kwartaal_eind_kwartaal . '-' . $actielijn_kwartaal_eind_jaar;
                  
                }

              }

            }
      
            echo ' </td>';

            echo '<td>';
            echo do_pt_frontend_display_actielijn_info( $select_actielijn->ID, false, true, $year_start, $year_end );          
            echo ' </td>';
  
            echo '</tr>';
  
          }
  
        }
  
        echo '</table>';

      }
  
    }

  }
  else { //   if( have_rows('actielijnen_per_thema', $acfid ) ) {

    echo '<p>' . __( "Geen actielijnen geselecteerd voor deze pagina, dus alle actielijnen per digibeter-kleur worden getoond.", "do-planning-tool" ) . '</p>';
    
  
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
            echo do_pt_frontend_display_actielijn_info( $select_actielijn->ID, false, true, $year_start, $year_end );          
      
          endwhile;
  
          echo '</div>';
      
        }
      }
    }
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

// add custom tax support to actielijn
add_action( 'admin_init', 'do_pt_cpt_tag_support' );

function do_pt_cpt_tag_support() {
  
  if ( defined( 'RHSWP_CT_DIGIBETER' ) &&  defined( 'DOPT__ACTIELIJN_CPT' ) ) {

    register_taxonomy_for_object_type( RHSWP_CT_DIGIBETER, DOPT__ACTIELIJN_CPT );
  
  }

}

//========================================================================================================

/**
 * Append related actielijnen or gebeurtenissen
 */
 
function do_pt_frontend_display_actielijn_info( $postid, $showheader = false, $doecho = false, $startyear = '', $endyear = '', $titletext = '' ) {

  $returnstring = '';


  if( get_field( 'related_gebeurtenissen_actielijnen', $postid ) ) {
    
    // alleen header teruggeven als er uberhaupt iets te melden is

    if ( DOPT__GEBEURTENIS_CPT == get_post_type( $postid ) ) {

      if ( ! $titletext ) {
        $titletext = _x( 'Actielijnen', 'tussenkopje', "do-planning-tool" );
      }
  
      if ( $showheader ) {
        $returnstring = '<h2>' . $titletext . '</h2>';
      }
  
      $gebeurtenis_datum     = get_field( 'gebeurtenis_datum', $postid );
  
      if ( $gebeurtenis_datum ) {
        echo '<p>' . date_i18n( get_option( 'date_format' ), strtotime( $gebeurtenis_datum ) ) . '</p>';
      }
  
    }
    elseif ( DOPT__ACTIELIJN_CPT == get_post_type( $postid ) ) {

      if ( ! $titletext ) {
        $titletext = _x( 'Gebeurtenissen', 'tussenkopje', "do-planning-tool" );
      }
  
      if ( $showheader ) {
        $returnstring = '<h2>' . $titletext . '</h2>';
      }
  
    } 

  
    $returnstring .= '<ul>';
  
    $relatedobjects = get_field('related_gebeurtenissen_actielijnen', $postid );
    
    $sortthisarray = array();
      
    foreach ( $relatedobjects as $relatedobject ) {      
      $datumveld = get_field( 'gebeurtenis_datum', $relatedobject->ID );
      if ( $datumveld ) {
        $sortthisarray[ strtotime( $datumveld ) ] = $relatedobject->ID;
      }
      else {
        $sortthisarray[ $relatedobject->ID ] = $relatedobject->ID;
      }
    }      

    ksort( $sortthisarray );    

    foreach( $sortthisarray as $key => $value ){

      $gebeurtenis_datum    = '';
      $title                = '';
      $styling              = '';
      $daydiff              = '';
  
      if ( $key ) {
        $title = date_i18n( get_option( 'date_format' ), $key ) ;
      }

      if ( intval( $endyear ) > 0 ) {
        
        $yearevent        = date_i18n( "Y", $key );
        $daydiff          = dateDiff( ( $yearevent + 1 ) . '-01-01', date_i18n( get_option( 'date_format' ), $key )  );
        $emdag            = round( ( 16 / 365 ), 2 ); // 16em per jaar
        $extradagen       = round( ( $daydiff * $emdag ), 2);
        
        $styling = ' style="padding-right: ' . ( ( ( intval( $endyear ) - $yearevent ) * 16 ) + $extradagen ) . 'em;"';

      }

  
      $returnstring .= '<li' . $styling . '><a href="' . get_permalink( $value ) . '" title="' . $title . '">' . get_the_title( $value ) . '</a></li>';
  
    }
  
    $returnstring .= '</ul>';
  
  }
  else {
//    dodebug( 'geen data voor ' . $postid );
  }
  
  if ( $doecho ) {
    echo $returnstring;
  }
  else {
    return $returnstring;
  }


}

//========================================================================================================

function dateDiff ($d1, $d2) {
// Return the number of days between the two dates:

  return round(abs(strtotime($d1)-strtotime($d2))/86400);

}  // end function dateDiff

//========================================================================================================

add_filter('acf/update_value/name=related_gebeurtenissen_actielijnen', 'bidirectional_acf_update_value', 10, 3);

//========================================================================================================


