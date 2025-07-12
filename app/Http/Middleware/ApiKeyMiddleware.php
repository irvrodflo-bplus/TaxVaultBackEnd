<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware {
    public function handle(Request $request, Closure $next) {
        $requestKey = $request->header('X-API-KEY');
        $apiKey = config('app.api_key');


/*         if ($requestKey !== $apiKey) {
            return response()->json(['message' => 'Unauthorized', 'success' => false], 401);
        }
 */
        return $next($request);
    }
}
