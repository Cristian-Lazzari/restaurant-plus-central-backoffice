<?php

return [
    'messages' => [
        'otp_sent' => 'Nous avons envoye votre code de confirmation par email.',
        'otp_rate_limited' => 'Vous avez demande trop de codes. Veuillez patienter quelques minutes avant de reessayer.',
        'otp_invalid_or_expired' => 'Le code est invalide ou a expire.',
        'otp_too_many_attempts' => 'Trop de tentatives. Veuillez demander un nouveau code.',
        'otp_incorrect' => 'Le code saisi est incorrect.',
        'otp_verified' => 'Email confirme avec succes.',
        'checkout_verification_required' => 'Veuillez confirmer votre email avec le code recu avant de finaliser la commande.',
        'registration_incomplete' => 'Completez les informations requises pour terminer l inscription.',
        'registration_completed' => 'Profil client complete avec succes.',
        'consents_updated' => 'Preferences de confidentialite mises a jour avec succes.',
        'logout_completed' => 'Deconnexion effectuee avec succes.',
        'order_cancelled' => 'Commande annulee avec succes.',
        'reservation_cancelled' => 'Reservation annulee avec succes.',
        'cancellation_not_allowed' => 'L annulation n est pas disponible pour cette date ou ce statut.',
        'cancellation_unavailable' => 'Je n ai pas pu finaliser l annulation. Contactez le restaurant.',
    ],
    'mail' => [
        'subject' => 'Code de confirmation email',
        'eyebrow' => 'Confirmation rapide',
        'title' => 'Votre code de verification',
        'intro' => 'Utilisez ce code pour confirmer votre email et continuer sur le site du restaurant.',
        'expires_in' => 'Le code expire dans :minutes minutes.',
        'ignore' => 'Si vous n avez pas demande ce code, vous pouvez ignorer cet email.',
    ],
];
