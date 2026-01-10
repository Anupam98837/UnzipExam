<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\QuizzController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\ExamController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\MediaController;
use App\Http\Controllers\API\QuizzResultController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Auth Routes 

Route::post('/auth/login',  [UserController::class, 'login']);
Route::post('/auth/logout', [UserController::class, 'logout'])
    ->middleware('checkRole');
Route::get('/auth/check',   [UserController::class, 'authenticateToken']);


// Users Routes

// Read-only (lists + show) – allow exam staff + admins
Route::middleware(['checkRole:examiner,admin,super_admin'])->group(function () {
    Route::get('/users',      [UserController::class, 'index']);   // paginated
    Route::get('/users/all',  [UserController::class, 'all']);     // lightweight list
    Route::get('/users/{id}', [UserController::class, 'show']);    // single user detail
});

// Full management – only admins / super admins
Route::middleware(['checkRole:admin,super_admin'])->group(function () {
    Route::post('/users',                        [UserController::class, 'store']);
    Route::match(['put','patch'], '/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}',                 [UserController::class, 'destroy']);
    Route::post('/users/{id}/restore',           [UserController::class, 'restore']);
    Route::delete('/users/{id}/force',           [UserController::class, 'forceDelete']);
    Route::patch('/users/{id}/password',         [UserController::class, 'updatePassword']);
    Route::post('/users/{id}/image',             [UserController::class, 'updateImage']);
    // Quiz assignments for a user
    Route::get ('/users/{id}/quizzes',          [UserController::class, 'userQuizzes']);
    Route::post('/users/{id}/quizzes/assign',   [UserController::class, 'assignQuiz']);
    Route::post('/users/{id}/quizzes/unassign', [UserController::class, 'unassignQuiz']);

    Route::post('/users/import-csv',             [UserController::class, 'importUsersCsv']);

});

// Quizz Routes 

Route::middleware('checkRole:admin,super_admin,student,examiner')
    ->prefix('quizz')->name('quizz.')
    ->group(function () {
 
    // ===== Quizzes list/create =====
    Route::get('/',   [QuizzController::class, 'index'])->name('index');
    Route::post('/',  [QuizzController::class, 'store'])->name('store');
    Route::get('/my', [QuizzController::class, 'myQuizzes'])->name('my');
    // ===== Questions (place BEFORE any /{key} routes) =====
    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/',            [QuestionController::class, 'index'])->name('index');   // GET /api/quizz/questions?quiz=...
        Route::post('/',           [QuestionController::class, 'store'])->name('store');
        Route::get('/{key}',       [QuestionController::class, 'show'])->name('show');
        Route::match(['put','patch'],'/{key}', [QuestionController::class, 'update'])->name('update');
        Route::delete('/{key}',    [QuestionController::class, 'destroy'])->name('destroy');
    });
 
    // ===== Status & lifecycle =====
    Route::patch ('/{key}/status',  [QuizzController::class, 'updateStatus'])->name('status');
    Route::patch ('/{key}/restore', [QuizzController::class, 'restore'])->name('restore');
    Route::delete('/{key}',         [QuizzController::class, 'destroy'])->name('destroy');
    Route::delete('/{key}/force',   [QuizzController::class, 'forceDelete'])->name('force');
    Route::get('/deleted', [QuizzController::class, 'deletedIndex']);

    // ===== Optional notes =====
    Route::get ('/{key}/notes',     [QuizzController::class, 'listNotes'])->name('notes.list');
    Route::post ('/{key}/notes',    [QuizzController::class, 'addNote'])->name('notes.add');
 
    // ===== Show/Update generic (MUST be last) =====
    Route::get ('/{key}',           [QuizzController::class, 'show'])->name('show');
    Route::match(['put','patch'],'/{key}', [QuizzController::class, 'update'])->name('update');
    
});


// Exam Routes 




Route::middleware(['checkRole:student,admin,examiner,super_admin'])
    ->prefix('exam')
    ->group(function () {

        // student exam flow
        Route::post('/quizzes/{quizKey}/start',   [ExamController::class, 'start']);
        Route::get ('/attempts/{attempt}/questions', [ExamController::class, 'questions']);
        Route::post('/attempts/{attempt}/answer',    [ExamController::class, 'saveAnswer']);
        Route::post('/attempts/{attempt}/submit',    [ExamController::class, 'submit']);
        Route::get ('/attempts/{attempt}/status',    [ExamController::class, 'status']);
        Route::post('/attempts/{attempt}/focus',     [ExamController::class, 'focus']);

        // student self-view
        Route::get('/quizzes/{quizKey}/my-attempts', [ExamController::class, 'myAttemptsForQuiz']);
        Route::get('/results/{resultKey}',            [ExamController::class, 'resultDetail']);
        Route::get('/results/{resultId}/export',     [ExamController::class, 'export']);

        // examiner / instructor views (used by the new UI)
        Route::get('/quizzes/{quizKey}/assigned-results', [
            ExamController::class,
            'assignedResultsForQuiz',   // make sure this method exists
        ]);
       Route::get('/results/{resultUuid}', [
            ExamController::class,
            'resultDetailForInstructor',
        ]);
    });

    Route::post('/exam/attempts/{attempt}/bulk-answer', [ExamController::class, 'bulkAnswer']);



    Route::get('/quizz/result/all', [QuizzResultController::class, 'index']);
  
    



// Admin dashboard (only admin + super_admin)
Route::middleware(['checkRole:admin,super_admin'])->group(function () {
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);
});

// Student dashboard (student can see their view, admin/super_admin can also inspect)
Route::middleware(['checkRole:student,admin,super_admin'])->group(function () {
    Route::get('/dashboard/student', [DashboardController::class, 'studentDashboard']);
});

// ✅ Examiner dashboard (examiner + admin/super_admin can see)
Route::middleware(['checkRole:examiner,admin,super_admin'])->group(function () {
    Route::get('/dashboard/examiner', [DashboardController::class, 'examinerDashboard']);
});

// All Media Routes 
Route::middleware(['checkRole:admin,super_admin,instructor,author'])->group(function () {
    Route::get('/media',  [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::delete('/media/{idOrUuid}', [MediaController::class, 'destroy']);
});

