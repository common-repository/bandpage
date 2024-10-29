<?php
/*
Copyright © 2012 BandPage, Inc.

This program is free software: you can redistribute it and/or 
modif it under the terms of the GNU General Public License as 
published by the Free Software Foundation, either version 3 
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
See the GNU General Public License for more details. 

You should have received a copy of the GNU General Public 
License along with this program.  
If not, see <http://www.gnu.org/licenses/>.




	Plugin Name: BandPage
	Plugin URI: http://wordpress.org/extend/plugins/bandpage-connect/
	Description: BandPage is used by musicians to share music, videos, photos, shows, bio and more with their fans.
	Author: BandPage, Inc
	Version: 1.0
	Author URI: http://www.BandPage.com/
    Text Domain: BandPage
 */

define( 'BP_ADMIN_URI', plugin_dir_url(__FILE__) );
define( 'BP_BASE_PATH', dirname(__FILE__) );
define( 'BP_WIDGET_PATH', dirname(__FILE__) . '/widgets' );

// Hook definitions for activation
register_activation_hook( __FILE__, array( 'BandPage', 'install' ) );
register_deactivation_hook( __FILE__, array( 'BandPage', 'uninstall' ) );

// Hook definitions for regular use.
add_action( 'init', array( 'BandPage', 'init' ) );
add_action( 'plugins_loaded', array( 'BandPage', 'load_widgets' ), 100 );

class BandPage {

	// For wordpress.com, hard code these.
    public $token_url           = 'https://api-read.bandpage.com/token';
    public $widgets             = array();
    public $buttons             = array();

    // 4 min
    var $token_cache_length = 240;
    
    // Constructor
    function __construct() {

        global $Band_Page;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

		add_action( 'wp_footer', array( $this, 'footer_script'), 30 );
		add_action( 'admin_footer', array( $this, 'footer_script'), 300 );

        // Defaults keys, app and secret can be removed if needed.

		$this->show_admin_settings = false;

        if ( ! defined( 'BANDPAGE_API_KEY' ) ) {
            $api_key = get_option( 'bandpage_api_key' );
            define( 'BANDPAGE_API_KEY', $api_key );
			$this->show_admin_settings = true;
        }

        if ( ! defined( 'BANDPAGE_SECRET_KEY' ) ) {
            $secret_key = get_option( 'bandpage_secret_key' );
            define( 'BANDPAGE_SECRET_KEY', $secret_key );
			$this->show_admin_settings = true;
        }

        if ( ! defined( 'BANDPAGE_APP_ID' ) ) {
            $app_id =  get_option( 'bandpage_app_id' );
            define( 'BANDPAGE_APP_ID', $app_id );
			$this->show_admin_settings = true;
        }

		if ( true == $this->show_admin_settings ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}

        $Band_Page = $this;
    }

    function init() {
    
        $bootstrapperjs = BP_ADMIN_URI . 'js/bandpage-bootstrapper.js';
        wp_register_script( 'bandpage-bootstrapper-js', $bootstrapperjs, array('jquery'), false, false );

        static $instance = false;

		if ( ! $instance ) {
			$instance = new BandPage;
		}

		return $instance;

    }

    function add_extension( $settings = array() ) {
        if ( is_array( $settings ) ) {
            $this->widget_count++;
            $this->widgets[] = $settings;
        }
    }

    function add_button( $settings = array() ) {
        if ( is_array( $settings ) ) {
            $this->buttons[] = $settings;
        }
    }

    function display_render_script( $settings ) {
        if ( isset( $settings['opacity']) && $settings['opacity'] > 0 ) {
            $opacity = $settings['opacity'] / 100;
        } elseif ( ! isset( $settings['opacity'] ) ) {
            $opacity = 1;
        } else {
            $opacity = 0;
        }

        ?>
        bandpage.sdk.createExtension({
                    bandbid: '<?php echo esc_js( $settings['bandbid'] ); ?>',
                    container: jQuery('#<?php echo esc_js( $settings['widget_id'] ); ?> .container'),
                    extensionType: '<?php echo esc_js( $settings['type'] ); ?>',
                    access_token : '<?php echo esc_js( $settings['access_token'] ); ?>',
                    theme: '<?php echo esc_js( $settings['theme'] ); ?>',
                    opacity: (1-<?php echo intval( $opacity ); ?>) ,
                    width: <?php echo intval( $settings['width'] ); ?>,
                    height: <?php echo intval( $settings['height'] ); ?>

                });
        <?php
    }

