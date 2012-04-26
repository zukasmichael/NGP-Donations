<?php
/*
	Plugin Name: NGP Donations
	Plugin URI: http://revolutionmessaging.com
	Description: Integrate NGP donation forms with your site
	Version: 0.2.1
	Author: Revolution Messaging
	Author URI: http://revolutionmessaging.com
	Tags: NGP, NGPVAN, Voter Action Network, donations, FEC, politics, fundraising
	License: MIT

	Copyright 2011 Revolution Messaging LLC (email: support@revolutionmessaging.com)
	
	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
	*/

$GLOBALS['ngp'] = (object) array(
	// 'forms' => $GLOBALS['wpdb']->prefix . 'ngp_forms',
	// 'database_version' => '0.1',
	'version' => '0.2.1'
);

include_once(dirname(__FILE__).'/NgpDonation.php');
// include_once(dirname(__FILE__).'/ngp-setup.php');
// include_once(dirname(__FILE__).'/ngp-functions.php');
include_once(dirname(__FILE__).'/ngp-frontend.php');

if(strpos($_SERVER['REQUEST_URI'], 'ngp-donations/admin')!==false) {
	include_once(dirname(__FILE__).'/ngp-manage.php');
}

if (!function_exists('add_action')){
	require_once("../../../wp-config.php");
}

add_action('admin_init', 'ngp_admin_init');
add_shortcode('ngp_show_form', 'ngp_show_form');

if(isset($_POST['ngp_add'])) {
	add_action('wp', 'ngp_process_form');
}

function ngp_admin_init() {
	// add_action('admin_menu', 'ngp_plugin_menu');
	register_setting('general', 'ngp_api_key', 'esc_attr');
	add_settings_field(
		'ngp_api_key',
		'<label for="ngp_api_key">'.__('NGP API Key' , 'ngp_api_key' ).'</label>',
		'ngp_api_key_field',
		'general'
	);
	register_setting('general', 'ngp_thanks_url', 'esc_attr');
	add_settings_field(
		'ngp_thanks_url',
		'<label for="ngp_api_key">'.__('"Thanks for Contributing" URL' , 'ngp_thanks_url' ).'<br /> (e.g. "/thank-you")</label>',
		'ngp_thanks_url_field',
		'general'
	);
	register_setting('general', 'ngp_secure_url', 'esc_attr');
	add_settings_field(
		'ngp_secure_url',
		'<label for="ngp_secure_url">'.__('Secure URL (No https://)' , 'ngp_secure_url' ).'</label>',
		'ngp_secure_url',
		'general'
	);
	register_setting('general', 'ngp_support_phone', 'esc_attr');
	add_settings_field(
		'ngp_support_phone',
		'<label for="ngp_support_phone">'.__('Donation Support Phone Line' , 'ngp_support_phone' ).'</label>',
		'ngp_support_phone',
		'general'
	);
	register_setting('general', 'ngp_footer_info', 'esc_attr');
	add_settings_field(
		'ngp_footer_info',
		'<label for="ngp_footer_info">'.__('Addt\'l Information for Donation Footer' , 'ngp_footer_info' ).'</label>',
		'ngp_footer_info',
		'general'
	);
	// add_action('wp_head', 'ngp_head');
	// add_action('admin_head', 'ngp_head');
}

function ngp_api_key_field() {
	$value = get_option('ngp_api_key', '');
	echo '<input type="text" style="width:300px;" id="ngp_api_key" name="ngp_api_key" value="' . $value . '" />';
}

function ngp_thanks_url_field() {
	$value = get_option('ngp_thanks_url', '');
	echo '<input type="text" style="width:300px;" id="ngp_thanks_url" name="ngp_thanks_url" value="' . $value . '" />';
}

function ngp_support_phone() {
	$value = get_option('ngp_support_phone', '');
	echo '<input type="text" style="width:150px;" id="ngp_support_phone" name="ngp_support_phone" value="' . $value . '" />';
}

function ngp_secure_url() {
	$value = get_option('ngp_secure_url', '');
	echo '<input type="text" style="width:150px;" id="ngp_secure_url" name="ngp_secure_url" value="' . $value . '" />';
}

function ngp_footer_info() {
	$value = get_option('ngp_footer_info', '');
	echo '<textarea style="width:300px;height:150px;" id="ngp_footer_info" name="ngp_footer_info">'.$value.'</textarea>';
}

// register_activation_hook(__FILE__, 'psc_activate');
// register_deactivation_hook(__FILE__, 'psc_deactivate');
// register_uninstall_hook(__FILE__, 'psc_uninstall');

// function ngp_plugin_menu() {
// 	add_submenu_page('settings.php', 'NGP Donations', 'NGP Donations', 'ngp_manage_forms');
// }