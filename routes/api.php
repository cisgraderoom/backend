<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HTTP_Response;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get("/health", function () {
    return response()->json([
        'status' => 'success',
        'message' => 'everything is fine.'
    ], HTTP_Response::HTTP_OK);
});

Route::prefix('/user')->group(function () {
    Route::get('/checklogin', [UserController::class, 'checklogin'])->name('login');
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/adduser', [UserController::class, 'addUser']);
});

Route::group(
    ['middleware' => 'auth:sanctum'],
    function () {
        Route::prefix('/user')->group(function () {
            Route::post('/upload', [UserController::class, 'uploadStudent']);
        });
    }
);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/classroom')->group(function () {
        Route::post('/new', [ClassroomController::class, 'newClass']);
        Route::post('/join', [ClassroomController::class, 'joinClass']);
        Route::get('/list', [ClassroomController::class, 'listClass']);
        Route::get('/{classcode}', [ClassroomController::class, 'classroomByClasscode']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/post')->group(function () {
        Route::post('/', [PostController::class, 'newPost']);
        Route::put('/', [PostController::class, 'updatePost']);
        Route::get('/{classcode}', [PostController::class, 'getPost']);
        Route::delete('/', [PostController::class, 'deletePost']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/comment')->group(function () {
        Route::post('/', [CommentController::class, 'newComment']);
        Route::put('/', [CommentController::class, 'updateComment']);
        Route::get('/', [CommentController::class, 'getComment']);
        Route::delete('/', [CommentController::class, 'deleteComment']);
    });
});
