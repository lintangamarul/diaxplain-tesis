<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NakesRatingController;
use App\Http\Controllers\ScreeningController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [DashboardController::class, 'landing'])->name('landing');
Route::get('/tentang-model', [DashboardController::class, 'aboutModel'])->name('about');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});
Route::post('/demo-login/{role}', [AuthController::class, 'quickDemoLogin'])->name('demo.login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Authenticated Routes (All Active Roles: pengguna, nakes, admin)
Route::middleware(['auth', 'role:pengguna|nakes|admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Skrining 21 Fitur & Laporan SPK
    Route::get('/skrining/baru', [ScreeningController::class, 'create'])->name('skrining.create');
    Route::post('/skrining', [ScreeningController::class, 'store'])->name('skrining.store');
    Route::get('/skrining/riwayat', [ScreeningController::class, 'index'])->name('skrining.index');
    Route::get('/skrining/bandingkan', [ScreeningController::class, 'compare'])->name('skrining.compare');
    Route::post('/skrining/hapus-semua', [ScreeningController::class, 'purgeMyData'])->name('skrining.purge');

    Route::get('/skrining/{screening}', [ScreeningController::class, 'show'])->name('skrining.show');
    Route::get('/skrining/{screening}/narasi', [ScreeningController::class, 'pollNarrative'])->name('skrining.narasi');
    Route::post('/skrining/{screening}/regenerasi-narasi', [ScreeningController::class, 'regenerateNarrative'])->name('skrining.regenerate');
    Route::get('/skrining/{screening}/pdf', [ScreeningController::class, 'downloadPdf'])->name('skrining.pdf');
    Route::delete('/skrining/{screening}', [ScreeningController::class, 'destroy'])->name('skrining.destroy');
});

// Tenaga Medis & Admin Routes (Evaluasi Narasi LLM — RM6)
Route::middleware(['auth', 'role:nakes|admin'])->prefix('nakes')->name('nakes.')->group(function () {
    Route::get('/penilaian', [NakesRatingController::class, 'index'])->name('ratings.index');
    Route::post('/penilaian/{interpretation}', [NakesRatingController::class, 'store'])->name('ratings.store');
});

// Admin Routes (Dashboard Eksperimen E0-E6, SHAP Global, Konfigurasi API Key LLM, Model, Pengguna, Audit)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/eksperimen', [AdminController::class, 'experiments'])->name('experiments');
    Route::post('/eksperimen/sinkron', [AdminController::class, 'syncExperiments'])->name('experiments.sync');

    Route::get('/llm-config', [AdminController::class, 'llmConfig'])->name('llm.config');
    Route::post('/llm-config', [AdminController::class, 'updateLlmConfig'])->name('llm.update');

    Route::get('/models', [AdminController::class, 'models'])->name('models');
    Route::post('/models/{modelVersion}/activate', [AdminController::class, 'activateModel'])->name('models.activate');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');

    Route::get('/ratings/export', [AdminController::class, 'exportRatingsCsv'])->name('ratings.export');
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit');
});
