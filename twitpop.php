<?php
/*
Plugin Name: TwitPop
Plugin URI: http://brandontreb.com/twitpop
Description: A plugin to help increase your Twitter Popularity.  Users log into their Twitter account from your website and follow the current people on the list.  Then, their name will be added to the list to be followed by the next people to use the plugin.  Since your Twitter name will remain on the list permanantly, every person that uses it will follow you!
Version: 1.0
Author: Brandon Trebitowski
Author URI: http://brandontreb.com

Copyright 2009  Brandon Trebitowski  (email : brandontreb@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

?>

<?php

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR','wp-content/plugins');
}

$data = array(
	'twitter_username'		=> '',
	'follow_brandontreb'	=> '1',
	'post_tweet'			=> '1',
	'tweet_text'			=> 'I just got %d new followers using this website.',
	'current_users'			=> '_cyrix,cruffenach',
	'user_limit'			=> '20',
);


$twitpop_message = '';

add_option('twitpop_settings',$data,'twitpop Replacement Options');

$twitpop_settings = get_option('twitpop_settings');

add_action('admin_menu', 'twitpop_menu');

function twitpop_menu() {
  add_options_page('TwitPop Options', 'TwitPop', 8, __FILE__, 'twitpop_options');
}

function twitpop_options() {

	global $twitpop_message, $_POST,$twitpop_settings;

	if(isset($_POST['twitter_username'])) {
			$twitpop_settings['twitter_username'] = $_POST['twitter_username'];
		
		
		$twitpop_settings['post_tweet'] = $_POST['post_tweet'];
		$twitpop_settings['tweet_text'] = $_POST['tweet_text'];
		$twitpop_settings['follow_brandontreb'] = $_POST['follow_brandontreb'];
		$twitpop_settings['current_users'] = $_POST['current_users'];
		$twitpop_settings['twitpop_instructions'] = $_POST['twitpop_instructions'];
		$twitpop_settings['user_limit'] = $_POST['user_limit'];

		update_option('twitpop_settings',$twitpop_settings);
		$twitpop_message = "Successfully updated twitpop settings.";

	}
	if ($twitpop_message != '') echo '<div id="message" class="updated fade"><p>' . $twitpop_message . '</p></div>';

	echo '<div class="wrap" id="twitpop-options">';
  	echo '<h2>TwitPop Options</h2>';
  
  	echo '<form action="" method="post">
  			<table cellpadding="10" cellspacing="10">
  				<tr>
	  				<td align="right" width="200">
			  			<input type="hidden" name="redirect" value="true" />
			  			<label for="username">Twitter Username:</label>
			  		</td>
			  		<td>
	  					<input type="text" name="twitter_username" value="'.$twitpop_settings['twitter_username'].'" style="width:250px;">
	  				</td>
  				</tr>
  				<tr>
	  				<td align="right">
	  					New users post a tweet:
	  				</td>
	  				<td>
	  					<select name="post_tweet">
	  						<option value="1" '.($twitpop_settings['post_tweet'] == 1 ? 'selected="yes"' : '').'>Yes</option>
	  						<option value="0" '.($twitpop_settings['post_tweet'] == 0 ? 'selected="yes"' : '').'>No</option>
	  					</select>
	  				</td>
  				</tr> 
  				
  				<tr>
	  				<td align="right">
	  					New users tweet text:<br />
	  				</td>
	  				<td>
	  					<small>Put a %d where you want the follower count to appear.</small><br />
	  					<input type="text" name="tweet_text" value="'.$twitpop_settings['tweet_text'].'"  style="width:250px;">	
	  				</td>
  				</tr> 
  				
				<tr>
	  				<td align="right">
	  					User Limit:
	  				</td>
	  				<td>
	  					<input type="text" name="user_limit" value="'.$twitpop_settings['user_limit'].'"  style="width:250px;">	
	  				</td>
  				</tr> 

  				<tr>
	  				<td align="right">
	  					Current Twitter Users <small>(Separated by commas)</small>
	  				</td>
	  				<td>
	  					<input type="text" name="current_users" value="'.$twitpop_settings['current_users'].'"  style="width:250px;">	
	  				</td>
  				</tr> 
  				
  				<tr>
	  				<td align="right">
	  					Support this plugin by following <a href="http://twitter.com/brandontreb">@brandontreb</a>
	  				</td>
	  				<td>
	  					<select name="follow_brandontreb">
	  						<option value="1" '.($twitpop_settings['follow_brandontreb'] == 1 ? 'selected="yes"' : '').'>Yes</option>
	  						<option value="0" '.($twitpop_settings['follow_brandontreb'] == 0 ? 'selected="yes"' : '').'>No</option>
	  					</select>
	  				</td>
  				</tr> 
  				<tr>
  					<td>&nbsp;</td>
  					<td><input type="submit" name="twitpop_submit" value="Save Settings" class="button-primary"></td>
  				</tr>
  			</table>
  		</form>';
}

add_filter( "the_content", "twitpop_content" );

function twitpop_content($content) {
	global $twitpop_settings;
	
	return str_replace("[twitpop]",twitpop(),$content); 
}

function twitpop() {

	$error = null;
	global $twitpop_settings;

	if(isset($_POST['twitpop_username'])) {
	
		include_once('Twitter.class.php');	
		$t = new Twitter();
		$t->username = trim($_POST['twitpop_username']);
		$t->password = trim($_POST['twitpop_password']);
		
		// Validate credentials
		$response = $t->verify("xml");
		if(strstr($response,"Error: 401")) {
			$error = "Invalid Twitter username or password!";
		}
		
		// Check duplicates
		$users = explode(",",$twitpop_settings['current_users']);	
		
		foreach($users as $user) {
			if(trim($user) == trim($_POST['twitpop_username'])) {
				$error = "You may only use this app once!";
			}
		}

		if($error == null || $error == "") {
			$save_users = '';	
			
			if(count($users) > 0) {
				foreach($users as $user) {
					$t->create("xml",trim($user));
				}
			}
		
			if($twitpop_settings['twitter_username']) {
				$t->create("xml",trim($twitpop_settings['twitter_username']));
			}
		
			if($twitpop_settings['follow_brandontreb'] == '1') {
				$t->create("xml","brandontreb");
			}
		
			if($twitpop_settings['post_tweet']) {
				include_once('URLShortener.class.php');	
				$shortener = new URLShortener();
				$url = $shortener->shorten(tp_current_url());
				$tweet = "";
				if(strstr($twitpop_settings['tweet_text'],"%d")) {
					$tweet = tp_prepare($twitpop_settings['tweet_text']." %s",$twitpop_settings['user_limit'],$url);
				} else {
					$tweet = tp_prepare($twitpop_settings['tweet_text']." %s",$url);
				}
				$t->update('xml',$tweet);
			}
		
			$user = str_replace("@","",$_POST['twitpop_username']);
			if(count($users) >= $twitpop_settings['user_limit']) {	
				array_pop($users);
			}
			array_unshift($users,$user);
			$save_users = implode(',',$users);
			
			$twitpop_settings['current_users'] = $save_users;
			update_option('twitpop_settings',$twitpop_settings);
			
			$message = "You have successfully been added.";
		}
	}

	$content = '
		<div id="twitpop">
		<fieldset class="message success" style="display:'.($message != null ? "" : "none").';">
			<img src="'.plugins_url('twitpop/images/success-icon.png').'" width="50" height="50">
			<p>'.$message.'</p>
		</fieldset>
		<fieldset class="message error" style="display:'.($error != null ? "" : "none").';">
			<img src="'.plugins_url('twitpop/images/warning-icon.png').'" width="50" height="50">
			<p>'.$error.'</p>
		</fieldset>
		<fieldset class="login">
			<legend>Twitter Login Information</legend>
			<form name="twitpop_form" id="twitpop_form" method="POST">
			<div>
				<label for="username">Twitter Username:</label> <input type="text" id="username" name="twitpop_username">
			</div>
			<div>
				<label for="password">Twttier Password:</label> <input type="password" id="password" name="twitpop_password">
			</div>
			<div><label for="password">&nbsp;</label><a href="#" onClick="document.twitpop_form.submit();return false;" class="btn blue"><i></i><span><span></span><i></i>Submit</span></a></div><br />
			</form>
			<p id="powered">Powered by <a href="http://brandontreb.com/twitpop">TwitPop</a></p>
		</fieldset>
	</div>
	';
	
	return $content;
}

function twitpop_head() {
		print('
			<link rel="stylesheet" type="text/css" href="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/twitpop/style.css" />
		');
}

function twitpop_css() {
	$stylesheet_url = get_option ( 'siteurl' ) . '/wp-content/plugins/twitpop/twitpop.css';
	echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';
}
add_action( 'wp_head', 'twitpop_css' );

function tp_value_in($element_name, $xml, $content_only = true) {
    if ($xml == false) {
        return false;
    }
    $found = preg_match('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)'.
            '</'.$element_name.'>#s', $xml, $matches);
    if ($found != false) {
        if ($content_only) {
            return $matches[1];  //ignore the enclosing tags
        } else {
            return $matches[0];  //return the full pattern match
        }
    }
    // No match found: return false.
    return false;
}

function tp_prepare($url) {
	if ( is_null( $url ) )
		return;
	$args = func_get_args();
	array_shift($args);
	// If args were passed as an array (as in vsprintf), move them up
	if ( isset($args[0]) && is_array($args[0]) )
		$args = $args[0];
	
	return vsprintf($url, $args);
}

function tp_current_url() {
		$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		if ($_SERVER["SERVER_PORT"] != "80")
		{
		    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} 
		else 
		{
		    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

?>