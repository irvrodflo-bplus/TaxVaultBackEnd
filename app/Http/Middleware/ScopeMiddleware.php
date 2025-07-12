<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Branch;
use App\Models\Business;

class ScopeMiddleware {
    public function handle(Request $request, Closure $next) {
        $userId = $request->header('X-USER-ID');

        if (!isset($userId)) {
            return response()->json([
                'message' => 'X-USER-ID header required', 
                'success' => false, 'id' => $userId
            ], 422);           
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'user not found', 
                'success' => false
            ], 404);
        }

        if($user->status != 1){
            return $this->disabledResponse('account');
        }

        $branch = $user->branch;
        $business = $branch->business; 

        if($business->status != 1){
            return $this->disabledResponse('business');
        }

        if($branch->status != 1){
            return $this->disabledResponse('branch');
        }

        $scope = $branch->is_main 
            ? ($business->is_main ? 'global' : 'business')
            : 'branch';

        $request->merge([
            'user_scope' => $scope,
            'user_id' => $user->id,
            'user_branch_id' => $branch->id,
            'user_business_id' => $business->id,
        ]);

        return $next($request);
    }

    private function disabledResponse(string $type) {
        return response()->json([
            'message' => "disabled $type",
            'success' => false
        ], 401);
    }
}
