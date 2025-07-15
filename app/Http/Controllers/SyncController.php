<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SyncService;

use App\Models\SyncRecord;

class SyncController extends Controller {
    private $syncService;

    public function __construct(SyncService $syncService){
        $this->syncService = $syncService;
    }

    public function index() {
        $records = SyncRecord::latest()->get();

        return response()->json([
            'success' => true,
            'records' => $records,
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'inserted' => 'required|integer',
            'updated' => 'required|integer',
            'errors' => 'required|integer',
            'user' => 'nullable|string'
        ]);
        
        $this->syncService->storageSync(
            $validated['inserted'],
            $validated['updated'],
            $validated['errors'],
            $validated['user'] ?? 'Administrador'
        );

        return response()->json([
            'success' => true,
            'message' => 'record saved successfully'
        ]); 
    }
}
