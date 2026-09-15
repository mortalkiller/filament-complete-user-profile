<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;
use Mortalkiller\FilamentCompleteUserProfile\Tokens\TokenContext;

class EnsureTokenContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $currentAccessToken = is_object($user) ? [$user, 'currentAccessToken'] : null;

        if (is_callable($currentAccessToken) === false) {
            abort(403, 'An authenticated API token is required.');
        }

        $token = $currentAccessToken();

        if ($token instanceof Model === false) {
            abort(403, 'An authenticated API token is required.');
        }

        if (app()->bound(TokenContextResolver::class) === false) {
            abort(403, 'A token context resolver is required for tenant-scoped API tokens.');
        }

        $contextModel = app(TokenContextResolver::class)->resolve();

        if ($contextModel instanceof Model === false) {
            abort(403, 'An active API token context is required.');
        }

        $contextType = $token->getAttribute('context_type');
        $contextId = $token->getAttribute('context_id');

        if (is_string($contextType) === false || is_scalar($contextId) === false) {
            abort(403, 'The API token is missing its tenant context.');
        }

        $tokenContext = new TokenContext($contextType, (string) $contextId);

        if ($tokenContext->matches($contextModel) === false) {
            abort(403, 'API token context does not match the active context.');
        }

        return $next($request);
    }
}
