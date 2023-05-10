<?php

use App\Http\Controllers\API\PrivilegeController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\InstitutionSyncController;
use App\Http\Controllers\InstitutionUserSyncController;
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


Route::delete('/sync/institutions', [InstitutionSyncController::class, 'index'])->withoutMiddleware('auth:api');
Route::delete('/sync/institutions/{id}', [InstitutionSyncController::class, 'show'])->whereUuid('id')->withoutMiddleware('auth:api');
Route::delete('/sync/institution-users', [InstitutionUserSyncController::class, 'index'])->withoutMiddleware('auth:api');
Route::delete('/sync/institution-users/{id}', [InstitutionUserSyncController::class, 'show'])->whereUuid('id')->withoutMiddleware('auth:api');
