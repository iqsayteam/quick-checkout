<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController ;
use App\Http\Controllers\AuthController ;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('login/{token}', [CheckoutController::class, 'login_page'])->name('login_page');
Route::post('loggedinuser', [AuthController::class, 'loggedinUser'])->name('loggedinUser');
