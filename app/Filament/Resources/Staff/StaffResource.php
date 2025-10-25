<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\Schemas\StaffForm;
use App\Filament\Resources\Staff\Tables\StaffTable;
use App\Models\Staff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;      // ← ini yang dipakai build lo
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        // Kalau lo punya helper class StaffForm, biarin begini.
        // Kalau nggak ada, sementara return $schema->schema([...]) sesuai gaya project lo.
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Sama: kalau lo punya StaffTable helper, keep.
        return StaffTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit'   => EditStaff::route('/{record}/edit'),
        ];
    }
}
