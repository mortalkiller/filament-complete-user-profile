<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Tables\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore;
use Mortalkiller\FilamentCompleteUserProfile\Livewire\SessionsTable;
use Mortalkiller\FilamentCompleteUserProfile\Sessions\DatabaseSessionStore;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class SessionsTest extends TestCase
{
    private const CURRENT_SESSION_ID = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const OTHER_SESSION_ID = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private const FOREIGN_SESSION_ID = 'cccccccccccccccccccccccccccccccccccccccc';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'database');
        config()->set('session.connection', 'testing');
        config()->set('session.table', 'sessions');
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.guards.profile', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
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
        $session->setId(self::CURRENT_SESSION_ID);

        $request = Request::create('/');
        $request->setLaravelSession($session);
        app()->instance('request', $request);

        Filament::setCurrentPanel(
            Panel::make()
                ->id('admin')
                ->authGuard('profile'),
        );
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

        $this->insertSession(self::CURRENT_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.1', 'Mozilla/5.0 Firefox/130.0 Linux');
        $this->insertSession(self::OTHER_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.2', 'Mozilla/5.0 Chrome/140.0 Windows NT 10.0');
        $this->insertSession(self::FOREIGN_SESSION_ID, $otherUser->getAuthIdentifier(), '10.0.0.3', 'Mozilla/5.0 Safari/605.1 Macintosh');

        $sessions = app(SessionStore::class)->sessionsFor($user)->keyBy('id');

        self::assertCount(2, $sessions);
        self::assertTrue($sessions->has(self::CURRENT_SESSION_ID));
        self::assertTrue($sessions->has(self::OTHER_SESSION_ID));
        self::assertFalse($sessions->has(self::FOREIGN_SESSION_ID));
        self::assertTrue($sessions[self::CURRENT_SESSION_ID]->current);
        self::assertFalse($sessions[self::OTHER_SESSION_ID]->current);
        self::assertSame('Firefox · Linux · Desktop', $sessions[self::CURRENT_SESSION_ID]->device);
    }

    public function test_current_session_cannot_be_revoked_and_foreign_session_ids_are_ignored(): void
    {
        $user = User::query()->create(['email' => 'pedro@example.test']);
        $otherUser = User::query()->create(['email' => 'other@example.test']);

        $this->insertSession(self::CURRENT_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.1');
        $this->insertSession(self::OTHER_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.2');
        $this->insertSession(self::FOREIGN_SESSION_ID, $otherUser->getAuthIdentifier(), '10.0.0.3');

        $store = app(SessionStore::class);
        $store->revoke($user, self::CURRENT_SESSION_ID);
        $store->revoke($user, self::FOREIGN_SESSION_ID);

        self::assertTrue(DB::table('sessions')->where('id', self::CURRENT_SESSION_ID)->exists());
        self::assertTrue(DB::table('sessions')->where('id', self::FOREIGN_SESSION_ID)->exists());

        $store->revoke($user, self::OTHER_SESSION_ID);

        self::assertFalse(DB::table('sessions')->where('id', self::OTHER_SESSION_ID)->exists());
    }

    public function test_revoke_others_preserves_current_session_and_other_users(): void
    {
        $user = User::query()->create(['email' => 'pedro@example.test']);
        $otherUser = User::query()->create(['email' => 'other@example.test']);

        $this->insertSession(self::CURRENT_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.1');
        $this->insertSession(self::OTHER_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.2');
        $this->insertSession(self::FOREIGN_SESSION_ID, $otherUser->getAuthIdentifier(), '10.0.0.3');

        app(SessionStore::class)->revokeOthers($user, self::CURRENT_SESSION_ID);

        self::assertTrue(DB::table('sessions')->where('id', self::CURRENT_SESSION_ID)->exists());
        self::assertFalse(DB::table('sessions')->where('id', self::OTHER_SESSION_ID)->exists());
        self::assertTrue(DB::table('sessions')->where('id', self::FOREIGN_SESSION_ID)->exists());
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

    public function test_sessions_component_uses_the_expected_native_filament_table_structure(): void
    {
        $component = new SessionsTable;
        $table = $component->table(Table::make($component));

        self::assertSame(
            ['device', 'ip_address', 'last_activity', 'status'],
            array_keys($table->getColumns()),
        );

        $headerAction = $table->getHeaderActions()['revokeOtherSessions'] ?? null;
        self::assertInstanceOf(Action::class, $headerAction);
        self::assertSame('revokeOtherSessions', $headerAction->getName());

        $recordAction = $table->getAction('revoke');
        self::assertNotNull($recordAction);
        self::assertSame('revoke', $recordAction->getName());
    }

    public function test_revoke_other_sessions_requires_shared_reauthentication(): void
    {
        $user = User::query()->create([
            'email' => 'pedro@example.test',
            'password' => Hash::make('secret-password'),
        ]);
        auth('profile')->setUser($user);

        $this->insertSession(self::CURRENT_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.1');
        $this->insertSession(self::OTHER_SESSION_ID, $user->getAuthIdentifier(), '10.0.0.2');

        $component = new SessionsTable;

        try {
            $component->revokeOtherSessions(['current_password' => 'wrong-password']);
            self::fail('Expected reauthentication to reject an invalid password.');
        } catch (ValidationException) {
            self::assertTrue(DB::table('sessions')->where('id', self::OTHER_SESSION_ID)->exists());
        }

        $component->revokeOtherSessions(['current_password' => 'secret-password']);

        self::assertTrue(DB::table('sessions')->where('id', self::CURRENT_SESSION_ID)->exists());
        self::assertFalse(DB::table('sessions')->where('id', self::OTHER_SESSION_ID)->exists());
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
