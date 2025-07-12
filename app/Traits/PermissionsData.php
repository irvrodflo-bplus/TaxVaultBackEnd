<?php

namespace App\Traits;

use App\Models\User;

trait PermissionsData {
    protected function filterAllUsePermission($query, int $userId, string $key, string $submodule) {
        $user = User::with('role.permissions.operation.submodule')->find($userId);
        $submodules = $this->extractOperations($user);
        $operations = $this->getSubmoduleOperations($submodule, $submodules);

        if(in_array('read-all', $operations)){
            return $query;
        }
        
        return $query->where($key, $userId);
    }

    private function extractOperations(User $user){
        $permissions = $user->role->permissions;
        $groupedPermissions = [];
        
        foreach ($permissions as $permission) {
            $submoduleId = $permission->operation->submodule->id;
            $submoduleName = $permission->operation->submodule->name;
            $submoduleValue = $permission->operation->submodule->value;
            
            if (!isset($groupedPermissions[$submoduleId])) {
                $groupedPermissions[$submoduleId] = [
                    'name' => $submoduleName,
                    'value' => $submoduleValue,
                    'operations' => []
                ];
            }
            
            $groupedPermissions[$submoduleId]['operations'][] = [
                'id' => $permission->operation->id,
                'label' => $permission->operation->label,
                'value' => $permission->operation->value
            ];
        }

        return array_values($groupedPermissions);
    }

    private function getSubmoduleOperations($submoduleValue, $submodules) {
        foreach ($submodules as $submodule) {
            if ($submodule['value'] === $submoduleValue) {

                return array_map(function ($operation) {
                    return $operation['value'];
                }, $submodule['operations']);
            }
        }
        
        return [];
    }
}