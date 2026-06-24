<?php
if (!defined('ABSPATH')) exit;

define('WPS247_ACCOUNT_PATH', plugin_dir_path(__FILE__));
define('WPS247_ACCOUNT_URL', plugin_dir_url(__FILE__));

require_once WPS247_ACCOUNT_PATH . 'ajax-auth.php';
require_once WPS247_ACCOUNT_PATH . 'shortcodes.php';

/* Enqueue JS */
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_script(
        'wps247-account',
        WPS247_ACCOUNT_URL . 'assets/account.js',
        ['jquery'],
        '2.5',
        true
    );

    wp_localize_script('wps247-account', 'WPS247_ACCOUNT', [
        'ajax'     => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wps247_account_nonce'),
        'redirect' => $siteurl.'/dashboard/'
    ]);
});
/* HANDLE LOGOUT */
add_action('init', function () {

    if (!isset($_GET['wps247_logout'])) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    if (!wp_verify_nonce($_GET['_wpnonce'], 'wps247_logout_nonce')) {
        wp_die('Security check failed');
    }

    wp_logout();

    wp_safe_redirect(home_url('/login/')); // change if needed
    exit;
});

add_shortcode('wps247_logout_url', function () {

    if (!is_user_logged_in()) {
        return '';
    }

    return esc_url( wp_logout_url( home_url('/') ) );
});
add_shortcode('wps247_logout_url1', function () {

    if (!is_user_logged_in()) {
        return '';
    }
	
    return str_replace('https://','', esc_url( wp_logout_url( home_url('/') ) ));;
});