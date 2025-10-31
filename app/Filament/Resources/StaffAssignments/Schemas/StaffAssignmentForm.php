<?php

namespace App\Filament\Resources\StaffAssignments\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;

class StaffAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('counter_id')
                    ->label('Counter')
                    ->relationship(name: 'counter', titleAttribute: 'name') // ganti 'name' kalau kolomnya beda
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                Select::make('staff_id')
                    ->label('Staff')
                    ->relationship(name: 'staff', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                DateTimePicker::make('starts_at')
                    ->label('Starts At')
                    ->seconds(false)
                    ->required(),

                DateTimePicker::make('ends_at')
                    ->label('Ends At')
                    ->seconds(false)
                    ->after('starts_at'),

                Textarea::make('note')
                    ->label('Note')
                    ->rows(3),
            ]);
    }
}
