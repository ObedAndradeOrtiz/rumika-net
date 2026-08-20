<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyOnboardingIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $user?->companies()->with('branches')->first();

        $hasCompleteBranch = $company?->branches->contains(fn ($branch) => filled($branch->name) && filled($branch->business_type_id));

        if ($company && ! $company->onboarding_completed_at && ! $hasCompleteBranch && ! $request->routeIs('onboarding.company')) {
            return redirect()->route('onboarding.company');
        }

        return $next($request);
    }
}
