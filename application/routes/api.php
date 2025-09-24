<?php

use App\Http\Controllers\API\PrivilegeController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionSyncController;
use App\Http\Controllers\InstitutionUserController;
use App\Http\Controllers\InstitutionUserImportController;
use App\Http\Controllers\InstitutionUserSyncController;
use App\Http\Controllers\InstitutionUserVacationController;
use App\Http\Controllers\InstitutionVacationController;
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

Route::withoutMiddleware(['auth:api', 'throttle:api'])->get('/jwt-claims', [JwtClaimsController::class, 'show'])->name('authorization.jwt-claims');

Route::get('/privileges', [PrivilegeController::class, 'index'])->name('authorization.privileges.index');

Route::prefix('/institutions')
    ->controller(InstitutionController::class)
    ->whereUuid('institution_id')
    ->group(function (): void {
        Route::get('/', 'index')->name('authorization.institutions.index');
        Route::get('/{institution_id}', 'show')->name('authorization.institutions.show');
        Route::put('/{institution_id}', 'update')->name('authorization.institutions.update');
        Route::get('/{institution_id}/logo', 'logo')->name('authorization.institutions.logo');
    });

Route::prefix('/roles')
    ->controller(RoleController::class)
    ->whereUuid('role_id')
    ->group(function (): void {
        Route::get('/', 'index')->name('authorization.roles.index');
        Route::post('/', 'store')->name('authorization.roles.store');
        Route::get('/{role_id}', 'show')->name('authorization.roles.show');
        Route::put('/{role_id}', 'update')->name('authorization.roles.update');
        Route::delete('/{role_id}', 'destroy')->name('authorization.roles.destroy');
    });

Route::prefix('/institution-users')
    ->controller(InstitutionUserController::class)
    ->whereUuid('institution_user_id')
    ->group(function (): void {
        Route::get('/', 'index')->name('authorization.institution_users.index');
        Route::put('/', 'updateCurrentInstitutionUser')->name('authorization.institution_users.updateCurrentInstitutionUser');
        Route::get('/{institution_user_id}', 'show')->name('authorization.institution_users.show');
        Route::put('/{institution_user_id}', 'update')->name('authorization.institution_users.update');
        Route::get('/export-csv', 'exportCsv')->name('authorization.institution_users.exportCsv');
        Route::post('/deactivate', 'deactivate')->name('authorization.institution_users.deactivate');
        Route::post('/activate', 'activate')->name('authorization.institution_users.activate');
        Route::post('/archive', 'archive')->name('authorization.institution_users.archive');
    });

Route::prefix('/institution-users')
    ->controller(InstitutionUserImportController::class)
    ->group(function (): void {
        Route::post('/import-csv', 'importCsv')->name('authorization.institution_users_import.importCsv');
        Route::post('/validate-import-csv', 'validateCsv')->name('authorization.institution_users_import.validateCsv');
        Route::post('/validate-import-csv-row', 'validateCsvRow')->name('authorization.institution_users_import.validateCsvRow');
    });

Route::prefix('/departments')
    ->controller(DepartmentController::class)
    ->whereUuid('department_id')
    ->group(function (): void {
        Route::get('/', 'index')->name('authorization.departments.index');
        Route::post('/', 'store')->name('authorization.departments.store');
        Route::put('/bulk', 'bulkUpdate')->name('authorization.departments.bulkUpdate');
        Route::get('/{department_id}', 'show')->name('authorization.departments.show');
        Route::put('/{department_id}', 'update')->name('authorization.departments.update');
        Route::delete('/{department_id}', 'destroy')->name('authorization.departments.destroy');
    });

Route::prefix('/institution-vacations')
    ->controller(InstitutionVacationController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('authorization.institution_vacations.index');
        Route::post('/sync', 'sync')->name('authorization.institution_vacations.sync');
    });

Route::prefix('/institution-user-vacations')
    ->controller(InstitutionUserVacationController::class)
    ->whereUuid('institution_user_id')
    ->group(function (): void {
        Route::get('/{institution_user_id}', 'index')->name('authorization.institution_user_vacations.index');
        Route::post('/sync', 'sync')->name('authorization.institution_user_vacations.sync');
    });

Route::withoutMiddleware(['auth:api', 'throttle:api'])->group(function () {
    Route::get('/sync/institutions', [InstitutionSyncController::class, 'index'])->name('authorization.sync.institutions.index');
    Route::get('/sync/institutions/{id}', [InstitutionSyncController::class, 'show'])->whereUuid('id')->name('authorization.sync.institutions.show');
    Route::get('/sync/institution-users', [InstitutionUserSyncController::class, 'index'])->name('authorization.sync.institution_users.index');
    Route::get('/sync/institution-users/{id}', [InstitutionUserSyncController::class, 'show'])->whereUuid('id')->name('authorization.sync.institution_users.show');
});
