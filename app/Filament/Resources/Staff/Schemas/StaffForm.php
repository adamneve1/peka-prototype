<?php

namespace App\Filament\Resources\Staff\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(120),

            Forms\Components\FileUpload::make('photo_path')
                ->label('Foto')
                ->disk('public')               // penting: simpan ke disk public
                ->directory('staff')
                ->image()
                ->imageEditor()
                ->maxSize(2048)
                ->visibility('public')
                ->acceptedFileTypes(['image/jpeg','image/png','image/webp']),
        ]);
    }
}
