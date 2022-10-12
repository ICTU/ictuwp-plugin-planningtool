<?php
/*
 * DO_Planningtool.
 *
 * Plugin Name:         ICTU / WP Planning Tool digitaleoverheid.nl
 * Plugin URI:          https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/
 * Description:         Plugin voor digitaleoverheid.nl waarmee extra functionaliteit mogelijk wordt voor het tonen van een planning met actielijnen en gebeurtenissen.
 * Version:             1.3.3
 * Version description: .currentyear verbergen bij smallere scherm ter voorkoming van horizontale scroll.
 * Author:              Paul van Buuren
 * Author URI:          https://wbvb.nl
 * License:             GPL-2.0+
 *
 * Text Domain:         ictuwp-plugin-planningtool
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
		public $version = '1.3.3';


		/**
		 * @var DO_Planningtool
		 */
		public $gcmaturity = null;

		/**
		 * @var DO_Planningtool
		 */

		public $option_name = null;

		public $dopt_years_start = null;
		public $dopt_years_end = null;
		public $dopt_years_max_nr = null; // for setting the width of the containers and indicator
		public $dopt_array_data = null; // array for storing retrieved data to be used throughout this plugin


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

			$protocol = strtolower( substr( $_SERVER["SERVER_PROTOCOL"], 0, strpos( $_SERVER["SERVER_PROTOCOL"], '/' ) ) ) . '://';

			$this->option_name       = 'ictudo_planning-option';
			$this->dopt_years_start  = get_field( 'planning_page_start_jaar', 'option' );
			$this->dopt_years_end    = get_field( 'planning_page_end_jaar', 'option' );
			$this->dopt_years_max_nr = ( $this->dopt_years_end - $this->dopt_years_start );
			$this->dopt_array_data   = array();

			define( 'DOPT__VERSION', $this->version );
			define( 'DOPT__FOLDER', 'ictuwp-plugin-planningtool' );
			define( 'DOPT__BASE_URL', trailingslashit( plugins_url( DOPT__FOLDER ) ) );
			define( 'DOPT__ASSETS_URL', trailingslashit( DOPT__BASE_URL ) );
			define( 'DOPT__PATH', plugin_dir_path( __FILE__ ) );
			define( 'DOPT__PATH_LANGUAGES', trailingslashit( DOPT__PATH . 'languages' ) );
			define( 'DOPT__ACTIELIJN_CPT', "actielijn" );
			define( 'DOPT__GEBEURTENIS_CPT', "gebeurtenis" );

			define( 'DOPT__SURVEY_DEFAULT_USERID', 2600 ); // 't is wat, hardgecodeerde userids (todo: invoerbaar maken via admin)

			define( 'DOPT_CT_PLANNINGLABEL', "Planning label" );

			define( 'DOPT_CT_TREKKER', "trekker" );

			define( 'DOPT_CT_ONDERWERP', "beleidsonderwerp" );
			define( 'DOPT_CT_ONDERWERP_DEFAULT', "NL Digibeter" );

			define( 'DOPT__QUESTION_PREFIX', DOPT__ACTIELIJN_CPT . '_pf_' ); // prefix for cmb2 metadata fields
			define( 'DOPT__CMBS2_PREFIX', DOPT__QUESTION_PREFIX . '_form_' ); // prefix for cmb2 metadata fields
			define( 'DOPT__FORMKEYS', DOPT__CMBS2_PREFIX . 'keys' ); // prefix for cmb2 metadata fields

//        define( 'ADD_DEFAULT_TERM_ID',              false );
			define( 'ADD_DEFAULT_TERM_ID', true );

//        define( 'DOPT__PLUGIN_DO_DEBUG',         true );
			define( 'DOPT__PLUGIN_DO_DEBUG', false );

