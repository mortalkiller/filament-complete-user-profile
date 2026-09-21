<?php

return [
    'page' => [
        'label' => 'My account',
        'heading' => 'My account',
        'subheading' => 'Manage your profile, security, and access.',
    ],
    'navigation' => [
        'label' => 'Account sections',
    ],
    'fields' => [
        'avatar' => 'Avatar',
        'locale' => 'Preferred language',
    ],
    'overview' => [
        'name' => 'Name',
        'email' => 'Email',
        'locale' => 'Preferred language',
    ],
    'security' => [
        'password' => [
            'action' => 'Change password',
            'new' => 'New password',
            'confirmation' => 'Confirm new password',
        ],
        'reauthentication' => [
            'current_password' => 'Current password',
            'unavailable' => 'This account cannot be reauthenticated with a local password.',
        ],
        'mfa' => [
            'requirement' => 'The authenticatable model must implement :contract when multi-factor authentication is enabled.',
        ],
        'email_authentication' => [
            'requirement' => 'The authenticatable model must implement :contract when email authentication is enabled.',
            'notifications' => 'The authenticated user model must support Laravel notifications to use email authentication.',
            'resend' => [
                'label' => 'Send a new code by email',
                'sent' => 'A new verification code has been sent.',
            ],
            'email' => [
                'subject' => 'Your :app verification code',
                'heading' => 'Confirm your identity',
                'intro' => 'A verification code was requested for your :app account. Enter the code below to continue.',
                'expiry' => 'This code expires in :minutes minute.|This code expires in :minutes minutes.',
                'warning' => 'Never share this code. Our team will never ask you for it.',
                'ignore' => 'If you did not request this code, you can safely ignore this email.',
                'footer' => 'Security message from :app.',
            ],
        ],
    ],
    'sessions' => [
        'columns' => [
            'device' => 'Device',
            'ip' => 'IP',
            'last_activity' => 'Last activity',
            'status' => 'Status',
        ],
        'status' => [
            'current' => 'Current',
            'active' => 'Active',
        ],
        'actions' => [
            'revoke' => 'Revoke',
            'revoke_others' => 'Revoke other sessions',
        ],
        'requirements' => [
            'database_driver' => 'Browser session management requires SESSION_DRIVER=database.',
            'table' => 'The configured Laravel sessions table does not exist.',
        ],
        'device' => [
            'unknown_browser' => 'Unknown browser',
            'unknown_platform' => 'Unknown platform',
            'tablet' => 'Tablet',
            'mobile' => 'Mobile',
            'desktop' => 'Desktop',
        ],
        'empty' => 'No browser sessions found',
    ],
    'api_tokens' => [
        'columns' => [
            'name' => 'Name',
            'permissions' => 'Permissions',
            'last_used' => 'Last used',
            'expires' => 'Expires',
        ],
        'never' => 'Never',
        'fields' => [
            'name' => 'Name',
            'permissions' => 'Permissions',
            'expiration' => 'Expires in days',
        ],
        'actions' => [
            'create' => 'Create token',
            'revoke' => 'Revoke',
            'done' => 'Done',
        ],
        'created' => [
            'heading' => 'API token created',
            'description' => 'Copy this token now. You will not be able to see it again.',
            'token' => 'Token',
        ],
        'requirements' => [
            'sanctum' => 'Laravel Sanctum must be installed to enable API token management.',
            'user_model' => 'The authenticated user model must use Laravel\\Sanctum\\HasApiTokens when API token management is enabled.',
            'abilities' => 'Configure at least one allowed API token ability before enabling API token management.',
            'context_migration' => 'Publish and run the filament-complete-user-profile token-context migration before enabling tenant-scoped API tokens.',
            'context_resolver' => 'Bind :contract before enabling tenant-scoped API tokens.',
        ],
        'empty' => 'No API tokens',
    ],
    'features' => [
        'overview' => [
            'label' => 'Overview',
            'description' => 'A summary of your account and enabled security features.',
        ],
        'profile' => [
            'label' => 'Profile',
            'description' => 'Manage your personal information and preferences.',
        ],
        'security' => [
            'label' => 'Security',
            'description' => 'Manage your password and account security.',
        ],
        'sessions' => [
            'label' => 'Sessions',
            'description' => 'Review and manage devices signed in to your account.',
        ],
        'api-tokens' => [
            'label' => 'API Tokens',
            'description' => 'Create and revoke personal API access tokens.',
        ],
    ],
];
