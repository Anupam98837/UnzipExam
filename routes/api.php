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
use App\Http\Controllers\API\PagePrivilegeController;
use App\Http\Controllers\API\DashboardMenuController;
use App\Http\Controllers\API\UserPrivilegeController;
use App\Http\Controllers\API\BubbleGameController;
use App\Http\Controllers\API\BubbleGameQuestionController;
use App\Http\Controllers\API\BubbleGameResultController;
use App\Http\Controllers\API\DoorGameController;
use App\Http\Controllers\API\DoorGameResultController;
use App\Http\Controllers\API\UserFolderController;
use App\Http\Controllers\API\StudentResultController;
use App\Http\Controllers\API\InterviewRegistrationCampaignController;
use App\Http\Controllers\API\MasterResultController;
use App\Http\Controllers\API\PathGameController;
use App\Http\Controllers\API\PathGameResultController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Auth Routes 

Route::post('/auth/login',  [UserController::class, 'login']);
Route::post('/auth/student-register', [UserController::class, 'studentRegister']);
Route::post('/auth/logout', [UserController::class, 'logout'])
    ->middleware('checkRole');
Route::get('/auth/check',   [UserController::class, 'authenticateToken']);
Route::get('/auth/me-role', [UserController::class, 'getMyRole']);
Route::get('/profile', [UserController::class, 'getProfile']);


// Users Routes

// Read-only (lists + show) – allow exam staff + admins
Route::middleware(['checkRole:examiner,admin,super_admin'])->group(function () {
    Route::get('/users',      [UserController::class, 'index']);   // paginated
    Route::get('/users/all',  [UserController::class, 'all']);     // lightweight list
    Route::get('/users/{id}', [UserController::class, 'show']);    // single user detail
});

