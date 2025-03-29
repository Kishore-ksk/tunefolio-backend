<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\SongController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


// ✅ Auth Routes
Route::post('/api/register', function (Request $request) {
    try {
        $controller = app()->make(App\Http\Controllers\AuthController::class);
        return $controller->register($request);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTrace()
        ], 500);
    }
});
Route::post('/api/login', [AuthController::class, 'login']);

Route::get('/api/status', function (): JsonResponse {
    return response()->json([
        'status' => 'OK',
        'message' => 'API is running'
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/user', [AuthController::class, 'user']);
    Route::post('/api/logout', [AuthController::class, 'logout']);
    Route::delete('/api/auth/delete', [AuthController::class, 'deleteAccount']);


    // ✅ Album Routes
    Route::post('/api/albums', [AlbumController::class, 'store']);
    Route::get('/api/albums', [AlbumController::class, 'index']);
    Route::get('/api/albums/{id}', [AlbumController::class, 'show']);
    Route::put('/api/albums/{id}', [AlbumController::class, 'update']);
    Route::delete('/api/albums/{id}', [AlbumController::class, 'destroy']);

    // ✅ Song Routes
    Route::post('/api/songs', [SongController::class, 'store']);
    Route::get('/api/songs', [SongController::class, 'index']);
    Route::get('/api/songs/{id}', [SongController::class, 'show']);
    Route::put('/api/songs/{id}', [SongController::class, 'update']);
    Route::delete('/api/songs/{id}', [SongController::class, 'destroy']);
    // ✅ Get songs by album ID
    Route::get('/api/albums/{id}/songs', [SongController::class, 'getSongsByAlbum']);
});




Route::get('/', function () {
    return view('welcome');
});
