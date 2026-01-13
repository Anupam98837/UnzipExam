<?php

use Illuminate\Support\Facades\Route;

// Login Routes 

Route::get('/', function () {
    return view('pages.auth.login');
});

// Admin Routes 

Route::get('/dashboard', function () {
    return view('pages.users.pages.common.dashboard');
})->name('dashboard');

Route::get('/users/manage', function () {
    return view('pages.users.pages.users.manageUsers');
});

Route::get('/quizz/create', function () {
    return view('pages.users.pages.quizz.createQuizz');
});
Route::get('/quizz/manage', function () {
    return view('pages.users.pages.quizz.manageQuizz');
});

Route::get('/quizz/questions/manage', function () {
    return view('pages.users.pages.questions.manageQuestion');
});

Route::get('/quizz/results', function () {
    return view('pages.users.pages.quizz.allResult');
});


// Exam Routes 

Route::get('/exam/{quiz}', function (\Illuminate\Http\Request $r, $quiz) {
    // Pass the quiz key (uuid or id) to the view
    return view('modules.exam.exam', ['quizKey' => $quiz]);
})->name('exam.take');

// Student Routes

// Route::get('student/dashboard', function () {
//     return view('modules.common.studentDashboard');
// })->name('student.dashboard');

Route::get('/quizzes', function () {
    return view('pages.users.pages.quizz.myQuizz');
});

Route::get('/exam/results/{resultId}/view', function ($resultId) {
    return view('modules.quizz.viewResult', ['resultId' => $resultId]);
})->name('exam.results.view');


// Examiner Routes 

// Route::get('examiner/dashboard', function () {
//     return view('modules.common.examinerDashboard');
// })->name('dashboard');

// Route::get('/examiner/users/manage', function () {
//     return view('pages.users.pages.users.manageUsers');
// });

// Route::get('/examiner/quizz/create', function () {
//     return view('pages.users.pages.quizz.createQuizz');
// });
// Route::get('/examiner/quizz/manage', function () {
//     return view('pages.users.pages.quizz.manageQuizz');
// });

// Route::get('/examiner/quizz/questions/manage', function () {
//     return view('pages.users.pages.questions.manageQuestion');
// });
Route::get('/quizz/result/manage', function () {
    return view('pages.users.pages.result.viewAssignedStudentResult');
});

//Dashboard menus & privileges
Route::get('/dashboard-menu/manage', fn () => view('modules.dashboardMenu.manageDashboardMenu'));
Route::get('/dashboard-menu/create', fn () => view('modules.dashboardMenu.createDashboardMenu'));

Route::get('/page-privilege/manage', fn () => view('modules.privileges.managePagePrivileges'));
Route::get('/page-privilege/create', fn () => view('modules.privileges.createPagePrivileges'));

Route::get('/user-privileges/manage', function () {
    $userUuid = request('user_uuid');
    $userId   = request('user_id');

    return view('modules.privileges.assignPrivileges', [
        'userUuid' => $userUuid,
        'userId'   => $userId,
    ]);
})->name('modules.privileges.assign.user');


Route::get('/bubble-games/questions/manage', function () {
    $gameUuid = request()->query('game');
    
    if (!$gameUuid) {
        // Redirect or show error
        return view('modules.bubbleGame.manageBubbleGameQuestions', [
            'gameUuid' => null,
            'error' => 'Please select a bubble game first'
        ]);
    }
    
    return view('modules.bubbleGame.manageBubbleGameQuestions', [
        'gameUuid' => $gameUuid
    ]);
})->name('bubblegame.manage');//profile

Route::get('/bubble-games/manage', fn () => view('modules.bubbleGame.manageBubbleGame'));


Route::get('/profile', fn () => view('pages.users.pages.common.profile'))->name('profile');
Route::get('/bubble-games/create', fn () => view('modules.bubbleGame.createBubbleGame'));

Route::get('/bubble-games/play', function () {
    return view('modules.bubbleGame.playBubbleGame');
})->name('bubble-games.play');
