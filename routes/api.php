<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/user')->group(function () {
    Route::get('/checklogin', [UserController::class, 'checklogin'])->name('login');
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/adduser', [UserController::class, 'addUser']);
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/classroom')->group(function () {
        Route::post('/newclass', [ClassroomController::class, 'newClass']);
        Route::post('/joinclass', [ClassroomController::class, 'joinClass']);
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
