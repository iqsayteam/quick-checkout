<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController ;
use App\Http\Controllers\AuthController ;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('getuniquelink', [CheckoutController::class, 'createUniqueLink'])->name('getuniquelink');
Route::get('getuniquelink/{user_id}', [CheckoutController::class, 'createUniqueLink'])->name('getuniquelink1');
Route::get('getuniquelink/{user_id}/{services}', [CheckoutController::class, 'createUniqueLink'])->name('getuniquelink1');