<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class UserModelResolver
{
    /** @return class-string<Authenticatable> */
    public function resolve(): string
    {
        $configuredModel = config('filament-complete-user-profile.user_model');

        if (is_string($configuredModel) && $configuredModel !== '') {
            return $this->validateModel($configuredModel);
        }

        $guard = config('auth.defaults.guard');
        $provider = is_string($guard) ? config("auth.guards.{$guard}.provider") : null;
        $providerModel = is_string($provider) ? config("auth.providers.{$provider}.model") : null;

        if (! is_string($providerModel) || $providerModel === '') {
            throw new LogicException('Unable to resolve the authenticatable model from the default authentication provider. Configure filament-complete-user-profile.user_model explicitly.');
        }

        return $this->validateModel($providerModel);
    }

    public function table(): string
    {
        $model = app($this->resolve());

        if (! $model instanceof Model) {
            throw new LogicException('The configured authenticatable must be an Eloquent model to use package-managed profile storage.');
        }

        return $model->getTable();
    }

    /** @return class-string<Authenticatable> */
    protected function validateModel(string $model): string
    {
        if (! class_exists($model) || ! is_a($model, Authenticatable::class, true)) {
            throw new LogicException("Configured user model [{$model}] must implement ".Authenticatable::class.'.');
        }

        /** @var class-string<Authenticatable> $model */
        return $model;
    }
}
