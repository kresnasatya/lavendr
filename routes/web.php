<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::employee-dashboard')->name('dashboard');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'role:manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('index');
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('roles', 'pages::roles')->name('roles');
    Route::livewire('employees', 'pages::employees')->name('employees');
    Route::livewire('machines', 'pages::machines')->name('machines');
    Route::livewire('slots', 'pages::slots')->name('slots');
    Route::livewire('balances', 'pages::balances')->name('balances');
    Route::livewire('reports', 'pages::reports')->name('reports');
});

require __DIR__.'/settings.php';
