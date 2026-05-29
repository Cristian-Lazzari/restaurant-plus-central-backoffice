<?php

return [
    'messages' => [
        'otp_sent' => 'Ti abbiamo inviato un codice di conferma via email.',
        'otp_rate_limited' => 'Hai richiesto troppi codici. Attendi qualche minuto prima di riprovare.',
        'otp_invalid_or_expired' => 'Il codice non e valido o e scaduto.',
        'otp_too_many_attempts' => 'Hai superato il numero massimo di tentativi. Richiedi un nuovo codice.',
        'otp_incorrect' => 'Il codice inserito non e corretto.',
        'otp_verified' => 'Email confermata con successo.',
        'checkout_verification_required' => 'Conferma prima la tua email con il codice ricevuto via email.',
        'registration_incomplete' => 'Completa i dati richiesti per terminare la registrazione.',
        'registration_completed' => 'Profilo cliente completato con successo.',
        'consents_updated' => 'Preferenze privacy aggiornate con successo.',
        'logout_completed' => 'Logout effettuato con successo.',
        'order_cancelled' => 'Ordine annullato correttamente.',
        'reservation_cancelled' => 'Prenotazione annullata correttamente.',
        'cancellation_not_allowed' => 'L\'annullamento non e disponibile per questa data o questo stato.',
        'cancellation_unavailable' => 'Non sono riuscito a completare l\'annullamento. Contatta il locale.',
    ],
    'mail' => [
        'subject' => 'Codice di conferma email',
        'eyebrow' => 'Conferma rapida',
        'title' => 'Il tuo codice di verifica',
        'intro' => 'Usa questo codice per confermare la tua email e continuare sul sito del ristorante.',
        'expires_in' => 'Il codice scade tra :minutes minuti.',
        'ignore' => 'Se non hai richiesto questo codice, puoi ignorare questa email.',
    ],
];
