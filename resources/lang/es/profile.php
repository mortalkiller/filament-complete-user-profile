<?php

return [
    'page' => [
        'label' => 'Mi cuenta',
        'heading' => 'Mi cuenta',
        'subheading' => 'Gestiona tu perfil, seguridad y acceso.',
    ],
    'navigation' => [
        'label' => 'Secciones de la cuenta',
    ],
    'fields' => [
        'avatar' => 'Avatar',
        'locale' => 'Idioma preferido',
    ],
    'overview' => [
        'name' => 'Nombre',
        'email' => 'Correo electrónico',
        'locale' => 'Idioma preferido',
    ],
    'security' => [
        'password' => [
            'action' => 'Cambiar contraseña',
            'new' => 'Nueva contraseña',
            'confirmation' => 'Confirmar nueva contraseña',
        ],
    ],
    'sessions' => [
        'columns' => [
            'device' => 'Dispositivo',
            'ip' => 'IP',
            'last_activity' => 'Última actividad',
            'status' => 'Estado',
        ],
        'status' => [
            'current' => 'Actual',
            'active' => 'Activa',
        ],
        'actions' => [
            'revoke' => 'Revocar',
            'revoke_others' => 'Revocar otras sesiones',
        ],
        'empty' => 'No se encontraron sesiones del navegador',
    ],
    'api_tokens' => [
        'columns' => [
            'name' => 'Nombre',
            'permissions' => 'Permisos',
            'last_used' => 'Último uso',
            'expires' => 'Caduca',
        ],
        'never' => 'Nunca',
        'fields' => [
            'name' => 'Nombre',
            'permissions' => 'Permisos',
            'expiration' => 'Caduca en días',
        ],
        'actions' => [
            'create' => 'Crear token',
            'revoke' => 'Revocar',
            'done' => 'Hecho',
        ],
        'created' => [
            'heading' => 'Token de API creado',
            'description' => 'Copia este token ahora. No podrás volver a verlo.',
            'token' => 'Token',
        ],
        'empty' => 'No hay tokens de API',
    ],
    'features' => [
        'overview' => [
            'label' => 'Resumen',
            'description' => 'Un resumen de tu cuenta y de las funciones de seguridad activadas.',
        ],
        'profile' => [
            'label' => 'Perfil',
            'description' => 'Gestiona tu información personal y tus preferencias.',
        ],
        'security' => [
            'label' => 'Seguridad',
            'description' => 'Gestiona tu contraseña y la seguridad de tu cuenta.',
        ],
        'sessions' => [
            'label' => 'Sesiones',
            'description' => 'Revisa y gestiona los dispositivos con sesión iniciada en tu cuenta.',
        ],
        'api-tokens' => [
            'label' => 'Tokens de API',
            'description' => 'Crea y revoca tokens personales de acceso a la API.',
        ],
    ],
];
