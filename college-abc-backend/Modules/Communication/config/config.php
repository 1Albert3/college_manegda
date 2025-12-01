<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Communication Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Communication module.
    | It provides a centralized way to configure all communication channels
    | (Email, SMS, Push notifications, In-app notifications).
    |
    */

    'default_channel' => env('COMMUNICATION_DEFAULT_CHANNEL', 'email'),

    'channels' => [
        'email' => [
            'enabled' => env('COMMUNICATION_EMAIL_ENABLED', true),
            'provider' => env('COMMUNICATION_EMAIL_PROVIDER', 'smtp'),
            'queue' => env('COMMUNICATION_EMAIL_QUEUE', 'default'),
            'from' => [
                'address' => env('COMMUNICATION_EMAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@example.com')),
                'name' => env('COMMUNICATION_EMAIL_FROM_NAME', env('MAIL_FROM_NAME', 'College ABC')),
            ],
            'providers' => [
                'smtp' => [
                    'host' => env('COMMUNICATION_EMAIL_HOST', env('MAIL_HOST')),
                    'port' => env('COMMUNICATION_EMAIL_PORT', env('MAIL_PORT')),
                    'username' => env('COMMUNICATION_EMAIL_USERNAME', env('MAIL_USERNAME')),
                    'password' => env('COMMUNICATION_EMAIL_PASSWORD', env('MAIL_PASSWORD')),
                    'encryption' => env('COMMUNICATION_EMAIL_ENCRYPTION', env('MAIL_ENCRYPTION')),
                ],
                'mailgun' => [
                    'domain' => env('COMMUNICATION_MAILGUN_DOMAIN'),
                    'secret' => env('COMMUNICATION_MAILGUN_SECRET'),
                    'endpoint' => env('COMMUNICATION_MAILGUN_ENDPOINT', 'api.mailgun.net'),
                ],
                'sendgrid' => [
                    'api_key' => env('COMMUNICATION_SENDGRID_API_KEY'),
                ],
                'ses' => [
                    'key' => env('COMMUNICATION_SES_KEY'),
                    'secret' => env('COMMUNICATION_SES_SECRET'),
                    'region' => env('COMMUNICATION_SES_REGION', 'us-east-1'),
                ],
            ],
        ],

        'sms' => [
            'enabled' => env('COMMUNICATION_SMS_ENABLED', false),
            'provider' => env('COMMUNICATION_SMS_PROVIDER', 'twilio'),
            'queue' => env('COMMUNICATION_SMS_QUEUE', 'default'),
            'from' => env('COMMUNICATION_SMS_FROM'),
            'providers' => [
                'twilio' => [
                    'sid' => env('COMMUNICATION_SMS_TWILIO_SID'),
                    'token' => env('COMMUNICATION_SMS_TWILIO_TOKEN'),
                    'from' => env('COMMUNICATION_SMS_TWILIO_FROM'),
                ],
                'africastalking' => [
                    'username' => env('COMMUNICATION_AFRICASTALKING_USERNAME'),
                    'api_key' => env('COMMUNICATION_AFRICASTALKING_API_KEY'),
                    'from' => env('COMMUNICATION_AFRICASTALKING_FROM'),
                ],
                'aws_sns' => [
                    'key' => env('COMMUNICATION_AWS_SNS_KEY'),
                    'secret' => env('COMMUNICATION_AWS_SNS_SECRET'),
                    'region' => env('COMMUNICATION_AWS_SNS_REGION', 'us-east-1'),
                    'from' => env('COMMUNICATION_AWS_SNS_FROM'),
                ],
            ],
        ],

        'push' => [
            'enabled' => env('COMMUNICATION_PUSH_ENABLED', false),
            'provider' => env('COMMUNICATION_PUSH_PROVIDER', 'firebase'),
            'queue' => env('COMMUNICATION_PUSH_QUEUE', 'default'),
            'providers' => [
                'firebase' => [
                    'project_id' => env('COMMUNICATION_PUSH_FIREBASE_PROJECT_ID'),
                    'credentials_path' => env('COMMUNICATION_PUSH_FIREBASE_CREDENTIALS_PATH'),
                    'credentials_json' => env('COMMUNICATION_PUSH_FIREBASE_CREDENTIALS_JSON'),
                ],
                'onesignal' => [
                    'app_id' => env('COMMUNICATION_ONESIGNAL_APP_ID'),
                    'rest_api_key' => env('COMMUNICATION_ONESIGNAL_REST_API_KEY'),
                ],
            ],
        ],

        'in_app' => [
            'enabled' => env('COMMUNICATION_IN_APP_ENABLED', true),
            'queue' => env('COMMUNICATION_IN_APP_QUEUE', 'default'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates Configuration
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'path' => resource_path('views/communication'),
        'cache' => env('COMMUNICATION_TEMPLATES_CACHE', true),
        'variables' => [
            'app_name' => env('APP_NAME', 'College ABC'),
            'app_url' => env('APP_URL'),
            'support_email' => env('COMMUNICATION_SUPPORT_EMAIL', 'support@example.com'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'enabled' => env('COMMUNICATION_QUEUE_ENABLED', true),
        'default' => env('COMMUNICATION_DEFAULT_QUEUE', 'default'),
        'retry_attempts' => env('COMMUNICATION_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('COMMUNICATION_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('COMMUNICATION_LOGGING_ENABLED', true),
        'table' => 'communication_logs',
        'retention_days' => env('COMMUNICATION_LOG_RETENTION_DAYS', 90),
        'sensitive_data' => [
            'password', 'token', 'secret', 'key', 'credentials'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'enabled' => env('COMMUNICATION_RATE_LIMITING_ENABLED', true),
        'limits' => [
            'email' => [
                'per_minute' => env('COMMUNICATION_EMAIL_RATE_LIMIT', 60),
                'per_hour' => env('COMMUNICATION_EMAIL_RATE_LIMIT_HOUR', 1000),
            ],
            'sms' => [
                'per_minute' => env('COMMUNICATION_SMS_RATE_LIMIT', 10),
                'per_hour' => env('COMMUNICATION_SMS_RATE_LIMIT_HOUR', 100),
            ],
            'push' => [
                'per_minute' => env('COMMUNICATION_PUSH_RATE_LIMIT', 100),
                'per_hour' => env('COMMUNICATION_PUSH_RATE_LIMIT_HOUR', 5000),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    */

    'events' => [
        'enabled' => env('COMMUNICATION_EVENTS_ENABLED', true),
        'listeners' => [
            // Auto-trigger communications based on events
            'Modules\Grade\Events\GradeCreated' => [
                'template' => 'grade-published',
                'channels' => ['email', 'in_app'],
            ],
            'Modules\Attendance\Events\AbsenceCreated' => [
                'template' => 'absence-notification',
                'channels' => ['email', 'sms'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    */

    'testing' => [
        'enabled' => env('COMMUNICATION_TESTING_ENABLED', env('APP_ENV') === 'testing'),
        'fake_channels' => env('COMMUNICATION_FAKE_CHANNELS', 'email,sms,push'),
        'log_to_console' => env('COMMUNICATION_LOG_TO_CONSOLE', true),
    ],
];
