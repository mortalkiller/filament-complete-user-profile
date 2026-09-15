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
        'reauthentication' => [
            'current_password' => 'Contraseña actual',
            'unavailable' => 'Esta cuenta no puede volver a autenticarse con una contraseña local.',
        ],
        'mfa' => [
            'requirement' => 'El modelo autenticable debe implementar :contract cuando la autenticación multifactor está activada.',
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
        'requirements' => [
            'database_driver' => 'La gestión de sesiones del navegador requiere SESSION_DRIVER=database.',
            'table' => 'La tabla de sesiones de Laravel configurada no existe.',
        ],
        'device' => [
            'unknown_browser' => 'Navegador desconocido',
            'unknown_platform' => 'Plataforma desconocida',
            'tablet' => 'Tableta',
            'mobile' => 'Móvil',
            'desktop' => 'Ordenador',
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
        'requirements' => [
            'sanctum' => 'Laravel Sanctum debe estar instalado para activar la gestión de tokens de API.',
            'user_model' => 'El modelo de usuario autenticado debe usar Laravel\\Sanctum\\HasApiTokens cuando la gestión de tokens de API está activada.',
            'abilities' => 'Configura al menos un permiso permitido para tokens de API antes de activar la gestión de tokens de API.',
            'context_migration' => 'Publica y ejecuta la migración de contexto de tokens de filament-complete-user-profile antes de activar tokens de API por tenant.',
            'context_resolver' => 'Registra :contract antes de activar tokens de API por tenant.',
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
