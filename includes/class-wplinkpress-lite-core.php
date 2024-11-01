<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPLinkPress_Lite_Core {
	protected static $_instance = null;

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action('admin_init', array($this, 'wplinkpress_get_accesstoken'));
		require_once WPLINKPRESS_LITE_PATH.'includes/wplinkpress-lite-functions.php';
		require_once WPLINKPRESS_LITE_PATH.'includes/api/class-wplinkpress-lite-api.php';
		require_once WPLINKPRESS_LITE_PATH.'includes/class-wplinkpress-lite-frontend.php';

		if($this->is_request('admin')) {
			require_once WPLINKPRESS_LITE_PATH.'admin/wplinkpress-lite-admin-settings.php';
			require_once WPLINKPRESS_LITE_PATH.'admin/wplinkpress-lite-admin-actions.php';
			wplinkpress_lite_admin_settings();
			wplinkpress_lite_admin_actions();
		}

		add_action('wp_enqueue_scripts', array($this, 'enqueue_global_scripts'));
		add_action('wp_ajax_add_comment_session', array($this, 'add_comment_session'));
		add_action('wp_ajax_nopriv_add_comment_session', array($this, 'add_comment_session'));
		add_action('wp_ajax_authorize_linkedin_oauth', array($this,'authorize_linkedin_oauth' ));
		add_action('wp_ajax_nopriv_authorize_linkedin_oauth', array($this,'authorize_linkedin_oauth' ));

		add_action('admin_init',array($this,'update_wplinkpress_lite'));
	}

	public function authorize_linkedin_oauth() {
		$url = '';
		if(isset($_POST['authorize_code'])) {
			$authorize_code = sanitize_text_field($_POST['authorize_code']);
			$options = get_option('wplinkpress_authorize_settings');
			
			//unset( $_COOKIE['wplinkpress_access_token'] );
  			//setcookie( 'wplinkpress_access_token', '', time() - ( 15 * 60 ) );
			$token = get_access_token_from_linkedin($authorize_code, $options);
			if(isset($token)) {
			$profile_data = get_profile_info($token,$options);
			}
		}

		if(isset($_SESSION['wplinkpress_current_url']))
		$url = $_SESSION['wplinkpress_current_url'];
		
		wp_send_json_success($url);

		wp_die();
	}
	public function wplinkpress_get_accesstoken() {
		if(isset($_GET['authorize_token'])) {
			//Set the access token
			$options = get_option('wplinkpress_authorize_settings');
			
			//unset( $_COOKIE['wplinkpress_access_token'] );
  			//setcookie( 'wplinkpress_access_token', '', time() - ( 15 * 60 ) );
			$token = get_access_token_from_linkedin($_GET['authorize_token'], $options);
			if(isset($token)) {
			$profile_data = get_profile_info($token,$options);
			}
		}
	}

	public function add_comment_session() {
		if(session_id()) {
		$_SESSION['comment'] = sanitize_text_field($_POST['comment_text']);
		$_SESSION['feed'] = sanitize_text_field($_POST['feed']);
		$_SESSION['wplinkpress_current_url'] = esc_url($_POST['current_url']);
		}
		wp_send_json_success();
		wp_die();
	}

	public function enqueue_global_scripts() {
		wp_register_style( 'wplinkpress-lite-frontend-css', WPLINKPRESS_LITE_URL . '/assets/css/wplinkpress-lite-frontend.css', false, WPLINKPRESS_LITE_VERSION );
		wp_enqueue_style( 'wplinkpress-lite-frontend-css' );
		wp_enqueue_style( 'wpb-fa-lite', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );
		wp_register_script( 'wplinkpress-lite-frontend-js', WPLINKPRESS_LITE_URL . '/assets/js/wplinkpress-lite-frontend.js', array('jquery-core'), false, true );
    	wp_localize_script('wplinkpress-lite-frontend-js', 'wplinkpress', array( 'ajaxurl' => admin_url( 'admin-ajax.php')));
		wp_enqueue_script( 'wplinkpress-lite-frontend-js' );
	}

	public function is_request( $type ) {
		switch($type) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX');
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	public function update_wplinkpress_lite(){
		if(isset($_GET['action']) && $_GET['action'] == 'wplinkpress_lite_update') {
			global $wpdb;
			
			$wplinkpress_lite_notice_update = get_option('wplinkpress_lite_notice_update');
			if($wplinkpress_lite_notice_update['updated'] != 'yes') {
			//get all comments image url
			$table = $wpdb->prefix.'wplinkpress_comments';
			$user_data = $wpdb->get_results("SELECT DISTINCT comment_profileid, comment_imageurl FROM $table",'ARRAY_A');
			if(!empty($user_data)) {
				foreach($user_data as $key => $value) {
					$profile_image = new_profile_image_db($value['comment_profileid'],$value['comment_imageurl']);
				}
			}
			$wplinkpress_lite_notice_update['updated']  = 'yes';
			$wplinkpress_lite_notice_update['notice_success'] = 'no';
			update_option('wplinkpress_lite_notice_update',$wplinkpress_lite_notice_update);
			}
		}
	}

}
