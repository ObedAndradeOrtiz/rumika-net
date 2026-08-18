<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RumikaBotController;

Route::post('/rumika-bot', [RumikaBotController::class, 'ask']);
