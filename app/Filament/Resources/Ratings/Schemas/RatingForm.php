<?php

namespace App\Filament\Resources\Ratings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('counter_id')
                    ->required()
                    ->numeric(),
                TextInput::make('service_id')
                    ->required()
                    ->numeric(),
                TextInput::make('staff_id')
                    ->numeric(),
                TextInput::make('score')
                    ->required()
                    ->numeric(),
                TextInput::make('comment'),
                Textarea::make('flags')
                    ->columnSpanFull(),
            ]);
    }
}
