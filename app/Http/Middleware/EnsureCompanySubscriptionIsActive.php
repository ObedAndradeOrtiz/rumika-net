<?php

namespace App\Http\Middleware;

use App\Support\CompanyPlanLimits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySubscriptionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_saas_admin) {
            return $next($request);
        }

        $company = $user->companies()->first();

        if (! $company) {
            return $next($request);
        }

        if (CompanyPlanLimits::isExpired($company)) {
            return redirect()->route('billing.blocked');
        }

        return $next($request);
    }
}
