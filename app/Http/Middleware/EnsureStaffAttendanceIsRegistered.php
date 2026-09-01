<?php

namespace App\Http\Middleware;

use App\Support\StaffAttendanceGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAttendanceIsRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('dashboard', 'security.face', 'logout')) {
            return $next($request);
        }

        $company = $user->companies()->first();

        if (StaffAttendanceGate::blocksOutsideSchedule($user, $company)) {
            return redirect()
                ->route('dashboard')
                ->with('attendance_required', 'Tu usuario solo puede usar Rumika dentro de su horario laboral.');
        }

        if (! StaffAttendanceGate::requiresOpenAttendance($user, $company)) {
            return $next($request);
        }

        return redirect()
            ->route('dashboard')
            ->with('attendance_required', 'Registra tu asistencia desde la sucursal para usar Rumika.');
    }
}
