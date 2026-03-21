<?php

namespace App\Filament\Concerns;

trait HasDashboardBreadcrumb
{
    public function getBreadcrumbs(): array
    {
        $homeUrl = filament()->getHomeUrl();
        $existing = parent::getBreadcrumbs();

        if (empty($existing)) {
            return [$homeUrl => 'Dashboard', $this->getHeading()];
        }

        return array_merge([$homeUrl => 'Dashboard'], $existing);
    }
}
