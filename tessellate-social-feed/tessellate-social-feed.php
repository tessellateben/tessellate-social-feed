<?php
/**
 * @package Tessellate Social Feed
 */
/*
Plugin Name: Tessellate Social Feed
Plugin URI: http://www.tessellate.co.uk/
Description: Facebook and Twitter feed plugin.
Version: 1.2
Author: Tessellate
Author URI: http://www.tessellate.co.uk/
*/

add_action( 'admin_menu', 'socialfeed_add_admin_menu' );
add_action( 'admin_init', 'socialfeed_settings_init' );

function socialfeed_add_admin_menu() {

	add_menu_page('Social Feed','Social Feed','read','tessellate-social-feed','socialfeed_options_page');

	add_submenu_page( 'tessellate-social-feed', 'Social Feed', 'Social Feed', 'manage_options', 'tessellate-social-feed', 'socialfeed_options_page' );

}

require_once( 'class-wp-license-manager-client.php' );

if ( is_admin() ) {
    $license_manager = new Wp_License_Manager_Client(
        'tessellate-social-feed',
        'Tessellate Social Feed',
        'tessellate-social-feed',
        'https://dev.tessellate.co.uk/api/license-manager/v1',
        'plugin',
        __FILE__
    );
}

function tsf_get_twitter_feed() {

	require_once('twitter_proxy.php');

	$oauth_access_token = get_option('tsf_twitter_access_token');
	$oauth_access_token_secret = get_option('tsf_twitter_access_token_secret');
	$consumer_key = get_option('tsf_twitter_consumer_key');
	$consumer_secret = get_option('tsf_twitter_consumer_secret');
	$user_id = get_option('tsf_twitter_user_id');
	$screen_name = get_option('tsf_twitter_screen_name');
	$count = get_option('tsf_twitter_count');
	if($count == '') $count = 10;

	$twitter_url = 'statuses/user_timeline.json';
	$twitter_url .= '?user_id=' . $user_id;
	$twitter_url .= '&screen_name=' . $screen_name;
	$twitter_url .= '&count=' . $count;

	$twitter_proxy = new TwitterProxy(
		$oauth_access_token,
		$oauth_access_token_secret,
		$consumer_key,
		$consumer_secret,
		$user_id,
		$screen_name,
		$count
	);

	$tweets = json_decode($twitter_proxy->get($twitter_url));

	$return = array();

	if(!is_array($tweets) and property_exists($tweets,'errors')):

		$return[] = '<strong>ERROR:</strong> There was an error connecting to Twitter. Please check the Social Feed settings.';

	else:

		foreach($tweets as $tweet):

			$formatted_text = preg_replace('/(\b(www\.|http\:\/\/)\S+\b)/', "<a target='_blank' href='$1'>$1</a>", $tweet->text);
			$formatted_text = preg_replace('/(\b(www\.|https\:\/\/)\S+\b)/', "<a target='_blank' href='$1'>$1</a>", $formatted_text);
			$formatted_text = preg_replace('/\#(\w+)/', "<a target='_blank' href='http://twitter.com/search?q=$1'>#$1</a>", $formatted_text);
			$formatted_text = preg_replace('/\@(\w+)/', "<a target='_blank' href='http://twitter.com/$1'>@$1</a>", $formatted_text);
			$return[strtotime($tweet->created_at)] = $formatted_text;

		endforeach;

	endif;

	if(empty($return)):

		$return[0] = 'There are no Twitter posts to show.';

	endif;

	return $return;
}

function tsf_get_facebook_feed() {

	$appID = get_option('tsf_facebook_app_id');
	$appSecret = get_option('tsf_facebook_app_secret');
	$limit = get_option('tsf_facebook_count');
	if($limit == '') $limit = 10;

	$accessToken = $appID . '|' . $appSecret;

	$id = get_option('tsf_facebook_page_id');

	$url = "https://graph.facebook.com/$id/posts?access_token=$accessToken&limit=$limit";

	$return = array();

	if($result = @file_get_contents($url)):

		$posts = json_decode($result, true)['data'];

		foreach($posts as $post):

			if(isset($post['message'])):

				$return[strtotime($post['created_time'])] = $post['message'];

			endif;

		endforeach;

		if(empty($return)):

			$return[] = 'There are no posts to show.';

		endif;

	else:

		$return[1] = '<strong>ERROR:</strong> There was an error connecting to Facebook. Please check the Social Feed settings.';

	endif;

	return $return;
}

