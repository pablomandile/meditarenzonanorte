<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // La key sola: termina con el centro de datos de la cuenta ("...-us21"), que es
    // el subdominio al que hay que pegarle. A qué audiencia se le manda, con qué
    // nombre y a qué casilla responden son ajustes del panel, no credenciales.
    'mailchimp' => [
        'key' => env('MAILCHIMP_API_KEY'),
        // El interruptor de los envíos a la audiencia real: va en true en UN solo
        // servidor, el de producción. No alcanza con mirar APP_ENV, porque el entorno
        // de pruebas de Hostinger también corre como production.
        // Ver App\Support\NewsletterSender::liveSendsAllowed().
        'live_sends' => env('MAILCHIMP_LIVE_SENDS', false),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/auth/google/callback'),
        // Solo estos emails pueden entrar al panel con Google (separados por coma).
        'allowed_emails' => env('GOOGLE_ALLOWED_EMAILS'),
    ],

];