//        define( 'DOPT__PLUGIN_OUTPUT_TOSCREEN',  false );
			define( 'DOPT__PLUGIN_OUTPUT_TOSCREEN', true );

			define( 'DOPT__PLUGIN_GENESIS_ACTIVE', true ); // todo: inbouwen check op actief zijn van Genesis framework

			define( 'DOPT__ALGEMEEN_LABEL', 'ictudo_planning_label' );
			define( 'DOPT__ALGEMEEN_KEY', 'ictudo_planning_key' );
			define( 'DOPT__PLUGIN_KEY', 'ictudo_planning' );

			define( 'DOPT__NR_QUARTERS', 5 );


			define( 'DOPT_CSS_YEARWIDTH', 13 ); // 12ems per year + 1em margin right
			define( 'DOPT_CSS_QUARTERWIDTH', 3 );
			define( 'DOPT_CSS_PADDINGLEFT', 26 ); // basically DOPT_CSS_YEARWIDTH but then twice

			define( 'DOPT__ARCHIVE_CSS', 'dopt-header-css' );

			//define( 'DOPT_CSS_RADIALGRADIENT', true );
			define( 'DOPT_CSS_RADIALGRADIENT', false );

		}

		//========================================================================================================

		/**
		 * All DO_Planningtool classes
		 */
		private function plugin_classes() {

			return array(
				'DO_PT_SystemCheck' => DOPT__PATH . 'inc/dopt.systemcheck.class.php',
			);

		}

		//========================================================================================================

		/**
		 * Load required classes
		 */
		private function includes() {

			$autoload_is_disabled = defined( 'DOPT__AUTOLOAD_CLASSES' ) && DOPT__AUTOLOAD_CLASSES === false;

			if ( function_exists( "spl_autoload_register" ) && ! ( $autoload_is_disabled ) ) {

				// >= PHP 5.2 - Use auto loading
				if ( function_exists( "__autoload" ) ) {
					spl_autoload_register( "__autoload" );
				}
				spl_autoload_register( array( $this, 'autoload' ) );

			} else {
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

			if ( in_the_loop() && is_single() && ( DOPT__ACTIELIJN_CPT == get_post_type() || DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {
//          return $content . do_pt_frontend_display_actielijn_info( $post->ID );
				return $content;
			} else {
				return $content;
			}

		}

		//========================================================================================================

		/**
		 * for single posts of the correct kind and type: NO post info
		 *
		 * @param string $post_info
		 *
		 * @return string  $post_info
		 */
		function filter_postinfo( $post_info ) {
			global $wp_query;
			global $post;


			if ( is_single() && ( DOPT__ACTIELIJN_CPT == get_post_type() || DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {
				return '';
			} else {
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

			if ( isset( $classes[ $class_name ] ) && is_readable( $classes[ $class_name ] ) ) {
				echo 'require: ' . $classes[ $class_name ] . '<br>';
				die();
				require_once( $classes[ $class_name ] );
			}

		}

		//========================================================================================================

		/**
		 * Hook DO_Planningtool into WordPress
		 */
		private function do_pt_init_setup_actions() {


			// add a page temlate name
			$this->templates    = array();
			$this->templatefile = 'planningtool-template.php';

			add_action( 'init', 'do_pt_init_register_post_type' );

			// add the page template to the templates list
			add_filter( 'theme_page_templates', array( $this, 'do_pt_init_add_page_templates' ) );

			// activate the page filters
			add_action( 'template_redirect', array( $this, 'do_pt_frontend_use_page_template' ) );

			// admin settings
			add_action( 'admin_init', array( $this, 'do_pt_admin_register_settings' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'do_pt_frontend_register_frontend_style_script' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'do_pt_admin_register_styles' ) );


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
		 *
		 * @return array Expanded array of page templates.
		 */
		function do_pt_init_add_page_templates( $post_templates ) {

			$post_templates[ $this->templatefile ] = _x( 'Planningtool Digitale Overheid', "naam template", "ictuwp-plugin-planningtool" );

			return $post_templates;

		}

		//========================================================================================================


		/**
		 * Register the options page
		 *
		 * @since    1.1.5
		 */
		public function do_pt_admin_register_settings() {

			// Add a General section
			add_settings_section(
				$this->option_name . '_general',
				__( 'General settings', "ictuwp-plugin-planningtool" ),
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
				wp_enqueue_style( 'ictuwp-plugin-planningtool-admin', DOPT__ASSETS_URL . 'css/ictuwp-plugin-planningtool-admin.css', false, DOPT__VERSION );
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
					'title'   => __( 'Documentation', "ictuwp-plugin-planningtool" ),
					'content' => "<p><a href='https://github.com/ICTU/Digitale-Overheid---WordPress-plugin-Planning-Tool/documentation/' target='blank'>" . __( 'GC Maturity documentation', "ictuwp-plugin-planningtool" ) . "</a></p>",
				)
			);
		}

		//========================================================================================================

		/**
		 * Register frontend styles
		 */
		public function do_pt_frontend_register_frontend_style_script() {

			$header_css    = '';
			$acfid         = get_the_id();
			$page_template = get_post_meta( $acfid, '_wp_page_template', true );

			if ( ! is_admin() && ( $this->templatefile == $page_template ) ) {

				$actielijnblokken = get_field( 'actielijnen_per_thema', $acfid );

				$this->dopt_years_start = get_field( 'planning_page_start_jaar', 'option' );
				$this->dopt_years_end   = get_field( 'planning_page_end_jaar', 'option' );

				$year_now = date( "Y" );
				$q_now    = date( "m" );

				if ( $q_now >= 10 ) {
					// als huidige maand oktober of later is dan is het huidige kwartaal: 4
					$q_now = 4;
				} elseif ( $q_now >= 7 ) {
					// anders, als huidige maand juli of later is dan is het huidige kwartaal: 3
					$q_now = 3;
				} elseif ( $q_now >= 4 ) {
					// anders, als huidige maand april of later is dan is het huidige kwartaal: 2
					$q_now = 2;
				} else {
					$q_now = 1;
				}

				if ( intval( $this->dopt_years_start > 0 ) && ( intval( $this->dopt_years_start ) > intval( date( "Y" ) ) ) ) {
					$this->dopt_years_start = date( "Y" );
				}

				if ( ! $this->dopt_years_start ) {
					$this->dopt_years_start = date( "Y" );
				}
				if ( ! $this->dopt_years_end ) {
					$this->dopt_years_end = ( date( "Y" ) + 1 );
				}


				$this->dopt_years_max_nr = ( ( $this->dopt_years_end - $this->dopt_years_start ) + 1 );


				$infooter = true;

				$breakpoint = '821px';

				wp_enqueue_script( 'functions-frontend-min', DOPT__ASSETS_URL . 'js/functions-frontend.js', '', DOPT__VERSION, $infooter );

				$header_css .= "@media only screen and ( max-width: " . $breakpoint . " ) { ";
				$header_css .= ".currentkwartaal, ";
				$header_css .= ".programma .intervalheader { ";
				$header_css .= "display: none;  ";
				$header_css .= "visibility: hidden;  ";
				$header_css .= "}  ";
				$header_css .= "}  ";

				$header_css .= "@media only screen and ( min-width: " . $breakpoint . " ) { ";
				$header_css .= ".programma .intervalheader { ";
				$header_css .= "display: flex;  ";
				$header_css .= "visibility: visible;  ";
				$header_css .= "}  ";
				$header_css .= "}  "; // ?

				$args = array(
					'post_type'      => DOPT__ACTIELIJN_CPT,
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
				);

				$wp_query_actielijnen = new WP_Query( $args );


				if ( $wp_query_actielijnen->have_posts() ) {

					while ( $wp_query_actielijnen->have_posts() ) : $wp_query_actielijnen->the_post();
						$theid = get_the_id();

						$actielijn_kwartaal_start_jaar     = 0;
						$actielijn_kwartaal_eind_jaar      = 0;
						$actielijn_kwartaal_eind_kwartaal  = 0;
						$actielijn_kwartaal_start_kwartaal = 0;
						$temparray                         = array();

						$kwartaal_start = preg_replace( "/[^0-9]/", "", get_field( 'actielijn_kwartaal_start_kwartaal', $theid ) );
						$kwartaal_end   = preg_replace( "/[^0-9]/", "", get_field( 'actielijn_kwartaal_eind_kwartaal', $theid ) );

						switch ( get_field( 'heeft_start-_of_einddatums', $theid ) ) {

							case 'start_eind':
								$actielijn_kwartaal_start_kwartaal = $kwartaal_start;
								$actielijn_kwartaal_start_jaar     = get_field( 'actielijn_kwartaal_start_jaar', $theid );
								$actielijn_kwartaal_eind_kwartaal  = $kwartaal_end;
								$actielijn_kwartaal_eind_jaar      = get_field( 'actielijn_kwartaal_eind_jaar', $theid );
								break;

							case 'start':
								$actielijn_kwartaal_start_kwartaal = $kwartaal_start;
								$actielijn_kwartaal_start_jaar     = get_field( 'actielijn_kwartaal_start_jaar', $theid );
								break;

							case 'eind':
								$actielijn_kwartaal_eind_kwartaal = $kwartaal_end;
								$actielijn_kwartaal_eind_jaar     = get_field( 'actielijn_kwartaal_eind_jaar', $theid );
								break;

						}

						$temparray['type']                       = DOPT__ACTIELIJN_CPT;
						$temparray['heeft_start-_of_einddatums'] = get_field( 'heeft_start-_of_einddatums', $theid );
						$temparray['start_kwartaal']             = $actielijn_kwartaal_start_kwartaal;
						$temparray['eind_kwartaal']              = $actielijn_kwartaal_eind_kwartaal;
						$temparray['start_jaar']                 = $actielijn_kwartaal_start_jaar;
						$temparray['eind_jaar']                  = $actielijn_kwartaal_eind_jaar;

						if ( intval( $actielijn_kwartaal_start_jaar > 0 ) && ( intval( $actielijn_kwartaal_start_jaar ) < intval( $this->dopt_years_start ) ) ) {
							$this->dopt_years_start = $actielijn_kwartaal_start_jaar;
						}

						if ( intval( $actielijn_kwartaal_eind_jaar > 0 ) && ( intval( $actielijn_kwartaal_eind_jaar ) > intval( $this->dopt_years_end ) ) ) {
							$this->dopt_years_end = $actielijn_kwartaal_eind_jaar;
						}
						$this->dopt_array_data[ $theid ] = $temparray;

					endwhile;

					// RESET THE QUERY
					wp_reset_query();

				}

				$args = array(
					'post_type'      => DOPT__GEBEURTENIS_CPT,
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
				);

				$wp_query_gebeurtenissen = new WP_Query( $args );


				if ( $wp_query_gebeurtenissen->have_posts() ) {

					while ( $wp_query_gebeurtenissen->have_posts() ) : $wp_query_gebeurtenissen->the_post();

						$theid = get_the_id();

						$actielijn_kwartaal_start_jaar = 0;
						$actielijn_kwartaal_eind_jaar  = 0;
						$temparray                     = array();

						$date            = get_field( 'gebeurtenis_datum', $theid );
						$yeargebeurtenis = date_i18n( "Y", strtotime( $date ) );

						$temparray['type']                        = DOPT__GEBEURTENIS_CPT;
						$temparray['gebeurtenis_datum']           = $date;
						$temparray['gebeurtenis_geschatte_datum'] = get_field( 'gebeurtenis_geschatte_datum', $theid );

						if ( intval( $yeargebeurtenis > 0 ) && ( intval( $yeargebeurtenis ) < intval( $this->dopt_years_start ) ) ) {
							$this->dopt_years_start = $yeargebeurtenis;
						}

						if ( intval( $yeargebeurtenis > 0 ) && ( intval( $yeargebeurtenis ) > intval( $this->dopt_years_end ) ) ) {
							$this->dopt_years_end = $yeargebeurtenis;
						}
						$this->dopt_array_data[ $theid ] = $temparray;

					endwhile;

					// RESET THE QUERY
					wp_reset_query();

				}

				$this->dopt_years_max_nr = ( ( $this->dopt_years_end - $this->dopt_years_start ) + 1 );

				$header_css .= ".actielijnen { ";
				$header_css .= " width: " . ( ( $this->dopt_years_max_nr * DOPT_CSS_YEARWIDTH ) + DOPT_CSS_PADDINGLEFT ) . "em;";
				$header_css .= " max-width: 100%;";
				$header_css .= " overflow: hidden;";
				$header_css .= "} ";

				$header_css .= ".programma .timescale-container {";
				$header_css .= " max-width: " . ( ( $this->dopt_years_max_nr * DOPT_CSS_YEARWIDTH ) + DOPT_CSS_PADDINGLEFT ) . "em;";
				$header_css .= "} ";

				// de kwartaalbalk is 2.7em breed
				// een jaar is 3em breed.
				// dus de linkerafstand vanwege de jaren moet opgehoogd worden met ( 3em - 2.7em ) = .3
				$distanceyears     = ( ( ( $year_now - $this->dopt_years_start ) * DOPT_CSS_YEARWIDTH ) + .3 );
				$distancekwartalen = ( ( ( $q_now - 1 ) * DOPT_CSS_QUARTERWIDTH ) + DOPT_CSS_PADDINGLEFT );


				$header_css .= ".currentkwartaal {";
				if ( $year_now <= $this->dopt_years_end ) {

					// toon de kwartaalbalk alleen als we niet voorbij de gewenste periode zijn
					// dat wil zeggen het huidige jaar is kleiner of gelijk aan het bepaalde eindjaar ($this->dopt_years_end)

					$header_css .= " position: absolute; ";
					$header_css .= " left: " . ( $distanceyears + $distancekwartalen ) . "em; /* dopt_years_start: " . $this->dopt_years_start . ",  year_now: " . $year_now . ",  dopt_years_end: " . $this->dopt_years_end . ", distanceyears: " . $distanceyears . "*/  ";
					$header_css .= " top: 0;";
					$header_css .= " bottom: 0;";

				} else {
					$header_css .= " display:none; ";
				}

				$header_css .= "}  ";

				//----------------------------------------------------------------------------------------------------
				//----------------------------------------------------------------------------------------------------

				if ( DOPT_CSS_RADIALGRADIENT ) {

					//----------------------------------------------------------------------------------------------------
					//----------------------------------------------------------------------------------------------------

					$breedtelaatstekolom = 1;
					$aantaljaar          = 5;
					$aantalkwartalen     = 4;
					$kwartaalbreedte     = 3;
					$jaarcounter         = 1;
					$kwartaalcounter     = 1;
					$afstand             = ( ( $kwartaalbreedte * $aantalkwartalen ) + $breedtelaatstekolom );
					$kwartaalstart       = ( 2 * $afstand );
					$verspring           = .001;
					$rgba                = "rgba(156,156,156,.4)";
					$rgba2               = "red";
					$rgba3               = "green";

					$header_css .= ".programma .timescale-container {";
					$header_css .= "background: ";
					$header_css .= "  linear-gradient( ";
					$header_css .= "    90deg,  ";
					$header_css .= "    white,  ";
					$header_css .= "    white " . ( 2 * $afstand ) . "em";
					$header_css .= "  ),";

					$header_css .= "  linear-gradient( ";
					$header_css .= "    90deg,  ";

					$startat = ( 2 * $afstand );

					$header_css .= "    white, ";
					$header_css .= "    white " . $startat . "em, ";

					$verspringpx  = '1px';
					$verspringpx2 = '2px';
					$verspringpx3 = '3px';

					while ( $jaarcounter <= $aantaljaar ) {

						$kwartaalcounter = 1;

						while ( $kwartaalcounter <= $aantalkwartalen ) {

							if ( 1 == $jaarcounter && 1 == $kwartaalcounter ) {

								$appendstring = " white calc(" . $startat . "em + " . $verspringpx . "),
                  " . $rgba . " calc(" . ( $startat ) . "em + " . $verspringpx . "),
                  " . $rgba3 . " calc(" . ( $startat + $kwartaalbreedte ) . "em - " . $verspringpx . "),
                  white calc(" . ( $startat + $kwartaalbreedte ) . "em - " . $verspringpx . "),";

							} else {

								$appendstring = "white calc(" . $startat . "em),
                  " . $rgba . " calc(" . ( $startat ) . "em + " . $verspringpx2 . "),
                  " . $rgba2 . " calc(" . ( $startat + $kwartaalbreedte ) . "em - " . $verspringpx3 . "),
                  white calc(" . ( $startat + $kwartaalbreedte ) . "em ),";

							}

							$header_css .= $appendstring . " ";
							$kwartaalcounter ++;
							$startat = ( $startat + $kwartaalbreedte );

						}

						$startat = ( $startat + $breedtelaatstekolom );
						$jaarcounter ++;

					}

					$header_css .= "    white " . ( ( $this->dopt_years_max_nr * DOPT_CSS_YEARWIDTH ) + DOPT_CSS_PADDINGLEFT ) . "em";
					$header_css .= "  ),";

					$header_css .= "  linear-gradient( ";
					$header_css .= "    90deg,  ";
					$header_css .= "    white " . ( ( $this->dopt_years_max_nr * DOPT_CSS_YEARWIDTH ) + DOPT_CSS_PADDINGLEFT ) . "em,  ";
					$header_css .= "    white 100% ";
					$header_css .= "  );";

					$header_css .= "background-size: 2em 22em," . ( ( $this->dopt_years_max_nr * DOPT_CSS_YEARWIDTH ) + DOPT_CSS_PADDINGLEFT ) . "em 100em, 84em .5em;";
					$header_css .= "background-repeat: repeat-y, repeat-y, repeat-y;";

					$header_css .= "}  ";

				} // DOPT_CSS_RADIALGRADIENT

				//----------------------------------------------------------------------------------------------------
				//----------------------------------------------------------------------------------------------------

				$oneemday = round( ( 12 / 365 ), 6 ); // 12em per jaar


				foreach ( $this->dopt_array_data as $key => $value ) {

					$actielijn_kwartaal_start_jaar = 0;

					if ( DOPT__GEBEURTENIS_CPT == $value['type'] ) {

						$gebeurtenis_datum = '';
						$datetext          = '';
						$daydiff           = '';

						$yearevent = date_i18n( "Y", strtotime( $value['gebeurtenis_datum'] ) );
						$mnt_event = date_i18n( "m", strtotime( $value['gebeurtenis_datum'] ) );
						$day_event = date_i18n( "d", strtotime( $value['gebeurtenis_datum'] ) );

						if ( $yearevent ) {

							$yeardiff = 0;

							if ( $this->dopt_years_start < $yearevent ) {
								$yeardiff = ( $yearevent - $this->dopt_years_start );
							}

							$translate      = 0;
							$translate_year = ( intval( $yeardiff ) * ( DOPT_CSS_YEARWIDTH ) );

							$startdatum = ( $yearevent ) . '-01-01';
							$einddatum  = ( $yearevent ) . '-' . $mnt_event . '-' . $day_event;

							$daydiff        = dateDiff( $startdatum, $einddatum );
							$translate_days = round( ( $daydiff * $oneemday ), 2 );
							$translate      = ( $translate_year + $translate_days );

						}

						$header_css .= "@media only screen and ( min-width: " . $breakpoint . " ) { ";
						$header_css .= "li." . $value['type'] . '-' . $key . " { ";
						$header_css .= "margin-left: " . $translate . "em;";
						$header_css .= "} ";
						$header_css .= "}"; // breakpoint

					} elseif ( DOPT__ACTIELIJN_CPT == $value['type'] ) {

						$startatyearq   = 0;
						$start_kwartaal = 0;
						$endatyearq     = 0;
						$eind_kwartaal  = 0;
						$emwidth_start  = 0;
						$emwidth_eind   = 0;
						$yeardiff       = 0;

						switch ( $value['heeft_start-_of_einddatums'] ) {

							case 'start_eind':
								$startatyearq   = $value['start_jaar'];
								$start_kwartaal = $value['start_kwartaal'];
								$endatyearq     = $value['eind_jaar'];
								$eind_kwartaal  = $value['eind_kwartaal'];

								break;

							case 'start':
								$startatyearq   = $value['start_jaar'];
								$start_kwartaal = $value['start_kwartaal'];

								break;

							case 'eind':
								$endatyearq    = $value['eind_jaar'];
								$eind_kwartaal = $value['eind_kwartaal'];

								break;

						}

						if ( intval( $startatyearq . $start_kwartaal ) > intval( $this->dopt_years_start . 1 ) ) {
							$yeardiff      = intval( ( $startatyearq - $this->dopt_years_start ) );
							$emwidth_start = ( $yeardiff * DOPT_CSS_YEARWIDTH );

							if ( intval( $start_kwartaal ) > 1 ) {
								$emwidth_start = ( $emwidth_start + ( ( $start_kwartaal - 1 ) * DOPT_CSS_QUARTERWIDTH ) );
							}

						}


						if ( $endatyearq && ( intval( $endatyearq . $eind_kwartaal ) < intval( $this->dopt_years_end . 4 ) ) ) {
							$yeardiff     = intval( ( $this->dopt_years_end - $endatyearq ) );
							$emwidth_eind = ( $yeardiff * DOPT_CSS_YEARWIDTH );

							if ( intval( $eind_kwartaal ) < 4 ) {
								$emwidth_eind = ( $emwidth_eind + ( ( 4 - $eind_kwartaal ) * DOPT_CSS_QUARTERWIDTH ) );
							}

						}

						if ( ( $emwidth_start ) || ( $emwidth_eind ) ) {
							/*
							 * 				$header_css .= "@media only screen and ( min-width: " . $breakpoint . " ) { ";
											$header_css .= ".programma .intervalheader { ";
											$header_css .= "display: flex;  ";
											$header_css .= "visibility: visible;  ";
											$header_css .= "}  ";
											$header_css .= "}  "; // ?

							 */
							$header_css .= "@media only screen and ( min-width: " . $breakpoint . " ) { ";
							$header_css .= " ." . $value['type'] . '-' . $key . " .ganttbar { ";
							if ( $emwidth_start ) {
								$header_css .= "margin-left: " . $emwidth_start . "em; ";
							}
							if ( $emwidth_eind ) {
								$header_css .= "margin-right: " . $emwidth_eind . "em; ";
							}
							$header_css .= "}";
							$header_css .= "}"; // breakpoint

						}
					}
				}

				wp_enqueue_style( DOPT__ARCHIVE_CSS, DOPT__ASSETS_URL . 'css/ictuwp-plugin-planningtool.css', array(), DOPT__VERSION, 'all' );

				if ( $header_css ) {
					wp_add_inline_style( DOPT__ARCHIVE_CSS, $header_css );
				}

			}
		}

		//========================================================================================================

		/**
		 * Handles the front-end display.
		 *
		 * @return void
		 */

		public function do_pt_do_frontend_pagetemplate_add_actielijnen() {

			if ( ADD_DEFAULT_TERM_ID && WP_DEBUG && DOPT__PLUGIN_DO_DEBUG ) {

				$args = array(
					'post_type'      => DOPT__ACTIELIJN_CPT,
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
				);

				$actielijnen = new WP_query();
				$actielijnen->query( $args );

				if ( $actielijnen->have_posts() ) {

					echo '<ul>';

					while ( $actielijnen->have_posts() ) : $actielijnen->the_post();

						$post_id = get_the_id();

						$terms = wp_get_post_terms( $post_id, DOPT_CT_ONDERWERP );

						if ( empty( $terms ) ) {

							if ( term_exists( DOPT_CT_ONDERWERP_DEFAULT, DOPT_CT_ONDERWERP ) ) {
								echo '<li>' . get_the_title( $post_id );
								wp_set_object_terms( $post_id, DOPT_CT_ONDERWERP_DEFAULT, DOPT_CT_ONDERWERP );

								echo 'now has <span style="display: inline-block; background: green; color: white;">term: ' . esc_html( $term->name ) . '</span >';

								echo '</li>';
							}

						} else {
							// do nothing, actielijn has terms
						}


					endwhile;

					echo '</ul>';

				}

				// RESET THE QUERY
				wp_reset_query();

			}

			$acfid            = get_the_id();
			$actielijnblokken = get_field( 'actielijnen_per_thema', $acfid );

			$numberofyears         = 0;
			$actielijnblok_counter = 0;

			$year_now = date( "Y" );
			$q_now    = date( "m" );

			$verschijningsvorm = 'gantt';

			if ( have_rows( 'actielijnen_per_thema', $acfid ) ) {

				if ( 'gantt' == $verschijningsvorm ) {

					$intervalheader = '<div class="intervalheader" aria-hidden="true">';
					$currentyear    = $this->dopt_years_start;
					$currentquarter = 1;

					while ( intval( $currentyear ) <= intval( $this->dopt_years_end ) ) :

						$intervalheader .= '<div class="intervalheader-year"><span>' . $currentyear . '</span>';

						while ( intval( $currentquarter ) <= intval( ( DOPT__NR_QUARTERS - 1 ) ) ) :

							$intervalheader .= '<div class="intervalheader-quarter">Q' . $currentquarter . '</div>';
							$currentquarter ++;

						endwhile;

						$intervalheader .= '</div>';
						$currentquarter = 1;
						$currentyear ++;

					endwhile;

					$intervalheader .= '</div>'; // class=intervalheader

					$currentkwartaal = '<div class="currentkwartaal">&nbsp;</div>'; // class=intervalheader


					// nu gegevens tonen

					foreach ( $actielijnblokken as $actielijnblok ) {

						$actielijnblok_counter ++;

						$actielijnblok_titel = esc_html( $actielijnblok['actielijnen_per_thema_titel'] );

						if ( ! $actielijnblok['actielijnen_per_thema_htmlid'] ) {
							$actielijnblok_htmlid = esc_html( $actielijnblok['actielijnen_per_thema_titel'] );
						} else {
							$actielijnblok_htmlid = esc_html( $actielijnblok['actielijnen_per_thema_htmlid'] );
						}

						if ( get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $actielijnblok['actielijnen_per_thema_kleur'] ) ) {
							// dit is de oude manier om de kleur te bepalen. Oude pagina's verwezen qua kleur nog naar
							// de kleur die bij een RHSWP_CT_DIGIBETER term gekozen is. Voor backward compatibilit
							// laten we deze staan
							$digibeterclass     = get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $actielijnblok['actielijnen_per_thema_kleur'] );
						} elseif ( $actielijnblok['actielijnen_per_thema_kleur'] ) {
							// de nieuwe manier om een kleur te kiezen is simpeler
							$digibeterclass     = $actielijnblok['actielijnen_per_thema_kleur'];
						} else {
							$digibeterclass = 'digibeter-blauw';
						}
						$select_actielijnen = $actielijnblok['actielijnen_per_thema_actielijnen'];
						$intervalheader2    = preg_replace( '/class="intervalheader"/', 'class="intervalheader" id="intervalheader_' . $actielijnblok_counter . '"', $intervalheader );

						$possiblewidth_timeline = ( ( $this->dopt_years_max_nr * DOPT_CSS_YEARWIDTH ) - 1 ); // -1 is to strip off the unnecessary margin-right of the last year
						$possiblewidth_total    = ( $possiblewidth_timeline + DOPT_CSS_PADDINGLEFT );


						echo '<section id="' . sanitize_title( $actielijnblok_htmlid ) . '" class="programma ' . $digibeterclass . '" data-possiblewidth="' . ( $possiblewidth_total + 1 ) . 'em">';

						// header
						echo '<header>';
						echo '<div class="container">';
						echo '<h2>' . $actielijnblok_titel . '</h2>';
						echo '</div>';
						echo '<div class="container">';
						echo $intervalheader2;
						echo '</div>';
						echo '</header>';

						echo '<div class="timescale-container">' . $currentkwartaal;
						echo '<div class="actielijnen">';

						if ( $select_actielijnen ) {

							foreach ( $select_actielijnen as $select_actielijn ) {

								$currentyear  = $this->dopt_years_start;
								$startatyearq = intval( $this->dopt_years_start . 1 );
								$endatyearq   = intval( $this->dopt_years_end . 4 );

								// we hebben een ID nodig voor dit element.
								// De combinatie van een titel van het blok en de ID van de actielijn zou voldoende moeten zijn
								// Dit ID wordt o.m. gebruikt voor het toevoegen van labeltjes en anchors op de pagina
								$identifier = DOPT__ACTIELIJN_CPT . '-' . $select_actielijn->ID;
								$planning   = wp_get_post_terms( $select_actielijn->ID, DOPT_CT_PLANNINGLABEL );

								// gebruik $identifier als class en niet als ID omdat een actielijn per ongeluk aan meerdere
								// blokken toegekend kan worden en dan dus de HTML ongeldig kan maken.
								echo '<div class="' . $identifier . ' single-actielijn" id="' . sanitize_title( $actielijnblok_titel ) . '-' . $select_actielijn->ID . '">';

								echo '<div class="description">';
								echo '<h3><a href="' . get_the_permalink( $select_actielijn->ID ) . '">' . get_the_title( $select_actielijn->ID ) . '</a></h3>';
								echo '</div>'; // .description

								echo '<div class="planning ' . get_field( 'heeft_start-_of_einddatums', $select_actielijn->ID ) . '">';

								$emptycontent = '&nbsp;';

								if ( $planning ) {

									if ( $planning[0]->name ) {
										$emptycontent = '<span aria-hidden="true">' . $planning[0]->name . '</span>';
									}
								}


								echo '<div class="ganttbar">';

								$startendlabel = _x( 'Gestart', 'standaard label planning', 'wp-rijkshuisstijl' );

								switch ( get_field( 'heeft_start-_of_einddatums', $select_actielijn->ID ) ) {

									case 'start_eind':
										$startendlabel = sprintf( _x( 'van %s-%s tot %s-%s', 'geschatte planning', 'wp-rijkshuisstijl' ),
												strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID ) ),
												$this->dopt_array_data[ $select_actielijn->ID ]['start_jaar'],
												strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID ) ),
												$this->dopt_array_data[ $select_actielijn->ID ]['eind_jaar'] ) . '. ';
										break;

									case 'start':
										$startendlabel = sprintf( _x( 'vanaf %s-%s', 'geschatte planning', 'wp-rijkshuisstijl' ),
												strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID ) ),
												$this->dopt_array_data[ $select_actielijn->ID ]['start_jaar'] ) . '. ';
										break;

									case 'eind':
										$startendlabel = sprintf( _x( 'tot %s-%s', 'geschatte planning', 'wp-rijkshuisstijl' ),
												strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID ) ),
												$this->dopt_array_data[ $select_actielijn->ID ]['eind_jaar'] ) . '. ';
										break;

								}


								if ( $planning ) {
									if ( $planning[0]->name ) {
										echo '<span class="hide-in-chartview">' . _x( 'Planning:', 'geschatte planning', 'wp-rijkshuisstijl' ) . '</span> <span class="planning-label">' . strtolower( $planning[0]->name ) . '</span>';
									}
								} else {
									echo '<span class="planning-label">' . $startendlabel . '</span>';
								}

								echo '<span class="hide-in-chartview">, ';

								echo $startendlabel;



								echo '</span>'; // .hide-in-chartview

								echo '</div>'; // .ganttbar


								$args = array(
									'id'              => $select_actielijn->ID,
									'echo'            => false,
									'showheader'      => 0,
									'startyear'       => $this->dopt_years_start,
									'endyear'         => $this->dopt_years_end,
									'uniqueelementid' => $identifier,
									'headertag'       => 'p',
									'titletext'       => __( 'Gebeurtenissen hierbij:', 'wp-rijkshuisstijl' )
								);

								echo do_pt_frontend_get_gebeurtenissen_for_actielijn( $args );

								echo '</div>'; // class=planning
								echo '</div>'; // class=single-actielijn

							}

						}

						echo '</div>'; // class=actielijnen

						echo '</div>'; // class=timescale-container;

						echo '</section>'; // class=programma

					}


				}    //  if ( 'gantt' == $verschijningsvorm
				elseif ( 'table' == $verschijningsvorm ) {

					foreach ( $actielijnblokken as $actielijnblok ) {

						$actielijnblok_titel = esc_html( $actielijnblok['actielijnen_per_thema_titel'] );
						$digibeterclass      = get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $actielijnblok['actielijnen_per_thema_kleur'] );
						$select_actielijnen  = $actielijnblok['actielijnen_per_thema_actielijnen'];

						echo '<table id="' . sanitize_title( $actielijnblok_titel ) . '" class="programma ' . $digibeterclass . '">';
						echo '<caption>' . $actielijnblok_titel . '</caption>';

						if ( $select_actielijnen ) {

							echo '<tr>';
							echo '<th scope="col" id="th_col_actielijn">' . _x( 'Actielijn', 'tussenkopje', "ictuwp-plugin-planningtool" ) . '</th>';
							echo '<th scope="col" id="th_col_planning">' . _x( 'Planning', 'tussenkopje', "ictuwp-plugin-planningtool" ) . '</th>';
							echo '<th scope="col" id="th_col_gebeurtenis">' . _x( 'Gebeurtenissen', 'tussenkopje', "ictuwp-plugin-planningtool" ) . '</th>';

							echo '</tr>';

							foreach ( $select_actielijnen as $select_actielijn ) {

								echo '<tr>';

								echo '<th scope="row">';
								echo '<a href="' . get_the_permalink( $select_actielijn->ID ) . '">' . get_the_title( $select_actielijn->ID ) . '</a>';
								echo '</th>';

								echo '<td>';

								$actielijn_kwartaal_start_kwartaal = get_field( 'actielijn_kwartaal_start_kwartaal', $select_actielijn->ID );
								$actielijn_kwartaal_start_jaar     = get_field( 'actielijn_kwartaal_start_jaar', $select_actielijn->ID );
								$actielijn_kwartaal_eind_kwartaal  = get_field( 'actielijn_kwartaal_eind_kwartaal', $select_actielijn->ID );
								$actielijn_kwartaal_eind_jaar      = get_field( 'actielijn_kwartaal_eind_jaar', $select_actielijn->ID );

								if ( ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) || ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar ) ) {

									$planning = wp_get_post_terms( $select_actielijn->ID, DOPT_CT_PLANNINGLABEL );

									if ( $planning ) {
										if ( $planning[0]->name ) {
											echo '<br>' . $planning[0]->name;
										}
									}

									if ( ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) || ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar ) ) {

										if ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) {

											if ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar ) {
												echo _x( 'Van', 'tussenkopje', "ictuwp-plugin-planningtool" ) . ' ';
											} else {
												echo _x( 'Start', 'tussenkopje', "ictuwp-plugin-planningtool" ) . ': ';
											}

											echo $actielijn_kwartaal_start_kwartaal . '-' . $actielijn_kwartaal_start_jaar;

										}

										if ( $actielijn_kwartaal_eind_kwartaal && $actielijn_kwartaal_eind_jaar ) {

											if ( $actielijn_kwartaal_start_kwartaal && $actielijn_kwartaal_start_jaar ) {
												echo ' ' . _x( 'tot', 'tussenkopje', "ictuwp-plugin-planningtool" ) . ' ';
											} else {
												echo _x( 'Eind', 'tussenkopje', "ictuwp-plugin-planningtool" ) . ': ';
											}

											echo $actielijn_kwartaal_eind_kwartaal . '-' . $actielijn_kwartaal_eind_jaar;

										}

									}

								}

								echo ' </td>';

								echo '<td>';
								echo $this->do_pt_frontend_display_actielijn_info( $select_actielijn->ID, false, true, $this->dopt_years_start, $this->dopt_years_end );
								echo ' </td>';

								echo '</tr>';

							}

						}

						echo '</table>';

					}

				}

			} else { //   if( have_rows('actielijnen_per_thema', $acfid ) ) {

				// loop through all the terms in DOPT_CT_ONDERWERP and see if the attached page has the same ID as the current page
				$select_term_id = '';
				$postid         = get_the_id();
				$terms2         = get_terms( DOPT_CT_ONDERWERP );

				foreach ( $terms2 as $_term ) {

					$planning_page = get_field( 'dopt_ct_onderwerp_page', DOPT_CT_ONDERWERP . '_' . $_term->term_id );

					if ( $postid == $planning_page->ID ) {
						// the attached page has the same ID as the current page
						// so keep the term ID for selection
						$select_term_id = $_term->term_id;
					}

				}


				$user = wp_get_current_user();
				if ( in_array( 'manage_categories', (array) $user->allcaps ) ) {
					// ingelogde gebruiker die minstens redactierechten heeft
					if ( $select_term_id ) {
						$termname = get_term( $select_term_id );
						$asdf     = __( ", dus we tonen de actielijnen onder", "ictuwp-plugin-planningtool" ) . " '" . $termname->name . "'.";
					} else {
						$asdf = __( ", dus ALLE actielijnen per digibeter-kleur worden getoond.", "ictuwp-plugin-planningtool" );
					}
					echo '<div class="wrap"><p class="debugstring" style="margin-bottom: 1em;">' .
					     __( "Hallo, lid van de redactie. Er zijn geen actielijnen geselecteerd voor deze pagina.", "ictuwp-plugin-planningtool" ) .
					     $asdf .
					     __( "<br>Normale bezoekers zien deze geel-rode melding uiteraard niet.", "ictuwp-plugin-planningtool" ) . '</p></div>';

				}
				$intervalheader = '<div class="intervalheader" aria-hidden="true">';
				$currentyear    = $this->dopt_years_start;
				$currentquarter = 1;

				while ( intval( $currentyear ) <= intval( $this->dopt_years_end ) ) :

					$intervalheader .= '<div class="intervalheader-year"><span>' . $currentyear . '</span>';

					while ( intval( $currentquarter ) <= intval( ( DOPT__NR_QUARTERS - 1 ) ) ) :

						$intervalheader .= '<div class="intervalheader-quarter">Q' . $currentquarter . '</div>';
						$currentquarter ++;

					endwhile;

					$intervalheader .= '</div>';
					$currentquarter = 1;
					$currentyear ++;

				endwhile;

				$intervalheader .= '</div>'; // class=intervalheader

				$currentkwartaal = '<div class="currentkwartaal">&nbsp;</div>'; // class=intervalheader

				$terms = get_terms( array(
					'taxonomy'   => RHSWP_CT_DIGIBETER,
					'hide_empty' => true,
				) );

				if ( $terms ) {

					foreach ( $terms as $term ) {

						$actielijnblok_counter ++;

						$actielijnblok_titel  = esc_html( $term->name );
						$digibeterclass       = get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $term->ID );
						$intervalheader2      = preg_replace( '/class="intervalheader"/', 'class="intervalheader" id="intervalheader_' . $actielijnblok_counter . '"', $intervalheader );
						$actielijnblok_htmlid = esc_html( $actielijnblok_titel );


						$possiblewidth_timeline = ( ( $this->dopt_years_max_nr * DOPT_CSS_YEARWIDTH ) - 1 ); // -1 is to strip off the unnecessary margin-right of the last year
						$possiblewidth_total    = ( $possiblewidth_timeline + DOPT_CSS_PADDINGLEFT );

						if ( $select_term_id ) {

							$args = array(
								'post_type'      => DOPT__ACTIELIJN_CPT,
								'post_status'    => 'publish',
								'tax_query'      => array(
									'relation' => 'AND',
									array(
										'taxonomy' => DOPT_CT_ONDERWERP,
										'field'    => 'term_id',
										'terms'    => $select_term_id,
									),
									array(
										'taxonomy' => RHSWP_CT_DIGIBETER,
										'field'    => 'term_id',
										'terms'    => $term->term_id,
									)
								),
								'posts_per_page' => - 1,
							);

						} else {

							$args = array(
								'post_type'      => DOPT__ACTIELIJN_CPT,
								'post_status'    => 'publish',
								'tax_query'      => array(
									array(
										'taxonomy' => RHSWP_CT_DIGIBETER,
										'field'    => 'term_id',
										'terms'    => $term->term_id,
									)
								),
								'posts_per_page' => - 1,
							);

						}


						$wp_queryposts = new WP_Query( $args );


						if ( $wp_queryposts->have_posts() ) {

							$digibeterclass = get_field( 'digibeter_term_achtergrondkleur', RHSWP_CT_DIGIBETER . '_' . $term->term_id );

							echo '<section id="' . sanitize_title( $actielijnblok_htmlid ) . '" class="programma ' . $digibeterclass . '" data-possiblewidth="' . ( $possiblewidth_total + 1 ) . 'em">';

							// header
							echo '<header>';
							echo '<div class="container">';
							echo '<h2>' . $term->name . '</h2>';
							echo '</div>';
							echo '<div class="container">';
							echo $intervalheader2;
							echo '<div>';
							echo '</header>';

							echo '<div class="timescale-container">' . $currentkwartaal;
							echo '<div class="actielijnen">';


							$postcounter = 0;

							while ( $wp_queryposts->have_posts() ) : $wp_queryposts->the_post();
								$postcounter ++;
								$theid = get_the_id();

								$currentyear  = $this->dopt_years_start;
								$startatyearq = intval( $this->dopt_years_start . 1 );
								$endatyearq   = intval( $this->dopt_years_end . 4 );

								// we hebben een ID nodig voor dit element.
								// De combinatie van een titel van het blok en de ID van de actielijn zou voldoende moeten zijn
								// Dit ID wordt o.m. gebruikt voor het toevoegen van labeltjes en anchors op de pagina
								$identifier = DOPT__ACTIELIJN_CPT . '-' . $theid;
								$planning   = wp_get_post_terms( $theid, DOPT_CT_PLANNINGLABEL );

								// gebruik $identifier als class en niet als ID omdat een actielijn per ongeluk aan meerdere
								// blokken toegekend kan worden en dan dus de HTML ongeldig kan maken.
								echo '<div class="' . $identifier . ' single-actielijn" id="' . sanitize_title( $actielijnblok_titel ) . '-' . $theid . '">';

								echo '<div class="description">';
								echo '<h3><a href="' . get_the_permalink( $theid ) . '">' . get_the_title( $theid ) . '</a></h3>';
								echo '</div>';

								echo '<div class="planning ' . get_field( 'heeft_start-_of_einddatums', $theid ) . '">';

								$emptycontent = '&nbsp;';

								if ( $planning ) {

									if ( $planning[0]->name ) {
										$emptycontent = '<span aria-hidden="true">' . $planning[0]->name . '</span>';
									}
								}

								echo '<div class="ganttbar">';

								if ( $planning ) {
									if ( $planning[0]->name ) {
										echo '<span class="visuallyhidden">' . _x( 'Planning:', 'geschatte planning', 'wp-rijkshuisstijl' ) . '</span> ' . strtolower( $planning[0]->name );
									}
								}

								/*

								// deze teksten leveren uiteindelijk niet meer duidelijkheid op, dus uitgecommentarieerd.

												  echo '<span class="visuallyhidden">, ';

												  switch ( get_field( 'heeft_start-_of_einddatums', $theid ) ) {

													case 'start_eind':
													  echo sprintf( _x( 'van %s-%s tot %s-%s', 'geschatte planning', 'wp-rijkshuisstijl' ),
																		strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $theid ) ),
																		$this->dopt_array_data[$theid]['start_jaar'],
																		strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $theid ) ),
																		$this->dopt_array_data[$theid]['eind_jaar'] ) . '. ';
													  break;

													case 'start':
													  echo sprintf( _x( 'vanaf %s-%s', 'geschatte planning', 'wp-rijkshuisstijl' ),
																		strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $theid ) ),
																		$this->dopt_array_data[$theid]['start_jaar'] ) . '. ';
													  break;

													case 'eind':
													  echo sprintf( _x( 'tot %s-%s', 'geschatte planning', 'wp-rijkshuisstijl' ),
																		strtoupper( get_field( 'actielijn_kwartaal_eind_kwartaal', $theid ) ),
																		$this->dopt_array_data[$theid]['eind_jaar'] ) . '. ';
													  break;

												  }


												  echo '</span>';
								*/
								echo '</div>';


								$args = array(
									'id'              => $theid,
									'echo'            => false,
									'showheader'      => 0,
									'startyear'       => $this->dopt_years_start,
									'endyear'         => $this->dopt_years_end,
									'uniqueelementid' => $identifier,
									'headertag'       => 'p',
									'titletext'       => __( 'Gebeurtenissen hierbij:', 'wp-rijkshuisstijl' )
								);

								echo do_pt_frontend_get_gebeurtenissen_for_actielijn( $args );

								echo '</div>'; // class=planning
								echo '</div>'; // class=single-actielijn

							endwhile;

							echo '</div>'; // class=actielijnen
							echo '</div>'; // class=timescale-container;
							echo '</section>'; // class=programma


						}


					}
				}
			}

		}

		//====================================================================================================

		public function do_pt_frontend_filter_breadcrumb( $crumb, $args ) {

			global $post;

			if ( $crumb ) {

				$span_before_start    = '<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
				$span_between_start   = '<span itemprop="name">';
				$span_before_end      = '</span>';
				$loop                 = rhswp_get_context_info();
				$berichtnaam          = get_the_title();
				$gebeurtenis_parentid = 0;

				if ( ( is_single() && DOPT__ACTIELIJN_CPT == get_post_type() ) ||
				     ( is_single() && DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {

					if ( DOPT__GEBEURTENIS_CPT == get_post_type() ) {

						$acfid = $post->ID;

						// Gerelateerde gebeurtenissen
						$related_actielijnen = get_field( 'related_gebeurtenissen_actielijnen', $acfid );

						if ( $related_actielijnen ) {

							foreach ( $related_actielijnen as $relatedobject ) {
								$gebeurtenis_parentid = $relatedobject->ID;
								$terms                = wp_get_post_terms( $gebeurtenis_parentid, DOPT_CT_ONDERWERP );
							}

						} else {
							// geen actielijn gevonden bij deze gebeurtenis
						}
					}
					if ( DOPT__ACTIELIJN_CPT == get_post_type() ) {
						// get page that goes with this DOPT_CT_ONDERWERP
						$terms = wp_get_post_terms( $post->ID, DOPT_CT_ONDERWERP );
					}

					if ( $terms ) {

						foreach ( $terms as $_term ) {

							$planning_page    = get_field( 'dopt_ct_onderwerp_page', DOPT_CT_ONDERWERP . '_' . $_term->term_id );
							$planning_page_id = $planning_page->ID;
							$pagetitle        = get_the_title( $planning_page_id );

						}
					} else {

						// actielijn not attached to any DOPT_CT_ONDERWERP term

						if ( ! $planning_page_id ) {
							$planning_page    = get_field( 'planning_page', 'option' );
							$planning_page_id = $planning_page->ID;
							$pagetitle        = get_the_title( $planning_page_id );
						}

						if ( ! $planning_page_id ) {
							// last resort. Default to post archive page
							//  ¯\_(ツ)_/¯
							$planning_page_id = get_option( 'page_for_posts' );
							$pagetitle        = get_the_title( $planning_page_id );
						}
					}

					if ( $planning_page_id ) {

						if ( DOPT__GEBEURTENIS_CPT == get_post_type() && $gebeurtenis_parentid ) {
							$crumb = $args['sep'] . '<a href="' . get_permalink( $gebeurtenis_parentid ) . '">' . get_the_title( $gebeurtenis_parentid ) . '</a>' . $args['sep'] . $berichtnaam;
						} else {
							$crumb = $args['sep'] . ' ' . $berichtnaam;
						}

						$crumb = ' <a href="' . get_permalink( $planning_page_id ) . '">' . get_the_title( $planning_page_id ) . '</a>' . $crumb;

						// haal de ancestors op voor de huidige pagina
						$ancestors = get_post_ancestors( $planning_page_id );

						// haal de hele keten aan ancestors op en zet ze in de returnstring
						foreach ( $ancestors as $ancestorid ) {

							$crumb = ' <a href="' . get_permalink( $ancestorid ) . '">' . get_the_title( $ancestorid ) . '</a>' . $crumb;

						}

						return $crumb;
					} else {
						return $crumb;
					}
				} else {
					return $crumb;
				}
			}
		}

		//====================================================================================================

		/**
		 * Handles the front-end display.
		 *
		 * @return void
		 */
		public function do_pt_do_frontend_single_actielijn_info() {

			global $post;

			$echo                 = true;
			$showheader           = true;
			$returnstring         = '';
			$actielijnentitletext = '';

			if ( is_single() && DOPT__ACTIELIJN_CPT == get_post_type() ) {

				$acfid = $post->ID;

				$actielijn_doel      = get_field( 'actielijn_doel', $acfid );
				$actielijn_resultaat = get_field( 'actielijn_resultaat', $acfid );

				if ( $actielijn_doel ) {
					$returnstring .= '<h2>' . _x( 'Doel', 'kopje', 'wp-rijkshuisstijl' ) . '</h2>';
					$returnstring .= $actielijn_doel;
				}

				if ( $actielijn_resultaat ) {
					$returnstring .= '<h2>' . _x( 'Resultaat', 'kopje', 'wp-rijkshuisstijl' ) . '</h2>';
					$returnstring .= $actielijn_resultaat;
				}

				//------------------------------------------------------------------------------------------------
				// haal de planning taxonomie op en toon deze

				$planning = wp_get_post_terms( $post->ID, DOPT_CT_PLANNINGLABEL );

				if ( $planning ) {
					$countertje   = 0;
					$returnstring .= '<h2>' . _x( 'Planning', 'kopje', 'wp-rijkshuisstijl' ) . '</h2>';
					$returnstring .= '<p>';

					foreach ( $planning as $term ) {
						$countertje ++;
						if ( $countertje <= 1 ) {
							$returnstring .= $term->name;
						} else {
							$returnstring .= ', ' . $term->name;
						}
					}

					$returnstring .= '.</p>';

				}

				//------------------------------------------------------------------------------------------------
				// haal de trekkers taxonomie op en toon deze

				$planning = wp_get_post_terms( $post->ID, DOPT_CT_TREKKER );

				if ( $planning ) {

					$tax_info   = get_taxonomy( DOPT_CT_TREKKER );
					$countertje = 0;

					$returnstring .= '<h2>';

					if ( count( $planning ) > 1 ) {
						$returnstring .= $tax_info->labels->name;
					} else {
						$returnstring .= $tax_info->labels->singular_name;
					}

					$returnstring .= '</h2>';
					$returnstring .= '<p>';

					foreach ( $planning as $term ) {
						$countertje ++;
						if ( $countertje <= 1 ) {
							$returnstring .= $term->name;
						} else {
							$returnstring .= ', ' . $term->name;
						}
					}

					$returnstring .= '.</p>';
				}

				//------------------------------------------------------------------------------------------------
				// kijken of er gerelateerde actielijnen zijn

				$related_actielijnen = get_field( 'related_actielijnen', $acfid );

				if ( $related_actielijnen ) {

					if ( ! $actielijnentitletext ) {
						$actielijnentitletext = _x( 'Gerelateerde actielijnen', 'tussenkopje', "ictuwp-plugin-planningtool" );
					}
					if ( $showheader ) {
						$returnstring         .= '<h2>' . $actielijnentitletext . '</h2>';
						$actielijnentitletext = '';
					}

					$returnstring .= '<ul>';

//				$related_actielijnen = get_field('related_actielijnen', $acfid );

					foreach ( $related_actielijnen as $relatedobject ) {
						$returnstring .= '<li>' . get_the_title( $relatedobject->ID ) . '</li>';
//					$returnstring .= '<li><a href="' . get_permalink( $relatedobject->ID ) . '">' . get_the_title( $relatedobject->ID ) . '</a></li>';
					}

					$returnstring .= '</ul>';

				}

				//------------------------------------------------------------------------------------------------
				// kijken of er gebeurtenissen aan deze actielijn gekoppeld zijn

				$related_gebeurtenissen = get_field( 'related_gebeurtenissen_actielijnen', $acfid );

				if ( $related_gebeurtenissen ) {

					if ( ! $actielijnentitletext ) {
						$actielijnentitletext = _x( 'Gerelateerde gebeurtenissen', 'tussenkopje', "ictuwp-plugin-planningtool" );
					}
					if ( $showheader ) {
						$returnstring .= '<h2>' . $actielijnentitletext . '</h2>';
					}

					$returnstring .= '<ul>';

					foreach ( $related_gebeurtenissen as $relatedobject ) {

						$gebeurtenis_datum = date_i18n( get_option( 'date_format' ), strtotime( get_field( 'gebeurtenis_datum', $relatedobject->ID ) ) );

						$returnstring .= '<li><a href="' . get_permalink( $relatedobject->ID ) . '">' . get_the_title( $relatedobject->ID ) . ' (' . $gebeurtenis_datum . ')</a></li>';
//					$returnstring .= '<li><a href="' . get_permalink( $relatedobject->ID ) . '">' . get_the_title( $relatedobject->ID ) . '</a></li>';
					}

					$returnstring .= '</ul>';

				}

				//------------------------------------------------------------------------------------------------

			}

			if ( $echo ) {
				echo $returnstring;
			} else {
				return $returnstring;
			}


		}

		//====================================================================================================

		/**
		 * Handles the front-end display.
		 *
		 * @return void
		 */
		public function do_pt_do_frontend_single_gebeurtenis_info() {

			global $post;

			$echo                 = true;
			$showheader           = true;
			$returnstring         = '';
			$actielijnentitletext = '';
			$acfid                = $post->ID;

			if ( is_single() && DOPT__GEBEURTENIS_CPT == get_post_type() ) {

				$gebeurtenis_datum = _x( 'Geen datum ingevoerd', 'geschatte planning', 'wp-rijkshuisstijl' );

				if ( get_field( 'gebeurtenis_geschatte_datum', $acfid ) ) {
					$gebeurtenis_datum = get_field( 'gebeurtenis_geschatte_datum', $acfid );
				} elseif ( get_field( 'gebeurtenis_datum', $postid ) ) {
					$gebeurtenis_datum = date_i18n( get_option( 'date_format' ), strtotime( get_field( 'gebeurtenis_datum', $acfid ) ) );
				}

				$returnstring .= '<p>' . _x( 'Planning:', 'geschatte planning', 'wp-rijkshuisstijl' ) . ' ' . strtolower( $gebeurtenis_datum ) . '.</p>';


				//------------------------------------------------------------------------------------------------
				// kijken of er gebeurtenissen aan deze actielijn gekoppeld zijn

				$related_gebeurtenissen = get_field( 'related_gebeurtenissen_actielijnen', $acfid );

				if ( $related_gebeurtenissen ) {

					if ( ! $actielijnentitletext ) {
						$actielijnentitletext = _x( 'Gerelateerde activiteiten', 'tussenkopje', "ictuwp-plugin-planningtool" );
					}
					if ( $showheader ) {
						$returnstring .= '<h2>' . $actielijnentitletext . '</h2>';
					}

					$returnstring .= '<ul>';


					foreach ( $related_gebeurtenissen as $relatedobject ) {
						$returnstring .= '<li><a href="' . get_permalink( $relatedobject->ID ) . '">' . get_the_title( $relatedobject->ID ) . '</a></li>';
					}

					$returnstring .= '</ul>';

				}

				//------------------------------------------------------------------------------------------------


			}


			if ( $echo ) {
				echo $returnstring;
			} else {
				return $returnstring;
			}

		}

		//====================================================================================================

		/**
		 * Append related actielijnen or gebeurtenissen
		 */

		public function do_pt_frontend_display_actielijn_info( $postid, $showheader = false, $echo = false, $startyear = '', $endyear = '', $titletext = '', $actielijnentitletext = '' ) {

			$returnstring = '';

			if ( DOPT__ACTIELIJN_CPT == get_post_type( $postid ) ) {

				if ( get_field( 'related_actielijnen', $postid ) ) {

					if ( ! $actielijnentitletext ) {
						$actielijnentitletext = _x( 'Gerelateerde actielijnen', 'tussenkopje', "ictuwp-plugin-planningtool" );
					}
					if ( $showheader ) {
						$returnstring .= '<h2>' . $actielijnentitletext . '</h2>';
					}

					$returnstring .= '<ul>';

					$related_actielijnen = get_field( 'related_actielijnen', $postid );

					foreach ( $related_actielijnen as $relatedobject ) {
						$returnstring .= '<li><a href="' . get_permalink( $relatedobject->ID ) . '">' . get_the_title( $relatedobject->ID ) . '</a></li>';
					}

					$returnstring .= '</ul>';

				}

			}


			if ( get_field( 'related_gebeurtenissen_actielijnen', $postid ) ) {

				// alleen header teruggeven als er uberhaupt iets te melden is

				if ( DOPT__GEBEURTENIS_CPT == get_post_type( $postid ) ) {

					if ( ! $titletext ) {
						$titletext = _x( 'Actielijnen', 'tussenkopje', "ictuwp-plugin-planningtool" );
					}

					if ( $showheader ) {
						$returnstring .= '<h2>' . $titletext . '</h2>';
					}


					$returnstring .= '<ul>';

					$related_actielijnen = get_field( 'related_gebeurtenissen_actielijnen', $postid );

					foreach ( $related_actielijnen as $relatedobject ) {
						$returnstring .= '<li><a href="' . get_permalink( $relatedobject->ID ) . '">' . get_the_title( $relatedobject->ID ) . '</a></li>';
					}

					$returnstring .= '</ul>';


				} elseif ( DOPT__ACTIELIJN_CPT == get_post_type( $postid ) ) {

					if ( ! $titletext ) {
						$titletext = _x( 'Gebeurtenissen (A)', 'tussenkopje', "ictuwp-plugin-planningtool" );
					}

					if ( $showheader ) {
						$returnstring .= '<h2>' . $titletext . '</h2>';
					}

					$args = array(
						'id'              => $postid,
						'echo'            => $echo,
						'startyear'       => $this->dopt_years_start,
						'endyear'         => $this->dopt_years_end,
						'uniqueelementid' => $identifier,
						'titletext'       => sprintf( __( 'Gebeurtenissen bij %s', 'wp-rijkshuisstijl' ), sanitize_title( get_the_title( $select_actielijn->ID ) ) )
					);

					echo do_pt_frontend_get_gebeurtenissen_for_actielijn( $args );

				}

				$returnstring .= do_pt_frontend_get_gebeurtenissen_for_actielijn( $postid, $showheader, $echo, $startyear, $endyear, $titletext, $actielijnentitletext );

			}

			if ( $echo ) {
				echo $returnstring;
			} else {
				return $returnstring;
			}

		}


		//====================================================================================================

		/**
		 * wrap the content in a wrapper as to limit its max width
		 */
		public function do_pt_frontend_append_content_wrapper( $content ) {

			if ( is_singular() && is_main_query() ) {

				// wrap the content in a wrapper
				$content = '<div class="wrap">' . $content . '</div>';

			}

			return $content;

		}

		//====================================================================================================

		/**
		 * Modify page content if using a specific page template.
		 */
		public function do_pt_frontend_use_page_template() {

			global $post;

			$page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );

			if ( $this->templatefile == $page_template ) {

				//* Force full-width-content layout
				add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );

				// wrap the text in a wrapper to contain its widths
				add_filter( 'the_content', array( $this, 'do_pt_frontend_append_content_wrapper' ), 15 );

				// append actielijnen
				add_action( 'genesis_after_entry_content', array(
					$this,
					'do_pt_do_frontend_pagetemplate_add_actielijnen'
				), 15 );

			}

			//=================================================

			if ( is_single() && ( DOPT__ACTIELIJN_CPT == get_post_type() || DOPT__GEBEURTENIS_CPT == get_post_type() ) ) {

				// check the breadcrumb
				add_filter( 'genesis_single_crumb', array( $this, 'do_pt_frontend_filter_breadcrumb' ), 10, 2 );
				add_filter( 'genesis_page_crumb', array( $this, 'do_pt_frontend_filter_breadcrumb' ), 10, 2 );
				add_filter( 'genesis_archive_crumb', array( $this, 'do_pt_frontend_filter_breadcrumb' ), 10, 2 );

				add_filter( 'genesis_post_info', array( $this, 'filter_postinfo' ), 10, 2 );


				if ( DOPT__ACTIELIJN_CPT == get_post_type() ) {
					add_action( 'genesis_entry_content', array( $this, 'do_pt_do_frontend_single_actielijn_info' ) );
				}

				if ( DOPT__GEBEURTENIS_CPT == get_post_type() ) {
					add_action( 'genesis_entry_content', array( $this, 'do_pt_do_frontend_single_gebeurtenis_info' ) );
				}

			}

		}

	}

