<?php

use App\Http\Controllers\API\PrivilegeController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionSyncController;
use App\Http\Controllers\InstitutionUserController;
use App\Http\Controllers\InstitutionUserImportController;
use App\Http\Controllers\InstitutionUserSyncController;
use App\Http\Controllers\JwtClaimsController;
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

Route::withoutMiddleware('auth:api')->get('/jwt-claims', [JwtClaimsController::class, 'show']);

Route::get('/privileges', [PrivilegeController::class, 'index']);
Route::get('/institutions', [InstitutionController::class, 'index']);

Route::prefix('/roles')
    ->controller(RoleController::class)
    ->whereUuid('role_id')
    ->group(function (): void {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{role_id}', 'show');
        Route::put('/{role_id}', 'update');
        Route::delete('/{role_id}', 'destroy');
    });

Route::prefix('/institution-users')
    ->controller(InstitutionUserController::class)
    ->whereUuid('institution_user_id')
    ->group(function (): void {
        Route::get('/', 'index');
        Route::get('/{institution_user_id}', 'show');
        Route::put('/{institution_user_id}', 'update');
        Route::get('/export-csv', 'exportCsv');
        Route::post('/deactivate', 'deactivate');
        Route::post('/activate', 'activate');
        Route::post('/archive', 'archive');
    });

Route::prefix('/institution-users')
    ->controller(InstitutionUserImportController::class)
    ->group(function (): void {
        Route::post('/import-csv', 'importCsv');
        Route::post('/validate-import-csv', 'validateCsv');
        Route::post('/validate-import-csv-row', 'validateCsvRow');
    });

Route::prefix('/departments')
    ->controller(DepartmentController::class)
    ->whereUuid('department_id')
    ->group(function (): void {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{department_id}', 'show');
        Route::put('/{department_id}', 'update');
        Route::delete('/{department_id}', 'destroy');
    });

Route::withoutMiddleware(['auth:api', 'throttle:api'])->group(function () {
    Route::get('/sync/institutions', [InstitutionSyncController::class, 'index']);
    Route::get('/sync/institutions/{id}', [InstitutionSyncController::class, 'show'])->whereUuid('id');
    Route::get('/sync/institution-users', [InstitutionUserSyncController::class, 'index']);
    Route::get('/sync/institution-users/{id}', [InstitutionUserSyncController::class, 'show'])->whereUuid('id');
});
