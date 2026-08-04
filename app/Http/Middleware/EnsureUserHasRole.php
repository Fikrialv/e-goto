<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route ke satu atau beberapa role: `role:admin`, `role:admin,vendor`.
 *
 * Guest ditolak 403, bukan diarahkan ke login — middleware ini dipasang
 * setelah `auth`, jadi kalau sampai kesini tanpa user berarti ada salah
 * susunan middleware, dan diam-diam mengarahkan ke login akan menyembunyikan
 * kesalahan itu.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $allowed = array_map(
            fn (string $role) => UserRole::from($role),
            $roles,
        );

        abort_unless(in_array($user->role, $allowed, strict: true), 403);

        return $next($request);
    }
}
