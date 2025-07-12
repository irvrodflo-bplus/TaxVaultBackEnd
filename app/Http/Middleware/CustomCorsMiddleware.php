<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomCorsMiddleware {
    public function handle(Request $request, Closure $next) {
        $response = $next($request);

        if ($response->headers->has('Access-Control-Allow-Origin')) {
            $response->headers->remove('Access-Control-Allow-Origin');
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
