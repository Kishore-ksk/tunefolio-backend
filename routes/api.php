<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\UserController;

// ✅ Status check
Route::get('/status', function (): JsonResponse {
  return response()->json([
    'status' => 'OK',
    'message' => 'API is running'
  ]);
});

Route::get('/debug', function () {
  return response()->json([
    'app_env' => env('APP_ENV'),
    'app_key_set' => env('APP_KEY') ? 'Yes' : 'No',
    'db_host' => env('DB_HOST'),
    'db_username' => env('DB_USERNAME'),
    'db_database' => env('DB_DATABASE'),
    'debug_mode' => (bool) env('APP_DEBUG'),
    'sanctum_domains' => env('SANCTUM_STATEFUL_DOMAINS'),
    'storage_linked' => file_exists(public_path('storage')) ? 'Yes' : 'No',
    'can_connect_db' => function_exists('mysqli_connect')
      ? (mysqli_connect(env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE')) ? 'Yes' : 'No')
      : 'mysqli not available',
    'recent_errors' => file_exists(storage_path('logs/laravel.log'))
      ? collect(explode("\n", file_get_contents(storage_path('logs/laravel.log'))))
        ->filter(fn($line) => str_contains($line, 'ERROR'))
        ->take(-5)
        ->values()
      : 'Log file not found',
  ]);
});
Route::get('/test-mysql', function () {
  try {
    \DB::statement('SELECT 1');
    return '✅ Laravel connected to MySQL';
  } catch (\Exception $e) {
    return '❌ ' . $e->getMessage();
  }
});


// ✅ Public Auth routes
Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);
Route::get('/users', [UserController::class, 'index']);

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
