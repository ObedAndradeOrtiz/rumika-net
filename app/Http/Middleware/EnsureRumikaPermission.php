<?php

namespace App\Http\Middleware;

use App\Support\RumikaAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRumikaPermission
{
    public function handle(Request $request, Closure $next, ?string $module = null, string $action = 'view'): Response
    {
        $user = $request->user();
        $module ??= RumikaAccess::moduleForRoute((string) $request->route()?->getName());

        $modules = collect(explode('|', (string) $module))
            ->map(fn (string $moduleName) => trim($moduleName))
            ->filter();

        abort_unless(
            $user
                && $modules->isNotEmpty()
                && $modules->contains(fn (string $moduleName) => RumikaAccess::can($user, $moduleName, $action)),
            403,
        );

        return $next($request);
    }
}
