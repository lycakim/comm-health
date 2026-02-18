<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LandingPagePhotos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Landing Page Photos';
    protected static ?string $title = 'Landing Page Photos';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.landing-page-photos';

    public ?array $uploadData = [];

    public static function canAccess(): bool
    {
        return Auth::check() && in_array(Auth::user()->role, [
            RoleEnum::ADMIN,
            RoleEnum::MHO,
        ]);
    }

    public function getSubheading(): ?string
    {
        return 'Upload and remove photos shown on the public landing page carousel.';
    }

    protected function getForms(): array
    {
        return ['uploadForm'];
    }

    public function uploadForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload new photos')
                    ->description('Photos appear in the 3D coverflow carousel on the landing page. Use JPG, PNG, GIF or WebP.')
                    ->schema([
                        FileUpload::make('photos')
                            ->label('Photos')
                            ->disk('public')
                            ->directory('landing-page-temp')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->multiple()
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Max 5MB per image. Multiple files allowed.'),
                    ]),
            ])
            ->statePath('uploadData');
    }

    public function getLandingPhotos(): array
    {
        $photoPath = public_path('landing-page');
        $photos = [];

        if (! File::exists($photoPath) || ! File::isDirectory($photoPath)) {
            return $photos;
        }

        foreach (File::files($photoPath) as $file) {
            $fileName = $file->getFilename();
            if (preg_match('/^photo-(\d+)\.(jpg|jpeg|png|gif|webp)$/i', $fileName, $matches)) {
                $photos[(int) $matches[1]] = [
                    'filename' => $fileName,
                    'path' => $file->getPathname(),
                    'url' => asset('landing-page/' . $fileName),
                    'index' => (int) $matches[1],
                ];
            }
        }

        ksort($photos);

        return array_values($photos);
    }

    public function uploadPhotos(): void
    {
        $data = $this->uploadForm->getState();
        $uploads = $data['photos'] ?? [];

        if (! is_array($uploads)) {
            $uploads = [$uploads];
        }

        $uploads = array_filter($uploads);

        if (empty($uploads)) {
            Notification::make()
                ->title('No photos selected')
                ->warning()
                ->send();
            return;
        }

        $photoPath = public_path('landing-page');
        File::ensureDirectoryExists($photoPath);

        $existing = $this->getLandingPhotos();
        $maxIndex = empty($existing) ? 0 : max(array_column($existing, 'index'));

        $uploaded = 0;
        foreach ($uploads as $storedPath) {
            $fullPath = Storage::disk('public')->path($storedPath);
            if (! File::exists($fullPath)) {
                continue;
            }

            $ext = strtolower(File::extension($fullPath));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'jpg';
            }
            $maxIndex++;
            $destName = 'photo-' . $maxIndex . '.' . $ext;
            File::move($fullPath, public_path('landing-page/' . $destName));
            Storage::disk('public')->delete($storedPath);
            $uploaded++;
        }

        $this->uploadData = ['photos' => []];
        $this->uploadForm->fill(['photos' => []]);

        Notification::make()
            ->title("{$uploaded} photo(s) added")
            ->success()
            ->send();
    }

    public function removePhoto(string $filename): void
    {
        $path = public_path('landing-page' . DIRECTORY_SEPARATOR . $filename);

        if (! File::exists($path)) {
            Notification::make()
                ->title('Photo not found')
                ->danger()
                ->send();
            return;
        }

        if (! preg_match('/^photo-\d+\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
            Notification::make()
                ->title('Invalid file')
                ->danger()
                ->send();
            return;
        }

        File::delete($path);

        Notification::make()
            ->title('Photo removed')
            ->body('The photo has been removed from the landing page.')
            ->success()
            ->send();
    }

    public function mount(): void
    {
        $this->uploadForm->fill(['photos' => []]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
