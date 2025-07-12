<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PacController;
use App\Http\Controllers\StatusController;

Route::controller(PacController::class)->prefix('pac')->group(function() {
    Route::post('/report', 'getReport');
    Route::post('/export_report', 'exportReport');
    Route::post('/download_files', 'exportReportData');
});

Route::controller(StatusController::class)->prefix('status')->group(function() {
    Route::get('/', 'checkStatus');
});