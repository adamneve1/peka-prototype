<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Layanan')
                ->required()
                ->maxLength(255),

            // >>> INI YANG NGEGANTI RELATION MANAGER <<<
           Forms\Components\CheckboxList::make('counters')
    ->label('Loket yang melayani')
    ->relationship('counters', 'name')
    ->columns(4)       // atur kolom sesuai selera
    ->gridDirection('row') // tampil horisontal
,
        ]);
    }
}
