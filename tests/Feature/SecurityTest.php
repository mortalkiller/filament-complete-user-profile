<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\Reauthentication;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Security\PasswordReauthentication;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.guards.profile', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);
        config()->set('filament-complete-user-profile.user_model', User::class);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('locale')->nullable();
            $table->timestamps();
        });

        Filament::setCurrentPanel(
            Panel::make()
                ->id('admin')
                ->authGuard('profile'),
        );
    }

    public function test_default_reauthentication_is_bound_and_fails_closed_for_passwordless_users(): void
    {
        self::assertTrue(interface_exists(Reauthentication::class));
        self::assertTrue(class_exists(PasswordReauthentication::class));

        $reauthentication = app(Reauthentication::class);
        self::assertInstanceOf(PasswordReauthentication::class, $reauthentication);

        $passwordUser = User::query()->create([
            'email' => 'password@example.test',
            'password' => Hash::make('secret-password'),
        ]);
        $passwordlessUser = User::query()->create([
            'email' => 'passwordless@example.test',
            'password' => null,
        ]);

        self::assertTrue($reauthentication->isAvailable($passwordUser));
        self::assertFalse($reauthentication->isAvailable($passwordlessUser));
    }

    public function test_password_reauthentication_uses_the_active_filament_guard(): void
    {
        self::assertTrue(class_exists(PasswordReauthentication::class));

        $user = User::query()->create([
            'email' => 'pedro@example.test',
            'password' => Hash::make('secret-password'),
        ]);

        auth('profile')->login($user);

        $reauthentication = app(PasswordReauthentication::class);
        $reauthentication->confirm($user, ['current_password' => 'secret-password']);

        $this->expectException(ValidationException::class);
        $reauthentication->confirm($user, ['current_password' => 'wrong-password']);
    }

    public function test_password_update_requires_reauthentication_and_securely_hashes_the_new_password(): void
    {
        self::assertTrue(method_exists(CompleteUserProfile::class, 'updatePassword'));

        $user = User::query()->create([
            'email' => 'pedro@example.test',
            'password' => Hash::make('secret-password'),
        ]);
        auth('profile')->login($user);

        $page = new TestableCompleteUserProfile;
        $page->userForTesting = $user;
        $page->applyPasswordUpdate([
            'current_password' => 'secret-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $user->refresh();
        self::assertTrue(Hash::check('new-secret-password', $user->getAuthPassword()));

        $this->expectException(ValidationException::class);
        $page->applyPasswordUpdate([
            'current_password' => 'wrong-password',
            'password' => 'another-secret-password',
            'password_confirmation' => 'another-secret-password',
        ]);
    }

    public function test_overview_contains_only_relevant_default_account_information(): void
    {
        self::assertTrue(method_exists(CompleteUserProfile::class, 'getOverviewData'));

        $user = User::query()->create([
            'name' => 'Pedro Monteiro',
            'email' => 'pedro@example.test',
            'avatar_url' => 'avatars/pedro.png',
            'locale' => 'pt',
        ]);

        $page = new TestableCompleteUserProfile;
        $page->userForTesting = $user;

        self::assertSame([
            'avatar' => 'avatars/pedro.png',
            'name' => 'Pedro Monteiro',
            'email' => 'pedro@example.test',
            'locale' => 'pt',
        ], $page->overviewDataForTesting());
    }
}

class TestableCompleteUserProfile extends CompleteUserProfile
{
    public User $userForTesting;

    public function getUser(): Authenticatable&Model
    {
        return $this->userForTesting;
    }

    /** @param array<string, mixed> $data */
    public function applyPasswordUpdate(array $data): void
    {
        $this->updatePassword($data);
    }

    /** @return array<string, mixed> */
    public function overviewDataForTesting(): array
    {
        return $this->getOverviewData();
    }
}
