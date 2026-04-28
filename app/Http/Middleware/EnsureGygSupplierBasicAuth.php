<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGygSupplierBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('gyg_supplier_api.username', '');
        $password = (string) config('gyg_supplier_api.password', '');

        if ($username === '' || $password === '') {
            return response()->json([
                'errorCode' => 'AUTHORIZATION_FAILURE',
                'errorMessage' => 'Supplier API credentials are not configured',
            ], 503);
        }

        $providedUser = (string) $request->getUser();
        $providedPassword = (string) $request->getPassword();

        if (! hash_equals($username, $providedUser) || ! hash_equals($password, $providedPassword)) {
            return response()->json([
                'errorCode' => 'AUTHORIZATION_FAILURE',
                'errorMessage' => 'Invalid credentials',
            ], 401, [
                'WWW-Authenticate' => 'Basic realm="GetYourGuide Supplier API"',
            ]);
        }

        return $next($request);
    }
}
