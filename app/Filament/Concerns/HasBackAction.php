<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

trait HasBackAction
{
    protected function getBackAction(): Action
    {
        return Action::make('back')
            ->label('Back')
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(fn () => static::getResource()::getUrl('index'));
    }
}
