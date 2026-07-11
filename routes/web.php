<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\SubmissionController;
use App\Http\Middleware\SecurePublicFormSubmission;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::view('/terms', 'terms')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:3,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Google OAuth
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/intelligence', [DashboardController::class, 'getIntelligenceData'])->name('dashboard.intelligence');

    Route::get('/profile', fn () => redirect('/dashboard#profile'))->name('profile');

    // Forms (authenticated)
    Route::get('/forms/create', [FormController::class, 'create'])->name('forms.create');
    Route::get('/forms/{formId}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::patch('/forms/{form}/activate', [FormController::class, 'activate'])->name('forms.activate');
    Route::patch('/forms/{form}/deactivate', [FormController::class, 'deactivate'])->name('forms.deactivate');
    Route::delete('/forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');

    // Submissions (authenticated)
    Route::get('/submissions/export', [SubmissionController::class, 'export'])->name('submissions.export');
    Route::get('/submissions/{submission}/files/{fieldId}', [SubmissionController::class, 'downloadFile'])
        ->name('submissions.files.download');
    Route::get('/submissions/{submission}/thumbnail/{fieldId}', [SubmissionController::class, 'showThumbnail'])
        ->name('submissions.files.thumbnail');
    Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
});

Route::get('/submissions/{submission}/files/{fieldId}/download', [SubmissionController::class, 'downloadFileAttachment'])
    ->middleware('throttle:30,1')
    ->name('submissions.files.download-attachment');


// Public form pages
Route::get('/f/{slug}', [FormController::class, 'show'])->name('forms.show');
Route::get('/f/{slug}/manifest.json', [FormController::class, 'manifest'])->name('forms.manifest');
Route::post('/f/{slug}/sync', [FormController::class, 'offlineSync'])
    ->middleware(['throttle:60,1'])
    ->name('forms.offline-sync');
Route::post('/f/{slug}/page/{page}', [FormController::class, 'savePage'])
    ->middleware(['throttle:30,1', SecurePublicFormSubmission::class])
    ->name('forms.save-page');
Route::post('/f/{slug}', [FormController::class, 'submit'])
    ->middleware(['throttle:10,1', SecurePublicFormSubmission::class])
    ->name('forms.submit');

