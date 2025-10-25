<?php

namespace App\Filament\Resources\StaffAssignments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StaffAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('counter_id')
                    ->required()
                    ->numeric(),
                TextInput::make('staff_id')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at'),
                TextInput::make('note'),
            ]);
    }
}
