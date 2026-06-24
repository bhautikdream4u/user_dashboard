<?php
if (!defined('ABSPATH')) exit;

/* ========================================
 * SEND OTP (FIXED)
======================================== */
add_action('wp_ajax_nopriv_wps247_send_otp', function () {

    check_ajax_referer('wps247_account_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);

    if (!is_email($email)) {
        wp_send_json_error('Invalid email address');
    }

    if (email_exists($email)) {
        wp_send_json_error('Email already registered');
    }

    $otp_key  = 'wps247_otp_' . md5($email);
    $time_key = 'wps247_otp_time_' . md5($email);

    // Throttle: 60 seconds
    $last_sent = get_transient($time_key);
    if ($last_sent && (time() - $last_sent) < 60) {
        wp_send_json_error('Please wait before requesting another code');
    }

    // Reuse OTP if still valid
    $otp = get_transient($otp_key);
    if (!$otp) {
        $otp = rand(100000, 999999);
        set_transient($otp_key, $otp, 10 * MINUTE_IN_SECONDS);
    }

    set_transient($time_key, time(), 2 * MINUTE_IN_SECONDS);

    wp_mail(
        $email,
        'Email Verification Code',
        "Your verification code is: {$otp}\n\nThis code is valid for 10 minutes."
    );

    wp_send_json_success('Verification code sent');
});

/* ========================================
 * VERIFY OTP
======================================== */
add_action('wp_ajax_nopriv_wps247_verify_otp', function () {

    check_ajax_referer('wps247_account_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);
    $otp   = sanitize_text_field($_POST['otp']);

    $saved = get_transient('wps247_otp_' . md5($email));

    if (!$saved || $saved != $otp) {
        wp_send_json_error('Invalid or expired code');
    }

    set_transient(
        'wps247_verified_' . md5($email),
        true,
        15 * MINUTE_IN_SECONDS
    );

    wp_send_json_success('Email verified');
});

/* ========================================
 * REGISTER USER
======================================== */
add_action('wp_ajax_nopriv_wps247_register', function () {

    check_ajax_referer('wps247_account_nonce', 'nonce');

    $email    = sanitize_email($_POST['email']);
    $username = sanitize_user($_POST['username']);
    $first    = sanitize_text_field($_POST['first_name']);
    $last     = sanitize_text_field($_POST['last_name']);
    $pass     = $_POST['password'];

    //  Check email verified
    if (!get_transient('wps247_verified_' . md5($email))) {
        wp_send_json_error('Email not verified');
    }

    //  Username validation
    if (empty($username)) {
        wp_send_json_error('Username is required');
    }

    if (username_exists($username)) {
        wp_send_json_error('Username already exists');
    }

    if (email_exists($email)) {
        wp_send_json_error('Email already exists');
    }

    if (strlen($pass) < 6) {
        wp_send_json_error('Password must be at least 6 characters');
    }

    //  Create user with username
    $user_id = wp_create_user($username, $pass, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }

    // Update first & last name
    wp_update_user([
        'ID'         => $user_id,
        'first_name' => $first,
        'last_name'  => $last,
    ]);

    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // One-time dashboard message
    update_user_meta($user_id, 'wps247_show_verified_notice', 1);

    // Cleanup
    delete_transient('wps247_verified_' . md5($email));
    delete_transient('wps247_otp_' . md5($email));
    delete_transient('wps247_otp_time_' . md5($email));

    wp_send_json_success([
        'message'  => 'You are successfully verified.',
        'redirect' => home_url('/dashboard/')
    ]);
});

/* ========================================
 * LOGIN USER (UNCHANGED)
======================================== */
add_action('wp_ajax_nopriv_wps247_login', function () {

    check_ajax_referer('wps247_account_nonce', 'nonce');

    if (empty($_POST['login']) || empty($_POST['password'])) {
        wp_send_json_error('Missing login or password');
    }

    $login    = sanitize_text_field($_POST['login']);
    $password = $_POST['password'];
    $redirect = !empty($_POST['redirect_to'])
        ? wp_validate_redirect($_POST['redirect_to'], site_url('/dashboard/'))
        : site_url('/dashboard/');

    // If email entered → convert to username
    if (is_email($login)) {
        $user = get_user_by('email', $login);
        if (!$user) {
            wp_send_json_error('No user found with this email');
        }
        $login = $user->user_login;
    }

    // 🔍 DEBUG: Check username exists
    if (!username_exists($login)) {
        wp_send_json_error('Username does not exist');
    }

    $creds = [
        'user_login'    => $login,
        'user_password' => $password,
        'remember'      => true
    ];

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
         $error_message = wp_strip_all_tags($user->get_error_message());
		wp_send_json_error($error_message);
    }

    wp_send_json_success([
        'message'  => 'Login successful',
        'redirect' => $redirect
    ]);
});




/* ========================================
 * UPDATE PROFILE (UNCHANGED)
======================================== */
add_action('wp_ajax_wps247_update_profile', function () {

    check_ajax_referer('wps247_account_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    $user_id = get_current_user_id();

    wp_update_user([
        'ID'         => $user_id,
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name'  => sanitize_text_field($_POST['last_name'])
    ]);

    wp_send_json_success('Profile updated');
});

/* ========================================
 * CHANGE PASSWORD (UNCHANGED)
======================================== */
add_action('wp_ajax_wps247_change_password', function () {

    check_ajax_referer('wps247_account_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm'];

    if ($pass1 !== $pass2) {
        wp_send_json_error('Passwords do not match');
    }

    if (strlen($pass1) < 6) {
        wp_send_json_error('Password too short');
    }

    wp_set_password($pass1, get_current_user_id());

    wp_send_json_success('Password changed');
});

/* ========================================
 * FORGOT PASSWORD
======================================== */
add_action('wp_ajax_nopriv_wps247_forgot_password', function () {

    check_ajax_referer('wps247_account_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);

    if (!is_email($email)) {
        wp_send_json_error('Please enter a valid email address');
    }

    $user = get_user_by('email', $email);

    if (!$user) {
        wp_send_json_error('No account found with this email');
    }

    $reset_key = get_password_reset_key($user);

    if (is_wp_error($reset_key)) {
        wp_send_json_error('Unable to generate reset link');
    }

    $reset_url = wp_login_url() . "?action=rp&key={$reset_key}&login=" . rawurlencode($user->user_login);

    $message = "Hello {$user->first_name},\n\n";
    $message .= "You requested to reset your password.\n\n";
    $message .= "Click the link below to set a new password:\n";
    $message .= $reset_url . "\n\n";
    $message .= "If you didn’t request this, please ignore this email.";

    wp_mail(
        $email,
        'Reset Your Password',
        $message
    );

    wp_send_json_success('Password reset link sent to your email');
});
