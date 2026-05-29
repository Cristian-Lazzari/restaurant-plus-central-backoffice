<?php

return [
    'messages' => [
        'otp_sent' => 'We sent your confirmation code by email.',
        'otp_rate_limited' => 'Too many codes requested. Please wait a few minutes before trying again.',
        'otp_invalid_or_expired' => 'The code is invalid or has expired.',
        'otp_too_many_attempts' => 'Too many attempts. Please request a new code.',
        'otp_incorrect' => 'The code you entered is incorrect.',
        'otp_verified' => 'Email confirmed successfully.',
        'checkout_verification_required' => 'Please confirm your email with the code we sent before completing checkout.',
        'registration_incomplete' => 'Complete the required information to finish registration.',
        'registration_completed' => 'Customer profile completed successfully.',
        'consents_updated' => 'Privacy preferences updated successfully.',
        'logout_completed' => 'Logout completed successfully.',
        'order_cancelled' => 'Order cancelled successfully.',
        'reservation_cancelled' => 'Booking cancelled successfully.',
        'cancellation_not_allowed' => 'Cancellation is not available for this date or status.',
        'cancellation_unavailable' => 'I could not complete the cancellation. Please contact the restaurant.',
    ],
    'mail' => [
        'subject' => 'Email confirmation code',
        'eyebrow' => 'Quick confirmation',
        'title' => 'Your verification code',
        'intro' => 'Use this code to confirm your email and continue on the restaurant website.',
        'expires_in' => 'The code expires in :minutes minutes.',
        'ignore' => 'If you did not request this code, you can ignore this email.',
    ],
];
