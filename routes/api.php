<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Jobs\Submission;
use Facade\FlareClient\Http\Response;
use Facade\Ignition\Tabs\Tab;
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

Route::get("/test", [SubmissionController::class, 'test']);

Route::prefix('/user')->group(function () {
    Route::get('/checklogin', [UserController::class, 'checklogin'])->name('login');
    Route::post('/login', [UserController::class, 'login']);
});

Route::group(
    ['middleware' => 'auth:sanctum'],
    function () {
        Route::prefix('/user')->group(function () {
            Route::post('/upload', [UserController::class, 'uploadStudent']);
            Route::put('/changepassword', [UserController::class, 'changePassword']);
            Route::get('/all', [UserController::class, 'getUserAll']);
            Route::get('/{username}', [UserController::class, 'getByUserId']);
            Route::put('/{username}', [UserController::class, 'updateUser']);
            Route::post('/{username}/reset', [UserController::class, 'resetPassword']);
            Route::post('/new', [UserController::class, 'newUser']);
        });
    }
);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/classroom')->group(function () {
        Route::post('/new', [ClassroomController::class, 'newClass']);
        Route::post('/join', [ClassroomController::class, 'joinClass']);
        Route::post('/add/teacher', [ClassroomController::class, 'joinTeacherClass']);
        Route::get('/list', [ClassroomController::class, 'listClass']);
        Route::get('/{classcode}', [ClassroomController::class, 'classroomByClasscode']);
        Route::get('/list/user/{classcode}', [ClassroomController::class, 'listUserByClasscode']);
        Route::delete('/user', [ClassroomController::class, 'deleteUserInClassroom']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/task')->group(function () {
        Route::post('/new', [TaskController::class, 'newTasks']);
        Route::post('/{id}', [TaskController::class, 'editTask']);
        Route::get('/list', [TaskController::class, 'getTask']);
        Route::get('/{id}', [TaskController::class, 'getTaskById']);
        Route::get('/admin/{id}', [TaskController::class, 'getTaskById']);
        Route::put('/status/{id}', [TaskController::class, 'hiddenProblem']);
        Route::delete('/{id}', [TaskController::class, 'deleteProblem']);
        Route::get("/asset/{classcode}/{id}", [TaskController::class, 'downloadAsset']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::prefix('/submission')->group(function () {
        Route::post('/submit/{id}', [SubmissionController::class, 'submit']);
        Route::get('/score/{classcode}/{id}', [SubmissionController::class, 'scoreByProblemId']);
        Route::get('/score/{classcode}', [SubmissionController::class, 'scoreByUser']);
        Route::get('/score/classroom/{classcode}/all', [SubmissionController::class, 'scoreByClassroom']);
        Route::get('/list/{classcode}/problem/{problem}', [SubmissionController::class, 'getListSubmission']);
        Route::post('/manage/{classcode}/{mode}/{id}', [SubmissionController::class, 'NewJudgeAndPlagiarism']);
        Route::get('/manage/{classcode}/{id}', [SubmissionController::class, 'getPlagiarism']);
        Route::get('/manage/{classcode}/{id}/{owner}/{compare}', [SubmissionController::class, 'getCodePlagiarism']);
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
