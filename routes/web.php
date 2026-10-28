<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\SupabaseAuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SketchfabController;
use App\Http\Controllers\Teacher\TeacherDataController;
use Kreait\Firebase\Contract\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rebuilt to remove the duplicate-route / route-shadowing bugs from the
| old file. Route names kept identical to what the existing Blade views
| already call via route('...'), so migrating views doesn't require
| touching every route() reference at once.
|
*/

// ---------------------------------------------------------------------
// Public / marketing (v2 — single page: hero + overview + login)
// Old blades live under resources/views(old)/ for reference only.
// ---------------------------------------------------------------------
Route::get('/', fn () => view('public.home'))->name('welcome');

// Login is on the home page (#login). Keep named route for redirects.
Route::middleware('guest.firebase')->group(function () {
    Route::get('/login', function () {
        return redirect()->to(route('welcome') . '#login');
    })->name('login');
});

// ---------------------------------------------------------------------
// Auth actions (session-based, Firebase Admin SDK server-side)
// ---------------------------------------------------------------------
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/register', [LoginController::class, 'register'])->name('register.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Legacy Firebase Google callback (kept temporarily; web UI uses Supabase)
Route::post('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Supabase Auth (migration Step 4)
Route::get('/auth/supabase/callback', [SupabaseAuthController::class, 'callbackPage'])->name('auth.supabase.callback');
Route::post('/auth/supabase/session', [SupabaseAuthController::class, 'sessionFromToken'])->name('auth.supabase.session');
Route::get('/auth/set-password', [SupabaseAuthController::class, 'showSetPassword'])->name('auth.set-password');
Route::post('/auth/set-password', [SupabaseAuthController::class, 'setPassword'])->name('auth.set-password.submit');
Route::post('/auth/set-password/skip', [SupabaseAuthController::class, 'skipPasswordSetup'])->name('auth.set-password.skip');

// ---------------------------------------------------------------------
// Authenticated teacher area
// ---------------------------------------------------------------------
Route::middleware('auth.firebase')->group(function () {

    Route::get('/dashboard', fn () => view('teacher.dashboard'))->name('dashboard');

    // Step 5 — teacher data API (Supabase-backed; replaces browser Firestore)
    Route::prefix('api/teacher')->group(function () {
        Route::get('/classes', [TeacherDataController::class, 'listClasses']);
        Route::post('/classes', [TeacherDataController::class, 'storeClass']);
        Route::get('/classes/{id}', [TeacherDataController::class, 'showClass']);
        Route::post('/classes/{classId}/members/{userId}/approve', [TeacherDataController::class, 'approveMember']);
        Route::post('/classes/{classId}/members/{userId}/reject', [TeacherDataController::class, 'rejectMember']);
        Route::patch('/classes/{id}', [TeacherDataController::class, 'updateClass']);
        Route::delete('/classes/{id}', [TeacherDataController::class, 'destroyClass']);

        Route::get('/classes/{classId}/announcements', [TeacherDataController::class, 'listAnnouncements']);
        Route::post('/classes/{classId}/announcements', [TeacherDataController::class, 'storeAnnouncement']);
        Route::post('/classes/{classId}/announcement-files', [TeacherDataController::class, 'uploadAnnouncementFile']);
        Route::delete('/classes/{classId}/announcements/{id}', [TeacherDataController::class, 'destroyAnnouncement']);

        Route::get('/classes/{classId}/activities', [TeacherDataController::class, 'listActivities']);
        Route::post('/activities', [TeacherDataController::class, 'storeActivity']);
        Route::get('/activities/{id}', [TeacherDataController::class, 'showActivity']);
        Route::patch('/activities/{id}', [TeacherDataController::class, 'updateActivity']);
        Route::delete('/activities/{id}', [TeacherDataController::class, 'destroyActivity']);
        Route::get('/activities/{activityId}/submissions', [TeacherDataController::class, 'listSubmissions']);
        Route::patch('/activities/{activityId}/submissions/{submissionId}/return', [TeacherDataController::class, 'returnSubmission']);
        Route::post('/activities/{activityId}/attachments', [TeacherDataController::class, 'uploadAttachment']);
        Route::delete('/activities/{activityId}/attachments', [TeacherDataController::class, 'deleteAttachment']);

        Route::get('/notifications', [TeacherDataController::class, 'listNotifications']);
        Route::patch('/notifications/{id}/read', [TeacherDataController::class, 'markNotificationRead']);
        Route::get('/lab-templates', [TeacherDataController::class, 'listTemplates']);
        Route::get('/lab-templates/{id}', [TeacherDataController::class, 'showTemplate']);
    });


    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');

    // Every class-section route lives under one prefix/name group so
    // there is exactly one place a given URI can match — no more
    // duplicate `/classes/{id}` declarations shadowing each other.
    Route::prefix('classes')->name('classes.')->group(function () {
        Route::get('/', fn () => view('classes.dashboard'))->name('dashboard');
        Route::get('/create', [ClassController::class, 'create'])->name('create');

        // Constrained so a literal segment like "create" or "dashboard"
        // can never be swallowed by this wildcard.
        Route::get('/{id}', fn ($id) => view('teacher.classroom', ['classId' => $id]))
            ->name('classroom')
            ->where('id', '[A-Za-z0-9\-]+');

        Route::get('/{classId}/activities/{activityId}', function ($classId, $activityId) {
            return view('teacher.activity-show', [
                'classId' => $classId,
                'activityId' => $activityId,
            ]);
        })->name('classes.activity.show')
          ->where(['classId' => '[A-Za-z0-9\-]+', 'activityId' => '[A-Za-z0-9\-]+']);
        Route::get(
            '/{classId}/activities/{activityId}/submissions/{submissionId}',
            function ($classId, $activityId, $submissionId) {
                return view(
                    'dash-component.classroom-content.activity-submission',
                    compact('classId', 'activityId', 'submissionId')
                );
            }
        )->name('activities.submissions.show');
    });

    // These stayed closures in the original file too — there's no
    // EquipmentController in this codebase. If you want one later
    // (e.g. once equipment data moves out of hardcoded Alpine arrays
    // and into Firestore), that's a real refactor, not part of this
    // routing cleanup.
    Route::get('/equipment', fn () => view('dash-component.equipment-content.index'))->name('equipment.index');
});

// ---------------------------------------------------------------------
// Diagnostics (safe to remove before the defense)
// ---------------------------------------------------------------------
Route::get('/firebase-test', function (Auth $auth) {
    try {
        $auth->listUsers(1);
        return response()->json(['status' => 'connected']);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'failed', 'error' => $e->getMessage()], 500);
    }
})->name('firebase.test');