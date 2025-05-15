<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Illuminate\Http\Response;

class CustomRegistrationResponse implements RegistrationResponse
{
    public function toResponse($request)
    {
        // Don't redirect, just return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
        ], Response::HTTP_OK);
    }
}