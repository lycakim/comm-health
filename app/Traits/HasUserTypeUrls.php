<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait HasUserTypeUrls
{
    /**
     * Get the URL for the resource index page based on user type
     */
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $userType = Auth::user()?->user_type;
        
        if (!$userType) {
            return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
        }
        
        $baseUrl = parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
        
        // Replace the base path with user-type specific path
        $pattern = '/\/commhealth\//';
        $replacement = "/commhealth/{$userType}/";
        
        return preg_replace($pattern, $replacement, $baseUrl, 1);
    }
    
    /**
     * Get navigation URL for this resource
     */
    public static function getNavigationUrl(): string
    {
        return static::getUrl();
    }
    
    /**
     * Get the resource slug with user type prefix
     */
    public static function getSlug(): string
    {
        $userType = Auth::user()?->user_type;
        $baseSlug = parent::getSlug();
        
        if (!$userType) {
            return $baseSlug;
        }
        
        return $baseSlug; // Keep the same slug, the path prefix handles the user type
    }
}