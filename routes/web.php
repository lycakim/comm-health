<?php

use Illuminate\Support\Facades\Route;

// Route::redirect('/', '/app')->name('login');
Route::get('/', function () {
    // return view('welcome');
    return view('landing');
    // return view('sample');
})->name('welcome');

Route::get('/login', function () {
    return redirect('/app/login');
})->name('login');

Route::get('/register', function () {
    return redirect('/app/register');
})->name('register');