<?php

use App\Http\Controllers\API\PrivilegeController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionUserController;
use App\Http\Controllers\InstitutionUserImportController;
use App\Http\Controllers\JwtClaimsController;
use App\Http\RouteConstants;
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

Route::prefix(RouteConstants::ROLES_INDEX)
    ->controller(RoleController::class)
    ->whereUuid(RouteConstants::ROLE_ID)
    ->group(function (): void {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get(RouteConstants::ROLE_SUBPATH, 'show');
        Route::put(RouteConstants::ROLE_SUBPATH, 'update');
        Route::delete(RouteConstants::ROLE_SUBPATH, 'destroy');
    });

Route::prefix(RouteConstants::INSTITUTION_USERS_INDEX)
    ->controller(InstitutionUserController::class)
    ->whereUuid(RouteConstants::INSTITUTION_USER_ID)
    ->group(function (): void {
        Route::get('/', 'index');
        Route::get(RouteConstants::INSTITUTION_USER_SUBPATH, 'show');
        Route::put(RouteConstants::INSTITUTION_USER_SUBPATH, 'update');
        Route::get('/export-csv', 'exportCsv');
    });

Route::prefix(RouteConstants::INSTITUTION_USERS_INDEX)
    ->controller(InstitutionUserImportController::class)
    ->group(function (): void {
        Route::post('/import-csv', 'importCsv');
        Route::post('/validate-import-csv', 'validateCsv');
        Route::post('/validate-import-csv-row', 'validateCsvRow');
    });
