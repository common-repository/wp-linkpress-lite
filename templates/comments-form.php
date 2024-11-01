<?php
if(!isset($profile_info['profile_image']) || (isset($profile_info['profile_image']) && !($profile_info['profile_image']))) {
	$default_img =  WPLINKPRESS_LITE_URL.'/assets/media/non-user-icon.jpg';
}
else {
	$default_img = $profile_info['profile_image'];
}

$content = '';
$enable = false;
if(isset($_SESSION['comment'])){
$content = $_SESSION['comment'];
}


if(isset($_SESSION['feed']) && $_SESSION['feed']=='yes')
$enable = true;

if(isset($_SESSION['comment']) || isset($_SESSION['feed'])) {
do_action('end_comment_session');
}
?>
<input type="hidden" id="wplinkpress-authorize-url" value="<?php echo $authorizationUrl;?>" />
<input type="hidden" id="wplinkpress-post-id" value="<?php echo $post_id; ?>" />
<?php if($info_present) : ?>
<input type="hidden" id="current-comment-profileid" value="<?php echo $profile_info['profile_id']; ?>"/>
<input type="hidden" id="current-comment-firstname" value="<?php echo $profile_info['first_name']; ?>"/>
<input type="hidden" id="current-comment-lastname" value="<?php echo $profile_info['last_name']; ?>"/>
<input type="hidden" id="current-comment-imageurl" value="<?php echo $profile_info['profile_image']; ?>"/>
<?php endif; ?>
<div class="ui wplinkpress comments">
<h3 class="ui dividing header"><?php esc_html_e('Comments', 'wplinkpress-lite');?></h3>
 <form id="add-wplinkpress-comment" method="POST"> 
<div class="comment add-comment">
	<a class="avatar"><img src="<?php echo $default_img; ?>" /></a>
	<div class="content">
	<textarea id="wplinkpress-comment-text" style="width:100%;" placeholder="<?php esc_html_e('Add a comment...', 'wplinkpress-lite'); ?>"><?php echo $content; ?></textarea>
	<div class="bottom-layer">
		<div class="comment-atts" style="float:left;">
		<div class="feed-share">
		<label class="switch tips">
			<input type="checkbox" id="toggle-linkedin-feed" <?php echo $enable ? 'checked': '' ?>>
        	<span class="slider round"></span>
		</label>
		<span><?php esc_html_e('Share on activity feed','wplinkpress-lite'); ?></span>
		</div>
		</div>
	<div class="wplinkpress_buttons">
	<?php if($info_present): ?>
	<button id="post_comment" disabled="disabled"><?php esc_html_e('Post with Linkedin' ,'wplinkpress'); ?></button>
	<span class="wplinkpress-logout"><?php esc_html_e('Not','wplinkpress-lite'); ?>&nbsp;<?php echo $profile_info['first_name'] ?>?<a id="wplinkpress-logout" href="">&nbsp;<?php echo _e('Logout','wplinkpress-lite');?></a></span>
	<?php else: ?>
	<button id="authorize_comment" disabled="disabled"><?php esc_html_e('Post with LinkedIn', 'wplinkpress'); ?></button>
	<?php endif; ?>
	</div>
	</div>
	</div>
</div>
</form>
<?php if(!empty($comment_data)) :
foreach($comment_data as $data) {
	$timestamp = human_time_diff(strtotime($data->comment_time),current_time('timestamp'));
	$timestamp = str_replace(" mins","m",$timestamp);
	$timestamp = str_replace(" min","m",$timestamp);
	$timestamp = str_replace(" hours","h",$timestamp);
	$timestamp = str_replace(" hour","h",$timestamp);
	$timestamp = str_replace(" days","d",$timestamp);
	$timestamp = str_replace(" day","d",$timestamp);
	$profile_image_url = check_image_exists($data->comment_profileid);
	
?>
<div class="comment">
	<a class="avatar"><img src="<?php echo esc_url($profile_image_url); ?>" /></a>
	<div class="content">
		<a class="author"><?php echo stripslashes($data->comment_firstname) . '&nbsp;' . stripslashes($data->comment_lastname); ?></a>
		<div class="metadata">
			<input type="hidden" name="comment-id" value="<?php echo $data->comment_profileid; ?>"/>
			<span class="date"><?php echo $timestamp; ?></span>
		</div>
		<div class="text">
        	<?php echo stripslashes($data->comment_text); ?>
        </div>
	</div>
</div>
<?php } endif; ?>
<h3 class="ui dividing header"><span class="wplinkpress-brand"><?php esc_html_e('Powered by WP LinkPress', 'wplinkpress-lite');?></span></h3>
</div>