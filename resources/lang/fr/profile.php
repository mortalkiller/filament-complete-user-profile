<?php

return [
    'page' => [
        'label' => 'Mon compte',
        'heading' => 'Mon compte',
        'subheading' => 'Gérez votre profil, votre sécurité et vos accès.',
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
        'reauthentication' => [
            'current_password' => 'Mot de passe actuel',
            'unavailable' => 'Ce compte ne peut pas être réauthentifié avec un mot de passe local.',
        ],
        'mfa' => [
            'requirement' => 'Le modèle authentifiable doit implémenter :contract lorsque l’authentification multifacteur est activée.',
        ],
        'email_authentication' => [
            'requirement' => 'Le modèle authentifiable doit implémenter :contract lorsque l’authentification par e-mail est activée.',
            'notifications' => 'Le modèle utilisateur authentifié doit prendre en charge les notifications Laravel pour utiliser l’authentification par e-mail.',
            'resend' => [
                'label' => 'Envoyer un nouveau code par e-mail',
                'sent' => 'Un nouveau code de vérification a été envoyé.',
            ],
            'email' => [
                'subject' => 'Votre code de vérification :app',
                'heading' => 'Confirmez votre identité',
                'intro' => 'Un code de vérification a été demandé pour votre compte :app. Saisissez le code ci-dessous pour continuer.',
                'expiry' => 'Ce code expire dans :minutes minute.|Ce code expire dans :minutes minutes.',
                'warning' => 'Ne partagez jamais ce code. Notre équipe ne vous le demandera jamais.',
                'ignore' => 'Si vous n’avez pas demandé ce code, vous pouvez ignorer cet e-mail en toute sécurité.',
                'footer' => 'Message de sécurité de :app.',
            ],
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
        'requirements' => [
            'database_driver' => 'La gestion des sessions du navigateur nécessite SESSION_DRIVER=database.',
            'table' => 'La table de sessions Laravel configurée n’existe pas.',
        ],
        'device' => [
            'unknown_browser' => 'Navigateur inconnu',
            'unknown_platform' => 'Plateforme inconnue',
            'tablet' => 'Tablette',
            'mobile' => 'Mobile',
            'desktop' => 'Ordinateur',
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
            'warning' => [
                'heading' => 'Copiez ce jeton maintenant',
            ],
            'description' => 'C\'est la seule fois où il sera affiché en entier. Assurez-vous de l\'avoir copié — vous ne pourrez plus le consulter.',
            'token' => 'Jeton',
        ],
        'requirements' => [
            'sanctum' => 'Laravel Sanctum doit être installé pour activer la gestion des jetons API.',
            'user_model' => 'Le modèle utilisateur authentifié doit utiliser Laravel\\Sanctum\\HasApiTokens lorsque la gestion des jetons API est activée.',
            'abilities' => 'Configurez au moins une autorisation de jeton API avant d’activer la gestion des jetons API.',
            'context_migration' => 'Publiez et exécutez la migration de contexte des jetons de filament-complete-user-profile avant d’activer les jetons API par tenant.',
            'context_resolver' => 'Enregistrez :contract avant d’activer les jetons API par tenant.',
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
    'aside' => [
        'security' => [
            'heading' => 'Sécurité du compte',
            'app_authentication' => [
                'label' => 'Application d\'authentification',
                'enabled' => 'Activée',
                'disabled' => 'Non configurée',
            ],
            'email_authentication' => [
                'label' => 'MFA par e-mail',
                'enabled' => 'Activée',
                'disabled' => 'Non configurée',
            ],
            'sessions' => [
                'label' => 'Sessions actives',
                'count' => ':count session active|:count sessions actives',
            ],
            'api_tokens' => [
                'label' => 'Jetons d\'accès personnels',
                'count' => ':count jeton|:count jetons',
            ],
        ],
    ],
];
