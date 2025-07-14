<?php

// app/Http/Controllers/DynamicDashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class DynamicDashboardController extends Controller
{
    public function mho()
    {
        return $this->renderDashboard('mho');
    }
    
    public function bhw()
    {
        return $this->renderDashboard('bhw');
    }
    
    public function rhu()
    {
        return $this->renderDashboard('rhu');
    }
    
    public function admin()
    {
        return $this->renderDashboard('admin');
    }
    
    private function renderDashboard($userType)
    {
        // Set the current user type for the session
        session(['current_user_type' => $userType]);
        
        // Render the Filament dashboard with the user type context
        return view('filament.pages.dashboard', [
            'userType' => $userType,
            'title' => $this->getTitle($userType),
            'heading' => $this->getHeading($userType),
        ]);
    }
    
    private function getTitle($userType)
    {
        return match($userType) {
            'mho' => 'MHO Dashboard',
            'bhw' => 'BHW Dashboard',
            'rhu' => 'RHU Dashboard',
            'admin' => 'Admin Dashboard',
            default => 'Dashboard'
        };
    }
    
    private function getHeading($userType)
    {
        $user = Auth::user();
        
        return match($userType) {
            'mho' => "Welcome, {$user->name} - Municipal Health Officer",
            'bhw' => "Welcome, {$user->name} - Barangay Health Worker",
            'rhu' => "Welcome, {$user->name} - Rural Health Unit",
            'admin' => "Welcome, {$user->name} - System Administrator",
            default => "Welcome, {$user->name}"
        };
    }
    
    public function handleMhoRoutes(Request $request, $resource = null, $record = null)
    {
        return $this->handleUserTypeRoutes('mho', $resource, $record);
    }
    
    public function handleBhwRoutes(Request $request, $resource = null, $record = null)
    {
        return $this->handleUserTypeRoutes('bhw', $resource, $record);
    }
    
    public function handleRhuRoutes(Request $request, $resource = null, $record = null)
    {
        return $this->handleUserTypeRoutes('rhu', $resource, $record);
    }
    
    public function handleAdminRoutes(Request $request, $resource = null, $record = null)
    {
        return $this->handleUserTypeRoutes('admin', $resource, $record);
    }
    
    private function handleUserTypeRoutes($userType, $resource = null, $record = null)
    {
        session(['current_user_type' => $userType]);
        
        if (!$resource) {
            return redirect("/commhealth/{$userType}/dashboard");
        }
        
        // Build the original filament route
        $originalRoute = "/commhealth/{$resource}";
        if ($record) {
            $originalRoute .= "/{$record}";
        }
        
        // Check if this is a valid resource for this user type
        if (!$this->isValidResourceForUserType($resource, $userType)) {
            return redirect("/commhealth/{$userType}/dashboard")
                ->with('error', 'You do not have access to this resource.');
        }
        
        // Proxy to the original Filament route
        return redirect($originalRoute);
    }
    
    private function isValidResourceForUserType($resource, $userType)
    {
        // Define which resources are accessible to which user types
        $resourceAccess = [
            'mho' => ['health-records', 'disease-surveillance', 'reports', 'rhu-units', 'health-programs'],
            'bhw' => ['residents', 'health-monitoring', 'immunization-records', 'reports'],
            'rhu' => ['patients', 'appointments', 'medical-records', 'immunization', 'maternal-care', 'inventory'],
            'admin' => ['users', 'system-logs', 'analytics', 'settings', 'backup', 'system-health']
        ];
        
        return in_array($resource, $resourceAccess[$userType] ?? []);
    }
}