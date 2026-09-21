<?php

return [
    'page' => [
        'label' => 'A minha conta',
        'heading' => 'A minha conta',
        'subheading' => 'Gerir o seu perfil, segurança e acesso.',
    ],
    'fields' => [
        'avatar' => 'Avatar',
        'locale' => 'Idioma preferido',
    ],
    'overview' => [
        'name' => 'Nome',
        'email' => 'E-mail',
        'locale' => 'Idioma preferido',
    ],
    'security' => [
        'password' => [
            'action' => 'Alterar palavra-passe',
            'new' => 'Nova palavra-passe',
            'confirmation' => 'Confirmar nova palavra-passe',
        ],
        'reauthentication' => [
            'current_password' => 'Palavra-passe atual',
            'unavailable' => 'Esta conta não pode ser reautenticada com uma palavra-passe local.',
        ],
        'mfa' => [
            'requirement' => 'O modelo autenticável tem de implementar :contract quando a autenticação de dois fatores está ativada.',
        ],
        'email_authentication' => [
            'requirement' => 'O modelo autenticável tem de implementar :contract quando a autenticação por e-mail está ativada.',
            'notifications' => 'O modelo de utilizador autenticado tem de suportar notificações do Laravel para usar autenticação por e-mail.',
            'resend' => [
                'label' => 'Enviar um novo código por e-mail',
                'sent' => 'Foi enviado um novo código de verificação.',
            ],
            'email' => [
                'subject' => 'O seu código de verificação do :app',
                'heading' => 'Confirme a sua identidade',
                'intro' => 'Foi pedido um código de verificação para a sua conta :app. Introduza o código abaixo para continuar.',
                'expiry' => 'Este código expira dentro de :minutes minuto.|Este código expira dentro de :minutes minutos.',
                'warning' => 'Nunca partilhe este código. A nossa equipa nunca lhe irá pedir este código.',
                'ignore' => 'Se não pediu este código, pode ignorar este e-mail em segurança.',
                'footer' => 'Mensagem de segurança de :app.',
            ],
        ],
    ],
    'sessions' => [
        'columns' => [
            'device' => 'Dispositivo',
            'ip' => 'IP',
            'last_activity' => 'Última atividade',
            'status' => 'Estado',
        ],
        'status' => [
            'current' => 'Atual',
            'active' => 'Ativa',
        ],
        'actions' => [
            'revoke' => 'Revogar',
            'revoke_others' => 'Revogar outras sessões',
        ],
        'requirements' => [
            'database_driver' => 'A gestão de sessões do browser requer SESSION_DRIVER=database.',
            'table' => 'A tabela de sessões do Laravel configurada não existe.',
        ],
        'device' => [
            'unknown_browser' => 'Navegador desconhecido',
            'unknown_platform' => 'Plataforma desconhecida',
            'tablet' => 'Tablet',
            'mobile' => 'Telemóvel',
            'desktop' => 'Computador',
        ],
        'empty' => 'Não foram encontradas sessões de browser',
    ],
    'api_tokens' => [
        'columns' => [
            'name' => 'Nome',
            'permissions' => 'Permissões',
            'last_used' => 'Última utilização',
            'expires' => 'Expira',
        ],
        'never' => 'Nunca',
        'fields' => [
            'name' => 'Nome',
            'permissions' => 'Permissões',
            'expiration' => 'Expira em dias',
        ],
        'actions' => [
            'create' => 'Criar token',
            'revoke' => 'Revogar',
            'done' => 'Concluído',
        ],
        'created' => [
            'heading' => 'Token de API criado',
            'warning' => [
                'heading' => 'Copie este token agora',
            ],
            'description' => 'Esta é a única vez que será mostrado por completo. Certifique-se de que o copiou — não poderá voltar a vê-lo.',
            'token' => 'Token',
        ],
        'requirements' => [
            'sanctum' => 'O Laravel Sanctum tem de estar instalado para ativar a gestão de tokens de API.',
            'user_model' => 'O modelo de utilizador autenticado tem de usar Laravel\\Sanctum\\HasApiTokens quando a gestão de tokens de API está ativada.',
            'abilities' => 'Configure pelo menos uma permissão permitida para tokens de API antes de ativar a gestão de tokens de API.',
            'context_migration' => 'Publique e execute a migration de contexto de tokens do filament-complete-user-profile antes de ativar tokens de API por tenant.',
            'context_resolver' => 'Registe :contract antes de ativar tokens de API por tenant.',
        ],
        'empty' => 'Sem tokens de API',
    ],
    'features' => [
        'overview' => [
            'label' => 'Visão geral',
            'description' => 'Um resumo da sua conta e das funcionalidades de segurança ativadas.',
        ],
        'profile' => [
            'label' => 'Perfil',
            'description' => 'Gerir as suas informações pessoais e preferências.',
        ],
        'security' => [
            'label' => 'Segurança',
            'description' => 'Gerir a sua palavra-passe e a segurança da conta.',
        ],
        'sessions' => [
            'label' => 'Sessões',
            'description' => 'Rever e gerir os dispositivos com sessão iniciada na sua conta.',
        ],
        'api-tokens' => [
            'label' => 'Tokens de API',
            'description' => 'Criar e revogar tokens pessoais de acesso à API.',
        ],
    ],
    'aside' => [
        'security' => [
            'heading' => 'Segurança da conta',
            'app_authentication' => [
                'label' => 'Aplicação de autenticação',
                'enabled' => 'Ativada',
                'disabled' => 'Por configurar',
            ],
            'email_authentication' => [
                'label' => 'MFA por e-mail',
                'enabled' => 'Ativada',
                'disabled' => 'Por configurar',
            ],
            'sessions' => [
                'label' => 'Sessões ativas',
                'count' => ':count sessão ativa|:count sessões ativas',
            ],
            'api_tokens' => [
                'label' => 'Tokens de acesso pessoal',
                'count' => ':count token|:count tokens',
            ],
        ],
    ],
];
