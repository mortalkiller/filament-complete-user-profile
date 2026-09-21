<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Support\UserModelResolver;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\Admin;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class UserModelResolverTest extends TestCase
{
    public function test_it_prefers_the_explicitly_configured_user_model(): void
    {
        config()->set('filament-complete-user-profile.user_model', Admin::class);
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web.provider', 'users');
        config()->set('auth.providers.users.model', User::class);

        $resolver = app(UserModelResolver::class);

        self::assertSame(Admin::class, $resolver->resolve());
        self::assertSame('admins', $resolver->table());
    }

    public function test_it_falls_back_to_the_default_authentication_provider(): void
    {
        config()->set('filament-complete-user-profile.user_model');
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web.provider', 'users');
        config()->set('auth.providers.users.model', User::class);

        $resolver = app(UserModelResolver::class);

        self::assertSame(User::class, $resolver->resolve());
        self::assertSame('users', $resolver->table());
    }

    public function test_it_fails_with_a_clear_error_when_no_model_can_be_resolved(): void
    {
        config()->set('filament-complete-user-profile.user_model');
        config()->set('auth.defaults.guard', 'missing');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to resolve the authenticatable model');

        app(UserModelResolver::class)->resolve();
    }
}
