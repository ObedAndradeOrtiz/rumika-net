<?php

namespace App\Http\Middleware;

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

        $now = now();
        $isTrialExpired = $company->status === 'trial'
            && $company->trial_ends_at
            && $company->trial_ends_at->lt($now);
        $isAccessExpired = in_array($company->status, ['active', 'past_due'], true)
            && $company->access_expires_at
            && $company->access_expires_at->lt($now);
        $isBlocked = in_array($company->status, ['blocked', 'suspended'], true);

        if ($isTrialExpired || $isAccessExpired || $isBlocked) {
            return redirect()->route('billing.blocked');
        }

        return $next($request);
    }
}
