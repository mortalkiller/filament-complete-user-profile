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
