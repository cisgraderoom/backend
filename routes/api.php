<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TaskController;
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
            Route::put('/changepassword', [UserController::class, 'changePassword']);
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
    Route::prefix('/task')->group(function () {
        Route::post('/new', [TaskController::class, 'newTask']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/post')->group(function () {
        Route::post('/', [PostController::class, 'newPost']);
        Route::get('/{classcode}/{id}', [PostController::class, 'getPostById']);
        Route::put('/{classcode}/{id}', [PostController::class, 'updatePost']);
        Route::get('/{classcode}', [PostController::class, 'getPost']);
        Route::delete('/{classcode}/{id}', [PostController::class, 'deletePost']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/comment')->group(function () {
        Route::post('/{classcode}/{id}', [CommentController::class, 'newComment']);
        Route::get('/{classcode}/{id}', [CommentController::class, 'getComment']);
        Route::delete('/{classcode}/{postId}/{id}', [CommentController::class, 'deleteComment']);
    });
});
