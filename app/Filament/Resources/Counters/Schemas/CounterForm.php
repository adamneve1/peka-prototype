<?php

namespace App\Filament\Resources\Counters\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class CounterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Loket')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3)
                ->maxLength(255)
                ->placeholder('Opsional: catatan tentang fungsi/posisi loket'),
        ]);
    }
}
