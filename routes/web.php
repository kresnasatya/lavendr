<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::retro-vending')->name('dashboard');
    Route::livewire('dashboard/modern', 'pages::employee-dashboard')->name('dashboard.modern');
});

// Manager Routes
Route::middleware(['auth', 'verified', 'role:manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('index');
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('employees', 'pages::employees')->name('employees');
    Route::livewire('machines', 'pages::machines')->name('machines');
    Route::livewire('slots', 'pages::slots')->name('slots');
    Route::livewire('balances', 'pages::balances')->name('balances');
    Route::livewire('reports', 'pages::reports')->name('reports');
});

// Superadmin Routes
Route::middleware(['auth', 'verified', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', fn () => redirect()->route('superadmin.dashboard'))->name('index');
    Route::livewire('dashboard', 'pages::superadmin-dashboard')->name('dashboard');
    Route::livewire('roles', 'pages::roles')->name('roles');
    Route::livewire('role-limits', 'pages::role-limits')->name('role-limits');
    Route::livewire('recharge-settings', 'pages::recharge-settings')->name('recharge-settings');
    Route::livewire('audit-logs', 'pages::audit-logs')->name('audit-logs');
});

require __DIR__.'/settings.php';
