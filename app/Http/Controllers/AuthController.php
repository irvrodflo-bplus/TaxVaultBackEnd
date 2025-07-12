<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Services\PermissionService;

class AuthController extends Controller {
    private $permissionService;

    public function __construct(PermissionService $permissionService) {
        $this->permissionService = $permissionService;
    }

    public function login(Request $request) {
        $user = User::with('role.permissions.operation.submodule.module', 'branch.business')->where('email', $request['email'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'invalid credentials'
            ], 401);
        }

        $isDeactive = $user->status == 0 || $user->branch->status == 0 || $user->branch->business->status == 0 || $user->role->status == 0;

        if($isDeactive){
            return response()->json([
                'message' => 'disabled account',
                'success' => false
            ], 401);
        }

        $branch = $user->branch;
        $business = $branch->business; 

        $scope = $branch->is_main 
            ? ($business->is_main ? 'global' : 'business')
            : 'branch';

        $role = $user->role;
        $modules = $this->permissionService->structureRolePermissions($role);

        return response()->json([
            'message' => 'user logged successfully',
            'success' => true,
            'user' => $user->makeHidden(['created_at', 'updated_at', 'role']),
            'user_scope' => $scope,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'modules' => $modules,
            ],
        ]);
    }
}
