<?php

use Illuminate\Support\Facades\Route;

// routes/web.php
Route::get('/', function () {
    return response('Welcome. This is an API-only app.', 200);
})->name('home');
