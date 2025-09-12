<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/plant', [\App\Http\Controllers\PlantController::class, 'index']);
Route::post('/plant', [\App\Http\Controllers\PlantController::class, 'store']);
Route::get('/plant/{name}', [\App\Http\Controllers\PlantController::class, 'show']);
Route::delete('/plant/{id}', [\App\Http\Controllers\PlantController::class, 'destroy']);

// UserPlant routes

Route::post('/user/plant', [\App\Http\Controllers\UserPlantController::class, 'store'])->middleware('auth:sanctum');
Route::get('/user/plants', [\App\Http\Controllers\UserPlantController::class, 'index'])->middleware('auth:sanctum');
Route::delete('/user/plant/{id}', [\App\Http\Controllers\UserPlantController::class, 'destroy'])->middleware('auth:sanctum');
