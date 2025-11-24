<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PrivacyModal extends Component
{
    public $showModal = false;

    public function mount()
    {
        $user = Auth::user();
        
        if (!$user || $user->isAdmin()) {
            $this->showModal = false;
            return;
        }
        
        // Check if this is a fresh login session
        $isFreshLogin = session()->get('fresh_login', false);
        
        // Check if user hasn't accepted privacy today
        $hasAcceptedToday = false;
        if ($user->privacy_accepted_at) {
            $privacyAcceptedAt = $user->privacy_accepted_at;
            $now = now();
            $hasAcceptedToday = $privacyAcceptedAt->isSameDay($now) && $privacyAcceptedAt->format('H:i:s') === $now->format('H:i:s');
        }
        
        // Show modal if it's a fresh login AND user hasn't accepted today
        // Modal will persist across refreshes until user accepts
        if ($isFreshLogin && !$hasAcceptedToday) {
            $this->showModal = true;
            return;
        }

        $this->showModal = false;
    }

    public function acceptPrivacy()
    {
        $user = Auth::user();
        
        if ($user && !$user->isAdmin()) {
            $user->update([
                'privacy_accepted_at' => now(),
            ]);
            
            // Clear the fresh login flag after acceptance
            session()->forget('fresh_login');
            
            $this->showModal = false;
            
            Notification::make()
                ->title('Privacy Notice Accepted')
                ->body('You have accepted the privacy notice.')
                ->success()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.privacy-modal');
    }
}