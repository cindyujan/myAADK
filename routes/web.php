<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PentadbirController;
use Illuminate\Support\Facades\Route;

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/', function () {
    return view('welcome');
});

// DASHBOARD
Route::get('/dashboard',[HomeController::class, 'index'])->middleware('auth')->name('dashboard');

// KEMASKINI PROFIL AKAUN PENGGUNA
Route::middleware('auth')->group(function () {
    Route::get('/kemaskini/kata-laluan', [ProfileController::class, 'updatePassword'])->name('update.password');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// PENTADBIR - DAFTAR PENGGUNA
Route::get('/senarai-pengguna',[PentadbirController::class, 'senaraiPengguna'])->middleware('auth')->name('senarai-pengguna');
Route::post('kemaskini-pengguna', [PentadbirController::class, 'kemaskiniPengguna'])->name('kemaskini-pengguna');
Route::post('daftar-pengguna', [PentadbirController::class, 'daftarPengguna'])->name('daftar-pengguna');


require __DIR__.'/auth.php';