// Full management – only admins / super admins
Route::middleware(['checkRole:admin,super_admin,student,examiner'])->group(function () {
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
    Route::post('/users/{uuid}/cv',              [UserController::class, 'uploadCvByUuid']);
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
            Route::get('/quizzes/{quizKey}',   [QuizzController::class, 'show']);

        // student self-view
        Route::get('/quizzes/{quizKey}/my-attempts', [ExamController::class, 'myAttemptsForQuiz']);
        Route::get('/results/{resultKey}',            [ExamController::class, 'resultDetail']);
        Route::get('/results/{resultId}/export',     [ExamController::class, 'export']);
            // ✅ Single publish/unpublish
    Route::patch('/result/{resultId}/publish', [QuizzResultController::class, 'publishToStudent']);

    // ✅ Bulk publish/unpublish
    Route::post('/result/publish/bulk', [QuizzResultController::class, 'bulkPublishToStudent']);

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


/*
|--------------------------------------------------------------------------
| Modules / Pages / User-Privileges
|--------------------------------------------------------------------------
*/

Route::middleware('checkRole:admin,super_admin,director,principal,hod')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Modules (prefix: modules)
        |--------------------------------------------------------------------------
        */
        Route::prefix('dashboard-menus')->group(function () {
            // Collection
            Route::get('/',          [DashboardMenuController::class, 'index'])->name('modules.index');
                    Route::get('/tree',    [DashboardMenuController::class, 'tree']);

            Route::get('/archived',  [DashboardMenuController::class, 'archived'])->name('modules.archived');
            Route::get('/bin',       [DashboardMenuController::class, 'bin'])->name('modules.bin');
            Route::post('/',         [DashboardMenuController::class, 'store'])->name('modules.store');

            // Extra collection: all-with-privileges
            Route::get('/all-with-privileges', [DashboardMenuController::class, 'allWithPrivileges'])
                ->name('modules.allWithPrivileges');

            // Module actions (specific)
            Route::post('{id}/restore',   [DashboardMenuController::class, 'restore'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.restore');

            Route::post('{id}/archive',   [DashboardMenuController::class, 'archive'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.archive');

            Route::post('{id}/unarchive', [DashboardMenuController::class, 'unarchive'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.unarchive');

            Route::delete('{id}/force',   [DashboardMenuController::class, 'forceDelete'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.forceDelete');

            // Reorder modules
            Route::post('/reorder', [DashboardMenuController::class, 'reorder'])
                ->name('modules.reorder');

            // Single-resource module routes
            Route::get('{id}', [DashboardMenuController::class, 'show'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.show');

            Route::match(['put', 'patch'], '{id}', [DashboardMenuController::class, 'update'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.update');

            Route::delete('{id}', [DashboardMenuController::class, 'destroy'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.destroy');

            // Module-specific privileges (same URL as before: modules/{id}/privileges)
            Route::get('{id}/privileges', [PagePrivilegeController::class, 'forModule'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('modules.privileges');
        });


        /*
        |--------------------------------------------------------------------------
        | Privileges (prefix: privileges)
        |--------------------------------------------------------------------------
        */
        Route::prefix('privileges')->group(function () {
            // Collection
            Route::get('/',          [PagePrivilegeController::class, 'index'])->name('privileges.index');
            Route::get('/index-of-api', [PagePrivilegeController::class, 'indexOfApi']);

            Route::get('/archived',  [PagePrivilegeController::class, 'archived'])->name('privileges.archived');
            Route::get('/bin',       [PagePrivilegeController::class, 'bin'])->name('privileges.bin');

            Route::post('/',         [PagePrivilegeController::class, 'store'])->name('privileges.store');

            // Bulk update
            Route::post('/bulk-update', [PagePrivilegeController::class, 'bulkUpdate'])
                ->name('privileges.bulkUpdate');

            // Reorder privileges
            Route::post('/reorder', [PagePrivilegeController::class, 'reorder'])
                ->name('privileges.reorder'); // expects { ids: [...] }

            // Actions on a specific privilege
            Route::delete('{id}/force', [PagePrivilegeController::class, 'forceDelete'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('privileges.forceDelete');

            Route::post('{id}/restore', [PagePrivilegeController::class, 'restore'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('privileges.restore');

            Route::post('{id}/archive', [PagePrivilegeController::class, 'archive'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('privileges.archive');

            Route::post('{id}/unarchive', [PagePrivilegeController::class, 'unarchive'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('privileges.unarchive');

            // Single privilege show/update/destroy
            Route::get('{id}', [PagePrivilegeController::class, 'show'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('privileges.show');

            Route::match(['put', 'patch'], '{id}', [PagePrivilegeController::class, 'update'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('privileges.update');

            Route::delete('{id}', [PagePrivilegeController::class, 'destroy'])
                ->where('id', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('privileges.destroy');
        });


        /*
        |--------------------------------------------------------------------------
        | User-Privileges (prefix: user-privileges)
        |--------------------------------------------------------------------------
        */
        Route::prefix('user-privileges')->group(function () {
            // Mapping operations
            Route::post('/sync',     [UserPrivilegeController::class, 'sync'])
                ->name('user-privileges.sync');

            Route::post('/assign',   [UserPrivilegeController::class, 'assign'])
                ->name('user-privileges.assign');

            Route::post('/unassign', [UserPrivilegeController::class, 'unassign'])
                ->name('user-privileges.unassign');

            Route::post('/delete',   [UserPrivilegeController::class, 'destroy'])
                ->name('user-privileges.destroy'); // revoke mapping (soft-delete)

            Route::get('/list',      [UserPrivilegeController::class, 'list'])
                ->name('user-privileges.list');
        });

        /*
        |--------------------------------------------------------------------------
        | User lookup related to privileges (same URLs as before)
        |--------------------------------------------------------------------------
        */
        Route::prefix('user')->group(function () {
            Route::get('{idOrUuid}', [UserPrivilegeController::class, 'show'])
                ->where('idOrUuid', '[0-9]+|[0-9a-fA-F\-]{36}')
                ->name('user.show');

            Route::get('by-uuid/{uuid}', [UserPrivilegeController::class, 'byUuid'])
                ->where('uuid', '[0-9a-fA-F\-]{36}')
                ->name('user.byUuid');
        });
    });

Route::middleware('checkRole')->group(function () {
  
    Route::get('/my/sidebar-menus', [\App\Http\Controllers\API\UserPrivilegeController::class, 'mySidebarMenus']);
    
});
// Bubble Game CRUD Routes
Route::middleware('checkRole')->prefix('bubble-games')->group(function () {
    
    // List all games
    Route::get('/', [BubbleGameController::class, 'index'])->name('bubble-games.index');
    
    // Create new game
    Route::post('/', [BubbleGameController::class, 'store'])->name('bubble-games.store');
     Route::get('/my', [BubbleGameController::class, 'myBubbleGames'])
        ->name('bubble-games.my');
    // Show specific game
    Route::get('/{uuid}', [BubbleGameController::class, 'show'])->name('bubble-games.show');
    
    // Update game
    Route::put('/{uuid}', [BubbleGameController::class, 'update'])->name('bubble-games.update');
    Route::patch('/{uuid}', [BubbleGameController::class, 'update'])->name('bubble-games.patch');
    
    // Delete game (soft delete)
    Route::delete('/{uuid}', [BubbleGameController::class, 'destroy'])->name('bubble-games.destroy');
    
    // Restore soft-deleted game
    Route::post('/{uuid}/restore', [BubbleGameController::class, 'restore'])->name('bubble-games.restore');
    
    // Permanently delete game
    Route::delete('/{uuid}/force', [BubbleGameController::class, 'forceDelete'])->name('bubble-games.force-delete');
    
    // Duplicate game
    Route::post('/{uuid}/duplicate', [BubbleGameController::class, 'duplicate'])->name('bubble-games.duplicate');

    /*
    |--------------------------------------------------------------------------
    | Bubble Game Questions Routes (Nested)
    |--------------------------------------------------------------------------
    */
    
    // List all questions for a game
    Route::get('/{gameUuid}/questions', [BubbleGameQuestionController::class, 'index'])
        ->name('bubble-games.questions.index');
    
    // Create new question for a game
    Route::post('/{gameUuid}/questions', [BubbleGameQuestionController::class, 'store'])
        ->name('bubble-games.questions.store');
    
    // Bulk create questions
    Route::post('/{gameUuid}/questions/bulk', [BubbleGameQuestionController::class, 'bulkStore'])
        ->name('bubble-games.questions.bulk-store');
    
    // Reorder questions
    Route::post('/{gameUuid}/questions/reorder', [BubbleGameQuestionController::class, 'reorder'])
        ->name('bubble-games.questions.reorder');
    
    // Show specific question
    Route::get('/{gameUuid}/questions/{questionUuid}', [BubbleGameQuestionController::class, 'show'])
        ->name('bubble-games.questions.show');
    
    // Update question
    Route::put('/{gameUuid}/questions/{questionUuid}', [BubbleGameQuestionController::class, 'update'])
        ->name('bubble-games.questions.update');
    Route::patch('/{gameUuid}/questions/{questionUuid}', [BubbleGameQuestionController::class, 'update'])
        ->name('bubble-games.questions.patch');
    
    // Delete question
    Route::delete('/{gameUuid}/questions/{questionUuid}', [BubbleGameQuestionController::class, 'destroy'])
        ->name('bubble-games.questions.destroy');
    
    // Duplicate question
    Route::post('/{gameUuid}/questions/{questionUuid}/duplicate', [BubbleGameQuestionController::class, 'duplicate'])
        ->name('bubble-games.questions.duplicate');
      
});
   /*
    |--------------------------------------------------------------------------
    | Bubble Game Results Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('checkRole')->prefix('bubble-games-results')->group(function () {
        Route::get('/', [BubbleGameResultController::class, 'index']);
        Route::post('/', [BubbleGameResultController::class, 'store']);
            Route::post('/submit/{gameUuid}', [BubbleGameResultController::class, 'submit'])->name('bubble-game-results.submit');

        Route::get('/{uuid}', [BubbleGameResultController::class, 'show']);
        Route::put('/{uuid}', [BubbleGameResultController::class, 'update']);
        Route::patch('/{uuid}', [BubbleGameResultController::class, 'update']);
        Route::delete('/{uuid}', [BubbleGameResultController::class, 'destroy']);
        Route::post('/{uuid}/restore', [BubbleGameResultController::class, 'restore']);
        Route::delete('/{uuid}/force', [BubbleGameResultController::class, 'forceDelete']);
    });

    Route::middleware('checkRole')->group(function () {
 // List bubble games for a user (assigned/unassigned info)
    Route::get('users/{id}/bubble-games', [UserController::class, 'userBubbleGames'])
        ->name('users.bubble-games.index');
    // Assign bubble game to user
    Route::post('/users/{id}/bubble-games/assign',   [UserController::class, 'assignBubbleGame'])
        ->name('users.bubble-games.assign');

    // Unassign (revoke) bubble game from user
    Route::post('/users/{id}/bubble-games/unassign', [UserController::class, 'unassignBubbleGame'])
        ->name('users.bubble-games.unassign');

});
Route::middleware('checkRole')->prefix('bubble-game-results')->group(function () {
        Route::get('/all', [BubbleGameResultController::class, 'index']);

    Route::get('/detail/{resultKey}', [BubbleGameResultController::class, 'resultDetail']);
    Route::get('/instructor/{resultId}', [BubbleGameResultController::class, 'resultDetailForInstructor']);
    Route::get('/assigned/{gameKey}', [BubbleGameResultController::class, 'assignedResultsForGame']);
    Route::get('/export/{resultId}', [BubbleGameResultController::class, 'export']);
    // ✅ publish/unpublish
Route::match(['PATCH','POST','PUT'], '/{resultId}/publish', [BubbleGameResultController::class, 'publishResultToStudent']);
      Route::post('/bulk-publish',       [\App\Http\Controllers\API\BubbleGameResultController::class, 'bulkSetPublishToStudent']);
});

// Public endpoints (no authentication required)
Route::prefix('door-games')->group(function () {
    // Get list of active games
    Route::get('/active', [DoorGameController::class, 'activeGames']);
    
    // Get game for playing
    Route::get('/play/{id}', [DoorGameController::class, 'playGame']);
});

// Protected endpoints (authentication required)
Route::middleware('checkRole')->prefix('door-games')->group(function () {
    // Standard CRUD operations
    Route::get('/', [DoorGameController::class, 'index']);
    Route::post('/', [DoorGameController::class, 'store']);
    Route::get('/my', [DoorGameController::class, 'myDoorGames']);
    Route::get('/{id}', [DoorGameController::class, 'show']);
    Route::put('/{id}', [DoorGameController::class, 'update']);
    Route::patch('/{id}', [DoorGameController::class, 'update']);
    Route::delete('/{id}', [DoorGameController::class, 'destroy']);
});

    Route::middleware('checkRole')->group(function () {
    Route::get('/users/{id}/door-games', [UserController::class, 'userDoorGames']);
Route::post('/users/{id}/door-games/assign', [UserController::class, 'assignDoorGame']);
Route::post('/users/{id}/door-games/unassign', [UserController::class, 'unassignDoorGame']);
Route::get('/users/{id}/path-games', [UserController::class, 'userPathGames']);
Route::post('/users/{id}/path-games/assign', [UserController::class, 'assignPathGame']);
Route::post('/users/{id}/path-games/unassign', [UserController::class, 'unassignPathGame']);


    });

// Public endpoints
Route::prefix('door-game-results')->group(function () {
    // Get leaderboard for a specific game
    Route::get('/leaderboard/{gameId}', [DoorGameResultController::class, 'leaderboard']);
    
    // Get user's results for a specific game
    Route::get('/user/{gameId}/{userId}', [DoorGameResultController::class, 'userResults']);
});

// Protected endpoints (authentication required)
Route::middleware('checkRole')->prefix('door-game-results')->group(function () {
    // Standard CRUD operations
    Route::get('/all', [DoorGameResultController::class, 'index']);
    Route::post('/', [DoorGameResultController::class, 'store']);
    Route::get('/{id}', [DoorGameResultController::class, 'show']);
    Route::put('/{id}', [DoorGameResultController::class, 'update']);
    Route::patch('/{id}', [DoorGameResultController::class, 'update']);
    Route::delete('/{id}', [DoorGameResultController::class, 'destroy']);
    // ✅ BULK must come BEFORE {resultKey}
    Route::patch('/bulk/publish-any', [\App\Http\Controllers\API\DoorGameResultController::class, 'bulkPublishAny']);

        // ✅ single publish/unpublish
    Route::patch('/{resultKey}/publish-to-student',   [\App\Http\Controllers\API\DoorGameResultController::class, 'publishResultToStudent']);
 Route::patch('/{resultKey}/unpublish-to-student', [\App\Http\Controllers\API\DoorGameResultController::class, 'unpublishResultToStudent']);
});
Route::prefix('user-folders')->group(function () {
    Route::get('/', [UserFolderController::class, 'index']);
    Route::post('/', [UserFolderController::class, 'store']);
    Route::get('/{id}', [UserFolderController::class, 'show']);
    Route::put('/{id}', [UserFolderController::class, 'update']);
    Route::delete('/{id}', [UserFolderController::class, 'destroy']);

    // optional (assign users)
    Route::post('/{id}/assign-users', [UserFolderController::class, 'assignUsers']);
});

Route::middleware('checkRole')->group(function () {
Route::post('/door-games-results/submit/{gameUuid}', [DoorGameResultController::class, 'submit']);
});
Route::middleware('checkRole')->group(function () {
Route::get('/door-game-results/detail/{resultKey}', [DoorGameResultController::class, 'resultDetail']);
Route::get('/door-game-results/instructor/{resultKey}', [DoorGameResultController::class, 'resultDetailForInstructor']);
Route::get('/door-game-results/assigned/{gameKey}', [DoorGameResultController::class, 'assignedResultsForGame']);
Route::get('/door-game-results/export/{resultKey}', [DoorGameResultController::class, 'export']);
});

Route::prefix('student-results')
    ->middleware('checkRole:student,admin,super_admin,director')
    ->group(function () {
        Route::get('/', [StudentResultController::class, 'index']);
         Route::get('/my', [StudentResultController::class, 'myPublished']);
        Route::get('/{uuid}', [StudentResultController::class, 'show']);

        Route::post('/', [StudentResultController::class, 'store']);
    });



    Route::prefix('interview-registration-campaigns')->group(function () {

        // ✅ Public: register page fetch
        Route::get('/public/{uid}', [InterviewRegistrationCampaignController::class, 'publicShow']);
    
        // Admin APIs
        Route::get('/',        [InterviewRegistrationCampaignController::class, 'index']);
        Route::get('/{id}',    [InterviewRegistrationCampaignController::class, 'show']);
        Route::post('/',       [InterviewRegistrationCampaignController::class, 'store']);
        Route::put('/{id}',    [InterviewRegistrationCampaignController::class, 'update']);
        Route::delete('/{id}', [InterviewRegistrationCampaignController::class, 'destroy']);
    });


Route::middleware('checkRole')->group(function () {
    Route::get('/reports/master-results', [MasterResultController::class, 'index']);
    Route::get('/reports/master-results/{student_uuid}', [MasterResultController::class, 'showStudent']);
});
Route::post('/users/import-csv',             [UserController::class, 'importUsersCsv']);


Route::middleware('checkRole')->group(function () {
Route::get('/door-game/result/export', [DoorGameResultController::class, 'export']);
Route::get('/bubble-game/result/export', [BubbleGameResultController::class, 'exportResults']);

});

Route::middleware('checkRole')->prefix('path-games')->group(function () {
    Route::get('/', [PathGameController::class, 'index']);
    Route::post('/', [PathGameController::class, 'store']);
    Route::get('/my', [PathGameController::class, 'myPathGames']);

    Route::get('/{idOrUuid}', [PathGameController::class, 'show']);
    Route::put('/{idOrUuid}', [PathGameController::class, 'update']);
    Route::delete('/{idOrUuid}', [PathGameController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Path Game Results Routes
|--------------------------------------------------------------------------
*/
Route::middleware('checkRole')->prefix('path-game-results')->group(function () {
    Route::get('/', [PathGameResultController::class, 'index']);

    /* ===========================
     | Helpers / Options
     =========================== */
    Route::get('/folder-options', [PathGameResultController::class, 'folderOptions']);

    /* ===========================
     | Export
     =========================== */
    Route::get('/export', [PathGameResultController::class, 'export']);

    /* ===========================
     | Game Actions
     | (submit / my-results / assigned-results)
     =========================== */
    Route::post('/{gameKey}/submit', [PathGameResultController::class, 'submit']);
    Route::get('/{gameKey}/my-results', [PathGameResultController::class, 'myResults']);
    Route::get('/game/{gameKey}/assigned-results', [PathGameResultController::class, 'assignedResultsForGame']);

    /* ===========================
     | Result Detail
     =========================== */
    Route::get('/detail/{resultKey}', [PathGameResultController::class, 'resultDetail']);
    Route::get('/result/instructor/{resultKey}', [PathGameResultController::class, 'resultDetailForInstructor']);

    /* ===========================
     | Publish / Unpublish / Bulk
     =========================== */
    Route::patch('/{resultKey}/publish-to-student', [PathGameResultController::class, 'publishResultToStudent']);
    Route::patch('/{resultKey}/unpublish', [PathGameResultController::class, 'unpublishResultToStudent']);
    Route::post('/result/bulk-publish', [PathGameResultController::class, 'bulkPublishAny']);

    /* ===========================
     | Results CRUD
     =========================== */
    Route::post('/', [PathGameResultController::class, 'store']);
    Route::get('/{idOrUuid}', [PathGameResultController::class, 'show']);
});