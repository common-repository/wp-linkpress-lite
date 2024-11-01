<?php

function get_access_token_from_linkedin($authorized_code,$options) {
	$token_url = 'https://www.linkedin.com/oauth/v2/accessToken?grant_type=authorization_code&';
	$fields = array(
		'code'	=>	$authorized_code,
		'redirect_uri'	=>	$options['redirect_url'],
		'client_id'		=>	$options['client_id'],
		'client_secret' => $options['client_secret'],
	);
	$token_url = $token_url.http_build_query($fields,'',"&");
	$token_data = get_api_data($token_url, '', 'POST', 'application/x-www-form-urlencoded', '',200);
	//Set the cookie
	if(!isset($token_data['error']) && isset($token_data['decodedBody']['access_token'])) {
		//set the cookie
		//Access token set
		setcookie('wplinkpress_access_token', $token_data['decodedBody']['access_token'], time() + 86400 * 60,"/");
		//Return the success message
		return $token_data['decodedBody']['access_token'];
		
	}
}

function get_profile_info($access_token, $options) {
	$result = array();
	$image_url = 'https://api.linkedin.com/v2/me?oauth2_access_token='.$access_token.'&projection=(id,profilePicture(displayImage~:playableStreams))';
	$profile_url = 'https://api.linkedin.com/v2/me?oauth2_access_token='.$access_token;
	$profile_data = get_api_data($profile_url,$access_token, 'GET', '','', 200);
	$data=array();
	if(!isset($profile_data['error'])) {
		$data['first_name'] = $profile_data['decodedBody']['localizedFirstName'];
		$data['last_name'] = $profile_data['decodedBody']['localizedLastName'];
		$data['profile_id'] = $profile_data['decodedBody']['id'];
		//check if profile image exist in wplinkpress datatable
		$profile_image_url = WPLINKPRESS_LITE_URL.'/assets/media/non-user-icon.jpg';
		$profile_id = $data['profile_id'];
		//retrieve via API and then save as attachment
		$profile_image = get_api_data($image_url,$access_token, 'GET', '','', 200);
		if(!isset($profile_image['error'])) {
			$data['profile_image'] = '';
			if(isset($profile_image['decodedBody']['profilePicture']['displayImage~']['elements'][0]['identifiers'][0]['identifier']))
			$data['profile_image'] = $profile_image['decodedBody']['profilePicture']['displayImage~']['elements'][0]['identifiers'][0]['identifier'];
				//save as attachment and insert new/update entry to DB
				$data['profile_image'] = new_profile_image_db($data['profile_id'],$data['profile_image']);
				} else {
				$data['profile_image'] = $profile_image_url;
				}
		} else {
			$data['profile_image'] = $profile_image_url;
		
		}

	return $data;
}

function new_profile_image_db($profile_id='',$url='') {
	
	include_once( ABSPATH . 'wp-admin/includes/admin.php' );
	if(!defined('ALLOW_UNFILTERED_UPLOADS'))
	define('ALLOW_UNFILTERED_UPLOADS', true);
	
	$return_url = wplinkpress_base_url();
	$wplinkpress_dir = wplinkpress_base_dir();
	create_wplinkpress_dir();
	$default_url = WPLINKPRESS_LITE_URL.'/assets/media/non-user-icon.jpg';
	
	// If the function it's not available, require it.
	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	require_once( ABSPATH . 'wp-includes/pluggable.php' );
	require_once(ABSPATH . 'wp-admin/includes/image.php');

	if(empty($url) || empty($profile_id))
	return false;

	$file = array();
    $file['name'] = $profile_id;
	$file['tmp_name'] = download_url($url);
	$file['type'] = 'image/jpeg';


	if (is_wp_error($file['tmp_name'])) {
		@unlink($file['tmp_name']);
		return $default_url;
	} else {

		$new_file_path = $wplinkpress_dir.'/'.$file['name'];
		$new_file_mime = mime_content_type( $file['tmp_name'] );

		
		if( !in_array( $new_file_mime, get_allowed_mime_types() ) )
			die( 'WordPress doesn\'t allow this type of uploads.' );

		$new_file_path = $new_file_path . '.jpg';
		// looks like everything is OK
		if( @copy( $file['tmp_name'], $new_file_path ) ) {
			@unlink($file['tmp_name']);
			$return_url = $return_url . '/'. $file['name'] . '.jpg';
			return $return_url;
		
		} else {
			return $url;
		}
	}
	
	

}

function share_post_to_linkedin($access_token,$options,$data,$post_id) {
	$url = 'https://api.linkedin.com/v2/ugcPosts?oauth2_access_token='.$access_token;

	$text = strip_tags($data['comment_text']);
	$permalink = get_permalink($post_id);
	$title = get_the_title($post_id);
	$description = get_the_excerpt($post_id);
	$new_fields = '{
    "author": "urn:li:person:'.$data['profile_id'].'",
    "lifecycleState": "PUBLISHED",
    "specificContent": {
        "com.linkedin.ugc.ShareContent": {
            "shareCommentary": {
                "text": '.json_encode($text).'
            },
            "shareMediaCategory": "ARTICLE",
            "media": [
                {
                    "status": "READY",
                    "description": {
                        "text": '.json_encode($description).'
                    },
                    "originalUrl": "'.$permalink.'",
                    "title": {
                        "text": '.json_encode($title).'
                    }
                }
            ]
        }
    },
    "visibility": {
        "com.linkedin.ugc.MemberNetworkVisibility": "CONNECTIONS"
    }
}';
	
	$data = get_api_data($url,$access_token,'POST','application/json',$new_fields,201);

	/*if(isset($data['decodedBody']['id'])){
		$urn = $data['decodedBody']['id'];
		$comment_url = 'https://api.linkedin.com/v2/socialActions/'.$urn.'/comments';
		$comment_fields = '{
    	"actor": "urn:li:person:'.$data['profile_id'].'",
    	"message": {
        	"attributes": [],
        	"text": "'.$text.'"
    	}';
	}*/

	/*$comment_data = get_api_data($comment_url,'POST','application_json',$comment_fields,201);
*/
	
}

function get_api_data($url, $access_token = '', $method='GET', $content_type='',$fields = array(),$response_code = 200) {
	$result = array();
	$args = array(
		'method'	=> $method,
		'headers'	=> array("Content-type" => $content_type),
		'sslverify' => true,
	);
	if($access_token !== '') {
		$args['headers']['Authorization'] =  'Bearer' . $access_token;
	}

	if($response_code === 201) {
		$args['headers']['X-Restli-Protocol-Version'] = '2.0.0';
		$args['body'] = $fields;
	}
	$response = wp_remote_request( $url, $args );
	$received_code = wp_remote_retrieve_response_code( $response );
	if($received_code === $response_code) {
		$result['decodedBody'] = json_decode(preg_replace('/("\w+"):(\d+(\.\d+)?)/', '\\1:"\\2"', $response['body']), true);
	} else {
		$result['error']['error_code'] = $received_code;
		$result['error']['message'] = wp_remote_retrieve_response_message( $response );
	}

	return $result;

}