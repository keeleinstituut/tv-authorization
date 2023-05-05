<?php

use App\Http\Controllers\InstitutionUserController;
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

Route::get('/jwt-claims', [JwtClaimsController::class, 'show'])
    ->withoutMiddleware('auth:api');

Route::get(
    '/institution-users/{institutionUserId}',
    [InstitutionUserController::class, 'show']
)->whereUuid('institutionUserId');

Route::put(
    '/institution-users/{institutionUserId}',
    [InstitutionUserController::class, 'update']
)->whereUuid('institutionUserId');
