<?php

namespace App\Filament\Resources\Ratings;

use App\Filament\Resources\Ratings\Pages\ListRatings;
use App\Filament\Resources\Ratings\Tables\RatingsTable;
use App\Models\Rating;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]); // read-only
    }

    public static function table(Table $table): Table
    {
        return RatingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRatings::route('/'),
        ];
    }
}
