<?php
/**
 * @package Tessellate Social Feed
 */
/*
Plugin Name: Tessellate Social Feed
Plugin URI: http://www.tessellate.co.uk/
Description: Facebook and Twitter feed plugin.
Version: 0.2
Author: Tessellate
Author URI: http://www.tessellate.co.uk/
*/

require 'plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker = PucFactory::buildUpdateChecker(
    'http://updates.tessellateonline.co.uk/plugins/tessellate-social-feed/info.json',
    __FILE__,
    'tessellate-social-feed'
);

function tsf_get_twitter_feed() {

	require_once('twitter_proxy.php');

	// Twitter OAuth Config options
	$oauth_access_token = get_option('socialfeed_settings')['socialfeed_twitter_access_token'];
	$oauth_access_token_secret = get_option('socialfeed_settings')['socialfeed_twitter_access_token_secret'];
	$consumer_key = get_option('socialfeed_settings')['socialfeed_twitter_consumer_key'];
	$consumer_secret = get_option('socialfeed_settings')['socialfeed_twitter_consumer_secret'];
	$user_id = get_option('socialfeed_settings')['socialfeed_twitter_user_id'];
	$screen_name = get_option('socialfeed_settings')['socialfeed_twitter_screen_name'];
	$count = get_option('socialfeed_settings')['socialfeed_twitter_count'];

	$twitter_url = 'statuses/user_timeline.json';
	$twitter_url .= '?user_id=' . $user_id;
	$twitter_url .= '&screen_name=' . $screen_name;
	$twitter_url .= '&count=' . $count;

	// Create a Twitter Proxy object from our twitter_proxy.php class
	$twitter_proxy = new TwitterProxy(
		$oauth_access_token,			// 'Access token' on https://apps.twitter.com
		$oauth_access_token_secret,		// 'Access token secret' on https://apps.twitter.com
		$consumer_key,					// 'API key' on https://apps.twitter.com
		$consumer_secret,				// 'API secret' on https://apps.twitter.com
		$user_id,						// User id (http://gettwitterid.com/)
		$screen_name,					// Twitter handle
		$count							// The number of tweets to pull out
	);

	// Invoke the get method to retrieve results via a cURL request
	$tweets = json_decode($twitter_proxy->get($twitter_url));

	$return = array();
	foreach($tweets as $tweet) {
		$formatted_text = preg_replace('/(\b(www\.|http\:\/\/)\S+\b)/', "<a target='_blank' href='$1'>$1</a>", $tweet->text);
		$formatted_text = preg_replace('/(\b(www\.|https\:\/\/)\S+\b)/', "<a target='_blank' href='$1'>$1</a>", $formatted_text);
		$formatted_text = preg_replace('/\#(\w+)/', "<a target='_blank' href='http://twitter.com/search?q=$1'>#$1</a>", $formatted_text);
		$formatted_text = preg_replace('/\@(\w+)/', "<a target='_blank' href='http://twitter.com/$1'>@$1</a>", $formatted_text);
		$return[strtotime($tweet->created_at)] = $formatted_text;
	}
	return $return;
}

function tsf_get_facebook_feed() {

	//Set your App ID and App Secret.
	$appID = get_option('socialfeed_settings')['socialfeed_facebook_app_id'];
	$appSecret = get_option('socialfeed_settings')['socialfeed_facebook_app_secret'];
	$limit = get_option('socialfeed_settings')['socialfeed_facebook_count'];

	//Create an access token using the APP ID and APP Secret.
	$accessToken = $appID . '|' . $appSecret;

	//The ID of the Facebook page in question.
	$id = get_option('socialfeed_settings')['socialfeed_facebook_page_id'];

	//Tie it all together to construct the URL
	$url = "https://graph.facebook.com/$id/posts?access_token=$accessToken&limit=$limit";

	//Make the API call
	$result = file_get_contents($url);

	//Decode the JSON result.
	$posts = json_decode($result, true)['data'];

	$return = array();
	foreach($posts as $post) {
		if(isset($post['message'])) {
			$return[strtotime($post['created_time'])] = $post['message'];
		}
	}
	return $return;
}

add_action( 'admin_menu', 'socialfeed_add_admin_menu' );
add_action( 'admin_init', 'socialfeed_settings_init' );

function socialfeed_add_admin_menu() {
	add_menu_page( 'Social Feed', 'Social Feed', 'manage_options', 'socialfeed', 'socialfeed_options_page' );
}

