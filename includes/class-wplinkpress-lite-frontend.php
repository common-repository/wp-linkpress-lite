<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLinkPress_Lite_Frontend {

	public function __construct() {
		add_action('init', array($this, 'start_session'),1);
		add_filter('the_content', array($this, 'display_comment_module'), 1000);
		add_shortcode('wplinkpress_comments', array($this,'render_comments'));
		add_action('end_comment_session', array($this, 'end_session'));
		add_action('wp_ajax_post_comment', array($this,'post_new_comment'));
		add_action('wp_ajax_nopriv_post_comment', array($this,'post_new_comment'));
		add_action('wp_ajax_wplinkpress_logout', array($this, 'wplinkpress_logout'));
		add_action('wp_ajax_nopriv_wplinkpress_logout', array($this, 'wplinkpress_logout'));
		//add_action('init',array($this, 'wplinkpress_logout'));
	}

	public function start_session() {
		if(!session_id()) {
			session_start();
		}
	}

	public function end_session() {
		session_destroy();
	}

	public function post_new_comment() {
			global $wpdb;
			$table=$wpdb->prefix.'wplinkpress_comments';
			
			if($_POST['imageurl'] === '') {
				$_POST['imageurl'] = WPLINKPRESS_LITE_URL.'/assets/media/non-user-icon.jpg';
			}

			$post_id = sanitize_text_field($_POST['post_id']);
			$comment_text = sanitize_text_field(stripslashes($_POST['text']));
			$comment_firstname = sanitize_text_field(stripslashes($_POST['firstname']));
			$comment_lastname = sanitize_text_field(stripslashes($_POST['lastname']));
			$comment_imageurl = esc_url_raw($_POST['imageurl']);
			$profile_id = sanitize_text_field($_POST['profileid']);
			$comment_data = array(
				'post_id'	=>	$post_id,
				'comment_text' => $comment_text,
				'comment_firstname' =>	$comment_firstname,
				'comment_lastname'	=>	$comment_lastname,
				'comment_imageurl'	=>	$comment_imageurl,
				'comment_profileid' =>	$profile_id,
				'comment_time'		=>	current_time('mysql'),
				'comment_position'	=>	''

			);
			
			$format = array('%d','%s','%s','%s','%s','%s','%s','%s');
			$id = $wpdb->insert($table,$comment_data,$format);
			
			$data = '';
			$data .= '<div class="comment">';
			$data .= '<a class="avatar"><img src="'.esc_url($_POST['imageurl']).'" /></a>';
			$data .= '<div class="content">';
			$data .= '<a class="author">'.$comment_firstname . '&nbsp;' . $comment_lastname .'</a>';
			$data .= '<div class="metadata">';
			$data .= '<input type="hidden" name="comment-id" value="'.$profile_id.'"/>';
			$data .= '<span class="date">now</span>';
			$data .= '</div>';
			$data .= '<div class="text">'.$comment_text;
        	$data .= '</div></div></div>';
			
			//Share the post
			if($_POST['feed'] == 'yes') {
			$access_token = $_COOKIE['wplinkpress_access_token'];
			$api_data['profile_id'] = $profile_id;
			$api_data['comment_text'] = $comment_text;
			$options = get_option('wplinkpress_authorize_settings');
			share_post_to_linkedin($access_token,$options,$api_data,$post_id);
			}
		echo json_encode($data);
		wp_die();
	}
	public function display_comment_module($content) {
		global $post;
		if(isset($post->ID)) {
		$enabled = get_post_meta($post->ID, '_enable_wplinkpress_comments', true);
		if(isset($enabled) && $enabled === 'yes') {
			$content .= do_shortcode('[wplinkpress_comments]');
		}
		}
		return $content;
	}

	public function render_comments() {
		ob_start();
		global $wpdb;
		$table=$wpdb->prefix.'wplinkpress_comments';
		$post_id = get_the_ID();
		//Get comments by ID
		$options = get_option('wplinkpress_authorize_settings');
		$profile_info = array();
		$info_present = false;
		
		if(isset($_COOKIE['wplinkpress_access_token'])) {
			//Get the name,profile image, and designation
			$access_token = $_COOKIE['wplinkpress_access_token'];
			$profile_info = get_profile_info($access_token, $options);
			if(isset($profile_info['first_name']) || isset($profile_info['last_name'])) {
				$info_present = true;
			} 
		}
		
		$comment_data = $wpdb->get_results("SELECT * FROM $table where post_id = $post_id AND comment_display = 'yes' ORDER BY comment_id DESC");
		$args = array(
			'authorizationUrl' => wplinkpress_authorization_url($options),
			'options'	=>	$options,
			'info_present' => $info_present,
			'profile_info' => $profile_info,
			'comment_data' => $comment_data,
			'post_id'	=>	$post_id
		);
		?>
		<?php
		wplinkpress_get_template("comments-form.php", WPLINKPRESS_LITE_PATH.'/templates/',$args);
		return ob_get_clean();
	}

	public function wplinkpress_logout() {
		if(!isset($_GET['linkedin-logout']))
		return;

		if($_GET['linkedin-logout'] === 'true') {
			if(isset($_GET['comment_text'])) {
				$_SESSION['comment'] = $_GET['comment_text'];
			}
			if(isset($_GET['feed'])) {
				$_SESSION['feed'] = $_GET['feed'];
			}
			unset( $_COOKIE['wplinkpress_access_token'] );
			setcookie( 'wplinkpress_access_token', '', time() - ( 15 * 60 ),"/");
			//wp_safe_redirect(home_url( wp_get_referer() ));
			wp_send_json_success();
			wp_die();
		}
	}

}

new WPLinkPress_Lite_Frontend();