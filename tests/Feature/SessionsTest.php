<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore;
use Mortalkiller\FilamentCompleteUserProfile\Sessions\DatabaseSessionStore;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class SessionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'database');
        config()->set('session.connection', 'testing');
        config()->set('session.table', 'sessions');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });

        $session = new Store('test', new ArraySessionHandler(120));
        $session->setId('current-session');

        $request = Request::create('/');
        $request->setLaravelSession($session);
        app()->instance('request', $request);
    }

    public function test_database_session_store_is_bound_and_supports_database_sessions(): void
    {
        self::assertInstanceOf(DatabaseSessionStore::class, app(SessionStore::class));
        self::assertTrue(app(SessionStore::class)->isSupported());

        config()->set('session.driver', 'file');

        self::assertFalse(app(DatabaseSessionStore::class)->isSupported());
    }

    public function test_sessions_are_strictly_scoped_to_the_authenticated_user(): void
    {
        $user = User::query()->create(['email' => 'pedro@example.test']);
        $otherUser = User::query()->create(['email' => 'other@example.test']);

        $this->insertSession('current-session', $user->getAuthIdentifier(), '10.0.0.1', 'Mozilla/5.0 Firefox/130.0 Linux');
        $this->insertSession('other-session', $user->getAuthIdentifier(), '10.0.0.2', 'Mozilla/5.0 Chrome/140.0 Windows NT 10.0');
        $this->insertSession('foreign-session', $otherUser->getAuthIdentifier(), '10.0.0.3', 'Mozilla/5.0 Safari/605.1 Macintosh');

        $sessions = app(SessionStore::class)->sessionsFor($user)->keyBy('id');

        self::assertCount(2, $sessions);
        self::assertTrue($sessions->has('current-session'));
        self::assertTrue($sessions->has('other-session'));
        self::assertFalse($sessions->has('foreign-session'));
        self::assertTrue($sessions['current-session']->current);
        self::assertFalse($sessions['other-session']->current);
        self::assertSame('Firefox · Linux · Desktop', $sessions['current-session']->device);
    }

    public function test_current_session_cannot_be_revoked_and_foreign_session_ids_are_ignored(): void
    {
        $user = User::query()->create(['email' => 'pedro@example.test']);
        $otherUser = User::query()->create(['email' => 'other@example.test']);

        $this->insertSession('current-session', $user->getAuthIdentifier(), '10.0.0.1');
        $this->insertSession('other-session', $user->getAuthIdentifier(), '10.0.0.2');
        $this->insertSession('foreign-session', $otherUser->getAuthIdentifier(), '10.0.0.3');

        $store = app(SessionStore::class);
        $store->revoke($user, 'current-session');
        $store->revoke($user, 'foreign-session');

        self::assertTrue(DB::table('sessions')->where('id', 'current-session')->exists());
        self::assertTrue(DB::table('sessions')->where('id', 'foreign-session')->exists());

        $store->revoke($user, 'other-session');

        self::assertFalse(DB::table('sessions')->where('id', 'other-session')->exists());
    }

    public function test_revoke_others_preserves_current_session_and_other_users(): void
    {
        $user = User::query()->create(['email' => 'pedro@example.test']);
        $otherUser = User::query()->create(['email' => 'other@example.test']);

        $this->insertSession('current-session', $user->getAuthIdentifier(), '10.0.0.1');
        $this->insertSession('other-session', $user->getAuthIdentifier(), '10.0.0.2');
        $this->insertSession('foreign-session', $otherUser->getAuthIdentifier(), '10.0.0.3');

        app(SessionStore::class)->revokeOthers($user, 'current-session');

        self::assertTrue(DB::table('sessions')->where('id', 'current-session')->exists());
        self::assertFalse(DB::table('sessions')->where('id', 'other-session')->exists());
        self::assertTrue(DB::table('sessions')->where('id', 'foreign-session')->exists());
    }

    public function test_missing_table_and_non_database_drivers_fail_gracefully(): void
    {
        Schema::drop('sessions');

        $store = app(DatabaseSessionStore::class);

        self::assertFalse($store->isSupported());
        self::assertSame([], $store->sessionsFor(new User)->all());

        config()->set('session.driver', 'redis');

        self::assertFalse($store->isSupported());
        self::assertSame([], $store->sessionsFor(new User)->all());
    }

    protected function insertSession(
        string $id,
        mixed $userId,
        string $ip,
        string $userAgent = 'Mozilla/5.0 Test Browser',
    ): void {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);
    }
}
