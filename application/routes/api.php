<?php

use App\Http\Controllers\JwtClaimsController;
use App\Http\Controllers\UsersImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/jwt-claims', [JwtClaimsController::class, 'show']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('/users/validate-import-file', [UsersImportController::class, 'validateFile']);
    Route::post('/users/validate-import-file-row', [UsersImportController::class, 'validateRow']);
    Route::post('/users/import', [UsersImportController::class, 'import']);
});
