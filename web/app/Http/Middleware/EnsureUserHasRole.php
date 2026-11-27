<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user || !$user->is_active) {
            abort(403, 'Akun tidak aktif atau belum terautentikasi.');
        }

        // Flatten pipe or comma separated roles
        $allowed = [];
        foreach ($roles as $r) {
            foreach (preg_split('/[|,]/', $r) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $allowed[] = $part;
                }
            }
        }

        if (!in_array($user->role, $allowed, true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}