    function display_connection_button( $settings ) {
        ?>
            var connection = bandpage.sdk.connect({
                    appId: '<?php echo esc_js( $settings['app_id'] ) ?>',
                    access_token : '<?php echo esc_js( $settings['access_token'] ) ?>',
                    container: jQuery('#<?php echo esc_js( $settings['widget_id'] ) ?>').get(0),
                    allow_reconnect: true
                });
        <?php
                    //allow_reconnect: true
        if ( isset( $settings['return_function'] ) ) {
        ?>
            connection.on("bpconnect.complete", <?php echo esc_js( $settings['return_function'] ) ?>);
        <?php
        }
    }
    /*
     * If API key and secret are not passed in, use default
     * Uses http://codex.wordpress.org/HTTP_API
     */
    function get_auth_token( $api_key = null, $secret = null ) {

        global $bandpage_auth_token;

        if ( isset( $bandpage_auth_token ) ) {
            return $bandpage_auth_token;
        }

        if ( false === ( $bandpage_auth_token = get_transient( 'bandpage_auth_token' ) ) ) {

            // TODO: Change to a nice error.  Get Options, etc.
            if ( $api_key == null ) {
                $api_key = BANDPAGE_API_KEY;
            }
            if ( $secret == null ) {
                $secret = BANDPAGE_SECRET_KEY;
            }

            $headers = array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':' . $secret)
            );

            $post = array(
                'client_id'  => $api_key, 
                'grant_type' => 'client_credentials'
            );

            $response = wp_remote_post( $this->token_url, array(
                    'headers' => $headers,
                    'body'    => $post
                )
            );

            $return_json = $response['body'];

            $return = json_decode( $return_json, true );
			if ( $return && isset( $return['access_token'] ) )
            	$bandpage_auth_token = sanitize_text_field( $return['access_token'] );
			else
				$bandpage_auth_token = '';

