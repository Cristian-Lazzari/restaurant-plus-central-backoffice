<?php

return [
    'messages' => [
        'otp_sent' => 'Wir haben dir einen Bestatigungscode per E-Mail geschickt.',
        'otp_rate_limited' => 'Du hast zu viele Codes angefordert. Bitte warte ein paar Minuten und versuche es erneut.',
        'otp_invalid_or_expired' => 'Der Code ist ungueltig oder abgelaufen.',
        'otp_too_many_attempts' => 'Zu viele Versuche. Bitte fordere einen neuen Code an.',
        'otp_incorrect' => 'Der eingegebene Code ist nicht korrekt.',
        'otp_verified' => 'E-Mail erfolgreich bestaetigt.',
        'checkout_verification_required' => 'Bitte bestaetige zuerst deine E-Mail mit dem zugesandten Code.',
        'registration_incomplete' => 'Vervollstaendige die erforderlichen Angaben, um die Registrierung abzuschliessen.',
        'registration_completed' => 'Kundenprofil erfolgreich vervollstaendigt.',
        'consents_updated' => 'Datenschutzeinstellungen erfolgreich aktualisiert.',
        'logout_completed' => 'Erfolgreich abgemeldet.',
        'order_cancelled' => 'Bestellung erfolgreich storniert.',
        'reservation_cancelled' => 'Reservierung erfolgreich storniert.',
        'cancellation_not_allowed' => 'Die Stornierung ist fuer dieses Datum oder diesen Status nicht verfuegbar.',
        'cancellation_unavailable' => 'Die Stornierung konnte nicht abgeschlossen werden. Bitte kontaktiere das Restaurant.',
    ],
    'mail' => [
        'subject' => 'E-Mail-Bestaetigungscode',
        'eyebrow' => 'Schnelle Bestaetigung',
        'title' => 'Dein Verifizierungscode',
        'intro' => 'Nutze diesen Code, um deine E-Mail zu bestaetigen und auf der Restaurant-Website fortzufahren.',
        'expires_in' => 'Der Code laeuft in :minutes Minuten ab.',
        'ignore' => 'Wenn du diesen Code nicht angefordert hast, kannst du diese E-Mail ignorieren.',
    ],
];
