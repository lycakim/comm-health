<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DynamicDashboardController;

// Route::redirect('/', '/app')->name('login');
Route::get('/', function () {
    // return view('welcome');
    return view('landing');
    // return view('sample');
})->name('welcome');

Route::get('/login', function () {
    return redirect('/commhealth/login');
})->name('login');

Route::get('/register', function () {
    return redirect('/commhealth/register');
})->name('register');

Route::middleware(['auth'])->group(function () {
    // MHO Routes
    Route::prefix('commhealth/mho')->middleware('check.user.type:mho')->group(function () {
        Route::get('/dashboard', [DynamicDashboardController::class, 'mho'])->name('commhealth.mho.dashboard');
        Route::get('/{resource?}/{record?}', [DynamicDashboardController::class, 'handleMhoRoutes'])->where('resource', '.*');
    });
    
    // BHW Routes
    Route::prefix('commhealth/bhw')->middleware('check.user.type:bhw')->group(function () {
        Route::get('/dashboard', [DynamicDashboardController::class, 'bhw'])->name('commhealth.bhw.dashboard');
        Route::get('/{resource?}/{record?}', [DynamicDashboardController::class, 'handleBhwRoutes'])->where('resource', '.*');
    });
    
    // RHU Routes
    Route::prefix('commhealth/rhu')->middleware('check.user.type:rhu')->group(function () {
        Route::get('/dashboard', [DynamicDashboardController::class, 'rhu'])->name('commhealth.rhu.dashboard');
        Route::get('/{resource?}/{record?}', [DynamicDashboardController::class, 'handleRhuRoutes'])->where('resource', '.*');
    });
    
    // Admin Routes
    Route::prefix('commhealth/admin')->middleware('check.user.type:admin')->group(function () {
        Route::get('/dashboard', [DynamicDashboardController::class, 'admin'])->name('commhealth.admin.dashboard');
        Route::get('/{resource?}/{record?}', [DynamicDashboardController::class, 'handleAdminRoutes'])->where('resource', '.*');
    });
    
    // Fallback redirect
    Route::get('/commhealth', function () {
        $user = Auth::user();
        
        if (!$user || !$user->user_type) {
            return redirect('/commhealth/login');
        }
        
        return redirect(match($user->user_type) {
            'mho' => '/commhealth/mho/dashboard',
            'bhw' => '/commhealth/bhw/dashboard',
            'rhu' => '/commhealth/rhu/dashboard',
            'admin' => '/commhealth/admin/dashboard',
            default => '/commhealth/login'
        });
    });
});

// Redirect any unauthorized access
Route::get('/commhealth/{userType}/{path?}', function ($userType) {
    if (!Auth::check()) {
        return redirect('/commhealth/login');
    }
    
    $user = Auth::user();
    
    if ($user->user_type !== $userType) {
        return redirect("/commhealth/{$user->user_type}/dashboard");
    }
    
    return redirect("/commhealth/{$userType}/dashboard");
})->where('userType', 'mho|bhw|rhu|admin')->where('path', '.*');

Route::get('/test-sms', function () {
    $semaphore = new \App\Services\SemaphoreService();
    $result = $semaphore->sendSMS('09171234567', 'Test message from Laravel');
    return $result;
});

Route::get('/test-gmail-direct', function () {
    try {
        Mail::raw('Direct test from Laravel', function ($message) {
            $message->to('your-test-email@gmail.com')
                    ->subject('Direct Gmail Test');
        });
        
        return 'Email sent! Check your inbox and spam folder.';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});