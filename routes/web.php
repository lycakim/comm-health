<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DynamicDashboardController;

// Route::redirect('/', '/app')->name('login');
Route::get('/', function () {
    // Collect photos from landing-page directory
    $photoPath = public_path('landing-page');
    $photos = [];
    
    // Use Laravel's File facade for better cross-platform compatibility
    if (File::exists($photoPath) && File::isDirectory($photoPath)) {
        $files = File::files($photoPath);
        $photoFiles = [];
        
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            
            // Match photo files with numeric suffix
            if (preg_match('/^photo-(\d+)\.(jpg|jpeg|png|gif|webp)$/i', $fileName, $matches)) {
                $photoFiles[(int)$matches[1]] = asset('landing-page/' . $fileName);
            }
        }
        
        // Sort by numeric key and convert to array
        ksort($photoFiles);
        $photos = array_values($photoFiles);
    }
    
    // return view('welcome');
    return view('landing', ['photos' => $photos]);
    // return view('sample');
})->name('welcome');

Route::get('/login', function () {
    return redirect('/commhealth/login');
})->name('login');


Route::redirect('profile', '/commhealth/settings')->name('profile');

Route::middleware(['auth'])->group(function () {
    // Referral PDF route
    Route::get('/referral-pdf/{consultation}', function ($consultation) {
        $consultation = \App\Models\Consultation::with(['referral', 'patient'])->findOrFail($consultation);
        
        if (!$consultation->referral) {
            abort(404, 'Referral not found for this consultation');
        }
        
        $pdfService = new \App\Services\PDFGenerationService();
        $pdf = $pdfService->generateReferralPdf($consultation->referral, $consultation->patient, $consultation);
        
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="referral-' . $consultation->referral->ref_id . '.pdf"');
    })->name('referral.pdf');
    
    // User-type specific routes (disabled to prevent conflicts with Filament navigation)
    // Filament handles role-based access through canAccess() methods in Resources and Pages
    /*
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
    */
    
    // Fallback redirect to Filament dashboard
    Route::get('/commhealth', function () {
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/commhealth/login');
        }
        
        // Redirect to the Filament dashboard page
        return redirect('/commhealth/dashboard');
    });
});

// Redirect any unauthorized access to user-type specific routes (disabled)
// Uncomment if you enable the user-type specific dashboard routes above
/*
Route::get('/commhealth/{userType}/{path?}', function ($userType) {
    if (!Auth::check()) {
        return redirect('/commhealth/login');
    }
    
    $user = Auth::user();
    
    // Only redirect if accessing user-type specific routes
    if ($user->user_type !== $userType) {
        return redirect("/commhealth/dashboard");
    }
    
    return redirect("/commhealth/{$userType}/dashboard");
})->where('userType', 'mho|bhw|rhu|admin|midwife|resident')->where('path', '.*');
*/

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