//========================================================================================================

endif;

//========================================================================================================

add_action( 'plugins_loaded', array( 'DO_Planning_Tool', 'init' ), 10 );

//========================================================================================================

add_action( 'wp_enqueue_scripts', 'do_pt_aux_remove_cruft', 100 ); // high prio, to ensure all junk is discarded

/**
 * Unhook DO_Planningtool styles from WordPress
 */
function do_pt_aux_remove_cruft() {

	wp_dequeue_style( 'cmb2-styles' );
	wp_dequeue_style( 'cmb2-styles-css' );

}

//========================================================================================================

if ( ! function_exists( 'do_pt_aux_write_to_log' ) ) {

	function do_pt_aux_write_to_log( $log ) {

		$subject = 'log';
		$subject .= ' (ID = ' . getmypid() . ')';

		$subjects   = array();
		$subjects[] = $log;

		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( $subject . ' - ' . print_r( $log, true ) );
			} else {
				error_log( $subject . ' - ' . $log );
			}
		}
	}

}

//========================================================================================================

if ( ! function_exists( 'dovardump' ) ) {

	function dovardump( $data, $context = '', $echo = true ) {

		if ( WP_DEBUG && DOPT__PLUGIN_DO_DEBUG ) {
			$contextstring = '';
			$startstring   = '<div class="debug-context-info">';
			$endtring      = '</div>';

			if ( $context ) {
				$contextstring = '<p>Vardump ' . $context . '</p>';
			}

			do_pt_aux_write_to_log( print_r( $data ), true );


			if ( $echo && DOPT__PLUGIN_OUTPUT_TOSCREEN ) {

				echo $startstring . '<hr>';
				echo $contextstring;
				echo '<pre>';
				print_r( $data );
				echo '</pre><hr>' . $endtring;
			} else {
				return '<hr>' . $contextstring . '<pre>' . print_r( $data, true ) . '</pre><hr>';
			}
		}
	}
}

