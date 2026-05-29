<?php

return [
    'messages' => [
        'otp_sent' => 'Hemos enviado tu codigo de confirmacion por email.',
        'otp_rate_limited' => 'Has solicitado demasiados codigos. Espera unos minutos antes de volver a intentarlo.',
        'otp_invalid_or_expired' => 'El codigo no es valido o ha caducado.',
        'otp_too_many_attempts' => 'Demasiados intentos. Solicita un nuevo codigo.',
        'otp_incorrect' => 'El codigo introducido no es correcto.',
        'otp_verified' => 'Email confirmado correctamente.',
        'checkout_verification_required' => 'Confirma tu email con el codigo recibido antes de completar el pedido.',
        'registration_incomplete' => 'Completa los datos obligatorios para finalizar el registro.',
        'registration_completed' => 'Perfil de cliente completado correctamente.',
        'consents_updated' => 'Preferencias de privacidad actualizadas correctamente.',
        'logout_completed' => 'Sesion cerrada correctamente.',
        'order_cancelled' => 'Pedido cancelado correctamente.',
        'reservation_cancelled' => 'Reserva cancelada correctamente.',
        'cancellation_not_allowed' => 'La cancelacion no esta disponible para esta fecha o estado.',
        'cancellation_unavailable' => 'No he podido completar la cancelacion. Contacta con el restaurante.',
    ],
    'mail' => [
        'subject' => 'Codigo de confirmacion por email',
        'eyebrow' => 'Confirmacion rapida',
        'title' => 'Tu codigo de verificacion',
        'intro' => 'Usa este codigo para confirmar tu email y continuar en la web del restaurante.',
        'expires_in' => 'El codigo caduca en :minutes minutos.',
        'ignore' => 'Si no has solicitado este codigo, puedes ignorar este email.',
    ],
];
