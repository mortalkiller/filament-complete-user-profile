<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tenancy;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;

final class TenancyManager
{
    /** @var TenancyResolver|Closure(): (Model|null)|class-string<TenancyResolver>|null */
    protected TenancyResolver|Closure|string|null $resolver = null;

    public function __construct(
        protected Container $container,
    ) {}

    /**
     * @param  TenancyResolver|Closure(): (Model|null)|class-string<TenancyResolver>  $resolver
     */
    public function useResolver(TenancyResolver|Closure|string $resolver): static
    {
        if (is_string($resolver) && ! is_a($resolver, TenancyResolver::class, true)) {
            throw new LogicException("Tenancy resolver [{$resolver}] must implement ".TenancyResolver::class.'.');
        }

        $this->resolver = $resolver;

        return $this;
    }

    public function hasCustomResolver(): bool
    {
        return $this->resolver !== null;
    }

    public function resolve(): ?Model
    {
        $resolver = $this->resolver ?? $this->defaultResolver();

        if ($resolver instanceof Closure) {
            $tenant = $this->container->call($resolver);
        } elseif (is_string($resolver)) {
            $instance = $this->container->make($resolver);

            if (! $instance instanceof TenancyResolver) {
                throw new LogicException("Tenancy resolver [{$resolver}] must implement ".TenancyResolver::class.'.');
            }

            $tenant = $instance->resolve();
        } else {
            $tenant = $resolver->resolve();
        }

        if ($tenant !== null && ! $tenant instanceof Model) {
            throw new LogicException('The tenancy resolver must return an Eloquent model or null.');
        }

        return $tenant;
    }

    public function requireTenant(): Model
    {
        $tenant = $this->resolve();

        if ($tenant instanceof Model) {
            return $tenant;
        }

        throw ValidationException::withMessages([
            'tokens' => 'An active tenant is required for tenant-scoped API tokens.',
        ]);
    }

    protected function defaultResolver(): TenancyResolver
    {
        if ($this->container->bound(TokenContextResolver::class)) {
            $legacyResolver = $this->container->make(TokenContextResolver::class);

            if (! $legacyResolver instanceof TokenContextResolver) {
                throw new LogicException('The legacy token context resolver binding is invalid.');
            }

            return $legacyResolver;
        }

        $resolver = $this->container->make(TenancyResolver::class);

        if (! $resolver instanceof TenancyResolver) {
            throw new LogicException('The tenancy resolver binding is invalid.');
        }

        return $resolver;
    }
}
