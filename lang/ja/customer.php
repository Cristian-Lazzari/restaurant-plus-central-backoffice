<?php

return [
    'messages' => [
        'otp_sent' => '確認コードをメールで送信しました。',
        'otp_rate_limited' => 'コードのリクエスト回数が多すぎます。数分待ってからもう一度お試しください。',
        'otp_invalid_or_expired' => 'コードが無効か、有効期限が切れています。',
        'otp_too_many_attempts' => '試行回数が上限に達しました。新しいコードをリクエストしてください。',
        'otp_incorrect' => '入力されたコードが正しくありません。',
        'otp_verified' => 'メールアドレスの確認が完了しました。',
        'checkout_verification_required' => '注文を完了する前に、受信したコードでメールアドレスを確認してください。',
        'registration_incomplete' => '登録を完了するには必要な情報を入力してください。',
        'registration_completed' => '顧客プロフィールが正常に完了しました。',
        'consents_updated' => 'プライバシー設定を更新しました。',
        'logout_completed' => 'ログアウトしました。',
        'order_cancelled' => '注文をキャンセルしました。',
        'reservation_cancelled' => '予約をキャンセルしました。',
        'cancellation_not_allowed' => 'この日時または状態ではキャンセルできません。',
        'cancellation_unavailable' => 'キャンセルを完了できませんでした。店舗へお問い合わせください。',
    ],
    'mail' => [
        'subject' => 'メール確認コード',
        'eyebrow' => 'クイック確認',
        'title' => '確認コード',
        'intro' => 'このコードを使ってメールアドレスを確認し、レストランサイトで続行してください。',
        'expires_in' => 'このコードの有効期限は :minutes 分です。',
        'ignore' => 'このコードに心当たりがない場合は、このメールを無視してください。',
    ],
];