//========================================================================================================

if ( ! function_exists( 'dodebug' ) ) {

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

	load_plugin_textdomain( "ictuwp-plugin-planningtool", false, basename( dirname( __FILE__ ) ) . '/languages' );

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
		} elseif ( isset( $_COOKIE[ $key ] ) && ( ! empty( $_COOKIE[ $key ] ) ) ) {
			$return = $_COOKIE[ $key ];
		}

	}

	return $return;

}

//========================================================================================================

add_filter( 'the_post_navigation', 'do_pt_remove_post_navigation_for_actielijn' );

function do_pt_remove_post_navigation_for_actielijn( $args ) {

	if ( DOPT__ACTIELIJN_CPT == get_type() ) {
		return '';
	} else {
		return '';
	}

	return $args;

}

//========================================================================================================

// add custom tax support to actielijn
add_action( 'admin_init', 'do_pt_cpt_tag_support' );

function do_pt_cpt_tag_support() {

	if ( defined( 'RHSWP_CT_DIGIBETER' ) && defined( 'DOPT__ACTIELIJN_CPT' ) ) {

		register_taxonomy_for_object_type( RHSWP_CT_DIGIBETER, DOPT__ACTIELIJN_CPT );

	}

}

//========================================================================================================

/**
 * Append related actielijnen or gebeurtenissen
 */

