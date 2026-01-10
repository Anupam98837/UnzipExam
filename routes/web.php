<?php

use Illuminate\Support\Facades\Route;

// Login Routes 

Route::get('/', function () {
    return view('pages.auth.login');
});

// Admin Routes 

Route::get('/admin/dashboard', function () {
    return view('pages.users.admin.pages.common.dashboard');
})->name('dashboard');

Route::get('/admin/users/manage', function () {
    return view('pages.users.admin.pages.users.manageUsers');
});

Route::get('/admin/quizz/create', function () {
    return view('pages.users.admin.pages.quizz.createQuizz');
});
Route::get('/admin/quizz/manage', function () {
    return view('pages.users.admin.pages.quizz.manageQuizz');
});

Route::get('/admin/quizz/questions/manage', function () {
    return view('pages.users.admin.pages.questions.manageQuestion');
});

Route::get('/admin/quizz/results', function () {
    return view('pages.users.admin.pages.quizz.allResult');
});


// Exam Routes 

Route::get('/exam/{quiz}', function (\Illuminate\Http\Request $r, $quiz) {
    // Pass the quiz key (uuid or id) to the view
    return view('modules.exam.exam', ['quizKey' => $quiz]);
})->name('exam.take');

// Student Routes

Route::get('/student/dashboard', function () {
    return view('pages.users.student.pages.common.dashboard');
})->name('student.dashboard');

Route::get('/student/quizzes', function () {
    return view('pages.users.student.pages.quizz.myQuizz');
});

Route::get('/exam/results/{resultId}/view', function ($resultId) {
    return view('modules.quizz.viewResult', ['resultId' => $resultId]);
})->name('exam.results.view');


// Examiner Routes 

Route::get('/examiner/dashboard', function () {
    return view('pages.users.examiner.pages.common.dashboard');
})->name('dashboard');

Route::get('/examiner/users/manage', function () {
    return view('pages.users.examiner.pages.users.manageUsers');
});

Route::get('/examiner/quizz/create', function () {
    return view('pages.users.examiner.pages.quizz.createQuizz');
});
Route::get('/examiner/quizz/manage', function () {
    return view('pages.users.examiner.pages.quizz.manageQuizz');
});

Route::get('/examiner/quizz/questions/manage', function () {
    return view('pages.users.examiner.pages.questions.manageQuestion');
});
Route::get('/examiner/quizz/result/manage', function () {
    return view('pages.users.examiner.pages.result.viewAssignedStudentResult');
});