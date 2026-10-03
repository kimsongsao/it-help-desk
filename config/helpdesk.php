<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | The slug of the role assigned to users who register themselves or are
    | created without an explicit role.
    |
    */

    'default_role' => env('HELPDESK_DEFAULT_ROLE', 'requester'),

    /*
    |--------------------------------------------------------------------------
    | Copyright Holder
    |--------------------------------------------------------------------------
    |
    | The company credited in the copyright notice at the foot of every page.
    |
    */

    'company' => [
        'name' => 'Kdigitized Solutions',
        'url' => 'https://kdigitized.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Time Zone
    |--------------------------------------------------------------------------
    |
    | Every date is stored in UTC. New organizations show dates and read their
    | business hours in this time zone until they choose another one.
    |
    */

    'default_timezone' => env('HELPDESK_TIMEZONE', 'Asia/Phnom_Penh'),

    /*
    |--------------------------------------------------------------------------
    | Public Email Domains
    |--------------------------------------------------------------------------
    |
    | Domains anyone can sign up for. They can never be used to let people
    | join a company automatically.
    |
    */

    'public_email_domains' => [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
        'icloud.com', 'me.com', 'aol.com', 'proton.me', 'protonmail.com', 'gmx.com', 'mail.com', 'yandex.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA "At Risk" Threshold
    |--------------------------------------------------------------------------
    |
    | Once this fraction of the time allowed by an SLA target has elapsed, the
    | ticket is flagged as "at risk".
    |
    */

    'sla_at_risk_threshold' => 0.75,

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | The interface languages users can choose from, keyed by locale. The
    | speech value is the language used for voice dictation in the browser.
    |
    */

    'locales' => [
        'en' => ['name' => 'English', 'speech' => 'en-US'],
        'km' => ['name' => 'ភាសាខ្មែរ', 'speech' => 'km-KH'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    |
    | Uploaded files are stored on a private disk and only served to users
    | who may view the ticket. Sizes are in kilobytes.
    |
    */

    'attachments' => [
        'disk' => env('HELPDESK_ATTACHMENTS_DISK', 'local'),
        'max_files' => 5,
        'max_size' => 10240,
        'extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'pdf', 'txt', 'log', 'csv',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip',
            'mp3', 'm4a', 'wav', 'ogg', 'webm', 'mp4',
        ],
        'voice_mime_types' => [
            'audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/wav', 'audio/x-wav',
            'video/webm', 'video/mp4',
        ],
    ],

];
