<?php

namespace App\Services;

use App\Models\SyncRecord;

class SyncService {    

    public function storageSync(int $insert, int $updated, int $errors, string $user = 'Administrador') {
        $success = $this->resolveStatus($insert, $updated, $errors);

        $data = [
            'user'     => $user,
            'inserted' => $insert,
            'updated'  => $updated,
            'errors'   => $errors,
            'status'   => $success,
        ];

        return SyncRecord::create($data);
    }

    private function resolveStatus(int $insert, int $updated, int $error): string {
        if($error == 0) return 'success';

        if($insert > 0 || $updated > 0) return 'partial';

        return 'error';
    }
}
