<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Chat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static string $view = 'filament.pages.chat';
    
    protected static ?string $navigationLabel = 'Messages';
    
    protected static ?string $title = 'Chat Messages';
    
    protected static ?int $navigationSort = 8;
}