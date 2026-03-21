<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use App\Filament\Resources\ConsultationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Concerns\HasBackAction;

class ViewConsultation extends ViewRecord
{
    use HasDashboardBreadcrumb;
    use HasBackAction;

    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->action(function ($record) {
                    $referral = $record->referral;

                    if (! $referral) {
                        $this->notify('danger', 'No referral found for this consultation.');
                        return;
                    }

                    $pdfService = new \App\Services\PDFGenerationService();

                    $pdf = $pdfService->generateReferralPdf($referral, $record->patient, $record);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, "referral-{$referral->ref_id}.pdf");
                })
                ->visible(fn ($record) => $record->referral()->exists()),
            Actions\EditAction::make()
                ->color('warning')
                ->icon('heroicon-o-pencil-square')
        ];
    }
}