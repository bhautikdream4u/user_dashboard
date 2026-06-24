jQuery(function ($) {

    /* ================================
     * REGISTRATION + OTP FLOW
    ================================= */

    const emailInput   = $('#wps247-email');
    const sendOtpBtn   = $('#wps247-send-otp');
    const otpWrap      = $('#wps247-otp-wrap');
    const registerBtn  = $('#wps247-register-btn');
    let otpSent = false;

    /* Show Send OTP button only if email is valid */
    emailInput.on('input', function () {
        const email = $(this).val();
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

        if (valid && !otpSent) {
            sendOtpBtn.show();
        } else {
            sendOtpBtn.hide();
            otpWrap.hide();
        }
    });

    /* SEND OTP */
    sendOtpBtn.on('click', function () {

        sendOtpBtn.prop('disabled', true);

        $.post(WPS247_ACCOUNT.ajax, {
            action: 'wps247_send_otp',
            nonce: WPS247_ACCOUNT.nonce,
            email: emailInput.val()
        }, res => {
            $('#wps247-msg').text(res.data);

            if (res.success) {
                otpWrap.show();
                otpSent = true;
                sendOtpBtn.hide();
            } else {
                sendOtpBtn.prop('disabled', false);
            }
        });
    });

    /* VERIFY OTP */
    $('#wps247-verify-otp').on('click', function () {

        $.post(WPS247_ACCOUNT.ajax, {
            action: 'wps247_verify_otp',
            nonce: WPS247_ACCOUNT.nonce,
            email: emailInput.val(),
            otp: $('input[name="otp"]').val()
        }, res => {
            $('#wps247-msg').text(res.data);

            if (res.success) {
				jQuery('#verifed_msg').html('<span style="color:green;">Email verified</span>');
				jQuery('div#wps247-otp-wrap').hide();
                registerBtn.prop('disabled', false);
                emailInput.prop('readonly', true);
            }
        });
    });

    /* REGISTER USER */
    $('#wps247-register-form').on('submit', function (e) {
        e.preventDefault();

        $.post(WPS247_ACCOUNT.ajax, {
            action: 'wps247_register',
            nonce: WPS247_ACCOUNT.nonce,
            first_name: $('input[name="first_name"]').val(),
            last_name: $('input[name="last_name"]').val(),
			username: $('input[name="username"]').val(),
            email: emailInput.val(),
            password: $('input[name="password"]').val()
        }, res => {
            if (res.success) {
                window.location = WPS247_ACCOUNT.redirect;
            } else {
                $('#wps247-msg').text(res.data);
            }
        });
    });

    /* ================================
     * LOGIN
    ================================= */
    $('#wps247-login-form').on('submit', function (e) {
		e.preventDefault();

		const form = $(this);

		$.post(WPS247_ACCOUNT.ajax, {
			action: 'wps247_login',
			nonce: WPS247_ACCOUNT.nonce,
			login: form.find('[name="login"]').val(),
			password: form.find('[name="password"]').val(),
			redirect_to: form.find('[name="redirect_to"]').val()
		}, function (res) {

			if (res.success) {
				window.location.href = res.data.redirect;
			} else {
				$('#wps247-login-msg').text(res.data);
			}

		});
	});



    /* ================================
     * PROFILE UPDATE
    ================================= */
    $('#wps247-profile-form').on('submit', function (e) {
        e.preventDefault();

        $.post(WPS247_ACCOUNT.ajax, {
            action: 'wps247_update_profile',
            nonce: WPS247_ACCOUNT.nonce,
            first_name: $(this).find('[name="first_name"]').val(),
            last_name: $(this).find('[name="last_name"]').val()
        }, res => {
            $('#wps247-profile-msg').text(res.data);
        });
    });

    /* ================================
     * PASSWORD CHANGE
    ================================= */
    $('#wps247-password-form').on('submit', function (e) {
        e.preventDefault();

        $.post(WPS247_ACCOUNT.ajax, {
            action: 'wps247_change_password',
            nonce: WPS247_ACCOUNT.nonce,
            password: $(this).find('[name="password"]').val(),
            confirm: $(this).find('[name="confirm"]').val()
        }, res => {
            if (res.success) {
                alert('Password changed. Please login again.');
                window.location.reload();
            } else {
                $('#wps247-password-msg').text(res.data);
            }
        });
    });

	/* ================================
	 * FORGOT PASSWORD
	================================ */
	$('#wps247-forgot-form').on('submit', function (e) {
		e.preventDefault();

		$.post(WPS247_ACCOUNT.ajax, {
			action: 'wps247_forgot_password',
			nonce: WPS247_ACCOUNT.nonce,
			email: $(this).find('[name="email"]').val()
		}, res => {
			$('#wps247-forgot-msg').text(res.data);
		});
	});


});
