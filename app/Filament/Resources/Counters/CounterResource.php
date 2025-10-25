<?php

namespace App\Filament\Resources\Counters;

use App\Filament\Resources\Counters\Pages\CreateCounter;
use App\Filament\Resources\Counters\Pages\EditCounter;
use App\Filament\Resources\Counters\Pages\ListCounters;
use App\Filament\Resources\Counters\Schemas\CounterForm;
use App\Filament\Resources\Counters\Tables\CountersTable;
use App\Models\Counter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CounterResource extends Resource
{
    protected static ?string $model = Counter::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBanknotes; // ganti kalau mau

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CounterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Tambah RelationManagers di sini kalau perlu (mis. RatingsRelationManager)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCounters::route('/'),
            'create' => CreateCounter::route('/create'),
            'edit'   => EditCounter::route('/{record}/edit'),
        ];
    }
}
