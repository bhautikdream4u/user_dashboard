<?php
if (!defined('ABSPATH')) exit;

add_shortcode('wps247_register', function () {
	
	ob_start(); ?>
<div class="wps247-auth-wrapper">

  <div class="wps247-auth-card">

    <h2 class="wps247-auth-title">Create Your Account</h2>
    <p class="wps247-auth-subtitle">
      Register to access your dashboard
    </p>

    <div id="wps247-msg" class="wps247-auth-msg"></div>

    <form id="wps247-register-form" method="post">

      <div class="wps247-field">
        <label>First Name</label>
        <input type="text" name="first_name" placeholder="First Name" required>
      </div>

      <div class="wps247-field">
        <label>Last Name</label>
        <input type="text" name="last_name" placeholder="Last Name" required>
      </div>
	  
	  <div class="wps247-field">
        <label>Username</label>
        <input type="text" name="username" placeholder="Username" required>
      </div>

      <div class="wps247-field">
        <label>Email address</label>
        <input type="email" name="email" id="wps247-email" placeholder="you@example.com" required>
      </div>

      <button type="button" id="wps247-send-otp" class="wps247-btn-outline" style="display:none;">
        Send Verification Code
      </button>
	  <div id="verifed_msg"></div>		
      <div id="wps247-otp-wrap" style="display:none; margin-top:16px;">
        <div class="wps247-field">
          <label>Verification Code</label>
          <input type="text" name="otp" placeholder="Enter verification code">
        </div>

        <button type="button" id="wps247-verify-otp" class="wps247-btn-secondary">
          Verify Email
        </button>
      </div>

      <div class="wps247-field">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>

      <div class="wps247-field">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="••••••••" required>
      </div>

      <button type="submit" id="wps247-register-btn" class="wps247-btn-primary" disabled>
        Create Account
      </button>
	  
	  <div class="wps247-divider">
			<span>OR</span>
		  </div>
		  
	<?php echo do_shortcode('[nextend_social_login]'); ?>

    </form>

    <div class="wps247-auth-footer">
      <span>Already have an account?</span>
      <a href="/login/" class="wps247-link-bold">Login</a>
    </div>

  </div>

</div>




<?php return ob_get_clean();
});

add_shortcode('wps247_logout', function ($atts) {

    if (!is_user_logged_in()) {
        return '';
    }

    $logout_url = wp_nonce_url(
        add_query_arg('wps247_logout', '1', home_url('/')),
        'wps247_logout_nonce'
    );

    return '<a href="' . esc_url($logout_url) . '" class="wps247-logout-link">Logout</a>';
});

/* LOGIN FORM */
add_shortcode('wps247_login', function () {
	
	$redirect_to = isset($_GET['redirect_to']) 
        ? esc_url_raw($_GET['redirect_to']) 
        : site_url('/dashboard/');

      ob_start(); ?>
	<div class="wps247-auth-wrapper">

	  <div class="wps247-auth-card">

		<h2 class="wps247-auth-title">Welcome Back</h2>
		<p class="wps247-auth-subtitle">
		  Login to access your dashboard
		</p>

		<div id="wps247-login-msg" class="wps247-auth-msg"></div>

		<form id="wps247-login-form" method="post">
			 <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
		  <div class="wps247-field">
			<label>UserName or Email</label>
			<input type="text" name="login" placeholder="Username or Email" required>
		  </div>

		  <div class="wps247-field">
			<label>Password</label>
			<input type="password" name="password" placeholder="••••••••" required>
		  </div>

		  <div class="wps247-auth-actions">
			<a href="/forgot-password/" class="wps247-link">
			  Forgot password?
			</a>
		  </div>

		  <button type="submit" class="wps247-btn-primary">
			Login
		  </button>
		  
		  <div class="wps247-divider">
			<span>OR</span>
		  </div>
		  
		   <?php echo do_shortcode('[nextend_social_login]'); ?>

		</form>

		<div class="wps247-auth-footer">
		  <span>Don’t have an account?</span>
		  <a href="/register/" class="wps247-link-bold">
			Create account
		  </a>
		</div>

	  </div>

	</div>
	

    
    <?php return ob_get_clean();
});

