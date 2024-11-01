<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
//Get Template
if( !function_exists('wplinkpress_get_template') ) {
	function wplinkpress_get_template($template_name, $path='', $args=array(), $return = false) {
		$located = wplinkpress_locate_template($template_name, $path);
		if ( $args && is_array ( $args ) ) {
	        extract ( $args );
	    }

	    if ( $return ) {
	        ob_start ();
	    }

	    // include file located
	    if ( file_exists ( $located ) ) {
	        include ( $located );
	    }

	    if ( $return ) {
	        return ob_get_clean ();
	    }
	}
}

//Locate Template
if( !function_exists('wplinkpress_locate_template')) {
	function wplinkpress_locate_template($template_name,$template_path) {
	// Look within passed path within the theme - this is priority.
		$template = locate_template(
			array(
				'templates/' . $template_name,
				$template_name,
			)
		);

		if ( ! $template ) {
	        $template = trailingslashit( $template_path ) . $template_name;
	    }

	    return $template;
	}
}

if( !function_exists('wplinkpress_authorization_url') ) {
	function wplinkpress_authorization_url($options) {
		$url = 'https://www.linkedin.com/oauth/v2/authorization?';
		if(is_admin())
		$current_url = admin_url('admin.php').'?page=wplinkpress&tab=authorize';
		else {
		global $wp;
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		}

		$fields = array(
			'response_type' => 'code',
			'client_id'	=> $options['client_id'],
			'redirect_uri' => $options['redirect_url'],
			'state'	=> $current_url
		);
		$url= $url.http_build_query($fields,'',"&");
		$scope = array('r_emailaddress','r_liteprofile','w_member_social');
		$url = $url.'&scope=r_liteprofile%20r_emailaddress%20w_member_social';
		return $url;
	}
}

