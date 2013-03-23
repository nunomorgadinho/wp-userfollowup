<?php
/*
Plugin Name: WP UserFollowUp
Plugin URI: http://widgilabs.com/
Description: UserFollowUp connects events triggered by your customers to automatic follow up emails sent in your name, automating common tasks, reminding them of incomplete actions, brigging back customers and creating better service.
Version: 1.0
Author: widgilabs.com
Author URI: http://widgilabs.com/
License: GPL2
*/

/**
 * wp_userfollowup class
 *
 * This plugin is a class because reasons, #blamenacin
 *
 */
if ( !class_exists( 'wp_userfollowup' ) ) {

	class wp_userfollowup {

		// Holds the api key value
		var $api_key;
		var $event;
		var $action;
		
		// instance
		static $instance;

		/**
		 * Add init hooks on class construction
		 */
		function wp_userfollowup() {

			// allow this instance to be called from outside the class
			self::$instance = $this;

			add_action( 'init', array( $this, 'init' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		}

		/**
		 * Init callback 
		 * 
		 * Load translations and add iframe code, if present
		 *
		 */
		function init() {

			load_plugin_textdomain( 'wp-userfollowup', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			$wp_userfollowup_vars = get_option( 'wp_userfollowup_vars' );

			if ( !empty( $wp_userfollowup_vars['apikey'] ) ) {
				$this->api_key = $wp_userfollowup_vars['apikey'];
				add_action( 'wp_footer', array( $this, 'add_code' ), 999 );
			}
			
			if ( !empty( $wp_userfollowup_vars['event'] ) ) {
				$this->event = $wp_userfollowup_vars['event'];
			}
			
			if ( !empty( $wp_userfollowup_vars['action'] ) ) {
				$this->action = $wp_userfollowup_vars['action'];
			}
		}


		/**
		 * Admin init callback
		 * 
		 * Register options, add settings page
		 *
		 */
		function admin_init() {

			register_setting(
				'wp_userfollowup_vars_group',
				'wp_userfollowup_vars',
				array( $this, 'validate_form' ) );

			add_settings_section(
				'wp_userfollowup_vars_id',
				__( 'Settings', 'wp-userfollowup' ),
				array( $this, 'overview' ),
				'WP UserFollowUp Settings' );

			add_settings_field(
				'wpcp-apikey',
				__( 'Tracking ID:', 'wp-userfollowup' ),
				array( $this, 'render_field' ),
				'WP UserFollowUp Settings',
				'wp_userfollowup_vars_id' );
			
			add_settings_section(
				'wp_userfollowup_events_id',
				__( 'Dashboard', 'wp-userfollowup' ),
				array( $this, 'events_overview' ),
				'WP UserFollowUp Settings' );
			
			add_settings_field(
					'wpcp-event',
					__( 'WordPress Event:', 'wp-userfollowup' ),
					array( $this, 'render_wordpress_event' ),
					'WP UserFollowUp Settings',
					'wp_userfollowup_events_id' );
			
			add_settings_field(
					'wpcp-action',
					__( 'Action to Trigger:', 'wp-userfollowup' ),
					array( $this, 'render_action' ),
					'WP UserFollowUp Settings',
					'wp_userfollowup_events_id' );
		}

		/**
		 * Build the menu and settings page callback
		 * 
		 */
		function admin_menu() {

			if ( !function_exists( 'current_user_can' ) || !current_user_can( 'manage_options' ) )
				return;

			if ( function_exists( 'add_options_page' ) )
				add_options_page( __( 'WP UserFollowUp Settings', 'wp-usrfollowup' ), __( 'WP UserFollowUp', 'wp-usrfollowup' ), 'manage_options', 'wp_userfollowup', array( $this, 'show_form' ) );
			
		}

		/**
		 * Show instructions
		 * 
		 */
		function overview() {

			printf( __( '<p>You need to have a valid UserFollowUp tracking ID. Example: <strong>21</strong> is the tracking ID for the code <code>http://app.kissfollowup.com/21.js</code>', 'wp-userfollowup' ), 'http://app.UserFollowUp.com/' );

			_e( '<p>Please <strong>enter only the ID</strong> on the field below.</p>', 'wp-userfollowup' ) . '</p>';

		}

		/**
		 * Show instructions
		 *
		 */
		function events_overview() {
			printf( __( '<p>Select the event and the action you want to trigger.</p>', 'wp-userfollowup' ), 'http://app.UserFollowUp.com/' );
		}
		
		function render_wordpress_event()
		{
			$wp_userfollowup_vars = get_option( 'wp_userfollowup_vars' );

			$items = array("User Login", "User Logout");
			
			echo "<select id='wp_events' name='wp_userfollowup_vars[wpcp-event]'>";
		
			foreach($items as $item) {
				$selected = ($wp_userfollowup_vars['wpcp-event']==$item) ? 'selected="selected"' : '';
				echo "<option value='$item' $selected>$item</option>";
			}
			echo "</select>";
		}
		
		function render_action()
		{
			$wp_userfollowup_vars = get_option( 'wp_userfollowup_vars' );
			?>
			<input type="text" name="wp_userfollowup_vars[wpcp-action]" value="<?php echo $wp_userfollowup_vars['wpcp-action']; ?>" ></input><br/><br/>
			<?php			
		}
		
		/**
		 * Render options field
		 * 
		 */ 
		function render_field() {
			$wp_userfollowup_vars = get_option( 'wp_userfollowup_vars' );

		 ?>
         <input id="wpcp-apikey" name="wp_userfollowup_vars[apikey]" class="regular-text" value="<?php echo $wp_userfollowup_vars['apikey']; ?>" />
         <?php
		}
		
		/**
		 * Validate user options
		 * 
		 */ 
		function validate_form( $input ) {

			//print_r($input);
			
			$wp_userfollowup_vars = get_option( 'wp_userfollowup_vars' );

			if ( isset( $input['apikey'] ) ) {
				// Strip all HTML and PHP tags and properly handle quoted strings
				$wp_userfollowup_vars['apikey'] = strip_tags( stripslashes( $input['apikey'] ) );
			}
			if ( isset( $input['wpcp-event'] ) && isset( $input['wpcp-action'] ) ) {
				$wp_userfollowup_vars['wpcp-event'] = strip_tags( stripslashes( $input['wpcp-event'] ) );
				$wp_userfollowup_vars['wpcp-action'] = strip_tags( stripslashes( $input['wpcp-action'] ) );
			}
			
			
			return $wp_userfollowup_vars;
		}

		/**
		 * Render options page
		 * 
		 */ 
		function show_form() {
			$wp_userfollowup_vars = get_option( 'wp_userfollowup_vars' );

?>
                                <div class="wrap">
                                        <?php screen_icon( "options-general" ); ?>
                                        <h2><?php _e( 'WP UserFollowUp Settings', 'wp-userfollowup' ); ?></h2>
                                        <form action="options.php" method="post">
                                                <?php settings_fields( 'wp_userfollowup_vars_group' ); ?>
                                                <?php do_settings_sections( 'WP UserFollowUp Settings' ); ?>
                                                <p class="submit">
                                                        <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'wp-userfollowup' ); ?>" />
                                                </p>
                                        </form>
                                </div>
                        <?php
		}

		/**
		 * Add iframe code to the site's footer
		 * 
		 */ 
		function add_code() {
			echo "\n<script src='http://app.kissfollowup.com/".sanitize_text_field( $this->api_key ).".js'></script>";
		}


	}

	new wp_userfollowup();
}