             set_transient( 'bandpage_auth_token', $bandpage_auth_token, $this->token_cache_length );
        }

        return $bandpage_auth_token;
    }
    
    //
    // TODO: Could place a check for upgrade here.  Save last update check.  
    // 												Make sure only on first login by an admin.
    // 
    function admin_init() {
    
		global $pagenow;

		if ( 'widgets.php' == $pagenow ) {
			wp_enqueue_script( 'bandpage-bootstrapper-js' );
        	add_action('admin_print_scripts', array( $this, 'admin_dashboard_save_script' ), 40 );
		}
        add_action('wp_ajax_bp_save_settings', array( $this, 'admin_save_settings_callback' ) );

		if ( true == $this->show_admin_settings ) {
			register_setting( 'bandpage-dashboard', 'bandpage_app_id', array( 'BandPage', 'sanitize_setting' ) );
			register_setting( 'bandpage-dashboard', 'bandpage_api_key', array( 'BandPage', 'sanitize_setting' ) );
			register_setting( 'bandpage-dashboard', 'bandpage_secret_key', array( 'BandPage', 'sanitize_setting' ) );
			register_setting( 'bandpage-dashboard', 'bandpage_bid', array( 'BandPage', 'sanitize_setting' ) );
		}
    }

    function load_scripts() { 
        // Only load the Javascript if the widget is enabled 
        if ( is_active_widget( false, false, 'bpextension' ) ) { 
            wp_enqueue_script( 'bandpage-bootstrapper-js' ); 
        } 
    }

    function load_widgets() {
    
        require_once( BP_WIDGET_PATH . '/bandpage-base.php' );
        require_once( BP_WIDGET_PATH . '/bandpage-extension.php' );

    }

    // Indented weird so the output looks cleaner
    function footer_script() {
    
        if ( ! empty( $this->buttons ) || ! empty( $this->widgets ) ) {

            ?>
            <script>
                 bandpage.load({
                        
                        done: function(bp){
                            <?php
                            if ( is_array( $this->buttons ) ) {
                                foreach ( $this->buttons as $button ) {
									$this->display_connection_button( $button );
                                }
                            }

                            if ( is_array( $this->widgets ) ) {
                                foreach ( $this->widgets as $extension ) {
									$this->display_render_script( $extension );
                                }
                            }
                            ?> 
                        }
                })
            </script>
            <?php
        } else {
            // Nothing to display
        }
    }

    function admin_save_settings_callback() {
		check_ajax_referer( 'bp_save_settings', '_nonce' );

        $bid =  sanitize_text_field( $_POST['bid'] );

        // validate and cleanse band id and name
        // Remove for testing
        update_option( 'bandpage_bid', $bid );

        echo $bid;

        die(); // this is required to return a proper result
    }

    
    function admin_dashboard_save_script() {
        ?>
        <script type="text/javascript" >
            var bp_connect_location = null;
            var bp_waiting_to_connect = [];
            function setup_button(container_str) {
                if (bp_connect_location == null) {
                    bp_connect_location = container_str
                    bandpage.load({
                        done: function(bp){

                            var connection = bandpage.sdk.connect({
                                appId : "<?php echo esc_js( BANDPAGE_APP_ID ); ?>",
                                access_token : "<?php echo esc_js( $this->get_auth_token() ); ?>",
                                container : jQuery(container_str).get(0),
                                allow_reconnect : true
                            });

                            connection.on("bpconnect.complete", function(bands){
                                var data = {
                                    action: 'bp_save_settings',
                                    bid: bands.approved[0].bid,
									_nonce: '<?php echo esc_js( wp_create_nonce( 'bp_save_settings' ) ); ?>'
                                };
                                // Save the band_id back
                                // ajaxurl is defined by wordpress
                                jQuery.post(ajaxurl, data, function(response) {
                                    // Not going to be good if the id changes
                                    // Hide the connect button and present the config form for any other bp widgets
                                    // Do this for all widgets including the blank instance. When that instance is then added
                                    // It should have the form present.
                                    jQuery('.bp-connect-config').hide(); 
                                    jQuery('.bp-connect-form').slideDown(); 
                                });

                            });

                            if (bp_waiting_to_connect) {
                                var html = jQuery(bp_connect_location).html();
                                // console.log('button html', html);
                                jQuery.each(bp_waiting_to_connect, function(index, button) {
                                    // console.log(button);
                                    jQuery(button).html(html);
                                    jQuery(button).click(function(){
                                        jQuery(bp_connect_location + ' .bandpage-connect').click();
                                    })

                                })
                            }

                        }
                    });
                } else {

                    var html = jQuery(bp_connect_location).html();
                    if (html) {
                        jQuery(container_str).html(html);
                        jQuery(container_str).click(function(){
                            jQuery(bp_connect_location + ' .bandpage-connect').click();
                        })
                    } else {
                        bp_waiting_to_connect.push(container_str);

                    }

                }

            }

            // piggyback off the ajax save request. Setup our button only after its been added to a sidebar.
            jQuery(document).ready(function($) {
                    $(document).ajaxSuccess(function(e, xhr, settings) {
                        data = $.unserialize(settings.data);
                        if (data.action == 'save-widget' && data.id_base == 'bpextension' && data.delete_widget == undefined) {
                            container_string = "#" + data.sidebar + ' div.widget[id$="' + data['widget-id'] + '"] .bp-connect';
                            setup_button(container_string);
                        }
                    });
            });
        </script>
        <?php
    }
    
    // Extend later if we need multiple permissions
    // Not sure what role WP.com requires
    function edit_permission( $page  = null ) {
	    
	    return "edit_theme_options";
    }

   
    function admin_menu() {
        $dashboard = add_submenu_page( 'options-general.php', __( 'BandPage Settings', 'bandpage' ), __( 'BandPage', 'bandpage' ), 
                        $this->edit_permission( 'admin_help' ),
                        'bandpage-dashboard', array( $this, 'admin_dashboard' ) );

        // Only load script on the BandPage dashboard
        add_action( 'admin_print_scripts-' . $dashboard, array( $this, 'load_scripts' ) );
    }

    // This function displayes the admin pages
    function admin_dashboard() {

	    // Load required scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

        // If not defined settings to null so in plugin keys cannot be seen,

        $update = false;

        $app_id = get_option( 'bandpage_app_id' );
        $api_key = get_option( 'bandpage_api_key' );
        $secret = get_option( 'bandpage_secret_key' );
        $band_id = get_option( 'bandpage_bid' );

        ?>
        <div id="bp-admin" class="wrap">
            <div id="icon-themes" class="icon32"></div>
            <h2>BandPage Connect Settings</h2>
            <p>You can think of BandPage Connect Extensions as advanced widgets — they're an extension of your photo gallery, 
                music player, show list, bio, mailing list, and videos. Whenever you add a new show or song to BandPage, 
                it'll automatically be updated in your WordPress sidebars or anywhere else you’ve embedded them online. </p>
                        
                        <p>BandPage Extensions are fully adjustable in size and support all layouts. 
                            You can select a light or dark Extension theme as well as customize their transparency to best 
                            fit the look and feel of your website or blog. Create as many Extensions as you need for your sites, 
                            then manage the content that goes on them from your BandPage.</p>
                        
                        <p>To learn more about BandPage Extensions, watch this <a 
                        href="http://www.youtube.com/watch?v=uxGStFfR_7Q" target="_blank">awesome video!</a></p>

            <h3>Get Started</h3>
            <p>In order to setup your BandPage plugin you must first create a 
            <a href="https://developers.bandpage.com/apps" target="_blank">BandPage application</a>. Once created enter the application settings below.</p>
 

            <form method="post" action="options.php">
                <div id="bp-form">
                <?php
                if ($app_id && $api_key && $secret ) {
                    $access_token = $this->get_auth_token($api_key, $secret);

                    if ( empty( $access_token ) ) {
                        ?>
                        <div class="error">
                            <p>The application settings below do not appear to be correct. Please verify your <a href="https://developer.bandpage.com/apps">BandPage application</a> settings.</p>
                        </div>
                        <?php
                    } else {
                        $this->add_button(array(
                                                'app_id' => $app_id,
                                                'widget_id' => 'bp-connect-btn',
                                                'access_token' => $access_token,
                                                'return_function' => 'bp_dashboard_hook'
                                                ));

                    ?>
                        <h3>Click below to connect to a band</h3>
                        <div id="bp-connect-btn">
                            <div class="container"></div>
                        </div>
                        <input type="hidden" id="band_id" name="bandpage_bid" value="<?php echo $band_id ?>">
                        <p id="band_name"></p>
                            <script>
                            function bp_dashboard_hook(bands) {
                            
                                if (bands.approved.length > 0 || bands.confirmed.length > 0) {
                                    jQuery('#band_id').val(bands.approved['0'].bid);
                                    jQuery('#band_name').text(bands.approved['0'].name);
                                    jQuery('#bp-connect-btn').hide();
                                }

                            }
                        </script>
                    <?php
                    }
                    if( $app_id && $api_key && $secret && ! empty( $access_token ) ) {
                        ?>
                        <div class="message">
                            <p>Changing any of the below settings will remove any connections to a band. After changing you must reconnect to BandPage.</p>
                        </div>
                        <?php
                    }
                }
                ?>
                                    

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><label for="app-id">App ID:</label></th>
                                <td><input type="text" name="bandpage_app_id" id="app-id" value="<?php echo esc_attr( $app_id ); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="api-key">API Key:</label></th>
                                <td><input type="text" name="bandpage_api_key" id="api-key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="secret-key">Secret Key:</label></th>
                                <td><input type="text" name="bandpage_secret_key" id="secret-key" value="<?php echo esc_attr( $secret ); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                        <tbody>
                    </table>

                    <p class="submit"><input type="submit" name="submit" id="pb-submit-btn" class="button-primary" value="Save Changes"></p>
                </div>
				<?php settings_fields( 'bandpage-dashboard' ); ?>
				<?php do_settings_sections( 'bandpage-dashboard' ); ?>
            </form>

        </div>
        <?php
    }

	function sanitize_setting( $value ) {
        // Clear the auth token anytime we save a new api setting
        delete_transient( 'bandpage_auth_token' );

		return sanitize_text_field( $value );
	}

    // Run when then plugin is first activated
    function install() {
    
    }

    // TODO: Should we notify bandpage.com when the plugin is uninstalled?  Ask Ryan.

    // Run when the plugin is deactivated
    function uninstall() {
        // Used for testing
        delete_option( 'bandpage_bid' );    
        delete_transient( 'bandpage_auth_token' );
    }

}

?>
