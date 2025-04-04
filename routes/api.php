<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\SongController;

// ✅ Status check
Route::get('/status', function (): JsonResponse {
  return response()->json([
    'status' => 'OK',
    'message' => 'API is running'
  ]);
});

// ✅ Debug route (optional: remove in production)
Route::get('/debug', function () {
  return response()->json([
    'env_exists' => file_exists(base_path('.env')) ? 'Yes' : 'No',
    'app_env' => env('APP_ENV'),
    'db_connection' => env('DB_CONNECTION'),
    'error_log' => file_get_contents(storage_path('logs/laravel.log'))
  ]);
});

// ✅ Public Auth routes
Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

// ✅ Protected routes
Route::middleware('auth:sanctum')->group(function () {
  Route::get('/user', [AuthController::class, 'user']);
  Route::post('/logout', [AuthController::class, 'logout']);
  Route::delete('/auth/delete', [AuthController::class, 'deleteAccount']);

  // Albums
  Route::post('/albums', [AlbumController::class, 'store']);
  Route::get('/albums', [AlbumController::class, 'index']);
  Route::get('/albums/{id}', [AlbumController::class, 'show']);
  Route::put('/albums/{id}', [AlbumController::class, 'update']);
  Route::delete('/albums/{id}', [AlbumController::class, 'destroy']);

  // Songs
  Route::post('/songs', [SongController::class, 'store']);
  Route::get('/songs', [SongController::class, 'index']);
  Route::get('/songs/{id}', [SongController::class, 'show']);
  Route::put('/songs/{id}', [SongController::class, 'update']);
  Route::delete('/songs/{id}', [SongController::class, 'destroy']);
  Route::get('/albums/{id}/songs', [SongController::class, 'getSongsByAlbum']);
});
