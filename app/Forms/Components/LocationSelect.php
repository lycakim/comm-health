<?php

namespace App\Forms\Components;

use App\Models\Location;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;

class LocationSelect
{
    /**
     * Build a Province → City/Municipality select group.
     *
     * @param string $provinceField
     * @param string $cityField
     */
    public static function make(
        string $provinceField = 'province_id',
        string $cityField = 'city_or_municipality_id',
        string $barangayField = 'barangay_id',
        string $purokField = 'purok_id'
    ): Group {
        return Group::make([
            Select::make($provinceField)
                ->label('Province')
                ->options(Location::provinces()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set($cityField, null)),

            Select::make($cityField)
                ->label('City / Municipality')
                ->options(function (callable $get) {
                    $provinceId = $get('province_id');
                    if (!$provinceId) return [];

                    return Location::where('parent_id', $provinceId)
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->preload() 
                ->searchable(),

            // Add barangay options
            Select::make($barangayField)
                ->label('Barangay')
                ->options(function (callable $get) {
                    $provinceId = $get('province_id');
                    $cityId = $get('city_or_municipality_id');
                    if (!$provinceId || !$cityId) return [];

                    return Location::where('parent_id', $cityId)
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->preload()
                ->searchable(),
            // Add purok options
            Select::make($purokField)
                ->label('Purok')
                ->options(function (callable $get) {
                    $barangayId = $get('barangay_id');
                    if (!$barangayId) return [];

                    return Location::where('parent_id', $barangayId)
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->preload()
                ->searchable(),
        ])
        ->columns(2);
    }
}