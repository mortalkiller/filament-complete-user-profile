<?php

declare(strict_types=1);

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Models\DemoUser;

final class LocalDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment('local', 'testing'), 404);

        $user = DemoUser::query()->firstOrCreate(
            ['email' => 'alex@example.test'],
            [
                'name' => 'Alex Morgan',
                'password' => Hash::make('workbench-password'),
            ],
        );

        auth()->setUser($user);

        $storage = app(ProfileStorage::class);

        if (blank($storage->get($user, 'locale'))) {
            $storage->put($user, 'locale', 'en');
        }

        return $next($request);
    }
}
