<?php

use App\Http\Controllers\Api\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::post('/purchase', [PurchaseController::class, 'store'])->name('api.purchase');
