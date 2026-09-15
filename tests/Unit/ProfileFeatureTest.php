<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
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

    public function test_locale_options_follow_explicit_supported_and_app_locale_priority(): void
    {
        config()->set('app.locale', 'en');
        config()->set('app.supported_locales', ['pt', 'en']);

        self::assertSame(['pt' => 'pt', 'en' => 'en'], Profile::make()->getLocaleOptions());
        self::assertSame(
            ['pt' => 'Português', 'en' => 'English'],
            Profile::make()->locale(['pt' => 'Português', 'en' => 'English'])->getLocaleOptions(),
        );

        config()->set('app.supported_locales');
        self::assertSame(['en' => 'en'], Profile::make()->getLocaleOptions());
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

        $profile->runAfterSave(new \Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User, []);
        self::assertTrue($called);
    }
}
