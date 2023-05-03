<?php

use App\Http\Controllers\InstitutionUserController;
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
    Route::post('/institution-users/validate-import-file', [UsersImportController::class, 'validateFile']);
    Route::post('/institution-users/validate-import-file-row', [UsersImportController::class, 'validateRow']);
    Route::post('/institution-users/import', [UsersImportController::class, 'import']);
    Route::get('/institution-users', [InstitutionUserController::class, 'index']);
});
