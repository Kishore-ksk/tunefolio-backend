<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = ['https://tunefolio-frontend.vercel.app', 'http://localhost:5176']; // ✅ Allow only your frontend

        $origin = $request->headers->get('Origin');

        $response = $next($request);

        if ($origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With,user-id');
            $response->headers->set('Access-Control-Allow-Credentials', 'true'); // ✅ Important for authentication
        }

        if ($request->getMethod() === "OPTIONS") {
            return response()->json('{"method":"OPTIONS"}', 200, $response->headers->all());
        }

        return $response;
    }
}