function socialfeed_settings_init() {

	register_setting('tsf_settings', 'tsf_twitter_access_token');
	register_setting('tsf_settings', 'tsf_twitter_access_token_secret');
	register_setting('tsf_settings', 'tsf_twitter_consumer_key');
	register_setting('tsf_settings', 'tsf_twitter_consumer_secret');
	register_setting('tsf_settings', 'tsf_twitter_user_id');
	register_setting('tsf_settings', 'tsf_twitter_screen_name');
	register_setting('tsf_settings', 'tsf_twitter_count');

	register_setting('tsf_settings', 'tsf_facebook_app_id');
	register_setting('tsf_settings', 'tsf_facebook_app_secret');
	register_setting('tsf_settings', 'tsf_facebook_page_id');
	register_setting('tsf_settings', 'tsf_facebook_count');

}

function socialfeed_options_page() {
	?>

	<div class="wrap">

		<h1>Social Feed Settings</h1>

		<div id="poststuff">

			<div id="post-body" class="columns-2">

				<form action="options.php" method="post">

					<?php settings_fields('tsf_settings') ?>

					<?php do_settings_sections('tsf_settings') ?>

					<div class="postbox">

						<h2 class="hndle">Twitter Settings</h2>

						<div class="inside">

							<p>Visit <a target="_blank" href="https://apps.twitter.com/">https://apps.twitter.com/</a> to set up a Twitter app and then copy the authentication details into this form.</p>

							<table class="form-table">

								<tr valign="top">

									<th scope="row">Access Token</th>

									<td>
										<input style="width:75%" type="text" name="tsf_twitter_access_token" value="<?= esc_attr(get_option('tsf_twitter_access_token')) ?>" />
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">Access Token Secret</th>

									<td>
										<input style="width:75%" type="text" name="tsf_twitter_access_token_secret" value="<?= esc_attr(get_option('tsf_twitter_access_token_secret')) ?>" />
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">Consumer Key</th>

									<td>
										<input style="width:75%" type="text" name="tsf_twitter_consumer_key" value="<?= esc_attr(get_option('tsf_twitter_consumer_key')) ?>" />
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">Consumer Secret</th>

									<td>
										<input style="width:75%" type="text" name="tsf_twitter_consumer_secret" value="<?= esc_attr(get_option('tsf_twitter_consumer_secret')) ?>" />
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">User ID</th>

									<td>
										<input style="width:75%" type="text" name="tsf_twitter_user_id" value="<?= esc_attr(get_option('tsf_twitter_user_id')) ?>" />

										<p class="description">

											Use <a target="_blank" href="http://gettwitterid.com/">http://gettwitterid.com/</a> to get your User ID.

										</p>
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">Screen Name</th>

									<td>
										<input style="width:75%" type="text" name="tsf_twitter_screen_name" value="<?= esc_attr(get_option('tsf_twitter_screen_name')) ?>" />
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">Number of tweets to display</th>

									<td>
										<input type="number" name="tsf_twitter_count" value="<?= esc_attr(get_option('tsf_twitter_count')) ?>" min="0" max="99" />
									</td>

								</tr>

							</table>

						</div>

					</div>

					<div class="postbox">

						<h2 class="hndle">Facebook Settings</h2>

						<div class="inside">

							<p>Visit <a target="_blank" href="https://developers.facebook.com/apps/">https://developers.facebook.com/apps/</a> to set up a Facebook app and then copy the authentication details into this form. You will need a Facebook developer account to do this.</p>

							<table class="form-table">

								<tr valign="top">

									<th scope="row">App ID</th>

									<td>
										<input style="width:75%" type="text" name="tsf_facebook_app_id" value="<?= esc_attr(get_option('tsf_facebook_app_id')) ?>" />
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">App Secret</th>

									<td>
										<input style="width:75%" type="text" name="tsf_facebook_app_secret" value="<?= esc_attr(get_option('tsf_facebook_app_secret')) ?>" />
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">Page ID</th>

									<td>
										<input style="width:75%" type="text" name="tsf_facebook_page_id" value="<?= esc_attr(get_option('tsf_facebook_page_id')) ?>" />

										<p class="description">

											Use <a target="_blank" href="http://findmyfbid.com/">http://findmyfbid.com/</a> to get your Page ID.

										</p>
									</td>

								</tr>

								<tr valign="top">

									<th scope="row">Number of posts to display</th>

									<td>
										<input type="number" name="tsf_facebook_count" value="<?= esc_attr(get_option('tsf_facebook_count')) ?>" min="0" max="99" />
									</td>

								</tr>

							</table>

						</div>

					</div>

					<?php submit_button() ?>

				</form>

			</div>

		</div>

	</div>

	<?php
}
?>