function socialfeed_settings_init() {

	register_setting( 'pluginPage', 'socialfeed_settings' );

	add_settings_section(
		'socialfeed_twitter_section',
		__( 'Twitter Feed Settings', 'socialfeed' ),
		'socialfeed_twitter_settings_section_callback',
		'pluginPage'
	);

	add_settings_field(
		'socialfeed_twitter_access_token',
		__( 'Access Token', 'socialfeed' ),
		'socialfeed_twitter_access_token_render',
		'pluginPage',
		'socialfeed_twitter_section'
	);

	add_settings_field(
		'socialfeed_twitter_access_token_secret',
		__( 'Access Token Secret', 'socialfeed' ),
		'socialfeed_twitter_access_token_secret_render',
		'pluginPage',
		'socialfeed_twitter_section'
	);

	add_settings_field(
		'socialfeed_twitter_consumer_key',
		__( 'Consumer Key', 'socialfeed' ),
		'socialfeed_twitter_consumer_key_render',
		'pluginPage',
		'socialfeed_twitter_section'
	);

	add_settings_field(
		'socialfeed_twitter_consumer_secret',
		__( 'Consumer Secret', 'socialfeed' ),
		'socialfeed_twitter_consumer_secret_render',
		'pluginPage',
		'socialfeed_twitter_section'
	);

	add_settings_field(
		'socialfeed_twitter_user_id',
		__( 'User ID', 'socialfeed' ),
		'socialfeed_twitter_user_id_render',
		'pluginPage',
		'socialfeed_twitter_section'
	);

	add_settings_field(
		'socialfeed_twitter_screen_name',
		__( 'Screen Name', 'socialfeed' ),
		'socialfeed_twitter_screen_name_render',
		'pluginPage',
		'socialfeed_twitter_section'
	);

	add_settings_field(
		'socialfeed_twitter_count',
		__( 'Number of tweets to return', 'socialfeed' ),
		'socialfeed_twitter_count_render',
		'pluginPage',
		'socialfeed_twitter_section'
	);


	add_settings_section(
		'socialfeed_facebook_section',
		__( 'Facebook Feed Settings', 'socialfeed' ),
		'socialfeed_facebook_settings_section_callback',
		'pluginPage'
	);

	add_settings_field(
		'socialfeed_facebook_app_id',
		__( 'App ID', 'socialfeed' ),
		'socialfeed_facebook_app_id_render',
		'pluginPage',
		'socialfeed_facebook_section'
	);

	add_settings_field(
		'socialfeed_facebook_app_secret',
		__( 'App Secret', 'socialfeed' ),
		'socialfeed_facebook_app_secret_render',
		'pluginPage',
		'socialfeed_facebook_section'
	);

	add_settings_field(
		'socialfeed_facebook_page_id',
		__( 'Page ID', 'socialfeed' ),
		'socialfeed_facebook_page_id_render',
		'pluginPage',
		'socialfeed_facebook_section'
	);

	add_settings_field(
		'socialfeed_facebook_count',
		__( 'Number of posts to return', 'socialfeed' ),
		'socialfeed_facebook_count_render',
		'pluginPage',
		'socialfeed_facebook_section'
	);
}

function socialfeed_twitter_access_token_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_twitter_access_token]' value='<?php echo $options['socialfeed_twitter_access_token']; ?>' class='regular-text'>
	<?php
}

function socialfeed_twitter_access_token_secret_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_twitter_access_token_secret]' value='<?php echo $options['socialfeed_twitter_access_token_secret']; ?>' class='regular-text'>
	<?php
}

function socialfeed_twitter_consumer_key_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_twitter_consumer_key]' value='<?php echo $options['socialfeed_twitter_consumer_key']; ?>' class='regular-text'>
	<?php
}

function socialfeed_twitter_consumer_secret_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_twitter_consumer_secret]' value='<?php echo $options['socialfeed_twitter_consumer_secret']; ?>' class='regular-text'>
	<?php
}

function socialfeed_twitter_user_id_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_twitter_user_id]' value='<?php echo $options['socialfeed_twitter_user_id']; ?>' class='regular-text'>
	<p class="description">Use <a target="_blank" href="http://gettwitterid.com/">http://gettwitterid.com/</a> to get your User ID</p>
	<?php
}

function socialfeed_twitter_screen_name_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_twitter_screen_name]' value='<?php echo $options['socialfeed_twitter_screen_name']; ?>' class='regular-text'>
	<?php
}

function socialfeed_twitter_count_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='number' min='1' name='socialfeed_settings[socialfeed_twitter_count]' value='<?php echo $options['socialfeed_twitter_count']; ?>' class='small-text'>
	<?php
}

function socialfeed_facebook_app_id_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_facebook_app_id]' value='<?php echo $options['socialfeed_facebook_app_id']; ?>' class='regular-text'>
	<?php
}

function socialfeed_facebook_app_secret_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_facebook_app_secret]' value='<?php echo $options['socialfeed_facebook_app_secret']; ?>' class='regular-text'>
	<?php
}

function socialfeed_facebook_page_id_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='text' name='socialfeed_settings[socialfeed_facebook_page_id]' value='<?php echo $options['socialfeed_facebook_page_id']; ?>' class='regular-text'>
	<p class="description">Use <a target="_blank" href="http://findmyfbid.com/">http://findmyfbid.com/</a> to get your Page ID</p>
	<?php
}

function socialfeed_facebook_count_render() {

	$options = get_option( 'socialfeed_settings' );
	?>
	<input type='number' min='1' name='socialfeed_settings[socialfeed_facebook_count]' value='<?php echo $options['socialfeed_facebook_count']; ?>' class='small-text'>
	<?php
}

function socialfeed_twitter_settings_section_callback() {
	echo __( 'Settings for the Twitter feed', 'socialfeed' );
}

function socialfeed_facebook_settings_section_callback() {
	echo __( 'Settings for the Facebook feed', 'socialfeed' );
}

function socialfeed_options_page() {
	?>
	<div class="wrap">
		<form action='options.php' method='post'>

			<h1>Social Feed Settings</h1>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

			<?php
			if(isset($_GET['debug']) and $_GET['debug']):

				echo '<h3>Twitter Posts</h3>';

				echo '<pre>';
				print_r(tsf_get_twitter_feed());
				echo '</pre>';

				echo '<h3>Facebook Posts</h3>';

				echo '<pre>';
				print_r(tsf_get_facebook_feed());
				echo '</pre>';

			endif;
			?>

		</form>
	</div>
	<?php
}
?>