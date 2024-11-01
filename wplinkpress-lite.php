<?php
/*
*Plugin Name: WP LinkPress Lite
*Plugin URI: https://www.wplinkpress.com
*Description: Integrating LinkedIn comments on your WordPress website
*Version: 1.1
*Author: WP LinkPress
*Author URI:  https://wplinkpress.com/about/
*Text Domain: wplinkpress-lite
*Licence: GPL2
*/

define("WPLINKPRESS_LITE_PATH",plugin_dir_path(__FILE__)); // Plugin path
define("WPLINKPRESS_LITE_PROFILE_PATH",plugin_dir_path(__FILE__).'assets/profiles/');  // Profile Image Path
define("WPLINKPRESS_LITE_URL",plugins_url('',__FILE__)); // plugin url
define("WPLINKPRESS_PLUGIN_BASENAME",plugin_basename( __FILE__ ));
define("WPLINKPRESS_LITE_VERSION","1.0"); //Plugin version


function wplinkpress_init() {
	load_plugin_textdomain(	'wplinkpress-lite', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	if( !class_exists('WPLinkPress_Lite_Core'))
	require WPLINKPRESS_LITE_PATH.'/includes/class-wplinkpress-lite-core.php';
	
	WPLinkPress_Lite_Core::get_instance();
}

add_action('plugins_loaded','wplinkpress_init');

function initial_tasks() {
	$page_title = __('Authorizing Linkedin Access', 'wplinkpress-lite');
	$page_content = __('Please wait, authorization is under process', 'wplinkpress-lite');
	$slug = 'authorize-linkedin';
 	$new_page = array(
		'post_type'	=>	'page',
		'post_title'=>	$page_title,
		'post_content' => $page_content,
		'post_name'	=>   $slug,
		'post_status'=> 'publish',
		'post_author'=> 1
	);
	$page_check = get_page_by_title($page_title);

	if(!isset($page_check->ID)) {
		$new_page_id = wp_insert_post($new_page);
		add_option('wplinkpress_page_id', $new_page_id);
	} else {
		update_option('wplinkpress_page_id', $page_check->ID);
	}

	if(!get_option('wplinkpress_lite_notice_update')) {
		$values = array(
			'updated'			=>	'',
			'notice_success'	=>	''
		);
		add_option('wplinkpress_lite_notice_update', $values);
	}

	//create the database
	global $wpdb;
	$version = get_option( 'wplinkpress_lite_version', '1.0' );
	$table_name = $wpdb->prefix.'wplinkpress_comments';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		comment_id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id mediumint(9) NOT NULL,
		comment_text varchar(65535),
		comment_firstname varchar(50),
		comment_lastname varchar(50),
		comment_imageurl varchar(500),
		comment_profileid varchar(50),
		comment_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		UNIQUE KEY comment_id (comment_id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	if( version_compare( $version, '8.0' ) < 0 ) {
	
		$sql = "CREATE TABLE $table_name (
			comment_id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id mediumint(9) NOT NULL,
			comment_email varchar(100) DEFAULT NULL,
			comment_text varchar(65535),
			comment_firstname varchar(50),
			comment_lastname varchar(50),
			comment_imageurl varchar(500),
			comment_profileid varchar(50),
			comment_position varchar(500),
			comment_share varchar(4) DEFAULT 'No',
			comment_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			comment_display char(4) DEFAULT 'yes' NOT NULL,
			comment_vanityname varchar(100) DEFAULT NULL,
			comment_parent mediumint(9),
			comment_referal mediumint(9),
			UNIQUE KEY comment_id (comment_id)
		) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
	update_option( 'wplinkpress_lite_version', '8.0' );

	}

	create_wplinkpress_dir();

}
register_activation_hook(__FILE__,'initial_tasks');

function wplinkpress_base_dir() {
	$upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $wplinkpress_profile_dir = $upload_dir . '/wplinkpress/profiles';
	return $wplinkpress_profile_dir;
}

function wplinkpress_base_url() {
	$upload = wp_upload_dir();
    $upload_dir = $upload['baseurl'];
    $wplinkpress_profile_url = $upload_dir . '/wplinkpress/profiles';
	return $wplinkpress_profile_url;
}

function create_wplinkpress_dir() {
	$wplinkpress_profile_dir = wplinkpress_base_dir();
    if (!is_dir($wplinkpress_profile_dir)) {
		//check for parent folder
		//$parent_dir = $upload_dir . '/wplinkpress';
		if( !is_dir($wplinkpress_profile_dir))
		wp_mkdir_p( $wplinkpress_profile_dir, 0700 );
       //mkdir( $wplinkpress_profile_dir, 0700 );
    }
}
add_action( 'admin_notices', 'update_wplinkpress_lite_notice');
function update_wplinkpress_lite_notice() {
	$wplinkpress_notice_update = get_option('wplinkpress_lite_notice_update');
	$update_status = $wplinkpress_notice_update['updated'];
	$notice_success = $wplinkpress_notice_update['notice_success'];
	if(!($update_status) || $update_status != 'yes') {
		//Display Update Notice
		?>
		<div class="notice notice-info">
			<p><span style="font-size:14px; font-weight:500;"><?php _e('WP LinkPress Lite database update is required, please click button to proceed','wplinkpress-lite');?></span><a style="float: right;padding:4px 6px;background-color:#0073aa;color:#fff;text-decoration:none;" href="<?php echo admin_url( 'admin.php?page=wplinkpress-lite&action=wplinkpress_lite_update' ); ?>">Update Now</a></p>
		</div>
		<?php
	}
	if($update_status == 'yes' && $notice_success != 'yes') {
		//Display Update success notice
	?>
	<div class="notice notice-success">
        <p><?php _e( 'WP LinkPress Lite plugin has been updated!', 'wplinkpress-lite' ); ?></p>
    </div>
	<?php
	$wplinkpress_notice_update['updated']  = $wplinkpress_notice_update['notice_success'] = 'yes';
	update_option('wplinkpress_lite_notice_update',$wplinkpress_notice_update);
	}
}
function check_image_exists($profile_id,$actual_url='') {
	$upload_dir = wp_upload_dir();
	$upload_base_url = $upload_dir['baseurl'];
	$upload_base_dir = $upload_dir['basedir'];
	$profile_path = $upload_base_dir.'/wplinkpress/profiles/'.$profile_id.'.jpg';
	$profile_url = $upload_base_url.'/wplinkpress/profiles/'.$profile_id.'.jpg';

	if(file_exists($profile_path)) {
		//get the relative url
		$return_url = $profile_url;
		return $return_url;
	} else if($actual_url != '') {
		//Some reason it doesn't exist so try to download the image and return on success
		$file = array();
		$file['name'] = $profile_id;
		$file['tmp_name'] = download_url($actual_url);
		$file['type'] = 'image/jpeg';
		
		if (!is_wp_error($file['tmp_name'])) {
			return $actual_url;
		}
	}
	$default_url = WPLINKPRESS_LITE_URL.'/assets/media/non-user-icon.jpg';
	
	return $default_url;
}


