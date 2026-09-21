<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public function __construct(
        protected ProfileStorage $storage,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if ($user instanceof Authenticatable) {
            $locale = $this->storage->get($user, 'locale');

            if (is_string($locale) && $locale !== '') {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
