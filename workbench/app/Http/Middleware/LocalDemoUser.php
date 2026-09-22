<?php

declare(strict_types=1);

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Models\DemoUser;

final class LocalDemoUser
{
    private const DEMO_PASSWORD = 'workbench-password';

    private const SECONDARY_SESSION_ID = 'workbench-secondary-session';

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment('local', 'testing'), 404);

        $user = DemoUser::query()->firstOrCreate(
            ['email' => 'alex@example.test'],
            [
                'name' => 'Alex Morgan',
                'job_title' => 'Product Engineer',
                'phone' => '+351 210 000 000',
                'password' => Hash::make(self::DEMO_PASSWORD),
            ],
        );

        auth()->setUser($user);

        $storage = app(ProfileStorage::class);

        if (blank($storage->get($user, 'locale'))) {
            $storage->put($user, 'locale', 'en');
        }

        $this->ensureDemoAddresses($user);
        $this->ensureSecondarySession($user);

        return $next($request);
    }

    private function ensureDemoAddresses(DemoUser $user): void
    {
        $user->addresses()->firstOrCreate(
            ['label' => 'Home'],
            [
                'line_one' => '12 Example Street',
                'city' => 'Lisbon',
                'country_code' => 'PT',
            ],
        );

        $user->addresses()->firstOrCreate(
            ['label' => 'Studio'],
            [
                'line_one' => '42 Demo Avenue',
                'city' => 'Porto',
                'country_code' => 'PT',
            ],
        );
    }

    private function ensureSecondarySession(DemoUser $user): void
    {
        if (! DB::getSchemaBuilder()->hasTable('sessions')) {
            return;
        }

        DB::table('sessions')->updateOrInsert(
            ['id' => self::SECONDARY_SESSION_ID],
            [
                'user_id' => $user->getAuthIdentifier(),
                'ip_address' => '203.0.113.24',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/18.0 Safari/605.1.15',
                'payload' => '',
                'last_activity' => now()->subMinutes(8)->timestamp,
            ],
        );
    }
}