/* PROFILE FORM */
add_shortcode('wps247_profile', function () {

    if (!is_user_logged_in()) {
        return '<p>Please login.</p>';
    }

    $user = wp_get_current_user();

    ob_start(); ?>
	<div class="wps247-auth-wrapper">

	  <div class="wps247-auth-card">

		<h2 class="wps247-auth-title">Profile Settings</h2>
		<p class="wps247-auth-subtitle">
		  Update your personal information
		</p>

		<div id="wps247-profile-msg" class="wps247-auth-msg"></div>

		<form id="wps247-profile-form" method="post">

		  <div class="wps247-field">
			<label>First Name</label>
			<input
			  type="text"
			  name="first_name"
			  value="<?php echo esc_attr($user->first_name); ?>"
			  required
			>
		  </div>

		  <div class="wps247-field">
			<label>Last Name</label>
			<input
			  type="text"
			  name="last_name"
			  value="<?php echo esc_attr($user->last_name); ?>"
			  required
			>
		  </div>

		  <div class="wps247-field">
			<label>Email Address</label>
			<input
			  type="email"
			  value="<?php echo esc_attr($user->user_email); ?>"
			  disabled
			>
		  </div>

		  <button type="submit" class="wps247-btn-primary">
			Save Changes
		  </button>

		</form>

	  </div>

	</div>

    
    <?php return ob_get_clean();
});

/* CHANGE PASSWORD FORM */
add_shortcode('wps247_password', function () {

    if (!is_user_logged_in()) {
        return '<p>Please login.</p>';
    }

    ob_start(); ?>
	<div class="wps247-auth-wrapper">

	  <div class="wps247-auth-card">

		<h2 class="wps247-auth-title">Change Password</h2>
		<p class="wps247-auth-subtitle">
		  Choose a strong password to keep your account secure
		</p>

		<div id="wps247-password-msg" class="wps247-auth-msg"></div>

		<form id="wps247-password-form">

		  <div class="wps247-field">
			<label>New Password</label>
			<input
			  type="password"
			  name="password"
			  placeholder="••••••••"
			  required
			>
		  </div>

		  <div class="wps247-field">
			<label>Confirm New Password</label>
			<input
			  type="password"
			  name="confirm"
			  placeholder="••••••••"
			  required
			>
		  </div>

		  <button type="submit" class="wps247-btn-primary">
			Update Password
		  </button>

		</form>

		<div class="wps247-auth-footer">
		  <small style="color:#64748b;">
			You’ll be asked to log in again after changing your password.
		  </small>
		</div>

	  </div>

	</div>

    
    <?php return ob_get_clean();
});

/* FORGOT PASSWORD FORM */
add_shortcode('wps247_forgot_password', function () {

    if (is_user_logged_in()) {
        wp_safe_redirect($siteurl.'/dashboard/');
        exit;
    }

    ob_start(); ?>
    
    <div class="wps247-auth-wrapper">
      <div class="wps247-auth-card">

        <h2 class="wps247-auth-title">Forgot Password</h2>
        <p class="wps247-auth-subtitle">
          Enter your email to receive a password reset link
        </p>

        <div id="wps247-forgot-msg" class="wps247-auth-msg"></div>

        <form id="wps247-forgot-form" method="post">
          <div class="wps247-field">
            <label>Email address</label>
            <input type="email" name="email" placeholder="you@example.com" required>
          </div>

          <button type="submit" class="wps247-btn-primary">
            Send Reset Link
          </button>
        </form>

        <div class="wps247-auth-footer">
          <a href="/login/" class="wps247-link">← Back to login</a>
        </div>

      </div>
    </div>

    <?php return ob_get_clean();
});

add_shortcode('wps247_reg_notification', function () {
$show_notice = false;

if (is_user_logged_in()) {
    $user_id = get_current_user_id();

    if (get_user_meta($user_id, 'wps247_show_verified_notice', true)) {
        $show_notice = true;
        delete_user_meta($user_id, 'wps247_show_verified_notice');
    }
}
 if ($show_notice){
  
   echo "You are successfully verified.";    
    }
	
});