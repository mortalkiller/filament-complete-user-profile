<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Security\PasswordReauthentication;
use Mortalkiller\FilamentCompleteUserProfile\Sessions\DatabaseSessionStore;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\TokenUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class TranslationsTest extends TestCase
{
    public function test_package_ships_complete_english_portuguese_spanish_and_french_translations(): void
    {
        $langPath = dirname(__DIR__, 2).'/resources/lang';
        $english = require $langPath.'/en/profile.php';
        $englishKeys = array_keys(Arr::dot($english));

        foreach (['pt', 'es', 'fr'] as $locale) {
            $file = $langPath."/{$locale}/profile.php";

            if (! file_exists($file)) {
                self::fail("Missing [{$locale}] package translation file.");
            }

            $translations = require $file;

            self::assertSame(
                $englishKeys,
                array_keys(Arr::dot($translations)),
                "Translation [{$locale}] must contain the same keys as English.",
            );
        }
    }

    public function test_page_copy_is_translated_for_each_shipped_locale(): void
    {
        $expected = [
            'en' => 'My account',
            'pt' => 'A minha conta',
            'es' => 'Mi cuenta',
            'fr' => 'Mon compte',
        ];

        foreach ($expected as $locale => $heading) {
            app()->setLocale($locale);

            self::assertSame(
                $heading,
                __('filament-complete-user-profile::profile.page.heading'),
            );
        }
    }

    public function test_reauthentication_ui_uses_package_translations(): void
    {
        app()->setLocale('pt');
        $this->setCurrentPanel();

        $reauthentication = app(PasswordReauthentication::class);
        $schema = $reauthentication->getFormSchema();
        $currentPassword = $schema[0] ?? null;

        self::assertInstanceOf(TextInput::class, $currentPassword);
        self::assertSame('Palavra-passe atual', $currentPassword->getLabel());

        try {
            $reauthentication->confirm(new User, ['current_password' => 'secret']);
            self::fail('Expected passwordless reauthentication to fail.');
        } catch (ValidationException $exception) {
            self::assertSame(
                'Esta conta não pode ser reautenticada com uma palavra-passe local.',
                $exception->errors()['current_password'][0] ?? null,
            );
        }
    }

    public function test_feature_requirement_messages_use_package_translations(): void
    {
        app()->setLocale('pt');

        self::assertSame(
            'O modelo de utilizador autenticado tem de usar Laravel\\Sanctum\\HasApiTokens quando a gestão de tokens de API está ativada.',
            ApiTokens::make()
                ->enabled()
                ->abilities(['customers:read' => 'Read customers'])
                ->getRequirementIssue(new User),
        );

        self::assertSame(
            'Configure pelo menos uma permissão permitida para tokens de API antes de ativar a gestão de tokens de API.',
            ApiTokens::make()
                ->enabled()
                ->getRequirementIssue(new TokenUser),
        );

        self::assertSame(
            'O modelo autenticável tem de implementar Mortalkiller\\FilamentCompleteUserProfile\\Contracts\\HasMultiFactorAuthentication quando a autenticação de dois fatores está ativada.',
            Security::make()
                ->multiFactorAuthentication()
                ->getMultiFactorAuthenticationRequirementIssue(new User),
        );
    }

    public function test_session_messages_and_generic_device_labels_use_package_translations(): void
    {
        app()->setLocale('pt');
        config()->set('session.driver', 'file');

        $store = app(DatabaseSessionStore::class);

        self::assertSame(
            'A gestão de sessões do browser requer SESSION_DRIVER=database.',
            $store->getUnsupportedReason(),
        );

        $testableStore = new class extends DatabaseSessionStore
        {
            public function describe(?string $userAgent): string
            {
                return $this->describeDevice($userAgent);
            }
        };

        self::assertSame(
            'Firefox · Linux · Computador',
            $testableStore->describe('Mozilla/5.0 Firefox/130.0 Linux'),
        );
        self::assertSame(
            'Navegador desconhecido · Plataforma desconhecida · Computador',
            $testableStore->describe('Custom Agent'),
        );
    }

    private function setCurrentPanel(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->default();

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);
    }
}
