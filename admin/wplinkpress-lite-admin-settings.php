<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WPLinkPress_Lite_Admin_Settings {
		protected static $_instance = null;

		public static function get_instance() {
			if(is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function __construct() {
			add_action('admin_menu', array($this, 'add_menu_page'));
			add_action('admin_init', array($this, 'wplinkpress_api_settings_init'));
			add_action('admin_enqueue_scripts', array($this, 'register_wplinkpress_admin_scripts'), 99);
			
		}


		public function register_wplinkpress_admin_scripts() {
			global $pagenow;
			if((isset($_GET['page']) && $_GET['page'] == 'wplinkpress-lite')) {
			//loadin css
			wp_register_style( 'wplinkpress-lite-admin-css', WPLINKPRESS_LITE_URL . '/admin/assets/css/wplinkpress-lite-admin.css', false, WPLINKPRESS_LITE_VERSION );
			wp_enqueue_style( 'wplinkpress-lite-admin-css' );
		
			// loading js
			wp_register_script( 'wplinkpress-lite-admin-js', WPLINKPRESS_LITE_URL . '/admin/assets/js/wplinkpress-lite-admin.js', array('jquery'), false, true );
    		wp_localize_script('wplinkpress-lite-admin-js', 'wplinkpress', array( 'ajaxurl' => admin_url( 'admin-ajax.php')));
			wp_enqueue_script( 'wplinkpress-lite-admin-js' );
			}
		}

		public function add_menu_page() {
			add_menu_page(__('WP LinkPress Lite Settings', 'wplinkpress-lite'), 'WP LinkPress Lite', 'manage_options', 'wplinkpress-lite', array($this, 'menu_page_callback'));
		}

		public function menu_page_callback() {
			global $wplinkpress_active_tab;
			$wplinkpress_active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'authorize';
			$args = array(
				'wplinkpress_active_tab'	=>	$wplinkpress_active_tab
			);
			
			wplinkpress_get_template("wplinkpress-lite-admin-settings-display.php", WPLINKPRESS_LITE_PATH.'/admin/templates/',$args);
		}

		public function wplinkpress_api_settings_init() {
			register_setting('wplinkpressPlugin', 'wplinkpress_authorize_settings');
			add_settings_section(
				'wplinkpress_api_wplinkpressPlugin_section',
				__('Linkedin APP Details', 'wplinkpress-lite'),
				array($this,'wplinkpress_authorize_settings_api_callback'),
				'wplinkpressPlugin'
			);

			add_settings_field(
				'client_id',
				__('Client ID','wplinkpress-lite'),
				array($this, 'render_client_id'),
				'wplinkpressPlugin',
				'wplinkpress_api_wplinkpressPlugin_section'
			);

			add_settings_field(
				'client_secret',
				__('Client Secret','wplinkpress-lite'),
				array($this, 'render_client_secret'),
				'wplinkpressPlugin',
				'wplinkpress_api_wplinkpressPlugin_section'
			);

			add_settings_field(
				'redirect_url',
				__('Redirect URL','wplinkpress-lite'),
				array($this, 'render_redirect_url'),
				'wplinkpressPlugin',
				'wplinkpress_api_wplinkpressPlugin_section'
			);
		}

		function render_client_id() {
			$options = get_option('wplinkpress_authorize_settings');
			?>
			<input type="text" name='wplinkpress_authorize_settings[client_id]' value="<?php echo $options['client_id'];?>">
			<?php
		}

		function render_client_secret() {
			$options = get_option('wplinkpress_authorize_settings');
			?>
			<input type="text" name='wplinkpress_authorize_settings[client_secret]' value="<?php echo $options['client_secret'];?>">
			<?php
		}

		function render_redirect_url() {
			$options = get_option('wplinkpress_authorize_settings');
			$redirect_url = site_url().'/authorize-linkedin/';
			?>
			<div class='copied'></div>
			<div class="copy-to-clipboard">
				<input style="width:50%;" readonly type="text" name='wplinkpress_authorize_settings[redirect_url]' value="<?php echo $redirect_url; ?>">
				<span style="display:block;"><i><?php esc_html_e('Copy & paste the redirect url into your App page settings','wplinkpress-lite'); ?></i></span>
			</div>
			<?php
		}

		function wplinkpress_authorize_settings_api_callback() {
			esc_html_e('Please refer the following link to create a LinkedIn Application', 'wplinkpress-lite'). '&nbsp;<a href="'.esc_url("https://www.linkedin.com/developers/apps").'" target="_blank">here</a>';
		}


	}

	function wplinkpress_lite_admin_settings() {
		return WPLinkPress_Lite_Admin_Settings::get_instance();
	}