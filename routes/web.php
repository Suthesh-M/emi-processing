<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard (requires authenticated + email-verified users)
Route::get('/dashboard', function () {
    return redirect()->route('admin.index');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
|
| Routes that require an authenticated user. Profile and Admin routes are
| grouped under the same middleware to avoid repeating middleware declarations.
|
*/
Route::middleware('auth')->group(function () {
    // GET  /admin         -> shows loan list + current emi_details
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    // POST /admin/process -> triggers EmiProcessingService->process()
    Route::post('/admin/process', [AdminController::class, 'processData'])->name('admin.process');
});

require __DIR__.'/auth.php';
