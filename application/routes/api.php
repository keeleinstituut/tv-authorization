<?php

use App\Http\Controllers\API\PrivilegeController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionUserController;
use App\Http\Controllers\InstitutionUserImportController;
use App\Http\Controllers\JwtClaimsController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/privileges', [PrivilegeController::class, 'index']);

Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'store']);
Route::get('/roles/{role_id}', [RoleController::class, 'show'])->whereUuid('role_id');
Route::put('/roles/{role_id}', [RoleController::class, 'update'])->whereUuid('role_id');
Route::delete('/roles/{role_id}', [RoleController::class, 'destroy'])->whereUuid('role_id');

Route::get('/jwt-claims', [JwtClaimsController::class, 'show'])->withoutMiddleware('auth:api');

Route::get('/institutions', [InstitutionController::class, 'index']);

Route::get('/institution-users', [InstitutionUserController::class, 'index']);
Route::get(
    '/institution-users/{institution_user_id}',
    [InstitutionUserController::class, 'show']
)->whereUuid('institution_user_id');
Route::put(
    '/institution-users/{institution_user_id}',
    [InstitutionUserController::class, 'update']
)->whereUuid('institution_user_id');

Route::get('/institution-users/export-csv', [InstitutionUserController::class, 'exportCsv']);
Route::post('/institution-users/import-csv', [InstitutionUserImportController::class, 'importCsv']);
Route::post('/institution-users/validate-import-csv', [InstitutionUserImportController::class, 'validateCsv']);
Route::post('/institution-users/validate-import-csv-row', [InstitutionUserImportController::class, 'validateCsvRow']);

Route::post('/institution-users/deactivate', [InstitutionUserController::class, 'deactivate']);
