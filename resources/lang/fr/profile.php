<?php

return [
    'page' => [
        'label' => 'Mon compte',
        'heading' => 'Mon compte',
        'subheading' => 'Gérez votre profil, votre sécurité et vos accès.',
    ],
    'navigation' => [
        'label' => 'Sections du compte',
    ],
    'fields' => [
        'avatar' => 'Avatar',
        'locale' => 'Langue préférée',
    ],
    'overview' => [
        'name' => 'Nom',
        'email' => 'E-mail',
        'locale' => 'Langue préférée',
    ],
    'security' => [
        'password' => [
            'action' => 'Modifier le mot de passe',
            'new' => 'Nouveau mot de passe',
            'confirmation' => 'Confirmer le nouveau mot de passe',
        ],
    ],
    'sessions' => [
        'columns' => [
            'device' => 'Appareil',
            'ip' => 'IP',
            'last_activity' => 'Dernière activité',
            'status' => 'Statut',
        ],
        'status' => [
            'current' => 'Actuelle',
            'active' => 'Active',
        ],
        'actions' => [
            'revoke' => 'Révoquer',
            'revoke_others' => 'Révoquer les autres sessions',
        ],
        'empty' => 'Aucune session de navigateur trouvée',
    ],
    'api_tokens' => [
        'columns' => [
            'name' => 'Nom',
            'permissions' => 'Autorisations',
            'last_used' => 'Dernière utilisation',
            'expires' => 'Expire',
        ],
        'never' => 'Jamais',
        'fields' => [
            'name' => 'Nom',
            'permissions' => 'Autorisations',
            'expiration' => 'Expire dans (jours)',
        ],
        'actions' => [
            'create' => 'Créer un jeton',
            'revoke' => 'Révoquer',
            'done' => 'Terminé',
        ],
        'created' => [
            'heading' => 'Jeton API créé',
            'description' => 'Copiez ce jeton maintenant. Vous ne pourrez plus le consulter ensuite.',
            'token' => 'Jeton',
        ],
        'empty' => 'Aucun jeton API',
    ],
    'features' => [
        'overview' => [
            'label' => "Vue d'ensemble",
            'description' => 'Un résumé de votre compte et des fonctionnalités de sécurité activées.',
        ],
        'profile' => [
            'label' => 'Profil',
            'description' => 'Gérez vos informations personnelles et vos préférences.',
        ],
        'security' => [
            'label' => 'Sécurité',
            'description' => 'Gérez votre mot de passe et la sécurité de votre compte.',
        ],
        'sessions' => [
            'label' => 'Sessions',
            'description' => 'Consultez et gérez les appareils connectés à votre compte.',
        ],
        'api-tokens' => [
            'label' => 'Jetons API',
            'description' => "Créez et révoquez des jetons personnels d'accès à l'API.",
        ],
    ],
];
