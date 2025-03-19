<?php

use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\QuoteController;
use App\Http\Controllers\API\SoftdeleteController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('quotes', QuoteController::class);



    Route::get('suppression', [SoftdeleteController::class,'index'])->name('is_deleted');
    Route::get('suppression/{id}', [SoftdeleteController::class,'show']);
    Route::post('suppression/{id}', [SoftdeleteController::class,'restore']);
    Route::delete('suppression/{id}', [SoftdeleteController::class,'destroy']);

});









// Authentification routes *************************************************************************************
Route::post('register', [AuthController::class, 'register']);

Route::post('login', [AuthController::class, 'login']);

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
