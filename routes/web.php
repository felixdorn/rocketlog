<?php

use App\Http\Controllers\CollectionBulletController;
use App\Http\Controllers\CollectionBulletDoneController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CollectionOrderController;
use App\Http\Controllers\CollectionUserController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::middleware(['auth:sanctum'])->group(function () {
    Route::patch('user/preferences', [UserPreferenceController::class, 'update'])->name('user-preferences.update');

    Route::put('daily-log', [DailyLogController::class, 'move'])->name('daily-log.move');

    Route::resource('daily-log', DailyLogController::class)
        ->only('index', 'store', 'update', 'destroy')
        ->parameters(['daily-log' => 'bullet']);

    Route::resource('c', CollectionController::class)
        ->only('show', 'store', 'update', 'destroy')
        ->parameters(['c' => 'collection']);

    Route::delete('c/{collection}/bullets/done', [CollectionBulletDoneController::class, 'destroy'])->name('c.destroy-done');

    Route::put('c/{collection}/bullets', [CollectionBulletController::class, 'move'])->name('c.bullets.move');

    Route::resource('c.bullets', CollectionBulletController::class)
        ->only('store', 'update', 'destroy')
        ->parameters(['c' => 'collection']);

    Route::put('c/{collection}/order', [CollectionOrderController::class, 'update'])->name('c.order.update');

    Route::resource('c.users', CollectionUserController::class)
        ->only('store', 'destroy')
        ->parameters(['c' => 'collection']);
});