function do_pt_frontend_get_gebeurtenissen_for_actielijn( $args ) {

	$returnstring = '';

	$defaults = array(
		'showheader' => 0,
		'echo'       => false,
		'headertag'  => 'h2',
		'startyear'  => '',
		'endyear'    => '',
		'titletext'  => __( 'Gebeurtenissen', "ictuwp-plugin-planningtool" )
	);

	/**
	 * Parse incoming $args into an array and merge it with $defaults
	 */
	$args = wp_parse_args( $args, $defaults );


	if ( ! isset( $args['id'] ) ) {
		return;
	}

	if ( get_field( 'related_gebeurtenissen_actielijnen', $args['id'] ) ) {

		$attr = ' class="visuallyhidden"';

		if ( $args['showheader'] ) {
			$attr = '';
		}

		$returnstring .= '<' . $args['headertag'] . $attr . '>' . $args['titletext'] . '</' . $args['headertag'] . '>';

		$returnstring .= '<ul>';

		$relatedobjects = get_field( 'related_gebeurtenissen_actielijnen', $args['id'] );

		$sortthisarray = array();

		foreach ( $relatedobjects as $relatedobject ) {
			$datumveld = get_field( 'gebeurtenis_datum', $relatedobject->ID );

			$yearevent = date_i18n( "Y", strtotime( $datumveld ) );
			$mnt_event = date_i18n( "m", strtotime( $datumveld ) );
			$day_event = date_i18n( "d", strtotime( $datumveld ) );

			if ( $datumveld ) {
				$sortthisarray[ strtotime( $datumveld ) ] = $relatedobject->ID;
			} else {
				$sortthisarray[ $relatedobject->ID ] = $relatedobject->ID;
			}
		}

		ksort( $sortthisarray );

		foreach ( $sortthisarray as $key => $value ) {

			$gebeurtenis_datum = _x( 'Geen datum ingevoerd', 'geschatte planning', 'wp-rijkshuisstijl' );

			$label = _x( 'datum:', 'label voor verborgen datum in ganttchart', "ictuwp-plugin-planningtool" );

			if ( get_field( 'gebeurtenis_geschatte_datum', $value ) ) {
				$gebeurtenis_datum = get_field( 'gebeurtenis_geschatte_datum', $value );
				$label             = _x( 'geschatte datum:', 'label voor verborgen datum in ganttchart', "ictuwp-plugin-planningtool" );
			} elseif ( $key ) {
				$gebeurtenis_datum = date_i18n( get_option( 'date_format' ), $key );
			}

			$returnstring .= '<li class="' . DOPT__GEBEURTENIS_CPT . '-' . $value . '"><a href="' . get_permalink( $value ) . '">' . get_the_title( $value ) . '<span class="visuallyhidden">' . sprintf( _x( ', %s %s', 'verborgen datum in ganttchart', "ictuwp-plugin-planningtool" ), $label, strtolower( $gebeurtenis_datum ) ) . '</span></a></li>';

		}

		$returnstring .= '</ul>';

	}


	if ( $args['echo'] ) {
		echo $returnstring;
	} else {
		return $returnstring;
	}


}

//========================================================================================================

function dateDiff( $d1, $d2 ) {
// Return the number of days between the two dates:

	return round( abs( strtotime( $d1 ) - strtotime( $d2 ) ) / 86400 );

}  // end function dateDiff

//========================================================================================================

add_filter( 'acf/update_value/name=related_gebeurtenissen_actielijnen', 'bidirectional_acf_update_value', 10, 3 );

add_filter( 'acf/update_value/name=related_actielijnen', 'bidirectional_acf_update_value', 10, 3 );

//========================================================================================================


