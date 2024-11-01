<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLinkPress_Lite_Admin_Actions {
	protected static $_instance = null;

	public $access_token_message = '';
	public static function get_instance() {
		if(is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action('add_meta_boxes', array($this, 'global_wplinkpress_comment_metabox'));
		add_action('wplinkpress_settings_tab', array($this, 'wplinkpress_settings_tab_title'));
		add_action('wplinkpress_settings_tab_content', array($this, 'wplinkpress_settings_tab_content'));
		add_filter('parse_query', array($this,'exclude_pages_from_admin'));
		add_action('save_post', array($this, 'save_wplinkpress_comment_data'));
	}

	public function global_wplinkpress_comment_metabox() {
		$screens = get_post_types();
		foreach($screens as $scrren) {
		add_meta_box('wplinkpress-comments', __('WP LinkPress Comments', 'wplinkpress-lite'), array($this,'global_wplinkpress_comments_callback'), $scrren);
		}
	}

	public function global_wplinkpress_comments_callback($post) {
		wp_nonce_field(basename( __FILE__ ), 'wplinkpress_comments_enable_nonce');
		$value = get_post_meta($post->ID, '_enable_wplinkpress_comments', true);
		if($value === 'yes') {
			$checked = "checked";
		} else {
			$checked = "";
		}
		?>
		<input type="hidden" name="_enable_wplinkpress_comments" value="no" />
		<input type="checkbox" name="_enable_wplinkpress_comments" value="yes" <?php echo $checked; ?>/> <?php esc_html_e('Enable comments with LinkedIn by WP Linkpress', 'wplinkpress-lite'); 
	}

	public function save_wplinkpress_comment_data($post_id) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
		}

		if ( !isset( $_POST['wplinkpress_comments_enable_nonce'] ) || !wp_verify_nonce( $_POST['wplinkpress_comments_enable_nonce'], basename( __FILE__ ) ) ){
		return;
		}

		if(isset($_POST['_enable_wplinkpress_comments'])) {
			$enable_status = sanitize_text_field($_POST['_enable_wplinkpress_comments']);
			if($enable_status === 'yes') {
				$value = 'yes';
			} else {
				$value = 'no';
			}
			update_post_meta($post_id, '_enable_wplinkpress_comments', $value );
		}


	}

	public function exclude_pages_from_admin() {
		global $pagenow, $post_type;
		if (current_user_can( 'editor' ) && $pagenow=='edit.php' && $post_type =='page') {
			$page_id = get_option('wplinkpress_page_id');
        	$query->query_vars['post__not_in'] = array($page_id);
    	}
	}

	public function wplinkpress_settings_tab_title() {
		global $wplinkpress_active_tab;
		?>
		<nav class="nav-tab-wrapper">
		<a class="nav-tab <?php echo $wplinkpress_active_tab == 'authorize' || '' ? 'nav-tab-active' : ''; ?>" 
		href="<?php echo esc_url(admin_url('admin.php?page=wplinkpress-lite&tab=authorize'));?>"><?php esc_html_e( 'Authorize', 'wplinkpress-lite' ); ?> </a>
		<a class="nav-tab <?php echo $wplinkpress_active_tab == 'moderate' ? 'nav-tab-active' : ''; ?>" 
		href="<?php echo esc_url(admin_url('admin.php?page=wplinkpress-lite&tab=moderate'));?>"><?php esc_html_e( 'Comments Moderation', 'wplinkpress-lite' ); ?> </a>
		<a class="nav-tab <?php echo $wplinkpress_active_tab == 'moderate' ? 'nav-tab-active' : ''; ?>" 
		href="<?php echo esc_url(admin_url('admin.php?page=wplinkpress-lite&tab=moderate'));?>"><?php esc_html_e( 'Contact Moderation', 'wplinkpress-lite' ); ?> </a>
		<a class="nav-tab <?php echo $wplinkpress_active_tab == 'moderate' ? 'nav-tab-active' : ''; ?>" 
		href="<?php echo esc_url(admin_url('admin.php?page=wplinkpress-lite&tab=moderate'));?>"><?php esc_html_e( 'Email notifications', 'wplinkpress-lite' ); ?> </a>
		</nav>
		<?php
	}

	public function wplinkpress_settings_tab_content() {
		global $wplinkpress_active_tab;
		if ('' || 'authorize' == $wplinkpress_active_tab ) { 
			//Display the Authorize Form 
		
			$args = array(
				'options'	=>	get_option('wplinkpress_authorize_settings')
			);
			
			wplinkpress_get_template("authorize-linkedin.php", WPLINKPRESS_LITE_PATH.'/admin/templates/tabs/', $args);
		} else if ('moderate' == $wplinkpress_active_tab) {
			$args = array();
			wplinkpress_get_template("comments-moderate.php", WPLINKPRESS_LITE_PATH.'/admin/templates/tabs/', $args);
		}
	}
	
}
function wplinkpress_lite_admin_actions() {
	return WPLinkPress_Lite_Admin_Actions::get_instance();
}