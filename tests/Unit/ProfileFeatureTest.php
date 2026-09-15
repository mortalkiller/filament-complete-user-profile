<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class ProfileFeatureTest extends TestCase
{
    public function test_profile_fields_are_enabled_by_default(): void
    {
        $profile = Profile::make();

        self::assertTrue($profile->hasAvatar());
        self::assertTrue($profile->hasName());
        self::assertTrue($profile->hasEmail());
        self::assertTrue($profile->hasLocale());
    }

    public function test_locale_options_prefer_available_locales_and_resolve_language_names(): void
    {
        config()->set('app.locale', 'en');
        config()->set('app.available_locales', ['pt', 'en']);
        config()->set('app.supported_locales', ['es', 'fr']);

        self::assertSame(
            ['pt' => 'Português', 'en' => 'English'],
            Profile::make()->getLocaleOptions(),
        );
    }

    public function test_locale_options_fall_back_to_supported_locales_then_app_locale(): void
    {
        config()->set('app.locale', 'en');
        config()->set('app.available_locales');
        config()->set('app.supported_locales', ['es', 'fr']);

        self::assertSame(
            ['es' => 'Español', 'fr' => 'Français'],
            Profile::make()->getLocaleOptions(),
        );

        config()->set('app.supported_locales');

        self::assertSame(
            ['en' => 'English'],
            Profile::make()->getLocaleOptions(),
        );
    }

    public function test_explicit_locale_codes_are_resolved_and_manual_labels_are_preserved(): void
    {
        self::assertSame(
            [
                'pt_PT' => 'Português (Portugal)',
                'pt_BR' => 'Português (Brasil)',
                'en' => 'English',
            ],
            Profile::make()->locale(['pt_PT', 'pt_BR', 'en'])->getLocaleOptions(),
        );

        self::assertSame(
            ['pt' => 'Português de Portugal', 'en' => 'English'],
            Profile::make()->locale([
                'pt' => 'Português de Portugal',
                'en' => 'English',
            ])->getLocaleOptions(),
        );
    }

    public function test_fields_append_and_modifier_can_reorder_them(): void
    {
        $name = TextInput::make('name');
        $phone = TextInput::make('phone');

        $profile = Profile::make()
            ->fields([$phone])
            ->modifyFieldsUsing(fn (array $fields): array => array_reverse($fields));

        self::assertSame([$phone], $profile->getAdditionalFields());
        self::assertSame([$phone, $name], $profile->modifyFields([$name, $phone]));
    }

    public function test_standard_fields_can_be_disabled_or_modified(): void
    {
        $profile = Profile::make()
            ->avatar(fn (FileUpload $field): FileUpload => $field->directory('custom-avatars'))
            ->locale(false);

        $avatar = $profile->configureAvatar(FileUpload::make('avatar'));

        self::assertTrue($profile->hasAvatar());
        self::assertFalse($profile->hasLocale());
        self::assertInstanceOf(FileUpload::class, $avatar);
        self::assertSame('custom-avatars', $avatar->getDirectory());
    }

    public function test_data_and_after_save_hooks_are_available(): void
    {
        $called = false;
        $profile = Profile::make()
            ->mutateDataBeforeSaveUsing(fn (array $data): array => [...$data, 'job_title' => 'Developer'])
            ->afterSave(function () use (&$called): void {
                $called = true;
            });

        self::assertSame(
            ['name' => 'Pedro', 'job_title' => 'Developer'],
            $profile->mutateDataBeforeSave(['name' => 'Pedro']),
        );

        $profile->runAfterSave(new User, []);
        self::assertTrue($called);
    }
}
