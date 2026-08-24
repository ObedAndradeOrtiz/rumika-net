<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureFaceVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->requires_face_verification) {
            return $next($request);
        }

        if ($request->routeIs('security.face') || $request->routeIs('logout')) {
            return $next($request);
        }

        $sessionUserId = (int) $request->session()->get('face_verified_user_id');
        $sessionIp = $request->session()->get('face_verified_ip');
        $currentIp = $request->ip();

        if ($sessionUserId === (int) $user->id && $sessionIp === $currentIp) {
            return $next($request);
        }

        $request->session()->put('url.intended', $request->fullUrl());

        $mode = $user->face_descriptor ? 'verify' : 'enroll';

        return redirect()->route('security.face', ['mode' => $mode]);
    }
}
