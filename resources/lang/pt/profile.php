<?php

return [
    'page' => [
        'label' => 'A minha conta',
        'heading' => 'A minha conta',
        'subheading' => 'Gerir o seu perfil, segurança e acesso.',
    ],
    'navigation' => [
        'label' => 'Secções da conta',
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
            'description' => 'Copie este token agora. Não poderá voltar a vê-lo.',
            'token' => 'Token',
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
];
