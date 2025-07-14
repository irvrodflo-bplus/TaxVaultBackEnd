<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PacController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\LocalVaultController;


Route::controller(PacController::class)->prefix('pac')->group(function() {
    Route::post('/report', 'getReport');
    Route::post('/export_report', 'exportReport');
    Route::post('/download_files', 'exportReportData');
    Route::post('/report_stats', 'getReportStats');
});

Route::controller(LocalVaultController::class)->prefix('local_vault')->group(function() {
    Route::post('/report', 'getReport');
    Route::post('/export_report', 'exportReport');
    Route::post('/report_stats', 'getReportStats');
    Route::get('/analythics/{year}', 'getYearReport');
});

Route::controller(StatusController::class)->prefix('status')->group(function() {
    Route::get('/', 'checkStatus');
});